<?php
require_once "../auth/conn.php";
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$id = $_GET['id'] ?? null;
$order = null;
$retail_price = 0; 

if ($id) {
    $stmt = $pdo->prepare("SELECT ro.*, p.product_name, p.retail_price 
            FROM retail_orders ro 
            JOIN products p ON ro.product_id = p.id 
            WHERE ro.id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if ($order) {
        $retail_price = (float)$order['retail_price'];
    }
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order'])) {
    $order_id = $_POST['id'];
    $new_qty = (int)$_POST['qty'];
    $order_date = $_POST['order_date'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT ro.qty, ro.product_id, p.retail_price 
                FROM retail_orders ro 
                JOIN products p ON ro.product_id = p.id 
                WHERE ro.id = ? FOR UPDATE");
        $stmt->execute([$order_id]);
        $current_order = $stmt->fetch();

        if ($current_order) {
            $old_qty = (int)$current_order['qty'];
            $product_id = $current_order['product_id'];
            $retail_price = (float)$current_order['retail_price']; 
            $new_subtotal = $new_qty * $retail_price;
            $diff = $new_qty - $old_qty;

            $updInventory = $pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $updInventory->execute([$diff, $product_id]);

            $sql = "UPDATE retail_orders 
                    SET qty = :qty, subtotal = :sub, order_date = :odate 
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'qty'   => $new_qty,
                'sub'   => $new_subtotal,
                'odate' => $order_date,
                'id'    => $order_id
            ]);

            $pdo->commit();
            $message = "<div class='alert alert-success shadow-sm'><i class='fa-solid fa-circle-check me-2'></i>Order and Inventory updated successfully!</div>";

            $order['qty'] = $new_qty;
            $order['subtotal'] = $new_subtotal;
            $order['order_date'] = $order_date;
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = "<div class='alert alert-danger'><i class='fa-solid fa-circle-exclamation me-2'></i>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Retail Order</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .btn-orange { background-color: #ff9800; color: white; border: none; transition: 0.3s; }
        .btn-orange:hover { background-color: #e68a00; color: white; transform: translateY(-1px); }
        .card { border: none; border-radius: 12px; }
        .card-header { border-bottom: 1px solid #eee; }
    </style>
</head>
<body class="bg-light pt-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <?= $message; ?>
            
            <?php if ($order): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white py-3">
                    <span class="fw-bold text-uppercase text-secondary" style="letter-spacing: 1px;">Edit Order #<?= $order['id'] ?></span>
                    <br>
                    <small class="text-primary fw-bold"><?= htmlspecialchars($order['product_name'] ?? 'Product') ?></small>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?= $order['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <input type="number" id="qty" name="qty" class="form-control form-control-lg" 
                                   value="<?= $order['qty'] ?>" 
                                   data-price="<?= $retail_price ?>" min="1" required>
                            <div class="form-text">Unit Price: <strong>₱<?= number_format($retail_price, 2) ?></strong></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Subtotal</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white">₱</span>
                                <input type="number" step="0.01" id="subtotal" name="subtotal" 
                                       class="form-control bg-light fw-bold text-success" value="<?= $order['subtotal'] ?>" readonly>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Order Date</label>
                            <input type="date" name="order_date" class="form-control" value="<?= date('Y-m-d', strtotime($order['order_date'])) ?>" required>
                        </div>

                        <button type="submit" name="update_order" class="btn btn-orange w-100 fw-bold py-2 shadow-sm">
                            <i class="fa-solid fa-save me-2"></i>Save Changes
                        </button>
                        
                        <a href="javascript:history.back()" class="btn btn-outline-secondary w-100 mt-2 py-2">
                            Cancel
                        </a>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-warning text-center">Order not found. <a href="retail_history.php">Go back</a></div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    const qtyInput = document.getElementById('qty');
    const subtotalInput = document.getElementById('subtotal');

    qtyInput.addEventListener('input', function() {
        const price = parseFloat(this.getAttribute('data-price')) || 0;
        const qty = parseFloat(this.value) || 0;
        const total = price * qty;
        subtotalInput.value = total.toFixed(2);
    });
</script>

</body>
</html>