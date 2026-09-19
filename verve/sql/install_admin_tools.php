<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . (getenv('VERVE_ENV') === 'production' ? '/../config/database.production.php' : '/../config/database.php');
if (!$pdo->query("SHOW COLUMNS FROM users LIKE 'admin_scope'")->fetch()) $pdo->exec("ALTER TABLE users ADD admin_scope VARCHAR(20) NOT NULL DEFAULT 'owner'");
$pdo->exec("CREATE TABLE IF NOT EXISTS store_settings (setting_key VARCHAR(60) PRIMARY KEY, setting_value VARCHAR(200) NOT NULL) ENGINE=InnoDB");
$pdo->exec("INSERT IGNORE INTO store_settings VALUES ('low_stock_threshold','5')");
$pdo->exec("CREATE TABLE IF NOT EXISTS admin_audit (id BIGINT AUTO_INCREMENT PRIMARY KEY, actor_id INT NULL, action VARCHAR(80) NOT NULL, entity_type VARCHAR(40) NOT NULL, entity_id INT NOT NULL, details JSON NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(created_at), INDEX(actor_id)) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS order_returns (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, order_item_id INT NOT NULL, quantity INT NOT NULL, item_condition VARCHAR(20) NOT NULL, restocked TINYINT NOT NULL, reason VARCHAR(500) NOT NULL, actor_id INT NOT NULL, request_key CHAR(64) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(order_id), FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS order_refunds (id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, amount DECIMAL(10,2) NOT NULL, reason VARCHAR(500) NOT NULL, reference VARCHAR(120) NOT NULL, actor_id INT NOT NULL, request_key CHAR(64) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE) ENGINE=InnoDB");
$pdo->exec("CREATE TABLE IF NOT EXISTS order_dispatch (order_id INT PRIMARY KEY, rider VARCHAR(120) NOT NULL, phone VARCHAR(30) NOT NULL, reference VARCHAR(120) NOT NULL, dispatched_at DATETIME NULL, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE) ENGINE=InnoDB");
echo "Admin tools installed. Existing admins retain full access.\n";
