<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';

// 支持GET和POST请求
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // 检查action参数
    $action = '';
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
    } elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
    }

    if ($action === 'verify') {
        // dylib授权验证 (POST with JSON)
        $input = file_get_contents('php://input');
        $requestData = json_decode($input, true);
        if (!$requestData) {
            throw new Exception('Invalid JSON data');
        }
        handleVerifyRequest($requestData);
        
    } elseif ($action === 'redeem_card') {
        // 网站端卡密兑换 (POST with form data)
        handleRedeemCardRequest();
        
    } elseif ($action === 'query_auth') {
        // 网站端授权查询 (GET)
        handleQueryAuthRequest();
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);

    // 安全地记录日志，避免日志错误导致脚本崩溃
    try {
        $requestData = isset($_REQUEST) ? $_REQUEST : null;
        logAuthAction('unknown', 'error', $requestData, ['error' => $e->getMessage()]);
    } catch (Exception $logError) {
        error_log("Failed to log auth action: " . $logError->getMessage());
    }
}

// dylib授权验证处理
function handleVerifyRequest($requestData) {
    // 验证请求数据格式
    if (!isset($requestData['data'])) {
        throw new Exception('Missing data field');
    }
    
    $encryptedData = $requestData['data'];
    
    // 解密数据
    $decryptedData = aesDecrypt($encryptedData, AES_KEY);
    if ($decryptedData === false) {
        throw new Exception('Failed to decrypt data');
    }
    
    // 解析数据格式: "wxid|timestamp|caihong"
    $parts = explode('|', $decryptedData);
    if (count($parts) !== 3) {
        throw new Exception('Invalid data format');
    }

    list($wxid, $timestamp, $suffix) = $parts;

    // 验证数据格式
    if (empty($wxid) || !is_numeric($timestamp) || $suffix !== 'caihong') {
        try {
            $logWxid = isset($wxid) ? $wxid : 'unknown';
            logAuthAction($logWxid, 'verify_failed', $requestData, ['error' => 'Invalid data format']);
        } catch (Exception $logError) {
            error_log("Failed to log auth action: " . $logError->getMessage());
        }
        throw new Exception('Invalid request data');
    }

    // 验证请求时间戳 (300秒过期)
    $currentTimestamp = time();
    $requestTimestamp = intval($timestamp);
    $timeDiff = abs($currentTimestamp - $requestTimestamp);

    if ($timeDiff > 300) {
        // 请求已过期
        $response = [
            'authorized' => false,
            'message' => '请求已过期'
        ];

        $responseJson = json_encode($response);
        $encryptedResponse = aesEncrypt($responseJson, AES_KEY);

        // 记录过期请求
        try {
            logAuthAction($wxid, 'verify_expired', $requestData, $response);
        } catch (Exception $logError) {
            error_log("Failed to log expired auth action: " . $logError->getMessage());
        }

        echo $encryptedResponse;
        return;
    }

    // 查询授权状态
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM authorizations WHERE wxid = ?");
    $stmt->execute([$wxid]);
    $auth = $stmt->fetch();
    
    $currentTimestamp = time();
    $authorized = false;
    $expireTimestamp = 0;
    $authInfo = null;
    
    if ($auth) {
        $expireTimestamp = $auth['expire_timestamp'];
        $authorized = $expireTimestamp > $currentTimestamp;
        
        if ($authorized) {
            $authInfo = "授权有效，到期时间：" . date('Y-m-d H:i:s', $expireTimestamp);
        }
    }
    
    // 生成响应 - 完全匹配原版格式
    $response = [
        'authorized' => $authorized
    ];

    // 添加消息字段
    if (!$authorized) {
        if ($auth && $auth['expire_timestamp'] <= $currentTimestamp) {
            $response['message'] = '授权已过期';
        } else {
            $response['message'] = '未找到授权';
        }
    }

    // 加密响应数据
    $responseJson = json_encode($response);
    $encryptedResponse = aesEncrypt($responseJson, AES_KEY);
    
    // 安全地记录日志
    try {
        logAuthAction($wxid, 'verify', $requestData, $response);
    } catch (Exception $logError) {
        error_log("Failed to log auth action: " . $logError->getMessage());
    }

    // 返回加密的响应 (aesEncrypt已经返回base64编码的数据)
    echo $encryptedResponse;
}

