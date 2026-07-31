<?php
// Send JSON header ONLY if requested directly via URL/fetch, not when included inside another PHP file
if (basename($_SERVER['SCRIPT_FILENAME']) === 'get_product_api.php') {
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *"); 
}

require_once __DIR__ . '/../auth/conn.php'; 

try {
    $stmt = $pdo->prepare("SELECT id, product_name, category, variation, description, wholesale_price, retail_price, quantity, image_path, max_quantity FROM products ORDER BY id DESC"); 
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "count" => count($products),
        "data" => $products
    ]);

} catch(PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>