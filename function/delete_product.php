<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../auth/conn.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(["status" => "error", "message" => "Invalid Product ID"]);
    exit();
}

try {
    // Start a transaction to ensure all queries succeed together
    $pdo->beginTransaction();

    // 1. Delete associated orders/logs referencing this product
    $stmt1 = $pdo->prepare("DELETE FROM retail_orders WHERE product_id = ?");
    $stmt1->execute([$id]);

    // 2. Delete inventory logs if you have an inventory_logs table (optional, safe to include)
    // $stmt2 = $pdo->prepare("DELETE FROM inventory_logs WHERE product_id = ?");
    // $stmt2->execute([$id]);

    // 3. Now delete the main product row
    $stmt3 = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt3->execute([$id]);

    // Commit changes
    $pdo->commit();

    echo json_encode(["status" => "success", "message" => "Product and associated records deleted successfully"]);

} catch (PDOException $e) {
    // Rollback changes if anything goes wrong
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>