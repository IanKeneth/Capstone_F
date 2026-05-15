<?php
require_once "../auth/conn.php";
session_start();

$id = $_GET['id'] ?? null; 
$item = null;

if ($id) {
    $sql = "SELECT di.*, p.product_name 
            FROM dispatch_items di 
            JOIN products p ON di.product_id = p.id 
            WHERE di.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_item'])) {
    $form_id = $_POST['item_id'];
    $new_qty = (int)$_POST['qty_taken'];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            SELECT di.qty_taken, di.product_id, p.product_name, p.quantity as stock_on_shelf 
            FROM dispatch_items di 
            JOIN products p ON di.product_id = p.id 
            WHERE di.id = ?
        ");
        $stmt->execute([$form_id]);
        $data = $stmt->fetch();

        if ($data) {
            $old_qty = (int)$data['qty_taken'];
            $product_id = $data['product_id'];
            $stock_on_shelf = (int)$data['stock_on_shelf'];
            $difference = $new_qty - $old_qty;

            if ($difference > $stock_on_shelf) {
                throw new Exception("Stock Error: You need $difference more units of '{$data['product_name']}', but only $stock_on_shelf remain in inventory.");
            }
            $updateItem = $pdo->prepare("UPDATE dispatch_items SET qty_taken = ? WHERE id = ?");
            $updateItem->execute([$new_qty, $form_id]);
            $updateProd = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $updateProd->execute([$difference, $product_id]);

            $pdo->commit();
            $message = "<div class='alert alert-success shadow-sm'><i class='fa-solid fa-check-circle me-2'></i>Inventory adjusted successfully!</div>";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "<div class='alert alert-danger shadow-sm'><i class='fa-solid fa-triangle-exclamation me-2'></i>" . $e->getMessage() . "</div>";
    }
    $stmt = $pdo->prepare("SELECT di.*, p.product_name, p.quantity as stock_on_shelf FROM dispatch_items di JOIN products p ON di.product_id = p.id WHERE di.id = ?");
    $stmt->execute([$form_id]);
    $item = $stmt->fetch();
} 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Item Quantity</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f0f2f5; padding-top: 50px; }
        .card { border: none; border-radius: 12px; }
        .product-title { color: #e67e22; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <?= $message; ?>
            
            <?php if ($item): ?>
                <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 text-secondary">
                            <i class="fa-solid fa-box-open me-2"></i>Edit Item Quantity
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="mb-4">Editing stock for: <span class="product-title"><?= htmlspecialchars($item['product_name']) ?></span></p>
                        
                        <form action="" method="POST">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="hidden" id="price" value="<?= $item['price_at_time'] ?>">

                            <div class="mb-3">
                                <label class="form-label fw-bold">Quantity Taken</label>
                                <input type="number" name="qty_taken" id="qty_input" class="form-control form-control-lg" 
                                    value="<?= $item['qty_taken'] ?>" required>
                                <small class="text-muted">Current Price: ₱<?= number_format($item['price_at_time'], 2) ?></small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted small uppercase">Estimated Subtotal</label>
                                <div class="fs-4 fw-bold text-dark">
                                    ₱ <span id="subtotal_display"><?= number_format($item['qty_taken'] * $item['price_at_time'], 2) ?></span>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="update_item" class="btn btn-primary py-2">
                                    Confirm Changes
                                </button>
                                <a href="javascript:history.back()" class="btn btn-outline-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">Item not found.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const qtyInput = document.getElementById('qty_input');
    const price = parseFloat(document.getElementById('price').value);
    const display = document.getElementById('subtotal_display');

    qtyInput.addEventListener('input', function() {
        const qty = parseFloat(this.value) || 0;
        const total = qty * price;
        display.innerText = total.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    });
</script>

</body>
</html>