<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['code_verified'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_pass = $_POST['password'];
    $confirm_pass = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];

    if ($new_pass === $confirm_pass) {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE admin SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
        $stmt->execute([$hashed, $email]);

        session_destroy();
        echo "<script>alert('Password updated! Please login.'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Passwords do not match.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/login-style.css">
</head>
<body>
    <div class="auth-card">
        <h2 class="auth-title">New <span>Password</span></h2>
        <form method="POST">
            <div class="input-group">
                <input type="password" name="password" placeholder="New Password" required>
            </div>
            <div class="input-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button class="btn-primary">Update Password</button>
        </form>
    </div>
</body>
</html>