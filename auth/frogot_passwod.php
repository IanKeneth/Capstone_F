<?php
date_default_timezone_set('Asia/Manila');
require 'conn.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

    if ($email) {
        $stmt = $pdo->prepare("SELECT id FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $verification_code = sprintf("%06d", mt_rand(1, 999999));
            $expires = date("Y-m-d H:i:s", strtotime("+15 minutes")); 

            $update = $pdo->prepare("UPDATE admin SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $update->execute([$verification_code, $expires, $email]);

            $_SESSION['reset_email'] = $email;
            echo "<script>
                    alert('Code: $verification_code | Expires at: $expires');
                    window.location.href = 'verify_code.php';
                  </script>";
        } else {
            echo "<script>alert('Email not found.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="assets/css/login-style.css">
</head>
<body>
    <div class="auth-card">
        <h2 class="auth-title">Forgot <span>Password</span></h2>
        <form method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="Enter your Email" required>
            </div>
            <button class="btn-primary">Send Code</button>
        </form>
    </div>
</body>
</html>