<?php 
session_start();
require_once "../auth/conn.php"; 

if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit;
}

function e(?string $value): string { 
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); 
} 

$id = $_GET['id'] ?? null; 
if (!$id) { header("Location: ../inventory.php"); exit; } 

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) { die("Product not found."); }

if (isset($_POST['update_product'])) { 
    $name = trim($_POST['product_name']); 
    $category = trim($_POST['category']); 
    $description = trim($_POST['description']);
    $variation = trim($_POST['variation']); 

    $wholesale = preg_replace('/[^0-9.]/', '', $_POST['wholesale_price']); 
    $retail = preg_replace('/[^0-9.]/', '', $_POST['retail_price']); 
    
    $new_quantity = (int)$_POST['quantity']; 
    $max_quantity = (int)$_POST['max_quantity']; 
    $old_quantity = (int)$product['quantity'];
    $admin_name = $_SESSION['admin_name'] ?? 'Admin'; 

    $image_name = $product['image_path']; 

    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
        $target_dir = "../uploads/";
        $file_tmp = $_FILES["product_image"]["tmp_name"];
        $check = getimagesize($file_tmp);
        if ($check !== false) {
            $file_ext = strtolower(pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION));
            $image_name = time() . "_" . bin2hex(random_bytes(4)) . "_" . preg_replace("/[^a-zA-Z0-9]/", "_", $name) . "." . $file_ext;
            
            if (move_uploaded_file($file_tmp, $target_dir . $image_name)) {
                if ($product['image_path'] && $product['image_path'] != 'default-product.png') {
                    $old_path = $target_dir . $product['image_path'];
                    if (file_exists($old_path)) { unlink($old_path); }
                }
            }
        }
    }

    try {
        $pdo->beginTransaction();
        
        $sql = "UPDATE products SET product_name = :name, category = :cat, description = :desc, 
                variation = :var, wholesale_price = :wh, retail_price = :ret, 
                quantity = :qty, max_quantity = :max, image_path = :img WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $name, ':cat' => $category, ':desc' => $description,
            ':var' => $variation, ':wh' => $wholesale, ':ret' => $retail,
            ':qty' => $new_quantity, ':max' => $max_quantity, ':img' => $image_name, ':id' => $id
        ]);

        if ($new_quantity !== $old_quantity) {
            $diff = $new_quantity - $old_quantity;
            $action = ($diff > 0) ? 'Added' : 'Removed';
            
            $log_sql = "INSERT INTO inventory_logs (product_id, admin_name, action, quantity_change, notes) 
                        VALUES (:pid, :admin, :act, :qty, :notes)";
            $pdo->prepare($log_sql)->execute([
                ':pid' => $id, 
                ':admin' => $admin_name, 
                ':act' => $action, 
                ':qty' => abs($diff),
                ':notes' => "Manual update from $old_quantity to $new_quantity"
            ]);
        }

        $pdo->commit();
        echo "<script>alert('Updated successfully!'); window.location.href='../inventory.php';</script>";
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
    }
} 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Edit Product - Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #f28c28; --secondary: #64748b; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
        .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 30px; width: 100%; max-width: 450px; }
        h1 { text-align: center; margin-bottom: 20px; color: #1e293b; font-size: 1.4rem; }
        .form-group { margin-bottom: 15px; }
        label { font-weight: 600; display: block; margin-bottom: 5px; color: #475569; font-size: 0.85rem; }
        .form-control { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 14px; box-sizing: border-box; }
        .row { display: flex; gap: 10px; }
        .col { flex: 1; }
        .image-preview { width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 2px solid #eee; margin-bottom: 10px; }
        .btn { padding: 12px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; width: 100%; margin-top: 10px; transition: 0.3s; text-decoration: none; display: block; text-align: center; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-secondary { background: #f1f5f9; color: var(--secondary); border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<div class="card">
    <h1>Edit Product Profile</h1>

    <form method="POST" enctype="multipart/form-data">
        
        <div style="text-align: center;">
            <img src="../uploads/<?php echo $product['image_path'] ?: 'default-product.png'; ?>" class="image-preview" id="preview">
            <div class="form-group">
                <label>Change Product Image</label>
                <input type="file" name="product_image" class="form-control" accept="image/*" onchange="previewImage(this)">
            </div>
        </div>

        <div class="form-group">
            <label>Product Name</label>
            <input type="text" name="product_name" class="form-control" value="<?php echo e($product['product_name']); ?>" required>
        </div>

        <div class="form-group">
            <label>Category</label>
            <input type="text" name="category" class="form-control" value="<?php echo e($product['category']); ?>" required>
        </div>

        <div class="form-group">
            <label>Description</label>
            <input type="text" name="description" class="form-control" value="<?php echo e($product['description']); ?>">
        </div>

        <div class="form-group">
            <label>Variation</label>
            <input type="text" name="variation" class="form-control" value="<?php echo e($product['variation']); ?>">
        </div>

        <div class="form-group">
            <label>Wholesale Price (₱)</label>
            <input type="number" step="0.01" name="wholesale_price" class="form-control" value="<?php echo e($product['wholesale_price']); ?>" required>
        </div>

        <div class="form-group">
            <label>Retail Price (₱)</label>
            <input 
                type="number" 
                id="retail_price"
                name="retail_price" 
                class="form-control" 
                step="0.10" 
                min="0.01"
                value="<?php echo e($product['retail_price']); ?>" 
                oninput="validateProfit()"
                required
            >
            <input type="hidden" id="wholesale_price" value="<?php echo e($product['wholesale_price']); ?>">
            
            <small id="profit-warning" style="display:none; color: #e74c3c; margin-top: 5px; font-weight: bold;">
                <i class="fa-solid fa-circle-exclamation"></i> Warning: Selling below wholesale price!
            </small>
        </div>

        <div class="row">
            <div class="col">
                <div class="form-group">
                    <label>Current Stock</label>
                    <input type="number" name="quantity" class="form-control" value="<?php echo e($product['quantity']); ?>" required>
                </div>
            </div>
            <div class="col">
                <div class="form-group">
                    <label>Max Capacity</label>
                    <input type="number" name="max_quantity" class="form-control" value="<?php echo e($product['max_quantity']); ?>" required>
                </div>
            </div>
        </div>

        <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
        <a href="../inventory.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('preview').src = e.target.result;
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function validateProfit() {
        const retailInput = document.getElementById('retail_price');
        const wholesalePrice = parseFloat(document.getElementById('wholesale_price').value) || 0;
        const currentRetail = parseFloat(retailInput.value) || 0;
        const warning = document.getElementById('profit-warning');
        const saveBtn = document.querySelector('button[type="submit"]'); 

        if (currentRetail < wholesalePrice) {
            warning.style.display = 'block';
            retailInput.style.borderColor = '#e74c3c';
            retailInput.style.backgroundColor = '#fff5f5';

        } else {
            warning.style.display = 'none';
            retailInput.style.borderColor = '#ced4da';
            retailInput.style.backgroundColor = '#fff';
        }
    }
    document.addEventListener('DOMContentLoaded', validateProfit);
</script>

</body>
</html>