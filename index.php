<?php
session_start();
require_once "auth/conn.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: auth/login.php");
    exit();
}

$today = date('Y-m-d');

$fiveMonthsAgo = date('Y-m-01', strtotime("-4 month"));

$query = "SELECT m, SUM(revenue) as total_rev
        FROM (
            SELECT DATE_FORMAT(date_today, '%Y-%m') as m, total_collected as revenue 
            FROM dispatch_sessions
            WHERE status = 'Completed' AND date_today >= :start_date1
            UNION ALL
            SELECT DATE_FORMAT(order_date, '%Y-%m') as m, subtotal as revenue 
            FROM retail_orders
            WHERE order_date >= :start_date2
        ) as combined_sales
        GROUP BY m ORDER BY m ASC";

$stmt = $pdo->prepare($query);
$stmt->execute(['start_date1' => $fiveMonthsAgo, 'start_date2' => $fiveMonthsAgo]);
$dataMap = [];
while ($row = $stmt->fetch()) {
    $dataMap[$row['m']] = (float)$row['total_rev']; 
}

$months = [];
for ($i = 4; $i >= 0; $i--) { $months[] = date('Y-m', strtotime("-$i month")); }
$monthlyLabels = [];
$monthlyValues = [];
foreach ($months as $m) {
    $monthlyLabels[] = date('M', strtotime($m));
    $monthlyValues[] = $dataMap[$m] ?? 0;
}

$dailySalesQuery = "SELECT SUM(rev) FROM (
                        SELECT total_collected as rev FROM dispatch_sessions WHERE status='Completed' AND date_today = :today1 
                        UNION ALL 
                        SELECT subtotal FROM retail_orders WHERE order_date = :today2
                    ) as t";
$stmtDaily = $pdo->prepare($dailySalesQuery);
$stmtDaily->execute(['today1' => $today, 'today2' => $today]);
$dailySales = $stmtDaily->fetchColumn() ?: 0;

$activeProductCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 30")->fetchColumn();

$topCombinedQuery = "SELECT p.product_name, SUM(all_sales.total_qty) as total_sold
                    FROM (
                        SELECT product_id, SUM(qty) as total_qty 
                        FROM retail_orders 
                        GROUP BY product_id
                        UNION ALL
                        SELECT product_id, SUM(qty_sold) as total_qty 
                        FROM dispatch_items 
                        GROUP BY product_id
                    ) as all_sales
                    JOIN products p ON all_sales.product_id = p.id
                    GROUP BY p.id, p.product_name
                    ORDER BY total_sold DESC
                    LIMIT 5";

$topProductsRes = $pdo->query($topCombinedQuery)->fetchAll();
$topProductLabels = [];
$topProductValues = [];
foreach ($topProductsRes as $prod) {
    $topProductLabels[] = $prod['product_name'];
    $topProductValues[] = (int)$prod['total_sold'];
}

$combinedTopQuery = "SELECT p.product_name, SUM(combined.retail_qty) as r_qty, SUM(combined.wholesale_qty) as w_qty
                    FROM (
                        SELECT product_id, qty as retail_qty, 0 as wholesale_qty FROM retail_orders
                        UNION ALL
                        SELECT product_id, 0 as retail_qty, qty_sold as wholesale_qty FROM dispatch_items
                    ) as combined
                    JOIN products p ON combined.product_id = p.id
                    GROUP BY p.id, p.product_name
                    ORDER BY (SUM(combined.retail_qty) + SUM(combined.wholesale_qty)) DESC
                    LIMIT 5";
$combinedRes = $pdo->query($combinedTopQuery)->fetchAll();

$compLabels = [];
$compRetailValues = [];
$compWholesaleValues = [];
foreach($combinedRes as $row) {
    $compLabels[] = $row['product_name'];
    $compRetailValues[] = (int)$row['r_qty'];
    $compWholesaleValues[] = (int)$row['w_qty'];
}

$logisticsQuery = "SELECT ds.worker_name, SUM(di.qty_taken) as taken, SUM(di.qty_sold) as sold, SUM(di.qty_returned) as returned
                FROM dispatch_items di
                JOIN dispatch_sessions ds ON di.session_id = ds.id
                WHERE ds.status = 'Completed'
                GROUP BY ds.worker_name
                ORDER BY sold DESC LIMIT 5";
$logisticsRes = $pdo->query($logisticsQuery)->fetchAll();

$workerLabels = [];
$workerSoldValues = [];
$workerReturnValues = [];
foreach($logisticsRes as $row) {
    $workerLabels[] = $row['worker_name'];
    $workerSoldValues[] = (int)$row['sold'];
    $workerReturnValues[] = (int)$row['returned'];
}

$nextMonthStr = date('Y-m', strtotime("+1 month"));
$mlPredictionQuery = "SELECT predicted_revenue FROM ml_predictions WHERE target_period = :next_month LIMIT 1";
$stmt = $pdo->prepare($mlPredictionQuery);
$stmt->execute(['next_month' => $nextMonthStr]);
$forecast = $stmt->fetchColumn();

