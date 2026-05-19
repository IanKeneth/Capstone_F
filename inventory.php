<?php
session_start();
require_once 'auth/conn.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: auth/login.php");
    exit();
}

/** @param mixed $value */
function e($value): string { 
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
    <link rel="stylesheet" href="assets/css/inventor_admin.css">
</head>
<body>

    <div class="container">
        <aside class="sidebar">
           <div class="sidebar-header">
                <img src="assets/img/download.jpeg" alt="Salescore Logo" class="sidebar-logo">
                
            </div>
          
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item active"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="inventory_logs.php" class="nav-item "><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="dispatchers.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Dispatchers</span></a>
                <a href="audit_trail.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Audit Trail</span></a>
                <a href="retailer.php" class="nav-item "><i class="fa-solid fa-shop"></i> <span>Retailer</span></a>
               <a href="sales.php" class="nav-item "><i class="fa-solid fa-coins"></i> <span>Sales History</span></a>
                <a href="setting.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
           
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="white-space: nowrap; margin-right: 20px;">Inventory Overview</h1>
                    <div class="search-container">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="inventorySearch" placeholder="Search product...">
                    </div>
                </div>
            </header>

            <section class="inventory-container">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 style="color: #2c3e50;"><i class="fa-solid fa-layer-group"></i> Current Catalog</h2>
                    <button class="refresh-btn" onclick="openForm()"><i class="fa-solid fa-plus"></i> Add New Product</button>
                </div>

                <div id="inventory-grid" class="inventory-grid">
                    <p style="grid-column: 1/-1; text-align: center; padding: 50px; color: #7f8c8d;">Loading catalog...</p>
                </div>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <span class="close" onclick="closeForm()">&times;</span>
                            <h2 style="margin:0; color:white;">Register New Stock</h2>
                        </div>
                        <div class="modal-body">
                            <form action="function/insert_into.php" method="POST" enctype="multipart/form-data">
                                <label>Product Photo</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="product_image" id="imgInput" accept="image/*" required onchange="previewImage(this)">
                                    <p style="font-size: 0.7rem; color: #7f8c8d;">Click to upload (PNG, JPG)</p>
                                    <center><img id="imagePreview" src="">
                                </div>

                                <label>Category</label>
                                <select name="category" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="Brooms">Brooms (Silhig)</option>
                                    <option value="Dustpan">Dustpan</option>
                                    <option value="Brushes">Brushes</option>
                                    <option value="Bucket">Bucket (Balde)</option>
                                    <option value="Tub">Tub (Labador)</option>
                                    <option value="Doormats">Doormats</option>
                                    <option value="Mops">Mops</option>
                                    <option value="Trash Can">Trash Can</option>
                                </select>

                                <label>Product Name</label>
                                <input type="text" name="product_name" placeholder="e.g. Walis Tambo Ordinary" required>

                                <label>Description</label>
                                <input type="text" name="description" placeholder="e.g. High-quality broom with durable handle" required>

                                <label>Variation</label>
                                <input type="text" name="variation" placeholder="e.g. Wooden Handle">

                                <div style="display: flex; gap: 10px;">
                                    <div style="flex:1;">
                                        <label>Wholesale Price (₱)</label>
                                        <input type="number" name="wholesale_price" step="0.01" required>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Retail Price (₱)</label>
                                        <input type="number" name="retail_price" step="0.01" required>
                                    </div>
                                    <div style="flex:1;">
                                        <label>Initial Qty</label>
                                        <input type="number" name="quantity" required>
                                    </div>
                                </div>
                                <label>Max Capacity</label>
                                <input type="number" name="max_quantity" value="100" required>

                                <button type="submit" class="btn-submit">Confirm & Save Product</button>
                            </form>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/api/inventory.js"></script>
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; }
                reader.readAsDataURL(input.files[0]);
            }
        }

        window.onload = loadInventory;
        function openForm() { document.getElementById("popupForm").style.display = "flex"; }
        function closeForm() { 
            document.getElementById("popupForm").style.display = "none";
            document.getElementById("imagePreview").style.display = "none";
        }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById("popupForm")) closeForm();
        }

        const sidebar = document.querySelector('.sidebar');
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
        });
    </script>
</body>
</html>