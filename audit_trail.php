<?php
session_start();
require_once "auth/conn.php";
if (!isset($_SESSION['admin_id'])) {
    header("Location: auth/login.php");
    exit();
}

try {
    $stmt = $pdo->query("SELECT * FROM audit_trail ORDER BY created_at DESC");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching logs: " . $e->getMessage());
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
    <style>
        .audit-table-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-top: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background-color: #f8f9fa;
            color: #333;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #eee;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            background: #e1f5fe;
            color: #0288d1;
        }
        .text-success { color: #2e7d32; font-weight: bold; }
        .text-danger { color: #d32f2f; font-weight: bold; }
        .sidebar-logo {
            width: 150px;
            height: 150px;
            object-fit: contain;
            border-radius: 50%;
            transition: all 0.3s ease;
            align-items: center;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            padding: 50px; 
            gap: 15px;
        }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/img/download.jpeg" alt="Salescore Logo" class="sidebar-logo">
                
            </div>

            
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item "><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="inventory_logs.php" class="nav-item "><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="dispatchers.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Dispatchers</span></a>
                <a href="audit_trail.php" class="nav-item active"><i class="fa-solid fa-clipboard-list"></i> <span>Audit Trail</span></a>
                <a href="retailer.php" class="nav-item "><i class="fa-solid fa-shop"></i> <span>Retailer</span></a>
                <a href="sales.php" class="nav-item "><i class="fa-solid fa-coins"></i> <span>Sales History</span></a>
                <a href="setting.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
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
                
            </header>

            <div class="audit-table-card" style="margin: 20px;">
                <h3><i class="fa-solid fa-clock-rotate-left" style="color: #f28c28; margin-right: 10px;"></i> Audit Logs</h3>
                <table>
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
                                <td><strong><?php echo htmlspecialchars($log['worker_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($log['product_name']); ?></td>
                                <td><?php echo $log['qty_taken']; ?></td>
                                <td class="text-success"><?php echo $log['qty_sold']; ?></td>
                                <td class="text-danger"><?php echo $log['qty_returned']; ?></td>
                                <td>₱<?php echo number_format($log['received_amount'], 2); ?></td>
                                <td><span class="status-badge"><?php echo $log['status']; ?></span></td>
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

    <script>
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');

    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
    });
    </script>
</body>
</html>