<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once "../auth/conn.php"; 

class RetailController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Handles the complete process of a retail transaction
     * @param array $data The $_POST data.
     * @param string $adminName The name of the session user.
     * @throws Exception
     */
    public function handleSale(array $data, string $adminName): bool {
        try {
            $this->pdo->beginTransaction();

            $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
            $qty       = isset($data['qty']) ? (int)$data['qty'] : 0;
            $orderDate = $data['order_date'] ?? date('Y-m-d H:i:s');

            if ($qty <= 0) {
                throw new Exception("Invalid quantity. Please enter a number greater than 0.");
            }

            $product = $this->validateStock($productId, $qty);
            $subtotal = (float)$product['retail_price'] * $qty;

            $this->recordOrder($productId, $qty, $subtotal, $orderDate);
            $this->deductInventory($productId, $qty);
            $this->logTransaction($productId, $qty, $orderDate, $adminName);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e; 
        }
    }

    private function validateStock(int $id, int $qty): array {
        $stmt = $this->pdo->prepare("SELECT product_name, quantity, retail_price FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception("Product not found.");
        }

        if ((int)$product['quantity'] < $qty) {
            throw new Exception("Insufficient stock for " . $product['product_name']);
        }

        return $product;
    }

    private function recordOrder(int $pid, int $qty, float $total, string $date): void {
        $sql = "INSERT INTO retail_orders (product_id, qty, subtotal, order_date) 
                VALUES (:pid, :qty, :total, :date)";
        $this->pdo->prepare($sql)->execute([
            ':pid'   => $pid,
            ':qty'   => $qty,
            ':total' => $total,
            ':date'  => $date
        ]);
    }

    private function deductInventory(int $pid, int $qty): void {
        $sql = "UPDATE products SET quantity = quantity - :qty WHERE id = :pid";
        $this->pdo->prepare($sql)->execute([':qty' => $qty, ':pid' => $pid]);
    }

    private function logTransaction(int $pid, int $qty, string $date, string $admin): void {
        $sql = "INSERT INTO inventory_logs (product_id, quantity_change, action, notes, admin_name) 
                VALUES (:pid, :qty, 'Removed', :notes, :admin)";
        $this->pdo->prepare($sql)->execute([
            ':pid'   => $pid,
            ':qty'   => $qty,
            ':notes' => "Retail Sale - Date: $date", 
            ':admin' => $admin
        ]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_retail'])) {
    $retailManager = new RetailController($pdo);
    $admin = $_SESSION['admin_name'] ?? 'System';

    try {
        $retailManager->handleSale($_POST, $admin);
        header("Location: ../retailer.php?status=success");
        exit();
    } catch (Exception $e) {
        header("Location: ../retailer.php?status=error&msg=" . urlencode($e->getMessage()));
        exit();
    }
}