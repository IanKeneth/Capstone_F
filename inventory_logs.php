<?php
session_start();
require_once "auth/conn.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$whereClauses = [];
$params = [];

if ($filter === 'In') {
    $whereClauses[] = "il.action = 'Added'";
    } elseif ($filter === 'Out') {
        $whereClauses[] = "il.action = 'Removed'";
    } elseif ($filter === 'Retail') { 

        $whereClauses[] = "il.notes LIKE :retail_note";
        $params[':retail_note'] = '%Retail%';
    } elseif ($filter === 'Wholesale') { 
        $whereClauses[] = "(il.notes LIKE :wholesale_note OR il.notes LIKE :return_note)";
        $params[':wholesale_note'] = '%Wholesale%';
        $params[':return_note'] = '%Returned%';
    }

    if (!empty($search)) {
        $whereClauses[] = "(p.product_name LIKE :search OR il.notes LIKE :search OR il.admin_name LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

$whereSQL = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

$query = "SELECT il.*, p.product_name, p.variation
        FROM inventory_logs il 
        JOIN products p ON il.product_id = p.id
        $whereSQL
        ORDER BY il.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$totalIn = $pdo->query("SELECT SUM(quantity_change) FROM inventory_logs WHERE action = 'Added'")->fetchColumn() ?? 0;
$totalOut = $pdo->query("SELECT SUM(quantity_change) FROM inventory_logs WHERE action = 'Removed'")->fetchColumn() ?? 0;

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/log.css">
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
                <a href="inventory_logs.php" class="nav-item active" data-title="Inventory Logs">
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
                <h1 style="white-space: nowrap; margin-right: 20px;">Inventory Logs Overview</h1>
            </div>
        </header>
        
        <section class="report-container">
            <div class="summary-cards">
                <div class="s-card">
                    <i class="fa-solid fa-circle-arrow-down fa-2x" style="color:var(--success)"></i>
                    <div><small>Total Stock In</small><h3><?= number_format($totalIn) ?></h3></div>
                </div>
                <div class="s-card">
                    <i class="fa-solid fa-circle-arrow-up fa-2x" style="color:var(--danger)"></i>
                    <div><small>Total Stock Out</small><h3><?= number_format($totalOut) ?></h3></div>
                </div>
        </div>

        <div class="controls-row">
            <div class="filter-group" style="display:flex; gap:8px; flex-wrap: wrap;">
                <a href="?filter=All" class="filter-btn <?= $filter === 'All' ? 'active' : '' ?>">All</a>
                <a href="?filter=In" class="filter-btn <?= $filter === 'In' ? 'active' : '' ?>">In </a>
                <a href="?filter=Out" class="filter-btn <?= $filter === 'Out' ? 'active' : '' ?>">Out </a>
                <a href="?filter=Retail" class="filter-btn <?= $filter === 'Retail' ? 'active' : '' ?>">Retail </a>
                <a href="?filter=Wholesale" class="filter-btn <?= $filter === 'Wholesale' ? 'active' : '' ?>">Wholesale</a>
            </div>
            <form method="GET">
                <input type="text" name="search" placeholder="Search product or note..." value="<?= e($search) ?>" style="padding:10px; border-radius:8px; border:1px solid #ddd; width: 100%; max-width: 300px; box-sizing: border-box;">
            </form>
        </div>

            <div class="table-responsive">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Admin/Source</th>
                            <th>Product</th>
                            <th>Action Type</th>
                            <th>Movement</th>
                            <th>Notes</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
                            <td><i class="fa-solid fa-user-tag" style="color:#94a3b8"></i> <?= e($log['admin_name']) ?></td>
                            <td><b><?= e($log['product_name']) ?></b> <br><small><?= e($log['variation']) ?></small></td>
                            <td>
                                <?php if(strpos($log['notes'], 'Retail') !== false): ?>
                                    <span class="badge badge-retail">OUT</span>
                                <?php elseif(strpos($log['notes'], 'Remit') !== false): ?>
                                    <span class="badge badge-wholesale">WHOLESALE</span>
                                <?php else: ?>
                                    <span class="badge <?= $log['action'] == 'Added' ? 'badge-in' : 'badge-out' ?>">
                                        <?= $log['action'] == 'Added' ? 'IN' : 'OUT' ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:bold; color: <?= $log['action'] == 'Added' ? 'var(--success)' : 'var(--danger)' ?>;">
                                <?= $log['action'] == 'Added' ? '+' : '-' ?> <?= number_format($log['quantity_change']) ?>
                            </td>
                            <td style="color: #64748b; font-style: italic; font-size: 0.8rem;"><?= e($log['notes']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
    </section>
</main>

<script>
    // Combined duplicate toggle listeners into a clean execution block
    document.getElementById('sidebarToggle').addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
        sidebar.classList.toggle('collapsed');
    });
</script>
</body>
</html>