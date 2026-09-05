<?php
require 'config.php';
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);

    if ($check->fetch()) {
        $error = "An account with this email already exists.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role, phone) VALUES (?, ?, ?, 'citizen', ?)");
        $stmt->execute([$name, $email, $hash, $phone]);
        $success = "Registration successful. You can now log in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Land Acquisition System</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-box">
        <h2>Citizen Registration</h2>
        <p class="subtitle">Create an account to track your land parcel</p>
        <?php if ($error): ?><p class="error-msg"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if ($success): ?><p class="success-msg"><?= htmlspecialchars($success) ?></p><?php endif; ?>
        <form method="POST">
            <label>Full Name</label>
            <input type="text" name="full_name" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Phone</label>
            <input type="text" name="phone">
            <label>Password</label>
            <input type="password" name="password" required>
            <button type="submit" style="width:100%">Register</button>
        </form>
        <p class="muted" style="margin-top:14px">
            Already have an account? <a href="index.php" style="color:var(--primary); font-weight:600">Login</a>
        </p>
    </div>
</div>
</body>
</html>
