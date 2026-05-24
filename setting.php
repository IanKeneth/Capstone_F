<?php
session_start();
require_once "auth/conn.php"; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: auth/login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

try {
    $stmt = $pdo->prepare("SELECT name, username, profile_pic FROM admin WHERE id = ? LIMIT 1");
    $stmt->execute([$admin_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: auth/login.php");
        exit();
    }

    $admin_name = $user['name'];
    $admin_username = $user['username'];
    $admin_pic = $user['profile_pic'];

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
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
    <link rel="stylesheet" href="assets/css/settings.css">
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
                <a href="sales.php" class="nav-item" data-title="Sales History">
                    <div class="icon"><i class="fa-solid fa-coins"></i></div>
                    <span>Sales History</span>
                </a>
                <a href="setting.php" class="nav-item active" data-title="Settings">
                    <div class="icon"><i class="fa-solid fa-gears"></i></div>
                    <span>Settings</span>
                </a>
            </nav>
            <div class="sidebar-footer">
                <a href="auth/logout.php" class="nav-item" data-title="Logout">
                    <div class="icon"><i class="fa-solid fa-right-from-bracket"></i> </div>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div style="display:flex; align-items:center; gap:15px;">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1 style="font-size:1.1rem; font-weight:700;">Settings</h1>
                </div>
                <div class="user-profile">
                    <?php 
                    $headerPic = "assets/uploads/profiles/" . $admin_pic;
                    if(!empty($admin_pic) && file_exists($headerPic)): 
                    ?>
                        <img src="<?= $headerPic ?>?t=<?= time() ?>" style="width:35px; height:35px; border-radius:50%; object-fit:cover;">
                    <?php else: ?>
                        <i class="fa-solid fa-circle-user"></i>
                    <?php endif; ?>
                </div>
            </header>

            <section class="settings-container">
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" id="successMsg">
                        <i class="fa-solid fa-circle-check"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form id="profileForm" action="function/admin_setting.php" method="POST" enctype="multipart/form-data">
                    <div class="settings-card">
                        <span class="card-title">Account Information</span>
                        <div class="profile-header">
                            <div class="profile-pic-container">
                                <div class="profile-box" id="picBox">
                                    <?php 
                                    $mainPic = "assets/uploads/profiles/" . $admin_pic;
                                    if(!empty($admin_pic) && file_exists($mainPic)): 
                                    ?>
                                        <img src="<?= $mainPic ?>?t=<?= time() ?>" id="profilePreview">
                                    <?php else: ?>
                                        <i class="fa-solid fa-circle-user" id="profileIcon"></i>
                                    <?php endif; ?>
                                </div>
                                <button type="button" class="change-photo-btn" onclick="document.getElementById('profile_upload').click();">Change photo</button>
                                <input type="file" name="profile_pic" id="profile_upload" accept="image/*" style="display: none;">
                            </div>

                            <div class="input-group">
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-user"></i>
                                    <input type="text" name="full_name" class="settings-input" value="<?= e($admin_name) ?>" required>
                                </div>
                                <div class="input-wrapper">
                                    <i class="fa-solid fa-user"></i>
                                    <input type="text" name="username" class="settings-input" value="<?= e($admin_username) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card" style="margin-top:15px;">
                        <span class="card-title" style="font-weight:bold; display:block; margin-bottom:15px;">Security</span>
                        
                        <div class="input-wrapper" style="margin-bottom: 12px;">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="current_password" class="settings-input" placeholder="Current Password" autocomplete="new-password">
                            <i class="fa-regular fa-eye toggle-password"></i>
                        </div>
                        
                        <div class="input-wrapper" style="margin-bottom: 12px;">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="new_password" class="settings-input" placeholder="New Password" autocomplete="new-password">
                            <i class="fa-regular fa-eye toggle-password"></i>
                        </div>
                        
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="confirm_password" class="settings-input" placeholder="Confirm New Password" autocomplete="new-password">
                            <i class="fa-regular fa-eye toggle-password"></i>
                        </div>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="apply-btn">Apply Changes</button>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script>
    document.querySelectorAll('.toggle-password').forEach(eyeIcon => {
        eyeIcon.addEventListener('click', function () {
            const passwordInput = this.parentElement.querySelector('.settings-input');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                this.classList.remove('fa-eye');
                this.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                this.classList.remove('fa-eye-slash');
                this.classList.add('fa-eye');
            }
        });
    });

    window.addEventListener('DOMContentLoaded', () => {
        setTimeout(() => {
            const currentPassField = document.querySelector('input[name="current_password"]');
            if(currentPassField) currentPassField.value = '';
        }, 50);
    });
    document.getElementById('profileForm').addEventListener('submit', function() {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.style.display = 'flex';
        }
    });

    document.getElementById('profile_upload').addEventListener('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('picBox').innerHTML = `<img src="${e.target.result}" id="profilePreview">`;
            }
            reader.readAsDataURL(file);
        }
    });
    setTimeout(() => {
        const msg = document.getElementById('successMsg');
        if(msg) msg.style.display = 'none';
    }, 4000);

    document.getElementById('sidebarToggle').addEventListener('click', () => {
        const sidebar = document.querySelector('.sidebar');
        if(sidebar) {
            sidebar.classList.toggle('active');
            sidebar.classList.toggle('collapsed');
        }
    });
    </script>
</body>
</html>