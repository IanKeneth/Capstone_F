<?php
session_start();
require 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'forgot_password') {
    $forgot_user = trim($_POST['forgot_username'] ?? '');
    $forgot_email = trim($_POST['forgot_email'] ?? '');

    if (empty($forgot_user) || empty($forgot_email)) {
        echo "<script>alert('All fields are required for password reset.'); history.back();</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name FROM admin WHERE username = ? AND email = ? LIMIT 1");
        $stmt->execute([$forgot_user, $forgot_email]);
        $admin = $stmt->fetch();

        if ($admin) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            $update = $pdo->prepare("UPDATE admin SET reset_token = ?, reset_expires_at = ? WHERE id = ?");
            $update->execute([$token, $expiry, $admin['id']]);

            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
            
            $resetLink = $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;

            if (sendRecoveryEmail($forgot_email, $admin['name'], $resetLink)) {
                echo "<script>alert('Account verified! A secure reset link has been sent to your email.'); window.location.href = '';</script>";
            } else {
                echo "<script>alert('Account verified, but email delivery failed. Please check your SMTP settings.'); history.back();</script>";
            }
            exit();
        } else {
            echo "<script>alert('Verification failed. Invalid Username or Recovery Email combination.'); history.back();</script>";
            exit();
        }
    } catch (PDOException $e) {
        error_log("Forgot Password Error: " . $e->getMessage());
        echo "<script>alert('System processing error. Please try again.'); history.back();</script>";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username)) {
        echo "<script>alert('Please enter your username.'); history.back();</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, password FROM admin WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {

            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['logged_in'] = true;

            echo "<script>
                    alert('Welcome back, " . addslashes($admin['name']) . "!');
                    window.location.href ='../index.php';
                  </script>";
            exit();

        } else {
            echo "<script>alert('Invalid credentials. Access Denied.'); history.back();</script>";
        }

    } catch (PDOException $e) {
        error_log("DB Error: " . $e->getMessage());
        echo "<script>alert('System error. Please try again later.');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    :root {
        --primary-orange: #f28b30;
        --dark-orange: #d9741e;
        --bg-gray: #f0f0f0;
        --text-dark: #333333;
        --text-muted: #666666;
        --input-border: #cccccc;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: var(--bg-gray);
        font-family: 'Segoe UI', Arial, sans-serif;
        position: relative;
        overflow: hidden;
    }

    body::before {
        content: "";
        position: absolute;
        width: 120%; 
        height: 120%;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-image: url('../assets/img/sale.png');
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        mix-blend-mode: multiply;
        opacity:0.5;
        z-index: 0;
        pointer-events: none;
    }

    .login-container {
        width: 100%;
        max-width: 420px;
        padding: 30px;
        text-align: center;
        z-index: 1; 
    }
    .brand-section {
        margin-bottom: 35px;
    }

    .brand-name {
        font-size: 26px;
        font-weight: 700;
        color: var(--dark-orange);
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .input-group {
        position: relative;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        background: #ffffff;
        border-radius: 50px; 
        border: 1px solid var(--input-border);
        padding: 4px 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .input-group:focus-within {
        border-color: var(--primary-orange);
    }

    .input-icon {
        color: #333333;
        font-size: 16px;
        width: 25px;
        text-align: center;
    }

    .divider {
        height: 20px;
        width: 1px;
        background-color: #dddddd;
        margin: 0 15px;
    }

    .input-group input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 12px 0;
        font-size: 16px;
        color: var(--text-dark);
        outline: none;
    }

    .input-group input::placeholder {
        color: #aaaaaa;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 0.5px;
    }

    .password-toggle {
        color: #888888;
        cursor: pointer;
        padding-left: 10px;
        font-size: 16px;
    }

    .password-toggle:hover {
        color: var(--primary-orange);
    }

    .btn-login {
        background-color: var(--primary-orange);
        color: #ffffff;
        border: none;
        padding: 14px;
        width: 100%;
        border-radius: 50px;
        font-size: 18px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(242, 139, 48, 0.2);
        transition: background-color 0.2s, transform 0.1s;
    }

    .btn-login:hover {
        background-color: var(--dark-orange);
    }

    .btn-login:active {
        transform: scale(0.99);
    }

    .forgot-link-wrapper {
        text-align: right;
        margin: -12px 15px 22px 0;
    }

    .forgot-password-link {
        color: var( --text-dark);
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        transition: color 0.2s, text-decoration 0.2s;
    }

    .forgot-password-link:hover {
        color: var(--text-muted);
        text-decoration: underline;
    }

    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .modal-overlay.active {
        opacity: 1;
        pointer-events: auto;
    }

    .modal-box {
        background: #ffffff;
        width: 90%;
        max-width: 400px;
        padding: 30px;
        border-radius: 20px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        transform: translateY(-20px);
        transition: transform 0.3s ease;
    }

    .modal-overlay.active .modal-box {
        transform: translateY(0);
    }

    .modal-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--text-dark);
        margin-bottom: 10px;
    }

    .modal-subtitle {
        font-size: 13px;
        color: var(--text-muted);
        margin-bottom: 20px;
        line-height: 1.4;
    }

    .modal-actions {
        display: flex;
        gap: 10px;
        margin-top: 25px;
    }

    .btn-cancel {
        background: #e0e0e0;
        color: var(--text-dark);
        border: none;
        padding: 12px;
        width: 50%;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-submit-forgot {
        background: var(--primary-orange);
        color: #ffffff;
        border: none;
        padding: 12px;
        width: 50%;
        border-radius: 50px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-submit-forgot:hover {
        background: var(--dark-orange);
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 10000;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }

    .loading-overlay.visible {
        opacity: 1;
        pointer-events: auto;
    }

    .spinner-box {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    .loading-spinner {
        font-size: 50px;
        color: var(--primary-orange);
        animation: spin 1.5s linear infinite;
    }

    .loading-text {
        color: #ffffff;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 0.5px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="spinner-box">
        <i class="fa-solid fa-gear loading-spinner"></i>
        <div class="loading-text">Sending recovery email, please wait...</div>
    </div>
</div>

<div class="login-container">
    <form method="POST">
        <div class="input-group">
            <span class="input-icon"><i class="fa-solid fa-user"></i></span>
            <div class="divider"></div>
            <input type="text" name="username" placeholder="Username" required autocomplete="off">
        </div>

        <div class="input-group">
            <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
            <div class="divider"></div>
            <input type="password" name="password" id="password" placeholder="••••••••••••" required>
            <span class="password-toggle" id="togglePassword">
                <i class="fa-regular fa-eye" id="eyeIcon"></i>
            </span>
        </div>

        <div class="forgot-link-wrapper">
            <a href="#" class="forgot-password-link" id="openForgotModal">Forgot Password?</a>
        </div>

        <button type="submit" class="btn-login">Login</button>
    </form>
</div>

<div class="modal-overlay" id="forgotModal">
    <div class="modal-box">
        <div class="modal-title">Reset Password</div>
        <div class="modal-subtitle">Verify your administrator identity matching information to receive a secure recovery email link.</div>
        
        <form method="POST" id="forgotPasswordForm">
            <input type="hidden" name="action" value="forgot_password">

            <div class="input-group">
                <span class="input-icon"><i class="fa-solid fa-user"></i></span>
                <div class="divider"></div>
                <input type="text" name="forgot_username" placeholder="Username" required autocomplete="off">
            </div>

            <div class="input-group">
                <span class="input-icon"><i class="fa-solid fa-envelope"></i></span>
                <div class="divider"></div>
                <input type="email" name="forgot_email" placeholder="Recovery Email" required autocomplete="off">
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-cancel" id="closeForgotModal">Cancel</button>
                <button type="submit" class="btn-submit-forgot">Send Link</button>
            </div>
        </form>
    </div>
</div>

<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', () => {
        const type = password.type === 'password' ? 'text' : 'password';
        password.type = type;
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });

    const forgotModal = document.getElementById('forgotModal');
    const openForgotModal = document.getElementById('openForgotModal');
    const closeForgotModal = document.getElementById('closeForgotModal');
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const loadingOverlay = document.getElementById('loadingOverlay');

    openForgotModal.addEventListener('click', (e) => {
        e.preventDefault();
        forgotModal.classList.add('active');
    });

    closeForgotModal.addEventListener('click', () => {
        forgotModal.classList.remove('active');
    });

    forgotModal.addEventListener('click', (e) => {
        if (e.target === forgotModal) {
            forgotModal.classList.remove('active');
        }
    });

    forgotPasswordForm.addEventListener('submit', function() {
        // Hide the input modal box out of view 
        forgotModal.classList.remove('active');
        loadingOverlay.classList.add('visible');
    });
</script>

</body>
</html>