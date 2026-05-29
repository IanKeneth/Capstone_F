<?php
session_start();
require_once "../auth/conn.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admin_id = $_SESSION['admin_id'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email'] ?? ''); 
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($full_name) || empty($email)) {
        $_SESSION['error'] = "Full name and Recovery Email cannot be empty.";
        header("Location: ../setting.php");
        exit();
    }

    // UPDATED: Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please provide a valid email address.";
        header("Location: ../setting.php");
        exit();
    }

    try {
        $pdo->beginTransaction();

        $check_email = $pdo->prepare("SELECT id FROM admin WHERE email = ? AND id != ? LIMIT 1");
        $check_email->execute([$email, $admin_id]);
        if ($check_email->fetch()) {
            throw new Exception("This recovery email is already in use by another account.");
        }

        $stmt = $pdo->prepare("SELECT password, profile_pic FROM admin WHERE id = ?");
        $stmt->execute([$admin_id]);
        $user = $stmt->fetch();

        if (!empty($current_password)) {
            if (password_verify($current_password, $user['password'])) {
                if (!empty($new_password)) {
                    if ($new_password === $confirm_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_pass = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
                        $update_pass->execute([$hashed_password, $admin_id]);
                    } else {
                        throw new Exception("New passwords do not match.");
                    }
                } else {
                    throw new Exception("New password cannot be empty if current password is provided.");
                }
            } else {
                throw new Exception("Current password is incorrect.");
            }
        }

        $update_info = $pdo->prepare("UPDATE admin SET name = ?, email = ? WHERE id = ?");
        $update_info->execute([$full_name, $email, $admin_id]);

        if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
            
            $file_tmp_path = $_FILES['profile_pic']['tmp_name'];
            $file_info = getimagesize($file_tmp_path);
            
            if ($file_info === false) {
                throw new Exception("Uploaded file is not a valid image.");
            }

            $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
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
                    throw new Exception("Failed to save the image to the server.");
                }
            } else {
                throw new Exception("Invalid format. Use JPG, PNG, GIF, or WEBP.");
            }
        }

        $pdo->commit();
        $_SESSION['success'] = "Profile updated successfully!";
        $_SESSION['admin_name'] = $full_name; 
        header("Location: ../setting.php");
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_SESSION['error'] = $e->getMessage();
        header("Location: ../setting.php");
        exit();
    }
}