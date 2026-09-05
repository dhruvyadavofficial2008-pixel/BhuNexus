<?php
require 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        header("Location: " . ($user['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
        exit;
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Land Acquisition System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Land Acquisition System</h2>
        <p class="subtitle">Real-Time National Land Acquisition & Management</p>
        <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="POST">
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit" style="width:100%">Login</button>
        </form>
        <p class="muted" style="margin-top:14px">
            No account? <a href="register.php" style="color:var(--primary); font-weight:600">Register as citizen</a>
        </p>
        <div class="muted" style="margin-top:18px; border-top:1px solid var(--border); padding-top:12px">
            Demo logins (password: <b>password123</b>)<br>
            Admin: admin@land.gov<br>
            Citizen: ramesh@example.com
        </div>
    </div>
</div>
</body>
</html>
