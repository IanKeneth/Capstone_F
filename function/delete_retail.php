<?php
session_start();
require_once "../auth/conn.php";

// Check authorization
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: ../retailer.php?status=error&msg=" . urlencode("Invalid Order ID."));
    exit();
}

try {
    // Delete order directly from retail_orders without restoring stock
    $deleteOrder = $pdo->prepare("DELETE FROM retail_orders WHERE id = ?");
    $deleteOrder->execute([$id]);

    if ($deleteOrder->rowCount() > 0) {
        header("Location: ../retailer.php?status=deleted");
        exit();
    } else {
        header("Location: ../retailer.php?status=error&msg=" . urlencode("Order record not found."));
        exit();
    }

} catch (PDOException $e) {
    header("Location: ../retailer.php?status=error&msg=" . urlencode($e->getMessage()));
    exit();
}