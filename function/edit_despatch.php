<?php
require_once "../auth/conn.php";
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? null; 
$item = null;
$message = "";

if ($id) {
    $sql = "SELECT di.*, p.product_name, p.quantity as stock_on_shelf 
            FROM dispatch_items di 
            JOIN products p ON di.product_id = p.id 
            WHERE di.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $form_id = $_POST['item_id'];
    $new_qty = (int)$_POST['qty_taken'];

    try {
        if ($new_qty < 0) {
            throw new Exception("Quantity cannot be less than zero.");
        }

        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            SELECT di.qty_taken, di.product_id, p.product_name, p.quantity as stock_on_shelf 
            FROM dispatch_items di 
            JOIN products p ON di.product_id = p.id 
            WHERE di.id = ? FOR UPDATE
        ");
        $stmt->execute([$form_id]);
        $data = $stmt->fetch();

        if ($data) {
            $old_qty = (int)$data['qty_taken'];
            $product_id = $data['product_id'];
            $stock_on_shelf = (int)$data['stock_on_shelf'];
            $difference = $new_qty - $old_qty;

            if ($difference > 0 && $difference > $stock_on_shelf) {
                throw new Exception("Stock Error: Not enough stock for '{$data['product_name']}'. Only $stock_on_shelf left.");
            }

            $updateItem = $pdo->prepare("UPDATE dispatch_items SET qty_taken = ? WHERE id = ?");
            $updateItem->execute([$new_qty, $form_id]);

            $updateProd = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $updateProd->execute([$difference, $product_id]);

            $logAction = $difference > 0 ? "Increased Dispatch" : "Decreased Dispatch";
            $logStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, admin_name, action, quantity_change, notes) VALUES (?, ?, ?, ?, ?)");
            $logStmt->execute([
                $product_id, 
                $_SESSION['admin_name'] ?? 'Admin', 
                $logAction, 
                abs($difference), 
                "Manual adjustment for item ID #$form_id"
            ]);

            $pdo->commit();
            $message = "<div class='alert alert-success shadow-sm'><i class='fa-solid fa-check-circle me-2'></i>Inventory adjusted successfully!</div>";
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='alert alert-danger shadow-sm'><i class='fa-solid fa-triangle-exclamation me-2'></i>" . $e->getMessage() . "</div>";
    }

    $stmt = $pdo->prepare("SELECT di.*, p.product_name, p.quantity as stock_on_shelf FROM dispatch_items di JOIN products p ON di.product_id = p.id WHERE di.id = ?");
    $stmt->execute([$form_id]);
    $item = $stmt->fetch();
} 
?>