<?php
session_start();
require 'conn.php';

$token = $_GET['token'] ?? $_POST['token'] ?? '';

if (empty($token)) {
    die("Invalid or missing security reset token context.");
}

// Check if token exists and has not expired
try {
    $stmt = $pdo->prepare("SELECT id, name FROM admin WHERE reset_token = ? AND reset_expires_at > NOW() LIMIT 1");
    $stmt->execute([$token]);
    $admin = $stmt->fetch();

    if (!$admin) {
        die("This password reset link is invalid or has expired. Please request a new one.");
    }
} catch (PDOException $e) {
    die("Database verification error: " . $e->getMessage());
}

// Process the actual password submission form update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_pass = $_POST['new_password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($new_pass) || empty($confirm_pass)) {
        echo "<script>alert('Please fill out all fields.');</script>";
    } elseif ($new_pass !== $confirm_pass) {
        echo "<script>alert('Passwords do not match. Please re-type.');</script>";
    } else {
        try {
            // Hash password securely matching password_verify native algorithms
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

            // Update user password and clear token fields to prevent reuse attacks
            $update = $pdo->prepare("UPDATE admin SET password = ?, reset_token = NULL, reset_expires_at = NULL WHERE id = ?");
            $update->execute([$hashed_password, $admin['id']]);

            echo "<script>
                    alert('Password updated successfully! Moving to sign-in page.');
                    window.location.href = 'login.php'; 
                  </script>";
            exit();
        } catch (PDOException $e) {
            error_log("Reset Save Exception: " . $e->getMessage());
            echo "<script>alert('Could not update password. System processing exception.');</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Set New Password</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-orange: #f28b30;
        --dark-orange: #d9741e;
        --bg-gray: #f0f0f0;
        --text-dark: #333333;
        --input-border: #cccccc;
    }
    body {
        margin: 0; padding: 0; display: flex; justify-content: center; align-items: center; min-height: 100vh;
        background-color: var(--bg-gray); font-family: 'Segoe UI', Arial, sans-serif;
    }
    .reset-container {
        width: 100%; max-width: 400px; padding: 30px; background: #ffffff; border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center;
    }
    .title { font-size: 24px; font-weight: 700; color: var(--dark-orange); margin-bottom: 10px; }
    .subtitle { font-size: 14px; color: #666; margin-bottom: 25px; }
    .input-group {
        position: relative; margin-bottom: 20px; display: flex; align-items: center;
        background: #ffffff; border-radius: 50px; border: 1px solid var(--input-border); padding: 4px 20px;
    }
    .input-group:focus-within { border-color: var(--primary-orange); }
    .input-icon { color: #333333; font-size: 16px; width: 25px; text-align: center; }
    .divider { height: 20px; width: 1px; background-color: #dddddd; margin: 0 15px; }
    .input-group input { width: 100%; border: none; background: transparent; padding: 12px 0; font-size: 16px; outline: none; }
    .btn-submit {
        background-color: var(--primary-orange); color: #ffffff; border: none; padding: 14px; width: 100%;
        border-radius: 50px; font-size: 16px; font-weight: 600; text-transform: uppercase; cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-submit:hover { background-color: var(--dark-orange); }
</style>
</head>
<body>

<div class="reset-container">
    <div class="title">Create New Password</div>
    <div class="subtitle">Welcome back, <?= htmlspecialchars($admin['name']) ?>. Please update your account credentials below.</div>

    <form method="POST">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="input-group">
            <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
            <div class="divider"></div>
            <input type="password" name="new_password" placeholder="New Password" required minlength="6">
        </div>

        <div class="input-group">
            <span class="input-icon"><i class="fa-solid fa-check-double"></i></span>
            <div class="divider"></div>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6">
        </div>

        <button type="submit" class="btn-submit">Update Password</button>
    </form>
</div>

</body>
</html>