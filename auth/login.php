<?php
session_start();
require 'conn.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';

    if (!$email) {
        echo "<script>alert('Please enter a valid email.'); history.back();</script>";
        exit();
    }

    try {
        $stmt = $pdo->prepare("SELECT id, name, password FROM admin WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
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

<link rel="stylesheet" href="../assets/css/login-style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

</head>
<body>

<div class="auth-card">

    <div class="auth-logo">
        <div class="avatar-circle">
            <i class="fa-solid fa-user person-icon"></i>
        </div>
    </div>

    <h2 class="auth-title">Welcome <span>Back</span></h2>
    <p class="auth-subtitle">Please enter your details</p>

    <form method="POST">

        <div class="input-group">
            <span class="input-icon"><i class="fa-regular fa-user"></i></span>
            <input type="email" name="email" placeholder="Email" required>
        </div>

        <div class="input-group">
            <span class="input-icon"><i class="fa-solid fa-lock"></i></span>
            <input type="password" name="password" id="password" placeholder="Password" required>
            
            <span class="password-toggle" id="togglePassword">
                <i class="fa-regular fa-eye" id="eyeIcon"></i>
            </span>
        </div>

        <div class="form-options">
            <a href="frogot_passwod.php" class="forgot-link">Forgot password?</a>
        </div>

        <button class="btn-primary">Login</button>

    </form>

    <div class="auth-footer">
        New here? <a href="registration.php">Create account</a>
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
</script>

</body>
</html>