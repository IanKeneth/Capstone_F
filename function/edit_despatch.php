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
                $logAction = $difference > 0 ? "Increased Dispatch" : "Decreased Dispatch";
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

    // Refresh item details
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
        --bg-gray: #f0f0f0;
        --text-dark: #333333;
        --text-muted: #666666;
        --input-border: #cccccc;
    }

    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

    body {
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        min-height: 100dvh;
        background-color: var(--bg-gray);
        font-family: 'Segoe UI', Arial, sans-serif;
        overflow-x: hidden;
    }

    /* Main Container scaled to fit inside the circular logo */
    .login-container {
        position: relative;
        width: 92vw;
        max-width: 440px;
        aspect-ratio: 1 / 1; /* Keeps the container a perfect square/circle shape */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        /* Padding keeps the inputs within the wide center of the circle */
        padding: 60px 35px; 
        text-align: center;
        z-index: 1; 
    }

    /* Circular Background Image centered directly behind form content */
    .login-container::before {
        content: "";
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        background-image: url('../assets/img/sale.png');
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
        mix-blend-mode: multiply;
        opacity: 0.35;
        z-index: -1;
        pointer-events: none;
    }

    .login-form-wrapper {
        width: 100%;
        max-width: 320px; /* Constrains inputs to the inner ring of the circle */
    }

    .brand-section {
        margin-bottom: 15px;
    }

    .brand-name {
        font-size: 22px;
        font-weight: 700;
        color: var(--dark-orange);
        letter-spacing: 1.5px;
        text-transform: uppercase;
    }

    .input-group {
        position: relative;
        margin-bottom: 14px;
        display: flex;
        align-items: center;
        background: #ffffff;
        border-radius: 50px; 
        border: 1px solid var(--input-border);
        padding: 2px 16px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.04);
    }

    .input-group:focus-within {
        border-color: var(--primary-orange);
    }

    .input-icon {
        color: #333333;
        font-size: 14px;
        width: 20px;
        text-align: center;
    }

    .divider {
        height: 18px;
        width: 1px;
        background-color: #dddddd;
        margin: 0 10px;
    }

    .input-group input {
        width: 100%;
        border: none;
        background: transparent;
        padding: 10px 0;
        font-size: 14px;
        color: var(--text-dark);
        outline: none;
    }

    .input-group input::placeholder {
        color: #aaaaaa;
        text-transform: uppercase;
        font-size: 12px;
        letter-spacing: 0.5px;
    }

    .password-toggle {
        color: #888888;
        cursor: pointer;
        padding-left: 8px;
        font-size: 14px;
    }

    .forgot-link-wrapper {
        text-align: right;
        margin: -8px 10px 14px 0;
    }

    .forgot-password-link {
        color: var(--text-dark);
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
    }

    .btn-login {
        background-color: var(--primary-orange);
        color: #ffffff;
        border: none;
        padding: 12px;
        width: 100%;
        border-radius: 50px;
        font-size: 16px;
        font-weight: 600;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(242, 139, 48, 0.2);
    }

    /* Mobile adjustments */
    @media (max-width: 400px) {
        .login-container {
            width: 96vw;
            padding: 50px 25px;
        }

        .login-form-wrapper {
            max-width: 280px;
        }

        .brand-name {
            font-size: 18px;
        }

        .input-group input {
            font-size: 13px;
            padding: 8px 0;
        }

        .btn-login {
            padding: 10px;
            font-size: 14px;
        }
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
            <p style="text-align: center; color: #64748b;">Item not found or has been deleted.</p>
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