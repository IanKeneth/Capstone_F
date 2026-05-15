<?php
session_start();
require_once "auth/conn.php"; 

$today = date('Y-m-d');

$query = "SELECT DATE_FORMAT(date, '%Y-%m') as m, SUM(revenue) as total_rev
        FROM (SELECT date_today as date, total_collected as revenue FROM dispatch_sessions WHERE status = 'Completed'
            UNION ALL
            SELECT order_date as date, subtotal as revenue FROM retail_orders) as combined_sales
        GROUP BY m ORDER BY m ASC LIMIT 5";

$res = $pdo->query($query);
$dataMap = [];
while ($row = $res->fetch()) { $dataMap[$row['m']] = (float)$row['total_rev']; }

$months = [];
for ($i = 4; $i >= 0; $i--) { $months[] = date('Y-m', strtotime("-$i month")); }
$monthlyLabels = [];
$monthlyValues = [];
foreach ($months as $m) {
    $monthlyLabels[] = date('M', strtotime($m));
    $monthlyValues[] = $dataMap[$m] ?? 0;
}

$dailySales = $pdo->query("SELECT SUM(rev) FROM (SELECT total_collected as rev FROM dispatch_sessions WHERE status='Completed' AND date_today='$today' UNION ALL SELECT subtotal FROM retail_orders WHERE order_date='$today') as t")->fetchColumn() ?: 0;
$activeProductCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM products WHERE quantity <= 5")->fetchColumn();

function getForecast(array $data): float {
    $n = count($data);
    if ($n < 2) return $n === 1 ? (float) end($data) : 0.0;
    $x = range(1, $n); $y = array_values($data);
    $m = ($n * array_sum(array_map(fn($a, $b) => $a * $b, $x, $y)) - array_sum($x) * array_sum($y)) / ($n * array_sum(array_map(fn($a) => $a*$a, $x)) - pow(array_sum($x), 2));
    $b = (array_sum($y) - $m * array_sum($x)) / $n;

    return round($m * ($n + 1) + $b, 2);
}
$nextMonthForecast = getForecast($monthlyValues);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; padding: 20px; }
        .stat-card { padding: 20px; background: white; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .value { font-size: 1.5rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
         <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>WMS Admin</span></div>
             
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item active"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item "><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="inventory_logs.php" class="nav-item "><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="dispatchers.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Dispatchers</span></a>
                <a href="audit_trail.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Audit Trail</span></a>
                <a href="retailer.php" class="nav-item "><i class="fa-solid fa-shop"></i> <span>Retailer</span></a>
                <a href="sales.php" class="nav-item "><i class="fa-solid fa-coins"></i> <span>Sales History</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
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
                <div class="stat-card" style="border-left: 5px solid #e74a3b;">
                    <h3 style="color:#e74a3b; font-size: 0.8rem;">STOCK ALERTS</h3>
                    <div class="value"><?= $lowStockCount ?></div>
                </div>
                <div class="stat-card" style="border-left: 5px solid #1cc88a;">
                    <h3 style="color:#1cc88a; font-size: 0.8rem;">ACTIVE INVENTORY</h3>
                    <div class="value"><?= $activeProductCount ?></div>
                    <span class="sub-value" style="font-size: 0.8rem;">Products in stock</span>
                </div>
            </div>

            <div style="padding: 20px;">
                <div class="content-box" style="background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 15px;">
                        <h2>5-Month Revenue Trajectory</h2>
                        <div style="background:#e8f5e9; padding:10px; border-radius:8px;">
                            <small>Predicted Next Month:</small>
                            <strong style="color:#1cc88a;">₱<?= number_format($nextMonthForecast, 2) ?></strong>
                        </div>
                    </div>
                    <div style="height: 300px;"><canvas id="liveTrendChart"></canvas></div>
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
           document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('active');
        });
         document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        exportBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            exportMenu.style.display = exportMenu.style.display === 'block' ? 'none' : 'block';
        });

        document.addEventListener('click', () => {
            exportMenu.style.display = 'none';
        });
    </script>
</body>
</html>