<?php
session_start();
require_once "auth/conn.php"; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: auth/login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

try {
    $stmt = $pdo->prepare("SELECT name, email, profile_pic FROM admin WHERE id = ? LIMIT 1");
    $stmt->execute([$admin_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header("Location: auth/login.php");
        exit();
    }

    $admin_name = $user['name'];
    $admin_email = $user['email'];
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
    <title>Admin Panel </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .main-content { background-color: #fdfbf4; height: 100vh; overflow-y: auto; display: flex; flex-direction: column; width: 100%; }
        .header { position: sticky; top: 0; z-index: 1000; background: #f28c28; padding: 10px 20px; display: flex; align-items: center; justify-content: space-between; color: white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .settings-container { max-width: 900px; margin: 0 auto; width: 100%; display: flex; flex-direction: column; gap: 15px; padding: 20px; }
        .settings-card { background: white; border: 1px solid #e8e4d8; border-radius: 12px; padding: 20px; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03); }
        .profile-header { display: flex; align-items: center; gap: 30px; flex-wrap: wrap; }
        .profile-pic-container { display: flex; flex-direction: column; align-items: center; gap: 10px; flex-shrink: 0; }
        .profile-box { width: 110px; height: 110px; border-radius: 50%; overflow: hidden; display: flex; align-items: center; justify-content: center; background: #eee; border: 2px solid #f28c28; }
        .profile-box img { width: 100%; height: 100%; object-fit: cover; }
        .profile-box i { font-size: 80px; color: #7ba6c9; }
        .change-photo-btn { background: #f28c28; color: white; border: none; padding: 6px 14px; border-radius: 15px; font-size: 0.75rem; cursor: pointer; font-weight: bold; }
        .input-group { flex: 1; min-width: 300px; display: flex; flex-direction: column; gap: 15px; }
        .input-wrapper { position: relative; width: 100%; }
        .input-wrapper i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #888; z-index: 5; }
        .settings-input { width: 100%; padding: 12px 40px; border: 1px solid #e0ddd0; border-radius: 8px; font-size: 0.9rem; box-sizing: border-box; }
        .btn-row { display: flex; justify-content: flex-end; margin-top: 10px; }
        .apply-btn { background: #f28c28; color: white; border: none; padding: 10px 35px; border-radius: 25px; font-weight: bold; cursor: pointer; }
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 15px; width: 100%; box-sizing: border-box; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        #loadingOverlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 9999; flex-direction: column; justify-content: center; align-items: center; }
        .spinner { width: 50px; height: 50px; border: 5px solid #f3f3f3; border-top: 5px solid #f28c28; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div id="loadingOverlay">
        <div class="spinner"></div>
        <p style="margin-top: 15px; font-weight: bold; color: #f28c28;">Updating Profile...</p>
    </div>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Settings</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="inventory_logs.php" class="nav-item"><i class="fa-solid fa-route"></i> <span>Inventory Logs</span></a>
                <a href="dispatchers.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Dispatchers</span></a>
                <a href="audit_trail.php" class="nav-item"><i class="fa-solid fa-clipboard-list"></i> <span>Audit Trail</span></a>
                <a href="retailer.php" class="nav-item"><i class="fa-solid fa-shop"></i> <span>Retailer</span></a>
                <a href="sales.php" class="nav-item"><i class="fa-solid fa-coins"></i> <span>Sales History</span></a>
                <a href="settings.php" class="nav-item active"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
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
                                    <i class="fa-solid fa-envelope"></i>
                                    <input type="email" name="email" class="settings-input" value="<?= e($admin_email) ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card" style="margin-top:15px;">
                        <span class="card-title">Security</span>
                        <div class="input-wrapper" style="margin-bottom: 12px;">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="current_password" class="settings-input" placeholder="Current Password">
                        </div>
                        <div class="input-wrapper" style="margin-bottom: 12px;">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="new_password" class="settings-input" placeholder="New Password">
                        </div>
                        <div class="input-wrapper">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="confirm_password" class="settings-input" placeholder="Confirm New Password">
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
        document.getElementById('profileForm').addEventListener('submit', function() {
            document.getElementById('loadingOverlay').style.display = 'flex';
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
            document.querySelector('.sidebar').classList.toggle('active');
        });
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>