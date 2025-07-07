<?php
require_once 'config.php';

session_start();

// 处理登录
if (isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = '请输入用户名和密码';
    } else {
        $loginResult = authenticateAdmin($username, $password);
        if ($loginResult['success']) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user_id'] = $loginResult['user_id'];
            $_SESSION['admin_username'] = $loginResult['username'];

            // 更新最后登录时间
            updateLastLogin($loginResult['user_id']);

            header('Location: admin.php');
            exit;
        } else {
            $error = $loginResult['message'];
        }
    }
}

// 处理登出
if (isset($_POST['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// 获取当前页面
$currentPage = $_GET['page'] ?? 'dashboard';

// 管理员身份验证函数
function authenticateAdmin($username, $password) {
    try {
        $pdo = getDatabase();

        // 查询用户
        $stmt = $pdo->prepare("SELECT id, username, password_hash, login_attempts, locked_until FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => '用户名或密码错误'];
        }

        // 检查账户是否被锁定
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $unlockTime = date('Y-m-d H:i:s', strtotime($user['locked_until']));
            return ['success' => false, 'message' => "账户已被锁定，解锁时间：{$unlockTime}"];
        }

        // 验证密码
        if (password_verify($password, $user['password_hash'])) {
            // 登录成功，重置登录尝试次数
            $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = 0, locked_until = NULL WHERE id = ?");
            $stmt->execute([$user['id']]);

            return [
                'success' => true,
                'user_id' => $user['id'],
                'username' => $user['username']
            ];
        } else {
            // 登录失败，增加尝试次数
            $attempts = $user['login_attempts'] + 1;
            $lockedUntil = null;

            // 如果尝试次数超过5次，锁定账户30分钟
            if ($attempts >= 5) {
                $lockedUntil = date('Y-m-d H:i:s', time() + 1800); // 30分钟后解锁
            }

            $stmt = $pdo->prepare("UPDATE admin_users SET login_attempts = ?, locked_until = ? WHERE id = ?");
            $stmt->execute([$attempts, $lockedUntil, $user['id']]);

            if ($lockedUntil) {
                return ['success' => false, 'message' => '登录失败次数过多，账户已被锁定30分钟'];
            } else {
                $remaining = 5 - $attempts;
                return ['success' => false, 'message' => "用户名或密码错误，还有 {$remaining} 次尝试机会"];
            }
        }
    } catch (Exception $e) {
        error_log("Admin authentication error: " . $e->getMessage());
        return ['success' => false, 'message' => '系统错误，请稍后重试'];
    }
}

// 更新最后登录时间
function updateLastLogin($userId) {
    try {
        $pdo = getDatabase();
        $stmt = $pdo->prepare("UPDATE admin_users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log("Update last login error: " . $e->getMessage());
    }
}

// 处理修改密码
if (isset($_POST['change_password']) && $_SESSION['admin_logged_in']) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $password_error = '请填写所有密码字段';
    } elseif ($newPassword !== $confirmPassword) {
        $password_error = '新密码和确认密码不匹配';
    } elseif (strlen($newPassword) < 6) {
        $password_error = '新密码长度至少6位';
    } else {
        // 验证当前密码
        $pdo = getDatabase();
        $stmt = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $stmt->execute([$_SESSION['admin_user_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($currentPassword, $user['password_hash'])) {
            // 更新密码
            $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$newPasswordHash, $_SESSION['admin_user_id']]);

            $password_success = '密码修改成功';
        } else {
            $password_error = '当前密码错误';
        }
    }
}

// 处理生成卡密
if (isset($_POST['generate_cards']) && $_SESSION['admin_logged_in']) {
    $count = intval($_POST['count']);
    $duration = intval($_POST['duration']);

    if ($count > 0 && $count <= 100 && $duration > 0) {
        $pdo = getDatabase();
        $generated = [];

        for ($i = 0; $i < $count; $i++) {
            $cardKey = 'Apibug_' . strtoupper(bin2hex(random_bytes(8)));
            $stmt = $pdo->prepare("INSERT INTO card_keys (card_key, duration_days) VALUES (?, ?)");
            $stmt->execute([$cardKey, $duration]);
            $generated[] = $cardKey;
        }

        $success_message = "成功生成 {$count} 个卡密";
    }
}

// 处理卡密删除
if (isset($_POST['delete_card']) && isset($_POST['card_id'])) {
    try {
        $pdo = getDatabase();
        $cardId = intval($_POST['card_id']);
        $stmt = $pdo->prepare("DELETE FROM card_keys WHERE id = ?");
        if ($stmt->execute([$cardId])) {
            $success_message = "卡密删除成功";
        } else {
            $error_message = "卡密删除失败";
        }
    } catch (Exception $e) {
        $error_message = "删除失败: " . $e->getMessage();
    }
}

// 处理批量删除卡密
if (isset($_POST['batch_delete_cards'])) {
    // 调试信息（可以在生产环境中删除）
    $debug_info = "DEBUG: batch_delete_cards triggered. ";
    $debug_info .= "selected_cards exists: " . (isset($_POST['selected_cards']) ? 'yes' : 'no') . ". ";
    if (isset($_POST['selected_cards'])) {
        $debug_info .= "Type: " . gettype($_POST['selected_cards']) . ". ";
        $debug_info .= "Count: " . (is_array($_POST['selected_cards']) ? count($_POST['selected_cards']) : 'not array') . ". ";
    }

    if (isset($_POST['selected_cards']) && is_array($_POST['selected_cards']) && count($_POST['selected_cards']) > 0) {
        try {
            $pdo = getDatabase();
            $selectedIds = array_map('intval', $_POST['selected_cards']);
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM card_keys WHERE id IN ($placeholders)");
            if ($stmt->execute($selectedIds)) {
                $success_message = "成功删除 " . count($selectedIds) . " 个卡密";
            } else {
                $error_message = "批量删除失败";
            }
        } catch (Exception $e) {
            $error_message = "批量删除失败: " . $e->getMessage();
        }
    } else {
        $error_message = "请先选择要删除的卡密。" . $debug_info;
    }
}

// 处理授权删除
if (isset($_POST['delete_auth']) && isset($_POST['auth_id'])) {
    try {
        $pdo = getDatabase();
        $authId = intval($_POST['auth_id']);
        $stmt = $pdo->prepare("DELETE FROM authorizations WHERE id = ?");
        if ($stmt->execute([$authId])) {
            $success_message = "授权删除成功";
        } else {
            $error_message = "授权删除失败";
        }
    } catch (Exception $e) {
        $error_message = "删除失败: " . $e->getMessage();
    }
}

// 处理批量删除授权
if (isset($_POST['batch_delete_auths'])) {
    if (isset($_POST['selected_auths']) && is_array($_POST['selected_auths']) && count($_POST['selected_auths']) > 0) {
        try {
            $pdo = getDatabase();
            $selectedIds = array_map('intval', $_POST['selected_auths']);
            $placeholders = str_repeat('?,', count($selectedIds) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM authorizations WHERE id IN ($placeholders)");
            if ($stmt->execute($selectedIds)) {
                $success_message = "成功删除 " . count($selectedIds) . " 个授权";
            } else {
                $error_message = "批量删除失败";
            }
        } catch (Exception $e) {
            $error_message = "批量删除失败: " . $e->getMessage();
        }
    } else {
        $error_message = "请先选择要删除的授权";
    }
}

if (!$_SESSION['admin_logged_in']) {
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #333;
            margin: 0;
            font-size: 28px;
            font-weight: 300;
        }
        .login-header p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        .login-btn:hover {
            transform: translateY(-2px);
        }
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #c33;
        }
        .default-info {
            background: #e8f4fd;
            color: #0066cc;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #0066cc;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>管理员登录</h2>
            <p>授权系统管理后台</p>
        </div>

        <div class="default-info">
            <strong>默认账号:</strong> admin<br>
            <strong>默认密码:</strong> admin123<br>
            <small>首次登录后请及时修改密码</small>
        </div>

        <form method="post">
            <div class="form-group">
                <label for="username">用户名:</label>
                <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="password">密码:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login" class="login-btn">登录</button>

            <?php if (isset($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
<?php
exit;
}

// 获取统计数据
$pdo = getDatabase();

// 卡密统计
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(is_used) as used FROM card_keys");
$cardStats = $stmt->fetch();

// 授权统计
$stmt = $pdo->query("SELECT COUNT(*) as total FROM authorizations");
$authStats = $stmt->fetch();

$stmt = $pdo->query("SELECT COUNT(*) as active FROM authorizations WHERE expire_timestamp > " . time());
$activeAuth = $stmt->fetch();

// 获取最近的卡密
$stmt = $pdo->query("SELECT * FROM card_keys ORDER BY created_at DESC LIMIT 20");
$recentCards = $stmt->fetchAll();

// 获取最近的授权
$stmt = $pdo->query("SELECT * FROM authorizations ORDER BY created_at DESC LIMIT 20");
$recentAuths = $stmt->fetchAll();

// 根据页面获取完整数据
if ($currentPage === 'cards') {
    // 分页参数
    $page = max(1, intval($_GET['p'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;

    // 获取卡密总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM card_keys");
    $totalCards = $stmt->fetch()['total'];
    $totalPages = ceil($totalCards / $limit);

    // 获取卡密列表
    $stmt = $pdo->prepare("SELECT * FROM card_keys ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $allCards = $stmt->fetchAll();
}

if ($currentPage === 'auth') {
    // 分页参数
    $page = max(1, intval($_GET['p'] ?? 1));
    $limit = 50;
    $offset = ($page - 1) * $limit;

    // 获取授权总数
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM authorizations");
    $totalAuths = $stmt->fetch()['total'];
    $totalPages = ceil($totalAuths / $limit);

    // 获取授权列表
    $stmt = $pdo->prepare("SELECT * FROM authorizations ORDER BY created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute([$limit, $offset]);
    $allAuths = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>授权系统管理后台</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #007cba; }
        .content { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .panel { background: white; padding: 20px; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .logout-btn { background: #dc3545; }
        .change-pwd-btn { background: #28a745; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .success { color: green; margin-bottom: 15px; }
        .card-list { max-height: 300px; overflow-y: auto; }
        .status-active { color: green; font-weight: bold; }
        .status-expired { color: red; }
        .status-used { color: orange; }

        /* 模态框样式 */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 30px;
            border-radius: 10px;
            width: 400px;
            max-width: 90%;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .pagination {
            display: flex;
            justify-content: center;
            margin: 20px 0;
        }
        .pagination a, .pagination span {
            padding: 8px 12px;
            margin: 0 4px;
            text-decoration: none;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .pagination a:hover {
            background: #f5f5f5;
        }
        .pagination .current {
            background: #007cba;
            color: white;
            border-color: #007cba;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .full-width {
            grid-column: 1 / -1;
        }
        .batch-actions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        .batch-actions.show {
            display: block;
        }
        .btn-batch {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-batch:hover {
            background: #c82333;
        }
        .select-all {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>授权系统管理后台</h1>
            <p>欢迎，<?php echo htmlspecialchars($_SESSION['admin_username']); ?></p>
        </div>
        <div>
            <button onclick="showPasswordModal()" class="change-pwd-btn">修改密码</button>
            <form method="post" style="display: inline; margin: 0;">
                <button type="submit" name="logout" class="logout-btn">退出登录</button>
            </form>
        </div>
    </div>

    <!-- 导航菜单 -->
    <div style="background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
        <a href="admin.php" style="background: <?php echo $currentPage === 'dashboard' ? '#0056b3' : '#007cba'; ?>; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; margin-right: 10px;">主页</a>
        <a href="admin.php?page=cards" style="background: <?php echo $currentPage === 'cards' ? '#e55a00' : '#fd7e14'; ?>; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; margin-right: 10px;">卡密管理</a>
        <a href="admin.php?page=auth" style="background: <?php echo $currentPage === 'auth' ? '#5a2d91' : '#6f42c1'; ?>; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; margin-right: 10px;">授权管理</a>
        <a href="admin_users.php" style="background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; margin-right: 10px;">账户管理</a>
        <a href="index.html" target="_blank" style="background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px;">前端页面</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $cardStats['total']; ?></div>
            <div>总卡密数</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $cardStats['used']; ?></div>
            <div>已使用卡密</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $authStats['total']; ?></div>
            <div>总授权数</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $activeAuth['active']; ?></div>
            <div>有效授权</div>
        </div>
    </div>

    <?php if ($currentPage === 'dashboard'): ?>
        <!-- 主页内容 -->
        <div class="content">
            <div class="panel">
                <h3>生成卡密</h3>
                <?php if (isset($success_message)): ?>
                    <div class="success"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label for="count">生成数量:</label>
                        <input type="number" id="count" name="count" min="1" max="100" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="duration">有效天数:</label>
                        <input type="number" id="duration" name="duration" min="1" value="30" required>
                    </div>
                    <button type="submit" name="generate_cards">生成卡密</button>
                </form>

                <?php if (isset($generated) && !empty($generated)): ?>
                    <h4>新生成的卡密:</h4>
                    <div class="card-list">
                        <?php foreach ($generated as $card): ?>
                            <div style="font-family: monospace; padding: 5px; background: #f8f9fa; margin: 2px 0; border-radius: 3px;">
                                <?php echo $card; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <h3>最近卡密</h3>
                <table>
                    <tr>
                        <th>卡密</th>
                        <th>天数</th>
                        <th>状态</th>
                        <th>使用者</th>
                    </tr>
                    <?php foreach ($recentCards as $card): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 12px;"><?php echo substr($card['card_key'], 0, 20) . '...'; ?></td>
                        <td><?php echo $card['duration_days']; ?>天</td>
                        <td class="<?php echo $card['is_used'] ? 'status-used' : 'status-active'; ?>">
                            <?php echo $card['is_used'] ? '已使用' : '未使用'; ?>
                        </td>
                        <td><?php echo $card['used_by'] ?: '-'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($currentPage === 'cards'): ?>
        <!-- 卡密管理页面 -->
        <div class="panel full-width">
            <h3>卡密管理</h3>
            <?php if (isset($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <strong>总计:</strong> <?php echo $totalCards; ?> 个卡密 |
                <strong>已使用:</strong> <?php echo $cardStats['used']; ?> 个 |
                <strong>未使用:</strong> <?php echo $totalCards - $cardStats['used']; ?> 个
            </div>

            <form id="cardsForm" method="post">
                <!-- 批量操作区域 -->
                <div id="batchActionsCards" class="batch-actions">
                    <strong>已选择 <span id="selectedCountCards">0</span> 个卡密</strong>
                    <button type="submit" name="batch_delete_cards" class="btn-batch" onclick="return confirm('确定要删除选中的卡密吗？此操作不可恢复！')">批量删除</button>
                    <button type="button" onclick="clearSelectionCards()" style="background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer;">取消选择</button>
                </div>
                <table>
                    <tr>
                        <th><input type="checkbox" id="selectAllCards" class="select-all" onchange="toggleAllCards()"> 全选</th>
                        <th>ID</th>
                        <th>卡密</th>
                        <th>有效天数</th>
                        <th>状态</th>
                        <th>使用者</th>
                        <th>使用时间</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                    <?php foreach ($allCards as $card): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_cards[]" value="<?php echo $card['id']; ?>" class="card-checkbox" onchange="updateBatchActionsCards()"></td>
                        <td><?php echo $card['id']; ?></td>
                        <td style="font-family: monospace; font-size: 12px;"><?php echo $card['card_key']; ?></td>
                        <td><?php echo $card['duration_days']; ?>天</td>
                        <td class="<?php echo $card['is_used'] ? 'status-used' : 'status-active'; ?>">
                            <?php echo $card['is_used'] ? '已使用' : '未使用'; ?>
                        </td>
                        <td><?php echo $card['used_by'] ?: '-'; ?></td>
                        <td><?php echo $card['used_at'] ? date('Y-m-d H:i:s', strtotime($card['used_at'])) : '-'; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($card['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('确定要删除这个卡密吗？')">
                                <input type="hidden" name="card_id" value="<?php echo $card['id']; ?>">
                                <button type="submit" name="delete_card" class="btn-danger">删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </form>

            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=cards&p=<?php echo $page-1; ?>">&laquo; 上一页</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=cards&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=cards&p=<?php echo $page+1; ?>">下一页 &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($currentPage === 'auth'): ?>
        <!-- 授权管理页面 -->
        <div class="panel full-width">
            <h3>授权管理</h3>
            <?php if (isset($success_message)): ?>
                <div class="success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <?php if (isset($error_message)): ?>
                <div class="error"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <strong>总计:</strong> <?php echo $totalAuths; ?> 个授权 |
                <strong>有效:</strong> <?php echo $activeAuth['active']; ?> 个 |
                <strong>过期:</strong> <?php echo $totalAuths - $activeAuth['active']; ?> 个
            </div>

            <form id="authsForm" method="post">
                <!-- 批量操作区域 -->
                <div id="batchActionsAuths" class="batch-actions">
                    <strong>已选择 <span id="selectedCountAuths">0</span> 个授权</strong>
                    <button type="submit" name="batch_delete_auths" class="btn-batch" onclick="return confirm('确定要删除选中的授权吗？此操作不可恢复！')">批量删除</button>
                    <button type="button" onclick="clearSelectionAuths()" style="background: #6c757d; color: white; padding: 8px 16px; border: none; border-radius: 5px; cursor: pointer;">取消选择</button>
                </div>
                <table>
                    <tr>
                        <th><input type="checkbox" id="selectAllAuths" class="select-all" onchange="toggleAllAuths()"> 全选</th>
                        <th>ID</th>
                        <th>WXID</th>
                        <th>到期时间</th>
                        <th>状态</th>
                        <th>卡密</th>
                        <th>授权信息</th>
                        <th>创建时间</th>
                        <th>操作</th>
                    </tr>
                    <?php foreach ($allAuths as $auth): ?>
                    <tr>
                        <td><input type="checkbox" name="selected_auths[]" value="<?php echo $auth['id']; ?>" class="auth-checkbox" onchange="updateBatchActionsAuths()"></td>
                        <td><?php echo $auth['id']; ?></td>
                        <td><?php echo $auth['wxid']; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', $auth['expire_timestamp']); ?></td>
                        <td class="<?php echo $auth['expire_timestamp'] > time() ? 'status-active' : 'status-expired'; ?>">
                            <?php echo $auth['expire_timestamp'] > time() ? '有效' : '已过期'; ?>
                        </td>
                        <td style="font-family: monospace; font-size: 12px;"><?php echo substr($auth['card_key'], 0, 20) . '...'; ?></td>
                        <td><?php echo $auth['auth_info'] ? '有' : '-'; ?></td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($auth['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display: inline;" onsubmit="return confirm('确定要删除这个授权吗？')">
                                <input type="hidden" name="auth_id" value="<?php echo $auth['id']; ?>">
                                <button type="submit" name="delete_auth" class="btn-danger">删除</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </form>

            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=auth&p=<?php echo $page-1; ?>">&laquo; 上一页</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=auth&p=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=auth&p=<?php echo $page+1; ?>">下一页 &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($currentPage === 'dashboard'): ?>
    <div class="panel" style="margin-top: 20px;">
        <h3>最近授权</h3>
        <table>
            <tr>
                <th>WXID</th>
                <th>到期时间</th>
                <th>状态</th>
                <th>卡密</th>
                <th>创建时间</th>
            </tr>
            <?php foreach ($recentAuths as $auth): ?>
            <tr>
                <td><?php echo $auth['wxid']; ?></td>
                <td><?php echo date('Y-m-d H:i:s', $auth['expire_timestamp']); ?></td>
                <td class="<?php echo $auth['expire_timestamp'] > time() ? 'status-active' : 'status-expired'; ?>">
                    <?php echo $auth['expire_timestamp'] > time() ? '有效' : '已过期'; ?>
                </td>
                <td style="font-family: monospace; font-size: 12px;"><?php echo substr($auth['card_key'], 0, 15) . '...'; ?></td>
                <td><?php echo $auth['created_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>

    <!-- 修改密码模态框 -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>修改密码</h3>
                <span class="close" onclick="closePasswordModal()">&times;</span>
            </div>

            <?php if (isset($password_success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($password_success); ?></div>
            <?php endif; ?>

            <?php if (isset($password_error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($password_error); ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="current_password">当前密码:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                <div class="form-group">
                    <label for="new_password">新密码:</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="confirm_password">确认新密码:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>
                <button type="submit" name="change_password">修改密码</button>
                <button type="button" onclick="closePasswordModal()" style="background: #6c757d; margin-left: 10px;">取消</button>
            </form>
        </div>
    </div>

    <script>
        function showPasswordModal() {
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        // 点击模态框外部关闭
        window.onclick = function(event) {
            var modal = document.getElementById('passwordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // 如果有密码相关的消息，自动显示模态框
        <?php if (isset($password_success) || isset($password_error)): ?>
            showPasswordModal();
        <?php endif; ?>

        // 密码确认验证
        document.getElementById('confirm_password').addEventListener('input', function() {
            var newPassword = document.getElementById('new_password').value;
            var confirmPassword = this.value;

            if (newPassword !== confirmPassword) {
                this.setCustomValidity('密码不匹配');
            } else {
                this.setCustomValidity('');
            }
        });

        // 卡密批量选择功能
        function toggleAllCards() {
            var selectAll = document.getElementById('selectAllCards');
            var checkboxes = document.querySelectorAll('.card-checkbox');

            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });

            updateBatchActionsCards();
        }

        function updateBatchActionsCards() {
            var checkboxes = document.querySelectorAll('.card-checkbox:checked');
            var count = checkboxes.length;
            var batchActions = document.getElementById('batchActionsCards');
            var selectedCount = document.getElementById('selectedCountCards');

            if (count > 0) {
                batchActions.classList.add('show');
                selectedCount.textContent = count;
            } else {
                batchActions.classList.remove('show');
            }

            // 更新全选状态
            var allCheckboxes = document.querySelectorAll('.card-checkbox');
            var selectAll = document.getElementById('selectAllCards');
            selectAll.checked = count === allCheckboxes.length;
        }

        function clearSelectionCards() {
            var checkboxes = document.querySelectorAll('.card-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            document.getElementById('selectAllCards').checked = false;
            updateBatchActionsCards();
        }

        // 授权批量选择功能
        function toggleAllAuths() {
            var selectAll = document.getElementById('selectAllAuths');
            var checkboxes = document.querySelectorAll('.auth-checkbox');

            checkboxes.forEach(function(checkbox) {
                checkbox.checked = selectAll.checked;
            });

            updateBatchActionsAuths();
        }

        function updateBatchActionsAuths() {
            var checkboxes = document.querySelectorAll('.auth-checkbox:checked');
            var count = checkboxes.length;
            var batchActions = document.getElementById('batchActionsAuths');
            var selectedCount = document.getElementById('selectedCountAuths');

            if (count > 0) {
                batchActions.classList.add('show');
                selectedCount.textContent = count;
            } else {
                batchActions.classList.remove('show');
            }

            // 更新全选状态
            var allCheckboxes = document.querySelectorAll('.auth-checkbox');
            var selectAll = document.getElementById('selectAllAuths');
            selectAll.checked = count === allCheckboxes.length;
        }

        function clearSelectionAuths() {
            var checkboxes = document.querySelectorAll('.auth-checkbox');
            checkboxes.forEach(function(checkbox) {
                checkbox.checked = false;
            });
            document.getElementById('selectAllAuths').checked = false;
            updateBatchActionsAuths();
        }
    </script>
</body>
</html>
