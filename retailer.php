<?php
require_once "auth/conn.php";
session_start();
try {

    $stmt_p = $pdo->query("SELECT id, product_name, retail_price FROM products ORDER BY product_name ASC");
    $all_products = $stmt_p->fetchAll();
    $stmt_o = $pdo->query("
        SELECT ro.*, p.product_name, p.retail_price as unit_price
        FROM retail_orders ro 
        JOIN products p ON ro.product_id = p.id 
        ORDER BY ro.id DESC
    ");
    $orders = $stmt_o->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: none; justify-content: center; align-items: center; z-index: 2000; }
        
        .retail-card { background: white; margin: 20px; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #eee; }
        .retail-header { background: #f28c28; color: white; padding: 15px 25px; display: flex; align-items: center; gap: 15px; }
        .retail-header h2 { margin: 0; font-size: 1.3rem; font-weight: 500; flex-grow: 1; }
        
        .table-container { padding: 25px; background: #fffdf9; }
        .main-table { width: 100%; border-collapse: collapse; background: white; border: 1px solid #f0f0f0; }
        .main-table th { background: white; color: #888; font-size: 11px; padding: 12px; border-bottom: 2px solid #f5f5f5; text-align: center; letter-spacing: 0.5px; }
        .main-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; text-align: center; color: #444; font-size: 14px; }
        
        .btn-add-order { background: #f28c28; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: bold; float: right; margin: 20px; box-shadow: 0 4px 6px rgba(242, 140, 40, 0.2); }

        .order-modal { 
            background: #ffffff; 
            width: 360px; 
            border-radius: 20px; 
            overflow: hidden; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: none;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }

        .order-modal-header { 
            background: #fffdf9; 
            padding: 20px; 
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b; 
            font-weight: 700; 
            font-size: 1.1rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }

        .order-form { padding: 25px; }

        .order-form label { 
            font-size: 0.8rem; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            color: #64748b; 
            margin-bottom: 8px; 
        }

        .order-form input, .order-form select { 
            width: 100%; 
            padding: 12px; 
            border: 1.5px solid #e2e8f0; 
            border-radius: 10px; 
            margin-bottom: 20px; 
            font-size: 14px; 
            transition: border-color 0.2s;
        }

        .order-form input:focus, .order-form select:focus { 
            border-color: #f28c28; 
            outline: none; 
        }
        .subtotal-container {
            background: #fff7ed;
            border-radius: 12px;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px dashed #fdba74;
        }
        .subtotal-label { font-size: 0.75rem; color: #ea580c; font-weight: 600; margin-bottom: 5px; }
        .subtotal-value { font-size: 1.5rem; color: #f28c28; font-weight: 800; }

        .btn-submit-order { 
            width: 100%; 
            background: #f28c28; 
            color: white; 
            border: none; 
            padding: 14px; 
            border-radius: 12px; 
            font-weight: 700; 
            cursor: pointer; 
            transition: background 0.2s;
        }
        .btn-submit-order:hover { background: #ea580c; }
        .btn-add-order{margin-right: 80%;}
        .btn-add-order:hover, .btn-submit-order:hover { background: #e67e22; }
    </style>
</head>
<body>

<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Retailer</span></div>
       
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item "><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="inventory_logs.php" class="nav-item "><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="dispatchers.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Dispatchers</span></a>
                <a href="audit_trail.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Audit Trail</span></a>
                <a href="retailer.php" class="nav-item active"><i class="fa-solid fa-shop"></i> <span>Retailer</span></a>
                <a href="sales.php" class="nav-item "><i class="fa-solid fa-coins"></i> <span>Sales History</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
    </aside>

    <main class="main-content">
        <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="white-space: nowrap; margin-right: 20px;">Retailer Orders</h1>
                </div>
            </header>
        <section class="content-area">
        <?php if (isset($_GET['status'])): ?>
            <?php if ($_GET['status'] === 'success'): ?>
                <div style="background: #dcfce7; color: #15803d; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                    Order saved and stock updated successfully!
                </div>
            <?php elseif ($_GET['status'] === 'error'): ?>
                <div style="background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                    Error: <?= htmlspecialchars($_GET['msg']) ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
            
            <div class="retail-card">
                <button class="btn-add-order" onclick="toggleModal('orderModal', true)">+ Add Retail Orders</button>
                <div class="table-container">
                    <table class="main-table">

                        <thead>
                            <tr>
                                <th>ORDER ID</th>
                                <th>PRODUCT NAME</th>
                                <th>RETAIL PRICE</th>
                                <th>QTY</th>
                                <th>SUBTOTAL</th>
                                <th>ORDER DATE</th>
                                <th>ACTION</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach($orders as $row): ?>
                            <tr>
                                <td>#<?= str_pad($row['id'], 3, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td>₱ <?= number_format($row['unit_price'], 2) ?></td>
                                <td><?= $row['qty'] ?> pcs</td>
                                <td>₱ <?= number_format($row['subtotal'], 2) ?></td>
                                <td><?= date('m-d-Y', strtotime($row['order_date'])) ?></td>
                                <td>
                                    <a href="function/edit_retail.php?id=<?= $row['id'] ?>">
                                        <i class="fa-solid fa-pencil" style="color:#666; margin-right:10px; cursor:pointer;"></i>
                                    </a>
                                    <i class="fa-solid fa-trash-can" style="color:#e74c3c; cursor:pointer;"></i>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <!-- Filler rows to match snapshot style -->
                            <?php for($i=0; $i < (5 - count($orders)); $i++): ?>
                                <tr style="height:48px;"><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                            <?php endfor; ?>
                        </tbody>

                    </table>
                </div>
            </div>
        </section>
    </main>
</div>

<div id="orderModal" class="modal-overlay">
    <div class="order-modal">
        <div class="order-modal-header">
            <span>ORDER ITEMS</span>
            <span onclick="toggleModal('orderModal', false)" style="cursor:pointer;">&times;</span>
        </div>
        <form class="order-form" method="POST" action="function/save_retail.php">
            <label>Date Today:</label>
            <input type="date" name="order_date" value="<?= date('Y-m-d') ?>" required>

            <label>Product Name:</label>
            <select name="product_id" id="prodSelect" onchange="calc()" required>
                <option value="" data-price="0">Select from Inventory...</option>
                <?php foreach($all_products as $p): ?>
                    <option value="<?= $p['id'] ?>" data-price="<?= $p['retail_price'] ?>">
                        <?= htmlspecialchars($p['product_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div style="display: flex; gap: 15px; width: 100%; margin-bottom: 15px;">
            <div style="flex: 1;">
                <label>Retail Price</label>
                <input type="text" id="viewPrice" value="40.00" readonly 
                style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; background: #f9f9f9; color: #777; text-align: center; box-sizing: border-box;">
            </div>
            
            <div style="flex: 1;">
                <label>Qty:</label>
                <input type="number" name="qty" id="viewQty" value="1" min="1" oninput="calc()" 
                style="width: 100%; padding: 12px; border: 1.5px solid #e2e8f0; border-radius: 10px; text-align: center; box-sizing: border-box;">
            </div>
        </div>

            <label>Subtotal:</label>
            <div class="subtotal-box" id="viewSub">₱ 0.00</div>
            <input type="hidden" name="subtotal" id="hiddenSub">

            <button type="submit" name="submit_retail" class="btn-submit-order">Submit Order</button>
        </form>
    </div>
</div>

<script>
    function toggleModal(id, show) { 
        document.getElementById(id).style.display = show ? 'flex' : 'none'; 
    }
    function toggleModal(id, show) { document.getElementById(id).style.display = show ? 'flex' : 'none'; }

    function calc() {
        const sel = document.getElementById('prodSelect');
        const price = parseFloat(sel.options[sel.selectedIndex].getAttribute('data-price')) || 0;
        const qty = parseInt(document.getElementById('viewQty').value) || 0;
        const total = price * qty;
        
        document.getElementById('viewPrice').value = price.toFixed(2);
        document.getElementById('viewSub').innerText = "₱ " + total.toLocaleString(undefined, {minimumFractionDigits: 2});
        document.getElementById('hiddenSub').value = total.toFixed(2);
    }
    document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('active');
    });
        document.getElementById('sidebarToggle').addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('collapsed');
    });
</script>

</body>
</html>