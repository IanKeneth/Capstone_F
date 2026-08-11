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

// Fetch initial item data
if ($id) {
    $sql = "SELECT di.*, p.product_name, p.wholesale_price, p.quantity as stock_on_shelf 
            FROM dispatch_items di 
            JOIN products p ON di.product_id = p.id 
            WHERE di.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}

// Handle Form Update
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

            // Update dispatch item qty
            $updateItem = $pdo->prepare("UPDATE dispatch_items SET qty_taken = ? WHERE id = ?");
            $updateItem->execute([$new_qty, $form_id]);

            // Adjust main inventory stock
            $updateProd = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $updateProd->execute([$difference, $product_id]);

            // Log stock movement if any change occurred
            if ($difference !== 0) {
                // Fixed: Shortened log strings to avoid SQL truncation error
                $logAction = $difference > 0 ? "Increased" : "Decreased";
                
                $logStmt = $pdo->prepare("INSERT INTO inventory_logs (product_id, admin_name, action, quantity_change, notes) VALUES (?, ?, ?, ?, ?)");
                $logStmt->execute([
                    $product_id, 
                    $_SESSION['admin_name'] ?? 'Admin', 
                    $logAction, 
                    abs($difference), 
                    "Manual adjustment for item ID #$form_id"
                ]);
            }

            $pdo->commit();
            $message = "<div class='alert alert-success'><i class='fa-solid fa-circle-check'></i> Item quantity adjusted successfully!</div>";
        } else {
            throw new Exception("Item record not found.");
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-triangle-exclamation'></i> " . htmlspecialchars($e->getMessage()) . "</div>";
    }

    $stmt = $pdo->prepare("SELECT di.*, p.product_name, p.wholesale_price, p.quantity as stock_on_shelf FROM dispatch_items di JOIN products p ON di.product_id = p.id WHERE di.id = ?");
    $stmt->execute([$form_id]);
    $item = $stmt->fetch();
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Dispatch Item</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
        --primary-orange: #f28b30;
        --dark-orange: #d9741e;
        --bg-gray: #f4f6f9;
        --text-dark: #333333;
        --text-muted: #666666;
        --border-color: #e2e8f0;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: var(--bg-gray);
        font-family: 'Segoe UI', Arial, sans-serif;
        padding: 20px;
    }

    /* Edit Card Layout */
    .edit-card {
        background: #ffffff;
        width: 100%;
        max-width: 480px;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        overflow: hidden;
    }

    .card-header {
        background: var(--primary-orange);
        color: #ffffff;
        padding: 18px 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header h2 {
        font-size: 18px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-body {
        padding: 24px;
    }

    /* Info Groups */
    .info-group {
        background: #f8fafc;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 14px 16px;
        margin-bottom: 20px;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: 14px;
    }

    .info-row:last-child {
        margin-bottom: 0;
    }

    .info-label {
        color: var(--text-muted);
        font-weight: 500;
    }

    .info-value {
        color: var(--text-dark);
        font-weight: 600;
    }

    /* Form Inputs */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 8px;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        font-size: 15px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        transition: border-color 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-orange);
    }

    /* Buttons */
    .btn-container {
        display: flex;
        gap: 12px;
        margin-top: 24px;
    }

    .btn {
        flex: 1;
        padding: 11px;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
        text-decoration: none;
        cursor: pointer;
        border: none;
        transition: background-color 0.2s;
    }

    .btn-cancel {
        background-color: #e2e8f0;
        color: #475569;
    }

    .btn-cancel:hover {
        background-color: #cbd5e1;
    }

    .btn-submit {
        background-color: var(--primary-orange);
        color: #ffffff;
    }

    .btn-submit:hover {
        background-color: var(--dark-orange);
    }

    /* Alerts */
    .alert {
        padding: 12px 16px;
        border-radius: 6px;
        font-size: 14px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background-color: #dcfce7;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }

    .alert-danger {
        background-color: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    </style>
</head>
<body>

<div class="edit-card">
    <div class="card-header">
        <h2><i class="fa-solid fa-pen-to-square"></i> Edit Dispatch Quantity</h2>
        <a href="../dispatchers.php" style="color: white; text-decoration: none;"><i class="fa-solid fa-xmark fa-lg"></i></a>
    </div>

    <div class="card-body">
        <?= $message ?>

        <?php if ($item): 
            $price = $item['price_at_time'] ?? $item['wholesale_price'];
            $total = $price * $item['qty_taken'];
        ?>
            <form method="POST">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">

                <!-- Summary Meta -->
                <div class="info-group">
                    <div class="info-row">
                        <span class="info-label">Product Name:</span>
                        <span class="info-value"><?= htmlspecialchars($item['product_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Unit Price:</span>
                        <span class="info-value">₱<span id="unitPrice"><?= number_format($price, 2) ?></span></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Available Shelf Stock:</span>
                        <span class="info-value" style="color: #2563eb;"><?= $item['stock_on_shelf'] ?> pcs</span>
                    </div>
                </div>

                <!-- Input Quantity -->
                <div class="form-group">
                    <label for="qty_taken">Quantity Taken:</label>
                    <input type="number" 
                           name="qty_taken" 
                           id="qty_taken" 
                           class="form-control" 
                           value="<?= $item['qty_taken'] ?>" 
                           min="0" 
                           required 
                           oninput="calculateTotal()">
                </div>

                <!-- Total Summary display -->
                <div class="info-group" style="background: #fff7ed; border-color: #ffedd5;">
                    <div class="info-row">
                        <span class="info-label" style="color: #c2410c; font-weight: 600;">Adjusted Subtotal:</span>
                        <span class="info-value" style="color: #c2410c; font-weight: 700; font-size: 16px;">
                            ₱<span id="subtotalDisplay"><?= number_format($total, 2) ?></span>
                        </span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="btn-container">
                    <a href="../dispatchers.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" name="update_item" class="btn btn-submit">Save Changes</button>
                </div>
            </form>
        <?php else: ?>
            <p style="text-align: center; color: #64748b; margin-bottom: 20px;">Item not found or has been deleted.</p>
            <div class="btn-container">
                <a href="../dispatchers.php" class="btn btn-cancel">Back to Dispatchers</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function calculateTotal() {
    const rawPrice = document.getElementById('unitPrice').innerText.replace(/,/g, '');
    const price = parseFloat(rawPrice) || 0;
    const qty = parseInt(document.getElementById('qty_taken').value) || 0;
    
    const total = price * Math.max(0, qty);
    document.getElementById('subtotalDisplay').innerText = total.toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>

</body>
</html>
