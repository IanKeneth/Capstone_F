<?php
session_start();
require_once "../auth/conn.php";

$admin_name = $_SESSION['admin_name'] ?? 'System';
$sid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$items = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settlement'])) {
    try {
        $pdo->beginTransaction();    
        $received_amount = (float)$_POST['received_amount'];
        
        $stmtW = $pdo->prepare("SELECT worker_name FROM dispatch_sessions WHERE id = ?");
        $stmtW->execute([$sid]);
        $worker_name = $stmtW->fetchColumn();

        if (isset($_POST['returns'])) {
            foreach ($_POST['returns'] as $r_item_id => $return_qty) {
                $return_qty = (int)$return_qty;
                $qty_taken  = (int)$_POST['qtys_taken'][$r_item_id];
                $pid        = (int)$_POST['product_ids'][$r_item_id];
                $p_name     = $_POST['product_names'][$r_item_id];
                $qty_sold   = $qty_taken - $return_qty;

                $stmtUpd = $pdo->prepare("UPDATE dispatch_items SET qty_returned = ?, qty_sold = ? WHERE id = ?");
                $stmtUpd->execute([$return_qty, $qty_sold, $r_item_id]);

                if ($return_qty > 0) {
                    $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?")
                        ->execute([$return_qty, $pid]);

                    $pdo->prepare("INSERT INTO inventory_logs (product_id, quantity_change, action, notes, admin_name) VALUES (?, ?, 'Added', ?, ?)")
                        ->execute([$pid, $return_qty, "Returned from Session #$sid", $admin_name]);
                }

                $audit = $pdo->prepare("INSERT INTO audit_trail (session_id, worker_name, product_id, product_name, qty_taken, qty_sold, qty_returned, received_amount, status) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $audit->execute([$sid, $worker_name, $pid, $p_name, $qty_taken, $qty_sold, $return_qty, $received_amount, 'Completed Remittance']);
            }
        }

        $closeSession = $pdo->prepare("UPDATE dispatch_sessions SET status = 'Completed', total_collected = ? WHERE id = ?");
        $closeSession->execute([$received_amount, $sid]);

        $pdo->commit();
        header("Location: ../dispatchers.php?remitted=1"); 
        exit();
    } catch (Exception $e) { 
        $pdo->rollBack(); 
        die("Error: " . $e->getMessage()); 
    }
}