if ($forecast === false) {
    $forecast = count($monthlyValues) > 0 ? array_sum($monthlyValues) / count($monthlyValues) : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>
<body>
    <div class="container">
         <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/logo.png" alt="Salescore Logo" class="sidebar-logo">
                
            </div>
            <nav style="flex-grow: 1;">
                <a href="index.php " class="nav-item  active" data-title="Dashboard">
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
                <a href="sales.php" class="nav-item" data-title="Sales History">
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
                    <h1 style="white-space: nowrap; margin-right: 20px;">Dashboard Overview</h1>
                </div>
            </header>

            <div class="dashboard-grid">
                <div class="stat-card" style="border-left: 5px solid #36b9cc;">
                    <h3 style="color:#36b9cc; font-size: 0.8rem;">DAILY REVENUE</h3>
                    <div class="value">₱<?= number_format($dailySales, 2) ?></div>
                </div>
                <div class="stat-card" style="border-left: 5px solid #f28c28;">
                    <h3 style="color:#f28c28; font-size: 0.8rem;">MONTHLY REVENUE</h3>
                    <div class="value">₱<?= number_format(array_sum($monthlyValues), 2) ?></div>
                </div>
                <div class="stat-card" style="border-left: 5px solid #4e73df;">
                    <h3 style="color:#4e73df; font-size: 0.8rem;">YEARLY REVENUE</h3>
                    <div class="value">₱<?= number_format(array_sum($monthlyValues), 2) ?></div>
                </div>
                <div class="stat-card" style="border-left: 5px solid #3be752;">
                    <h3 style="color:#e74a3b; font-size: 0.8rem;">STOCK ALERTS</h3>
                    <div class="value"><?= $lowStockCount ?></div>
                </div>
                <div class="stat-card" style="border-left: 5px solid #1cc88a;">
                    <h3 style="color:#1cc88a; font-size: 0.8rem;">ACTIVE INVENTORY</h3>
                    <div class="value"><?= $activeProductCount ?></div>
                    <span class="sub-value" style="font-size: 0.8rem;">Products in stock</span>
                </div>
            </div>

            <div class="charts-container">
                <div class="content-box">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                        <h2>5-Month Revenue Trajectory</h2>
                        <div style="background:#e8f5e9; padding:10px; border-radius:8px; border: 1px solid #1cc88a;">
                            <small style="color:#1cc88a; font-weight:bold;"><i class="fa-solid fa-brain"></i>  Forecast:</small><br/>
                            <strong style="color:#1cc88a;">₱<?= number_format($forecast, 2) ?></strong>
                        </div>
                    </div>
                    <div style="height: 300px;"><canvas id="liveTrendChart"></canvas></div>
                </div>

                <div class="content-box">
                    <div style="margin-bottom: 15px;">
                        <h2>Top Demand Products</h2>
                    </div>
                    <div style="height: 300px;"><canvas id="topProductsChart"></canvas></div>
                </div>
            </div>

            <div class="analysis-container">
                <div class="content-box">
                    <div style="margin-bottom: 15px;">
                        <h2>Retail vs Wholesale</h2>
                    </div>
                    <div style="height: 300px;"><canvas id="channelCompChart"></canvas></div>
                </div>

                <div class="content-box">
                    <div style="margin-bottom: 15px;">
                        <h2>Dispatcher Performance & Return</h2>
                    </div>
                    <div style="height: 300px;"><canvas id="dispatcherLogisticsChart"></canvas></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const ctx = document.getElementById('liveTrendChart').getContext('2d');
        new Chart(ctx, {
            data: {
                labels: <?= json_encode($monthlyLabels) ?>,
                datasets: [{ type: 'bar', label: 'Revenue', data: <?= json_encode($monthlyValues) ?>, backgroundColor: 'rgba(78, 115, 223, 0.2)', borderColor: '#4e73df', borderWidth: 1 },
                    { type: 'line', label: 'Trend', data: <?= json_encode($monthlyValues) ?>, borderColor: '#f28c28', borderWidth: 3, tension: 0.4 }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });

        const ctxProd = document.getElementById('topProductsChart').getContext('2d');
        new Chart(ctxProd, {
            type: 'bar',
            data: {
                labels: <?= json_encode($topProductLabels) ?>,
                datasets: [{
                    label: 'Total Units Sold (Retail + Wholesale)',
                    data: <?= json_encode($topProductValues) ?>,
                    backgroundColor: 'rgba(28, 200, 138, 0.2)',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }]
            },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });

        const ctxChannel = document.getElementById('channelCompChart').getContext('2d');
        new Chart(ctxChannel, {
            type: 'bar',
            data: {
                labels: <?= json_encode($compLabels) ?>,
                datasets: [
                    { label: 'Retail Sales', data: <?= json_encode($compRetailValues) ?>, backgroundColor: '#4e73df' },
                    { label: 'Wholesale Sales', data: <?= json_encode($compWholesaleValues) ?>, backgroundColor: '#f6c23e' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { x: { stacked: true }, y: { stacked: true } } }
        });

        const ctxLogistics = document.getElementById('dispatcherLogisticsChart').getContext('2d');
        new Chart(ctxLogistics, {
            type: 'bar',
            data: {
                labels: <?= json_encode($workerLabels) ?>,
                datasets: [
                    { label: 'Qty Sold', data: <?= json_encode($workerSoldValues) ?>, backgroundColor: '#1cc88a' },
                    { label: 'Qty Returned', data: <?= json_encode($workerReturnValues) ?>, backgroundColor: '#e74a3b' }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } }
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