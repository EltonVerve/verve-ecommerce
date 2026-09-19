<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . (getenv('VERVE_ENV') === 'production' ? '/../config/database.production.php' : '/../config/database.php');
foreach (['payment_status' => "VARCHAR(20) NOT NULL DEFAULT 'unpaid'", 'stock_restored' => 'TINYINT NOT NULL DEFAULT 0'] as $column => $definition) {
    if (!$pdo->query("SHOW COLUMNS FROM orders LIKE '$column'")->fetch()) {
        $pdo->exec("ALTER TABLE orders ADD `$column` $definition");
        if ($column === 'payment_status') $pdo->exec("UPDATE orders SET payment_status = CASE WHEN status = 'paid' THEN 'collected' ELSE 'unknown' END");
        if ($column === 'stock_restored') $pdo->exec("UPDATE orders SET stock_restored = 1 WHERE status = 'cancelled'");
    }
}
$pdo->exec("CREATE TABLE IF NOT EXISTS order_events (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, actor_id INT NULL, event_type VARCHAR(20) NOT NULL, old_value VARCHAR(30) NULL, new_value VARCHAR(30) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(order_id, id), FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS delivery_zones (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(120) NOT NULL, country VARCHAR(100) NOT NULL, fee DECIMAL(10,2) NOT NULL, free_over DECIMAL(10,2) NULL, active TINYINT NOT NULL DEFAULT 1) ENGINE=InnoDB");
if (!(int) $pdo->query('SELECT COUNT(*) FROM delivery_zones')->fetchColumn()) $pdo->exec("INSERT INTO delivery_zones (name, country, fee, free_over) VALUES ('Nairobi', 'Kenya', 200, 5000)");
echo "Order operations tables ready. Legacy payment states require review; legacy cancellations are not restocked retrospectively.\n";
