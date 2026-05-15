<?php
require_once "../auth/conn.php"; 
session_start();

class RetailController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * 
     * @param array $data The $_POST data.
     * @param string $adminName The name of the session user.
     * @throws Exception
     */
  public function handleSale(array $data, string $adminName) {
    try {
        $this->pdo->beginTransaction();

        $productId = $data['product_id'];
        $qty       = intval($data['qty']);
        $orderDate = $data['order_date'];
        if ($qty <= 0) {
            throw new Exception("Invalid quantity. Please enter a number greater than 0.");
        }

        $stmt = $this->pdo->prepare("SELECT product_name, quantity, retail_price FROM products WHERE id = :id");
        $stmt->execute([':id' => $productId]);
        $product = $stmt->fetch();

        if (!$product) throw new Exception("Product not found.");
        if ($product['quantity'] < $qty) {
            throw new Exception("Insufficient stock for " . $product['product_name']);
        }

        $subtotal = $product['retail_price'] * $qty;

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

    private function validateStock(int $id, int $qty) {
        $stmt = $this->pdo->prepare("SELECT product_name, quantity FROM products WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $product = $stmt->fetch();

        if (!$product) {
            throw new Exception("Product not found.");
        }

        if ($product['quantity'] < $qty) {
            throw new Exception("Insufficient stock for " . $product['product_name']);
        }
    }

    private function recordOrder(int $pid, int $qty, float $total, string $date) {
        $sql = "INSERT INTO retail_orders (product_id, qty, subtotal, order_date) 
                VALUES (:pid, :qty, :total, :date)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pid'   => $pid,
            ':qty'   => $qty,
            ':total' => $total,
            ':date'  => $date
        ]);
    }

    private function deductInventory(int $pid, int $qty) {
        $sql = "UPDATE products SET quantity = quantity - :qty WHERE id = :pid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':qty' => $qty, ':pid' => $pid]);
    }

    private function logTransaction(int $pid, int $qty, string $date, string $admin) {
        $sql = "INSERT INTO inventory_logs (product_id, quantity_change, action, notes, admin_name) 
                VALUES (:pid, :qty, 'Removed', :notes, :admin)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':pid'   => $pid,
            ':qty'   => $qty,
            ':notes' => "Retail Sale - Date: $date", 
            ':admin' => $admin
        ]);
    }
}

if (isset($_POST['submit_retail'])) {
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
} else {
    header("Location: ../retailer.php");
    exit();
}