// 处理网站端卡密兑换请求
function handleRedeemCardRequest() {
    // 获取POST参数
    $cardCode = $_POST['card_code'] ?? '';
    $wxid = $_POST['wxid'] ?? '';
    
    if (empty($cardCode) || empty($wxid)) {
        echo json_encode([
            'success' => false,
            'message' => '参数不完整'
        ]);
        return;
    }
    
    try {
        $pdo = getDatabase();
        $pdo->beginTransaction();
        
        // 检查卡密是否存在且未使用
        $stmt = $pdo->prepare("SELECT * FROM card_keys WHERE card_key = ? AND is_used = FALSE");
        $stmt->execute([$cardCode]);
        $card = $stmt->fetch();
        
        if (!$card) {
            echo json_encode([
                'success' => false,
                'message' => '卡密无效或已被使用'
            ]);
            logAuthAction($wxid, 'redeem_failed', $_POST, ['error' => 'Invalid card']);
            return;
        }
        
        // 检查该WXID是否已有授权
        $stmt = $pdo->prepare("SELECT * FROM authorizations WHERE wxid = ?");
        $stmt->execute([$wxid]);
        $existingAuth = $stmt->fetch();
        
        $currentTimestamp = time();
        $durationDays = $card['duration_days'];
        
        if ($existingAuth) {
            // 如果已有授权，延长时间
            $newExpireTimestamp = max($existingAuth['expire_timestamp'], $currentTimestamp) + ($durationDays * 24 * 60 * 60);
            
            $stmt = $pdo->prepare("UPDATE authorizations SET expire_timestamp = ?, card_key = ?, updated_at = CURRENT_TIMESTAMP WHERE wxid = ?");
            $stmt->execute([$newExpireTimestamp, $cardCode, $wxid]);
        } else {
            // 创建新授权
            $newExpireTimestamp = $currentTimestamp + ($durationDays * 24 * 60 * 60);
            
            $stmt = $pdo->prepare("INSERT INTO authorizations (wxid, expire_timestamp, card_key, auth_info) VALUES (?, ?, ?, ?)");
            $authInfo = "卡密兑换成功，有效期：{$durationDays}天";
            $stmt->execute([$wxid, $newExpireTimestamp, $cardCode, $authInfo]);
        }
        
        // 标记卡密为已使用
        $stmt = $pdo->prepare("UPDATE card_keys SET is_used = TRUE, used_by = ?, used_at = CURRENT_TIMESTAMP WHERE card_key = ?");
        $stmt->execute([$wxid, $cardCode]);
        
        $pdo->commit();
        
        // 记录日志
        logAuthAction($wxid, 'redeem_success', $_POST, [
            'success' => true,
            'duration_days' => $durationDays,
            'expire_timestamp' => $newExpireTimestamp
        ]);
        
        // 返回成功响应
        echo json_encode([
            'success' => true,
            'message' => '卡密兑换成功',
            'expire_date' => date('Y-m-d H:i:s', $newExpireTimestamp),
            'duration_days' => $durationDays
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode([
            'success' => false,
            'message' => '兑换失败：' . $e->getMessage()
        ]);
        
        logAuthAction($wxid, 'redeem_error', $_POST, ['error' => $e->getMessage()]);
    }
}

// 处理网站端授权查询请求
function handleQueryAuthRequest() {
    $wxid = $_GET['wxid'] ?? '';
    
    if (empty($wxid)) {
        echo json_encode([
            'success' => false,
            'message' => '缺少WXID参数'
        ]);
        return;
    }
    
    try {
        // 查询授权状态
        $pdo = getDatabase();
        $stmt = $pdo->prepare("SELECT * FROM authorizations WHERE wxid = ?");
        $stmt->execute([$wxid]);
        $auth = $stmt->fetch();
        
        $currentTimestamp = time();
        
        if (!$auth) {
            echo json_encode([
                'success' => true,
                'authorized' => false,
                'expired' => true,
                'message' => '未找到授权记录'
            ]);
            
            logAuthAction($wxid, 'query_no_auth', $_GET, ['authorized' => false]);
            return;
        }
        
        $expireTimestamp = $auth['expire_timestamp'];
        $authorized = $expireTimestamp > $currentTimestamp;
        $expired = !$authorized;
        
        $response = [
            'success' => true,
            'authorized' => $authorized,
            'expire_date' => date('Y-m-d H:i:s', $expireTimestamp),
            'expired' => $expired,
            'message' => $authorized ? '授权有效' : '授权已过期'
        ];
        
        // 记录日志
        logAuthAction($wxid, 'query_auth', $_GET, $response);
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => '查询失败：' . $e->getMessage()
        ]);
        
        logAuthAction($wxid, 'query_error', $_GET, ['error' => $e->getMessage()]);
    }
}

?>
