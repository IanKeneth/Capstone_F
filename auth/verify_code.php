<?php
require 'conn.php';
session_start();

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_code = $_POST['code'];
    $email = $_SESSION['reset_email'];
    $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ? AND reset_token = ? AND reset_expires > NOW() LIMIT 1");
    $stmt->execute([$email, $user_code]);
    
    if ($stmt->fetch()) {
        $_SESSION['code_verified'] = true;
        header("Location: reset_password.php");
        exit();
    } else {
        echo "<script>alert('Invalid or expired code.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Code</title>
    <link rel="stylesheet" href="assets/css/login-style.css">
</head>
<body>
    <div class="auth-card">
        <h2 class="auth-title">Verify <span>Code</span></h2>
        <p>Enter the 6-digit code sent to <?= htmlspecialchars($_SESSION['reset_email']) ?></p>
        <form method="POST">
            <div class="input-group">
                <input type="text" name="code" placeholder="6-digit code" maxlength="6" required>
            </div>
            <button class="btn-primary">Verify</button>
        </form>
    </div>
</body>
</html>