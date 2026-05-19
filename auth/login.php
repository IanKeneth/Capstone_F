<?php
session_start();
require 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
<title>Salescore Supplies - Login</title>
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
</style>
</head>
<body>

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


        <button type="submit" class="btn-login">Login</button>

    </form>
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
</script>

</body>
</html>