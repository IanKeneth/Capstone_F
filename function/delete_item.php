<?php
session_start();
require_once "../auth/conn.php";

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: ../dispatchers.php?error=" . urlencode("Invalid item ID provided."));
    exit();
}

try {
    $pdo->beginTransaction();

    // Fetch dispatch item details using session_id
    $stmt = $pdo->prepare("SELECT product_id, qty_taken, session_id FROM dispatch_items WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $sessionId = $item['session_id'];

        // Restore stock back to products table
        $restore = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $restore->execute([$item['qty_taken'], $item['product_id']]);

        // Delete record from dispatch_items
        $del = $pdo->prepare("DELETE FROM dispatch_items WHERE id = ?");
        $del->execute([$id]);

        //  Check if there are any remaining items in this session
        $checkRemaining = $pdo->prepare("SELECT COUNT(*) FROM dispatch_items WHERE session_id = ?");
        $checkRemaining->execute([$sessionId]);
        $remainingCount = $checkRemaining->fetchColumn();

        // If no items remain, delete the empty dispatch_sessions row
        if ($remainingCount == 0) {
            $deleteSession = $pdo->prepare("DELETE FROM dispatch_sessions WHERE id = ?");
            $deleteSession->execute([$sessionId]);
        }

        $pdo->commit();
        header("Location: ../dispatchers.php?msg=deleted");
        exit();
    } else {
        $pdo->rollBack();
        header("Location: ../dispatchers.php?error=" . urlencode("Record not found in database."));
        exit();
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header("Location: ../dispatchers.php?error=" . urlencode($e->getMessage()));
    exit();
}