<?php
session_start();
require_once '../auth/conn.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$target_dir = "../uploads/";
$image_name = "default-product.png";

if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
    $file_tmp = $_FILES['product_image']['tmp_name'];
    $check = getimagesize($file_tmp); 
    
    if ($check !== false) {
        $allowed_mimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $mime_type = $check['mime'];

        if (in_array($mime_type, $allowed_mimes)) {

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp'
            ];
            $file_ext = $extensions[$mime_type];
            
            $p_name = $_POST['product_name'] ?? 'product';
            $clean_name = preg_replace("/[^a-zA-Z0-9]/", "_", $p_name);

            $image_name = time() . "_" . bin2hex(random_bytes(4)) . "_" . $clean_name . "." . $file_ext;

            if (!move_uploaded_file($file_tmp, $target_dir . $image_name)) {
                $image_name = "default-product.png";
            }
        }
    }
}

try {
    $pdo->beginTransaction();

    $qty = max(0, (int)($_POST['quantity'] ?? 0));
    $wholesale = max(0, (float)($_POST['wholesale_price'] ?? 0));
    $retail = max(0, (float)($_POST['retail_price'] ?? 0));
    $max_qty = max(1, (int)($_POST['max_quantity'] ?? 100));

    $sql = "INSERT INTO products 
            (category, product_name, variation, description, wholesale_price, retail_price, quantity, max_quantity, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['category'] ?? 'General',
        $_POST['product_name'] ?? 'Unnamed',
        $_POST['variation'] ?? 'Standard',
        $_POST['description'] ?? '',
        $wholesale,
        $retail,
        $qty,
        $max_qty,
        $image_name 
    ]);

    $new_id = $pdo->lastInsertId();

    $log_sql = "INSERT INTO inventory_logs (product_id, admin_name, action, quantity_change, notes) 
                VALUES (?, ?, 'Added', ?, ?)";
    
    $log_stmt = $pdo->prepare($log_sql);
    $admin_name = $_SESSION['admin_name'] ?? 'System'; 
    $note = "Initial stock entry for " . ($_POST['product_name'] ?? 'new product');

    $log_stmt->execute([$new_id, $admin_name, $qty, $note]);

    $pdo->commit();
    header("Location: ../inventory.php?success=1");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { 
        $pdo->rollBack(); 
    }

    error_log("Upload Error: " . $e->getMessage());
    die("Error: Could not save product. Please check your data.");
}
?>