<?php
require_once "../auth/conn.php";

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: ../dispatchers.php"); exit(); }

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT product_id, qty_taken FROM dispatch_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();

    if ($item) {
        $restore = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $restore->execute([$item['qty_taken'], $item['product_id']]);

        $del = $pdo->prepare("DELETE FROM dispatch_items WHERE id = ?");
        $del->execute([$id]);
    }

    $pdo->commit();
    header("Location: ../dispatchers.php?msg=deleted");
} catch (Exception $e) {
    $pdo->rollBack();
    die("Error: " . $e->getMessage());
}