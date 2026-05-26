<?php
session_start();
require_once "auth/conn.php";
require_once "function/dispatchController.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: auth/login.php");
    exit();
}

$dispatchManager = new DispatchController($pdo);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['submit_dispatch'])) {

            $dispatchManager->createBulkDispatch($_POST, $_SESSION['admin_name'] ?? 'Admin');
            header("Location: dispatchers.php?success=1"); 
            exit();
        }
        if (isset($_POST['add_single_item'])) {
            $dispatchManager->addSingleItem($_POST, $_SESSION['admin_name'] ?? 'Admin');
            header("Location: dispatchers.php?success=added"); 
            exit();
        }
    } catch (Exception $e) {
        header("Location: dispatchers.php?error=" . urlencode($e->getMessage())); 
        exit();
    }
}

$grouped_data = $dispatchManager->getActiveDispatches();
$all_products = $pdo->query("SELECT id, product_name, wholesale_price FROM products ORDER BY product_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dispatchers.css">

</head>
<body>

<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header">
                <img src="assets/img/logo.png" alt="Salescore Logo" class="sidebar-logo">
                
            </div>
            <nav style="flex-grow: 1;">
                <a href="index.php " class="nav-item " data-title="Dashboard">
                    <div class="icon"><i class="fa-solid fa-chart-line"></i></div>
                    <span>Dashboard</span>
                </a>
                <a href="inventory.php" class="nav-item" data-title="Inventory">
                    <div class="icon"><i class="fa-solid fa-boxes-packing"></i></div>
                    <span>Inventory</span>
                </a>
                <a href="inventory_logs.php" class="nav-item" data-title="Inventory Logs">
                    <div class="icon"><i class="fa-solid fa-route"></i></div>
                    <span>Inventory Logs</span>
                </a>
                <a href="dispatchers.php" class="nav-item active" data-title="Dispatchers">
                    <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <span>Dispatchers</span>
                </a>
                <a href="retailer.php" class="nav-item" data-title="Retailer">
                    <div class="icon"><i class="fa-solid fa-shop"></i></div>
                    <span>Retailer</span>
                </a>
                <a href="audit_trail.php" class="nav-item" data-title="Audit Trail">
                    <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <span>Audit Trail</span>
                </a>
                <a href="sales.php" class="nav-item" data-title="Sales History">
                    <div class="icon"><i class="fa-solid fa-coins"></i></div>
                    <span>Sales History</span>
                </a>
                <a href="setting.php" class="nav-item " data-title="Settings">
                    <div class="icon"><i class="fa-solid fa-gears"></i></div>
                    <span>Settings</span>
                </a>
            </nav>
            </nav>
    </aside>

    <main class="main-content">
        <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="white-space: nowrap; margin-right: 20px;">Dispatch Overview</h1>
                </div>
        </header>
        <section class="content-area" style="padding: 20px;">
            <button class="btn-open" onclick="toggleModal('dispatchModal', true)" style="background: var(--primary); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; margin-bottom: 20px;">
                <i class="fa-solid fa-plus"></i> Record New Dispatch
            </button>
            <div class="search-container">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="inventorySearch" placeholder="Search worker name...">
            </div>
            
            <?php foreach($grouped_data as $sid => $data): ?>
            <div class="worker-group">
                <div class="worker-header">
                    <div>
                        <span style="color: var(--text); font-weight: 700;">Worker: <?= htmlspecialchars($data['info']['name']) ?></span>
                        <span class="status-badge" style="margin-left:10px;">Active</span>
                    </div>
                    <div>
                        <button onclick='openAddProductModal(<?= $sid ?>, <?= json_encode(array_column($data['items'], "product_name")) ?>)' class="action-pill" style="background: #22c55e; color: white;">+ PRODUCT</button>
                        <a href="function/afternoon_remit.php?id=<?= $sid ?>" class="action-pill" style="background: var(--primary); color:white;">REMIT</a>
                    </div>
                </div>
                <table class="table-responsive-wrapper">
                    <thead class="main-table">
                        <tr>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th>Stock</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grand_total = 0;
                        foreach($data['items'] as $item): 
                            $subtotal = $item['price_at_time'] * $item['qty_taken'];
                            $grand_total += $subtotal;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                            <td>₱<?= number_format($item['price_at_time'], 2) ?></td>
                            <td><?= $item['qty_taken'] ?></td>
                            <td>₱<?= number_format($subtotal, 2) ?></td>
                            <td style="color:#94a3b8;"><?= $item['inventory_qty'] ?></td>
                            <td style="text-align: center;">
                                <a href="function/delete_item.php?id=<?= $item['di_id'] ?>" onclick="return confirm('Remove product?')" style="color: #ef4444;"><i class="fa-solid fa-trash-can"></i></a>
                                <a href="function/edit_despatch.php?id=<?= $item['di_id'] ?>" class="fa-solid fa-pencil" style="color:#666; margin-right:10px; cursor:pointer;"></a>
                            </td>
                            
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Total Amount Accountable:</td>
                            <td colspan="3">₱<?= number_format($grand_total, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        </section>
    </main>
</div>

<div id="dispatchModal" class="modal-overlay" style="margin: auto;">
    <div class="modal-content">
        <form method="POST">
            <div class="card-header">
                <h2 style="margin:0;">Morning Pickup</h2>
                <span onclick="toggleModal('dispatchModal', false)" style="cursor:pointer;">&times;</span>
            </div>
            <div class="form-body">
                <label style="margin: 5px;">Worker Name:</label>
                <input type="text" name="worker_name" required style="width:70%; padding:10px; margin: 20px 0 20px 0; border: 1px solid #ddd; border-radius: 8px;">
                <input type="hidden" name="date_today" value="<?= date('Y-m-d') ?>">
                
                <div style="display:flex;  width:95%; justify-content:space-between; margin-bottom:10px;">
                    <label style="margin: 5px;">Products:</label>
                    <button type="button" onclick="addNewRow()">+ Add Row</button>
                </div>

                <table style="width:100%;" id="morningTable">
                    <tbody id="morningRows">
                        <tr>
                            <td>
                                <select name="product_ids[]" required style="border: 1px solid #ddd; border-radius: 8px; width:80%; margin: 5px; padding:5px;" onchange="updateDropdowns()">
                                    <option value="">-- Select Product --</option>
                                    <?php foreach($all_products as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['product_name']) ?> (₱<?= $p['wholesale_price'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="width:100px;"><input type="number" name="qtys[]" value="1" min="1" style=" border: 1px solid #ddd; border-radius: 8px; width:80%; padding:10px;"></td>
                            <td style="width:30px; text-align:right;"><i class="fa-solid fa-trash-can remove-icon" onclick="removeRow(this)"></i></td>
                        </tr>
                    </tbody>
                </table>
                <button type="submit" style="margin: 20px; width: 90%;" name="submit_dispatch" class="btn-submit">Submit Dispatch</button>
            </div>
        </form>
    </div>
</div>

<div id="addProductModal" class="modal-overlay">
    <div class="modal-content" style="max-width: 400px;">
        <form method="POST">
            <div class="card-header">
                <h2 style="margin:0;">Add Product</h2>
                <span onclick="toggleModal('addProductModal', false)" style="cursor:pointer;">&times;</span>
            </div>
            <div class="form-body">
                <input type="hidden" name="session_id" id="modal_session_id">
                <label>Select New Product:</label>
                <select name="new_product_id" id="filteredSelect" required style="width:100%; padding:10px; margin-top:10px;">
                    <option value="">-- Choose Product --</option>
                    <?php foreach($all_products as $p): ?>
                        <option value="<?= $p['id'] ?>" data-pname="<?= $p['product_name'] ?>">
                            <?= htmlspecialchars($p['product_name']) ?> (₱<?= $p['wholesale_price'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <label style="display:block; margin-top:15px;">Quantity:</label>
                <input type="number" name="new_qty" value="1" min="1" required style="width:100%; padding:10px;">
                <button type="submit" name="add_single_item" class="btn-submit">Add to List</button>
            </div>
        </form>
    </div>
</div>
<script src="assets/api/despatch.js"></script>
</body>
</html>