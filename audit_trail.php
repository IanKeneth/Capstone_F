<?php
session_start();
require_once "auth/conn.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: auth/login.php");
    exit();
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date   = isset($_GET['end_date']) ? $_GET['end_date'] : '';

$whereClauses = [];
$params = [];

if (!empty($start_date) && !empty($end_date)) {
    $whereClauses[] = "DATE(created_at) BETWEEN :start_date AND :end_date";
    $params[':start_date'] = $start_date;
    $params[':end_date']   = $end_date;
}

$whereSQL = !empty($whereClauses) ? " WHERE " . implode(" AND ", $whereClauses) : "";

// --- EXPORT TO CSV / EXCEL LOGIC ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $exportQuery = "SELECT created_at, worker_name, product_name, qty_taken, qty_sold, qty_returned, received_amount, status 
                    FROM audit_trail $whereSQL ORDER BY created_at DESC";

    $stmtExport = $pdo->prepare($exportQuery);
    $stmtExport->execute($params);
    $exportLogs = $stmtExport->fetchAll(PDO::FETCH_ASSOC);

    $filename = "audit_trail_report_" . date('Y-m-d_H-i') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel compatibility

    fputcsv($output, ['Date & Time', 'Worker Name', 'Product', 'Brought (Taken)', 'Sold', 'Returned', 'Amount Collected', 'Status']);

    foreach ($exportLogs as $row) {
        fputcsv($output, [
            date('M d, Y h:i A', strtotime($row['created_at'])),
            $row['worker_name'],
            $row['product_name'],
            $row['qty_taken'],
            $row['qty_sold'],
            $row['qty_returned'],
            $row['received_amount'],
            $row['status']
        ]);
    }

    fclose($output);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM audit_trail $whereSQL ORDER BY created_at DESC");
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching logs: " . $e->getMessage());
}

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/audit.css">
    
    <style>
        .header { display: flex; justify-content: space-between; align-items: center; }
        .report-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; }
        .report-modal.active { display: flex; }
        .modal-card { background: #fff; padding: 25px; border-radius: 12px; width: 100%; max-width: 450px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { margin: 0; color: #1e293b; }
        .close-btn { cursor: pointer; font-size: 18px; color: #64748b; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; color: #475569; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        .btn-gen { width: 100%; background: #f28c28; color: #fff; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; text-align: center; text-decoration: none; display: block; box-sizing: border-box; }
        .btn-gen:hover { background: #ea580c; }
        .btn-excel { background: #16a34a; }
        .btn-excel:hover { background: #15803d; }
        .btn-report-trigger { background: #1e293b; color: #fff; border: none; padding: 10px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; text-decoration: none; }
        .btn-report-trigger:hover { background: #0f172a; }

        @media print {
            body * { visibility: hidden; }
            #printable-report, #printable-report * { visibility: visible; }
            #printable-report { position: absolute; left: 0; top: 0; width: 100%; }
            .sidebar, .header, .btn-report-trigger, .report-modal { display: none !important; }
            .print-header-info { display: block !important; margin-bottom: 20px; text-align: center; }
        }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/logo.png" alt="Salescore Logo" class="sidebar-logo">
            </div>
            <nav style="flex-grow: 1;">
            <a href="index.php" class="nav-item" data-title="Dashboard">
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
            <a href="balance.php" class="nav-item " data-title="Worker Balances">
                <div class="icon"><i class="fa-solid fa-scale-unbalanced"></i></div>
                <span>Worker Balances</span>
            </a>
            <a href="retailer.php" class="nav-item" data-title="Retailer">
                <div class="icon"><i class="fa-solid fa-shop"></i></div>
                <span>Retailer</span>
            </a>
            <a href="audit_trail.php" class="nav-item active" data-title="Audit Trail">
                <div class="icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <span>Audit Trail</span>
            </a>
            <a href="sales.php" class="nav-item" data-title="Sales History">
                <div class="icon"><i class="fa-solid fa-coins"></i></div>
                <span>Sales History</span>
            </a>
            <a href="setting.php" class="nav-item" data-title="Settings">
                <div class="icon"><i class="fa-solid fa-gears"></i></div>
                <span>Settings</span>
            </a>
        </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1>Audit Trail</h1>
                </div>
                <div class="header-right">
                    <button class="btn-report-trigger" id="openModalBtn">
                        <i class="fa-solid fa-file-pdf"></i> Generate Report
                    </button>
                </div>
            </header>

            <div class="audit-table-card" id="printable-report" style="margin: 20px;">
                <div class="print-header-info" style="display:none;">
                    <h2>AUDIT TRAIL REPORT</h2>
                    <p>Period: <?= !empty($start_date) ? e($start_date) . ' to ' . e($end_date) : 'All Time History' ?></p>
                    <hr style="margin: 15px 0;">
                </div>
                <h3><i class="fa-solid fa-clock-rotate-left" style="color: #f28c28; margin-right: 10px;"></i> Audit Logs</h3>
                <table id="auditTable">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Worker Name</th>
                            <th>Product</th>
                            <th>Brought (Taken)</th>
                            <th>Sold</th>
                            <th>Returned</th>
                            <th>Amount Collected</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($logs) > 0): ?>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?></td>
                                <td><strong><?php echo e($log['worker_name']); ?></strong></td>
                                <td><?php echo e($log['product_name']); ?></td>
                                <td><?php echo $log['qty_taken']; ?></td>
                                <td class="text-success"><?php echo $log['qty_sold']; ?></td>
                                <td class="text-danger"><?php echo $log['qty_returned']; ?></td>
                                <td>₱<?php echo number_format($log['received_amount'], 2); ?></td>
                                <td><span class="status-badge"><?php echo e($log['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No audit logs found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Generate Report Modal -->
    <div class="report-modal" id="reportModal">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Generate Audit Trail Report</h3>
                <i class="fa-solid fa-xmark close-btn" id="closeModalBtn"></i>
            </div>
            <form method="GET" action="audit_trail.php">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= e($start_date ?: date('Y-m-01')) ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= e($end_date ?: date('Y-m-d')) ?>" required>
                </div>
                <button type="submit" name="print" value="1" class="btn-gen" style="background:#0f172a; margin-bottom: 10px;">
                    <i class="fa-solid fa-print"></i> Print / Export PDF
                </button>
                <button type="submit" name="export" value="csv" class="btn-gen btn-excel">
                    <i class="fa-solid fa-file-excel"></i> Export Filtered to Excel/CSV
                </button>
            </form>
        </div>
    </div>

    <script>
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });

        const reportModal = document.getElementById('reportModal');
        const openModalBtn = document.getElementById('openModalBtn');
        const closeModalBtn = document.getElementById('closeModalBtn');

        openModalBtn.addEventListener('click', () => reportModal.classList.add('active'));
        closeModalBtn.addEventListener('click', () => reportModal.classList.remove('active'));
        window.addEventListener('click', (e) => {
            if (e.target === reportModal) reportModal.classList.remove('active');
        });

        <?php if (isset($_GET['print']) && $_GET['print'] === '1'): ?>
            window.addEventListener('load', () => {
                window.print();
            });
        <?php endif; ?>
    </script>
</body>
</html>