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
            (at.received_amount / NULLIF(at.qty_sold, 0)) AS unit_price, 
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
            (ro.subtotal / NULLIF(ro.qty, 0)) AS unit_price, 
            ro.subtotal AS total, 
            ro.order_date AS sale_date,
            'Retail' AS sale_type
        FROM retail_orders ro
        JOIN products p ON ro.product_id = p.id)
        
        ORDER BY sale_date DESC";
        
    $stmt = $pdo->query($query);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($sales) {
        $grand_total = array_sum(array_column($sales, 'total'));
    } else {
        $grand_total = 0;
        $sales = []; 
    }

} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    die("A system error occurred. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/sales.css">
    
    <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
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
                <a href="dispatchers.php" class="nav-item" data-title="Dispatchers">
                    <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <span>Dispatchers</span>
                </a>
                <a href="retailer.php" class="nav-item" data-title="Retailer">
                    <div class="icon"><i class="fa-solid fa-shop"></i></div>
                    <span>Retailer</span>
                </a>
                <a href="audit_trail.php" class="nav-item " data-title="Audit Trail">
                    <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <span>Audit Trail</span>
                </a>
                <a href="sales.php" class="nav-item active" data-title="Sales History">
                    <div class="icon"><i class="fa-solid fa-coins"></i></div>
                    <span>Sales History</span>
                </a>
                <a href="setting.php" class="nav-item " data-title="Settings">
                    <div class="icon"><i class="fa-solid fa-gears"></i></div>
                    <span>Settings</span>
                </a>
            </nav>
            
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
            name: "Sales_Report_<?= date('Y-m-d') ?>.xlsx",
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