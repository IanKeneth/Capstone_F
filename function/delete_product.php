<?php
error_reporting(0);
require_once '../auth/conn.php';
/** @var PDO $pdo */
header('Content-Type: application/json');
$id = $_GET['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'No ID provided']);
    exit;
}

try {
    $pdo->beginTransaction();
    $stmtImg = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
    $stmtImg->execute([$id]);
    $product = $stmtImg->fetch(PDO::FETCH_ASSOC);

    $stmtLogs = $pdo->prepare("DELETE FROM inventory_logs WHERE product_id = ?");
    $stmtLogs->execute([$id]);

    $stmtDel = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmtDel->execute([$id]);

    if ($stmtDel->rowCount() > 0) {
        if ($product && $product['image_path'] && $product['image_path'] != 'default-product.png') {
            $file_path = "../uploads/" . $product['image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        $pdo->commit();
        echo json_encode(['status' => 'success']);
    } else {
        throw new Exception("Product not found or already deleted.");
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}