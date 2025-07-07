<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'wechatfaka');
define('DB_USER', 'wechatfaka');
define('DB_PASS', 'wechatfaka');
define('DB_CHARSET', 'utf8mb4');

// AES加密配置 - 与dylib中的密钥保持一致
define('AES_KEY', 'xuegao66xuegao66xuegao66xuegao66');
define('AES_METHOD', 'AES-256-CBC');

// 授权配置
define('DEFAULT_AUTH_DAYS', 30); // 默认授权天数

// 数据库连接
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("数据库连接失败");
        }
    }
    
    return $pdo;
}

// 创建数据库表
function createTables() {
    $pdo = getDatabase();

    // 创建卡密表
    $sql = "CREATE TABLE IF NOT EXISTS card_keys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        card_key VARCHAR(255) UNIQUE NOT NULL,
        duration_days INT NOT NULL DEFAULT 30,
        is_used BOOLEAN DEFAULT FALSE,
        used_by VARCHAR(255) NULL,
        used_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // 创建授权表
    $sql = "CREATE TABLE IF NOT EXISTS authorizations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        wxid VARCHAR(255) UNIQUE NOT NULL,
        expire_timestamp BIGINT NOT NULL,
        card_key VARCHAR(255) NOT NULL,
        auth_info TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // 创建验证日志表
    $sql = "CREATE TABLE IF NOT EXISTS auth_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        wxid VARCHAR(255) NOT NULL,
        action VARCHAR(50) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT NULL,
        request_data TEXT NULL,
        response_data TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // 创建管理员表
    $sql = "CREATE TABLE IF NOT EXISTS admin_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        email VARCHAR(100) NULL,
        last_login TIMESTAMP NULL,
        login_attempts INT DEFAULT 0,
        locked_until TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // 检查是否存在默认管理员，如果不存在则创建
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users WHERE username = 'admin'");
    $result = $stmt->fetch();
    if ($result['count'] == 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $defaultPassword, 'admin@example.com']);
    }
}

// AES加密函数 - 兼容iOS CommonCrypto
function aesEncrypt($data, $key) {
    $iv = random_bytes(16); // 生成16字节随机IV
    $encrypted = openssl_encrypt($data, AES_METHOD, $key, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $encrypted); // IV + 加密数据，然后base64编码
}

// AES解密函数 - 兼容iOS CommonCrypto
function aesDecrypt($data, $key) {
    $data = base64_decode($data);
    if (strlen($data) < 16) {
        return false;
    }
    
    $iv = substr($data, 0, 16); // 前16字节是IV
    $encrypted = substr($data, 16); // 剩余是加密数据
    
    return openssl_decrypt($encrypted, AES_METHOD, $key, OPENSSL_RAW_DATA, $iv);
}

// 验证数据格式 - 兼容dylib中的格式: "wxid|timestamp|caihong"
function validateAuthData($decryptedData, $expectedWxid) {
    $parts = explode('|', $decryptedData);
    if (count($parts) !== 3) {
        return false;
    }
    
    list($wxid, $timestamp, $suffix) = $parts;
    
    // 验证格式
    if ($wxid !== $expectedWxid || $suffix !== 'caihong') {
        return false;
    }
    
    // 验证时间戳（允许5分钟误差）
    $currentTime = time();
    $requestTime = intval($timestamp);
    if (abs($currentTime - $requestTime) > 300) {
        return false;
    }
    
    return true;
}

// 生成授权响应数据
function generateAuthResponse($wxid, $authorized, $expireTimestamp, $authInfo = null) {
    $response = [
        'authorized' => $authorized,
        'expire_timestamp' => $expireTimestamp
    ];
    
    if ($authInfo) {
        $response['auth_info'] = base64_encode(aesEncrypt($authInfo, AES_KEY));
    }
    
    return $response;
}

// 记录日志
function logAuthAction($wxid, $action, $requestData = null, $responseData = null) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("INSERT INTO auth_logs (wxid, action, ip_address, user_agent, request_data, response_data) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $wxid,
            $action,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            $requestData ? json_encode($requestData) : null,
            $responseData ? json_encode($responseData) : null
        ]);
    } catch (Exception $e) {
        error_log("Failed to log auth action: " . $e->getMessage());
    }
}

// 初始化数据库
try {
    createTables();
} catch (Exception $e) {
    error_log("Failed to create tables: " . $e->getMessage());
}
?>
