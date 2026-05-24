<?php
class DispatchController {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getActiveDispatches(): array {
        $stmt = $this->pdo->query(
            "SELECT ds.id AS session_id, ds.worker_name, ds.date_today, ds.status,
                    di.id AS di_id, di.product_id, di.qty_taken, di.price_at_time, 
                    p.product_name, p.quantity AS inventory_qty
                FROM dispatch_sessions ds
                JOIN dispatch_items di ON di.session_id = ds.id
                JOIN products p ON p.id = di.product_id
                WHERE ds.status = 'Active'
                ORDER BY ds.date_today DESC, ds.id, di.id"
        );
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grouped = [];
        foreach ($items as $row) {
            $sid = $row['session_id'];
            if (!isset($grouped[$sid])) {
                $grouped[$sid] = [
                    'info' => ['name' => $row['worker_name'], 'date_today' => $row['date_today'], 'status' => $row['status']],
                    'items' => []
                ];
            }
            $grouped[$sid]['items'][] = $row;
        }
        return $grouped;
    }

    public function createBulkDispatch(array $data, string $admin): bool {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO dispatch_sessions (worker_name, date_today, status) VALUES (?, ?, 'Active')");
            $stmt->execute([$data['worker_name'], $data['date_today']]);
            $sid = (int)$this->pdo->lastInsertId();

            $worker = $data['worker_name'];

            foreach ($data['product_ids'] as $idx => $pid) {
                $qty = (int)$data['qtys'][$idx];
                $pid = (int)$pid;
                if ($qty > 0) $this->processItem($sid, $pid, $qty, $worker, $admin);
            }
            return $this->pdo->commit();
        } catch (Exception $e) { 
            $this->pdo->rollBack(); 
            throw $e; 
        }
    }

    public function addSingleItem(array $data, string $admin): bool {
        try {
            $this->pdo->beginTransaction();
            $sStmt = $this->pdo->prepare("SELECT worker_name FROM dispatch_sessions WHERE id = ?");
            $sStmt->execute([$data['session_id']]);
            $worker = $sStmt->fetchColumn();

            $sessionId = (int)$data['session_id'];
            $newPid = (int)$data['new_product_id'];
            $this->processItem($sessionId, $newPid, (int)$data['new_qty'], $worker, $admin);
            return $this->pdo->commit();
        } catch (Exception $e) { 
            $this->pdo->rollBack(); 
            throw $e; 
        }
    }

    private function processItem(int $sid, int $pid, int $qty, string $worker, string $admin): void {
        $p = $this->pdo->prepare("SELECT product_name, quantity, wholesale_price FROM products WHERE id = ?");
        $p->execute([$pid]);
        $prod = $p->fetch(PDO::FETCH_ASSOC);

        if (!$prod) throw new Exception("Product ID {$pid} not found.");
        if ($qty > $prod['quantity']) throw new Exception("Insufficient Stock for {$prod['product_name']}");

        $this->pdo->prepare("INSERT INTO dispatch_items (session_id, product_id, qty_taken, price_at_time) VALUES (?,?,?,?)")
                 ->execute([$sid, $pid, $qty, $prod['wholesale_price']]);
        
        $this->pdo->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?")->execute([$qty, $pid]);

        $log = $this->pdo->prepare("INSERT INTO inventory_logs (product_id, quantity_change, action, notes, admin_name) VALUES (?, ?, 'Removed', ?, ?)");
        $log->execute([$pid, $qty, "Added to session #$sid ($worker)", $admin]);
    }
}