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
    <link rel="stylesheet" href="assets/css/audit.css">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

    <style>
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .report-dropdown {
            position: relative;
            display: inline-block;
        }
        .btn-report {
            background-color: #f28c28;
            color: white;
            padding: 10px 15px;
            font-size: 14px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background 0.3s ease;
        }
        .btn-report:hover {
            background-color: #d77a1e;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #ffffff;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 100;
            border-radius: 4px;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .dropdown-content a {
            color: #333;
            padding: 12px 16px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            transition: background 0.2s;
        }
        .dropdown-content a:hover {
            background-color: #f1f1f1;
        }
        .show {
            display: block;
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
                <a href="audit_trail.php" class="nav-item active" data-title="Audit Trail">
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
                    <button id="sidebarToggle" class="hamburger-btn">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <h1>Audit Trail</h1>
                </div>
                
                <div class="report-dropdown">
                    <button onclick="toggleDropdown()" class="btn-report">
                        <i class="fa-solid fa-file-export"></i> Generate Report <i class="fa-solid fa-caret-down"></i>
                    </button>
                    <div id="reportDropdown" class="dropdown-content">
                        <a href="#" onclick="exportToExcel()"><i class="fa-solid fa-file-excel" style="color: #217346;"></i> Excel (.xlsx)</a>
                        <a href="#" onclick="exportToCSV()"><i class="fa-solid fa-file-csv" style="color: #1d723a;"></i> CSV (.csv)</a>
                        <a href="#" onclick="exportToPDF()"><i class="fa-solid fa-file-pdf" style="color: #d9383a;"></i> PDF (.pdf)</a>
                    </div>
                </div>
            </header>

            <div class="audit-table-card" style="margin: 20px;">
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

    function toggleDropdown() {
        document.getElementById("reportDropdown").classList.toggle("show");
    }

    window.onclick = function(event) {
        if (!event.target.matches('.btn-report') && !event.target.matches('.btn-report *')) {
            var dropdowns = document.getElementsByClassName("dropdown-content");
            for (var i = 0; i < dropdowns.length; i++) {
                var openDropdown = dropdowns[i];
                if (openDropdown.classList.contains('show')) {
                    openDropdown.classList.remove('show');
                }
            }
        }
    }

    function exportToExcel() {
        const table = document.getElementById("auditTable");
        const workbook = XLSX.utils.table_to_book(table, { sheet: "Audit Logs" });
        XLSX.writeFile(workbook, "Audit_Trail_Report.xlsx");
    }

    function exportToCSV() {
        const table = document.getElementById("auditTable");
        const workbook = XLSX.utils.table_to_book(table);
        XLSX.writeFile(workbook, "Audit_Trail_Report.csv", { bookType: 'csv' });
    }

    function exportToPDF() {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF('l', 'mm', 'a4'); 
        
        doc.text("Audit Trail Report", 14, 15);
        doc.setFontSize(10);
        
        doc.autoTable({
            html: '#auditTable',
            startY: 22,
            theme: 'striped',
            headStyles: { fillColor: [242, 140, 40] }, 
            styles: { fontSize: 9 }
        });
        
        doc.save("Audit_Trail_Report.pdf");
    }
    </script>
</body>
</html>