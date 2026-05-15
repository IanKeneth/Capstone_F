<?php
session_start();
require_once "auth/conn.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

try {
    $query = "
        (SELECT 
            at.id AS sale_id, 
            at.product_name, 
            at.worker_name AS customer_name, 
            at.qty_sold AS qty, 
            (at.received_amount / at.qty_sold) AS unit_price, 
            at.received_amount AS total, 
            at.created_at AS sale_date,
            'Wholesale' AS sale_type
        FROM audit_trail at
        WHERE at.status = 'Completed Remittance')
        
        UNION ALL

        (SELECT 
            ro.id AS sale_id, 
            p.product_name, 
            'Walk-in Retail' AS customer_name, 
            ro.qty AS qty, 
            (ro.subtotal / ro.qty) AS unit_price, 
            ro.subtotal AS total, 
            ro.order_date AS sale_date,
            'Retail' AS sale_type
        FROM retail_orders ro
        JOIN products p ON ro.product_id = p.id)
        
        ORDER BY sale_date DESC";
        
    $stmt = $pdo->query($query);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $grand_total = array_sum(array_column($sales, 'total'));

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    
    <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    
    <style>
        .sales-card { background: white; border-radius: 12px; margin: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #eee; }
        .sales-header { background: #fffdf9; padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f0; }
        .total-badge { background: #f28c28; color: white; padding: 10px 20px; border-radius: 8px; font-weight: bold; }
        
        .sales-table { width: 100%; border-collapse: collapse; }
        .sales-table th { background: white; color: #888; font-size: 11px; padding: 15px; border-bottom: 2px solid #f28c28; text-align: left; text-transform: uppercase; }
        .sales-table td { padding: 15px; border-bottom: 1px solid #f9f9f9; font-size: 14px; color: #444; }
        
        .type-tag { padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; }
        .type-wholesale { background: #e3f2fd; color: #1976d2; }
        .type-retail { background: #f3e5f5; color: #7b1fa2; }
        
        .amount-text { font-weight: bold; color: #27ae60; }
        .action-btn { border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-boxes-stacked"></i> <span>Sales</span>
            </div>
        
                <nav style="flex-grow: 1;">
                    <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                    <a href="inventory.php" class="nav-item "><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                    <a href="inventory_logs.php" class="nav-item "><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                    <a href="dispatchers.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Dispatchers</span></a>
                    <a href="audit_trail.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Audit Trail</span></a>
                    <a href="retailer.php" class="nav-item "><i class="fa-solid fa-shop"></i> <span>Retailer</span></a>
                <a href="sales.php" class="nav-item active"><i class="fa-solid fa-coins"></i> <span>Financial Report</span></a>
                    <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
                </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
        <header class="header">
                    <div class="header-left">
                        <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                        <h1 style="white-space: nowrap; margin-right: 20px;">financial Overview</h1>
                    </div>
            </header>

            <section class="sales-card">
                <div class="sales-header">
                    <div style="display: flex; gap: 10px;">
                        <button class="action-btn" style="background:#2c3e50; color:white;" id="exportBtn">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </button>
                    
                    </div>
                </div>

                <table class="sales-table" id="salesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Product</th>
                            <th>Customer/Worker</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Subtotal</th>
                            <th>Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (!empty($sales)): ?>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($sale['sale_id']) ?></td>
                                <td>
                                    <span class="type-tag <?= $sale['sale_type'] == 'Wholesale' ? 'type-wholesale' : 'type-retail' ?>">
                                        <?= $sale['sale_type'] ?>
                                    </span>
                                </td>
                                <td><strong><?= htmlspecialchars($sale['product_name']) ?></strong></td>
                                <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                <td><?= number_format($sale['qty']) ?></td>
                                <td>₱<?= number_format($sale['unit_price'], 2) ?></td>
                                <td class="amount-text">₱<?= number_format($sale['total'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($sale['sale_date'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="8" style="text-align:center; padding:50px; color:#999;">No records found.</td></tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </section>
        </main>
    </div>

<script>
    document.getElementById('exportBtn').addEventListener('click', function() {
        TableToExcel.convert(document.getElementById("salesTable"), {
            name: "Master_Sales_<?= date('Y-m-d') ?>.xlsx",
            sheet: { name: "Revenue" }
        });
    });
     document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });
         document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
</script>

</body>
</html>