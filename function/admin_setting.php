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

        $stmt = $pdo->prepare("SELECT password, profile_pic FROM admin WHERE id = ?");
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

        if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            
            $file_tmp_path = $_FILES['profile_pic']['tmp_name'];
            $file_name = $_FILES['profile_pic']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            

            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($file_ext, $allowed_extensions)) {

                $new_file_name = "admin_" . $admin_id . "_" . time() . "." . $file_ext;
                $upload_dir = "../assets/uploads/profiles/";
                
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $dest_path = $upload_dir . $new_file_name;

                if (move_uploaded_file($file_tmp_path, $dest_path)) {
                    if (!empty($user['profile_pic'])) {
                        $old_file = $upload_dir . $user['profile_pic'];
                        if (file_exists($old_file)) {
                            unlink($old_file);
                        }
                    }

                    $update_pic = $pdo->prepare("UPDATE admin SET profile_pic = ? WHERE id = ?");
                    $update_pic->execute([$new_file_name, $admin_id]);
                } else {
                    $_SESSION['error'] = "Failed to save the uploaded image safely.";
                    header("Location: ../setting.php");
                    exit();
                }
            } else {
                $_SESSION['error'] = "Invalid format extension. Use JPG, PNG, GIF, or WEBP.";
                header("Location: ../setting.php");
                exit();
            }
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