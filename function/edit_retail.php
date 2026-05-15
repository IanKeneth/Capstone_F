<?php
require_once "../auth/conn.php";
session_start();

$id = $_GET['id'] ?? null;
$order = null;
$actual_unit_price = 0;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM retail_orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if ($order) {
        $p_stmt = $pdo->prepare("SELECT retail_price FROM products WHERE id = ?");
        $p_stmt->execute([$order['product_id']]);
        $product = $p_stmt->fetch();
        $retail_price = $product['retail_price'] ?? 0;
    }
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order'])) {
    try {
        $sql = "UPDATE retail_orders 
                SET qty = :qty, subtotal = :sub, order_date = :odate 
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'qty'   => $_POST['qty'],
            'sub'   => $_POST['subtotal'],
            'odate' => $_POST['order_date'],
            'id'    => $_POST['id']
        ]);
        $message = "<div class='alert alert-success'>Update successful!</div>";
        $order['qty'] = $_POST['qty'];
        $order['subtotal'] = $_POST['subtotal'];
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Edit Retail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-light pt-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <?= $message; ?>
            
            <?php if ($order): ?>
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-bold">Edit Order #<?= $order['id'] ?></div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="id" value="<?= $order['id'] ?>">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Quantity</label>
                            <!-- data-price is the TRUE price from the products table -->
                            <input type="number" id="qty" name="qty" class="form-control" 
                                value="<?= $order['qty'] ?>" 
                                data-price="<?= $retail_price ?>" required>
                            <small class="text-muted">Unit Price: $<?= number_format($retail_price, 2) ?></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Subtotal</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" id="subtotal" name="subtotal" 
                                       class="form-control" value="<?= $order['subtotal'] ?>" readonly>
                            </div 
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Order Date</label>
                            <input type="date" name="order_date" class="form-control" value="<?= $order['order_date'] ?>" required>
                        </div>

                        <button type="submit" name="update_order" class="btn btn-primary w-100">Save Changes</button>
                    </form>
                </div>
            </div>
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