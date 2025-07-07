<?php
require_once 'config.php';

session_start();

// 检查是否已登录
if (!$_SESSION['admin_logged_in']) {
    header('Location: admin.php');
    exit;
}

// 处理创建新管理员
if (isset($_POST['create_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } elseif (strlen($password) < 6) {
        $error = '密码长度至少6位';
    } else {
        try {
            $pdo = getDatabase();
            
            // 检查用户名是否已存在
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM admin_users WHERE username = ?");
            $stmt->execute([$username]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                $error = '用户名已存在';
            } else {
                // 创建新管理员
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, email) VALUES (?, ?, ?)");
                $stmt->execute([$username, $passwordHash, $email]);
                
                $success = "管理员账户 '{$username}' 创建成功";
            }
        } catch (Exception $e) {
            $error = '创建失败：' . $e->getMessage();
        }
    }
}

// 处理删除管理员
if (isset($_POST['delete_admin'])) {
    $adminId = intval($_POST['admin_id']);
    
    // 不能删除自己
    if ($adminId == $_SESSION['admin_user_id']) {
        $error = '不能删除当前登录的账户';
    } else {
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ?");
            $stmt->execute([$adminId]);
            
            $success = '管理员账户删除成功';
        } catch (Exception $e) {
            $error = '删除失败：' . $e->getMessage();
        }
    }
}

// 处理重置密码
if (isset($_POST['reset_password'])) {
    $adminId = intval($_POST['admin_id']);
    $newPassword = 'admin123'; // 重置为默认密码
    
    try {
        $pdo = getDatabase();
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ?, login_attempts = 0, locked_until = NULL WHERE id = ?");
        $stmt->execute([$passwordHash, $adminId]);
        
        $success = "密码已重置为默认密码：{$newPassword}";
    } catch (Exception $e) {
        $error = '重置失败：' . $e->getMessage();
    }
}

// 获取所有管理员
$pdo = getDatabase();
$stmt = $pdo->query("SELECT * FROM admin_users ORDER BY created_at DESC");
$admins = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员账户管理</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .nav { margin-bottom: 20px; }
        .nav a { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px; }
        .nav a:hover { background: #005a8b; }
        .content { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .panel { background: white; padding: 20px; border-radius: 10px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-danger { background: #dc3545; }
        .btn-warning { background: #ffc107; color: #212529; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status-locked { color: red; font-weight: bold; }
        .status-normal { color: green; }
        .action-buttons { display: flex; gap: 5px; }
        .action-buttons button { padding: 5px 10px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>管理员账户管理</h1>
        <div>
            <span>当前用户：<?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
        </div>
    </div>

    <div class="nav">
        <a href="admin.php">返回主页</a>
        <a href="admin_users.php">账户管理</a>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="content">
        <div class="panel">
            <h3>创建新管理员</h3>
            <form method="post">
                <div class="form-group">
                    <label for="username">用户名:</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">密码:</label>
                    <input type="password" id="password" name="password" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="email">邮箱 (可选):</label>
                    <input type="email" id="email" name="email">
                </div>
                <button type="submit" name="create_admin">创建管理员</button>
            </form>
        </div>

        <div class="panel">
            <h3>管理员列表</h3>
            <table>
                <tr>
                    <th>ID</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>最后登录</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
                <?php foreach ($admins as $admin): ?>
                <tr>
                    <td><?php echo $admin['id']; ?></td>
                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                    <td><?php echo htmlspecialchars($admin['email'] ?: '-'); ?></td>
                    <td><?php echo $admin['last_login'] ? date('Y-m-d H:i', strtotime($admin['last_login'])) : '从未登录'; ?></td>
                    <td>
                        <?php if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()): ?>
                            <span class="status-locked">已锁定</span>
                        <?php else: ?>
                            <span class="status-normal">正常</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($admin['id'] != $_SESSION['admin_user_id']): ?>
                                <form method="post" style="display: inline;" onsubmit="return confirm('确定要重置此账户的密码吗？')">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    <button type="submit" name="reset_password" class="btn-warning">重置密码</button>
                                </form>
                                <form method="post" style="display: inline;" onsubmit="return confirm('确定要删除此管理员账户吗？此操作不可恢复！')">
                                    <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                                    <button type="submit" name="delete_admin" class="btn-danger">删除</button>
                                </form>
                            <?php else: ?>
                                <span style="color: #666; font-size: 12px;">当前账户</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <div class="panel" style="margin-top: 20px;">
        <h3>安全提醒</h3>
        <ul>
            <li>请为每个管理员设置强密码</li>
            <li>定期检查管理员账户的登录记录</li>
            <li>及时删除不再需要的管理员账户</li>
            <li>重置密码后请通知相关人员及时修改</li>
            <li>账户连续登录失败5次将被锁定30分钟</li>
        </ul>
    </div>
</body>
</html>
