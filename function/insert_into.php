<?php
session_start();
require_once '../auth/conn.php';

$target_dir = "../uploads/";
$image_name = "default-product.png";

if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['product_image']['type'];

    if (in_array($file_type, $allowed_types)) {
        $file_ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $p_name = $_POST['product_name'] ?? 'product';
        $clean_name = preg_replace("/[^a-zA-Z0-9]/", "_", $p_name);
        $image_name = time() . "_" . $clean_name . "." . $file_ext;

        if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $target_dir . $image_name)) {
            $image_name = "default-product.png";
        }
    }
}

try {
    $pdo->beginTransaction();

    $sql = "INSERT INTO products 
            (category, product_name, variation, description, wholesale_price, retail_price, quantity, max_quantity, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['category'] ?? 'General',
        $_POST['product_name'] ?? 'Unnamed',
        $_POST['variation'] ?? 'Standard',
        $_POST['description'] ?? '',
        $_POST['wholesale_price'] ?? 0,
        $_POST['retail_price'] ?? 0,
        $_POST['quantity'] ?? 0,
        $_POST['max_quantity'] ?? 100,
        $image_name 
    ]);

    $new_id = $pdo->lastInsertId();

    $log_sql = "INSERT INTO inventory_logs (product_id, admin_name, action, quantity_change, notes) 
                VALUES (?, ?, 'Added', ?, ?)";
    
    $log_stmt = $pdo->prepare($log_sql);
    $qty = $_POST['quantity'] ?? 0;
    $admin_name = $_SESSION['admin_name'] ?? 'System'; 
    $note = "Initial stock entry for " . ($_POST['product_name'] ?? 'new product');

    $log_stmt->execute([$new_id, $admin_name, $qty, $note]);

    $pdo->commit();
    header("Location: ../inventory.php?success=1");
    exit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    die("Error: " . $e->getMessage());
}
?>