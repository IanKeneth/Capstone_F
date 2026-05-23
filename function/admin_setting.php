<?php
session_start();
require_once "../auth/conn.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_SESSION['admin_id'];
    $full_name = trim($_POST['full_name']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    try {
        $stmt = $pdo->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->execute([$admin_id]);
        $user = $stmt->fetch();

        if (!empty($current_password)) {
            if (password_verify($current_password, $user['password'])) {
                if (!empty($new_password) && $new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_pass = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
                    $update_pass->execute([$hashed_password, $admin_id]);
                } else {
                    $_SESSION['error'] = "New passwords do not match.";
                    header("Location: ../setting.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Current password is incorrect.";
                header("Location: ../setting.php");
                exit();
            }
        }

        $update_name = $pdo->prepare("UPDATE admin SET name = ? WHERE id = ?");
        $update_name->execute([$full_name, $admin_id]);
        if (!empty($_FILES['profile_pic']['name'])) {
        }

        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: ../setting.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header("Location: ../setting.php");
        exit();
    }
}