if ($sid > 0) {
    $stmt = $pdo->prepare("SELECT di.id AS item_id, di.product_id AS p_id, 
        COALESCE(di.price_at_time, p.wholesale_price) AS price_at_time, 
        p.product_name, di.qty_taken
        FROM dispatch_items di
        JOIN products p ON di.product_id = p.id
        WHERE di.session_id = ?"); 
    $stmt->execute([$sid]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Afternoon Remittance</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background-color: #f9f9f9; padding: 20px; }

        .container { 
            background: #ffffff; 
            padding: 30px; 
            border-radius: 16px; 
            max-width: 900px; 
            margin: 20px auto; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .header { 
            color: #f28c28; 
            font-weight: 800; 
            font-size: 24px; 
            margin-bottom: 30px; 
            padding-bottom: 15px; 
            border-bottom: 2px solid #fff7ed; 
        }

        .product-row { 
            display: grid; 
            grid-template-columns: 2fr 1fr 1fr 1.5fr 1.5fr; 
            align-items: center; 
            gap: 15px; 
            padding: 16px; 
            background: #fffcf8; 
            border-radius: 10px; 
            border: 1px solid #ffe4c4; 
            margin-bottom: 12px; 
        }

        .input-style { 
            border: 1px solid #e2e8f0; 
            padding: 10px; 
            border-radius: 8px; 
            width: 100%; 
            box-sizing: border-box; 
            text-align: center; 
        }

        .total-section { 
            margin-top: 30px; 
            padding: 20px; 
            background: #f8fafc; 
            border-radius: 12px; 
            border: 1px solid #e2e8f0; 
        }

        .total-line { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 15px; 
        }

        .btn-submit { 
            margin:15px;
            background: #f28c28; 
            color: white; 
            border: none; 
            padding: 10px 30px; 
            border-radius: 10px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 500; 
            width: 200px; 
            transition: 0.2s; 
        }

        .btn-submit:hover { 
            background: #ea580c; 
        }
        .cancel {
            background-color: #f28c28;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 700;
            width: 100px;
            text-align: center;
            display: inline-block;
            transition: 0.2s;
            margin-left: 20px;
            margin: 0;
            text-decoration: none;
        }

        .return-label {
            font-size: 10px; 
            font-weight: 700; 
            color: #ea580c; 
            text-transform: uppercase; 
            margin-bottom: 4px; 
            display: block;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">TOTAL REMITTANCE SUMMARY (Session #<?= $sid ?>)</div>
    <form method="POST">
        <?php foreach ($items as $item): 
            $subtotal = $item['price_at_time'] * $item['qty_taken'];
        ?>
            <div class="product-row" data-price="<?= $item['price_at_time'] ?>" data-taken="<?= $item['qty_taken'] ?>">
                <div style="min-width: 200px; font-weight:600;"><?= htmlspecialchars($item['product_name']) ?></div>
                <div style="width: 80px;">₱<?= number_format($item['price_at_time'], 2) ?></div>
                <div class="input-style" style="width: 70px;"><?= $item['qty_taken'] ?> pcs</div>
                <div style="text-align: center;">
                    <label style="font-size: 10px; display: block; color: #f28c28; font-weight: bold;">RETURN ITEMS</label>
                    <input type="number" name="returns[<?= $item['item_id'] ?>]" required class="input-style return-qty" style="width: 80px;" value="0" min="0" max="<?= $item['qty_taken'] ?>">
                </div>
                <div style="margin-left: auto; font-weight: bold;">Settlement: ₱<span class="row-settlement"><?= number_format($subtotal, 2) ?></span></div>
                
                <input type="hidden" name="product_ids[<?= $item['item_id'] ?>]" value="<?= $item['p_id'] ?>">
                <input type="hidden" name="product_names[<?= $item['item_id'] ?>]" value="<?= $item['product_name'] ?>">
                <input type="hidden" name="qtys_taken[<?= $item['item_id'] ?>]" value="<?= $item['qty_taken'] ?>">

            </div>
        <?php endforeach; ?>

        <div class="total-section">
            <div style="font-size: 18px; margin-bottom: 10px;">
                <strong>Total Amount Due:</strong> <span class="highlight">₱<span id="grand_total">0.00</span></span>
            </div>
            <div style="margin-bottom: 10px;">
                <strong>Received Cash:</strong> ₱<input type="number" name="received_amount" id="received_amt" class="input-style" step="0.01" style="width: 150px;" required>
            </div>
            <div><strong>Balance:</strong> <span id="balance_display">0.00</span></div>
        </div>
        <button type="submit" name="save_settlement" class="btn-submit">Complete Remittance</button>
        <a href="javascript:history.back()" class="cancel">Cancel</a>
    </form>
</div>

<script>
    const rows = document.querySelectorAll('.product-row');
    const receivedInput = document.getElementById('received_amt');
    const grandTotalDisplay = document.getElementById('grand_total');
    const balanceDisplay = document.getElementById('balance_display');

    function runCalculations() {
        let totalSettlement = 0;
        rows.forEach(row => {
            const price = parseFloat(row.dataset.price) || 0;
            const taken = parseInt(row.dataset.taken) || 0;
            const returns = parseInt(row.querySelector('.return-qty').value) || 0;
            const settlement = price * (taken - returns);
            row.querySelector('.row-settlement').innerText = settlement.toLocaleString(undefined, {minimumFractionDigits: 2});
            totalSettlement += settlement;
        });
        grandTotalDisplay.innerText = totalSettlement.toLocaleString(undefined, {minimumFractionDigits: 2});
        
        if (!receivedInput.dataset.manual) { 
            receivedInput.value = totalSettlement.toFixed(2); 
        }
        
        let balance = parseFloat(receivedInput.value) - totalSettlement;
        balanceDisplay.innerText = balance.toFixed(2);
    }

    document.addEventListener('input', (e) => {
        if (e.target.classList.contains('return-qty')) runCalculations();
    });

    receivedInput.addEventListener('input', () => { 
        receivedInput.dataset.manual = "true"; 
        runCalculations(); 
    });

    runCalculations();
</script>
</body>
</html>