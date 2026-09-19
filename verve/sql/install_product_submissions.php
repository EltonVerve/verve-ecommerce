<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . (getenv('VERVE_ENV') === 'production' ? '/../config/database.production.php' : '/../config/database.php');
$pdo->exec("CREATE TABLE IF NOT EXISTS product_submissions (
 id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL UNIQUE, target_id INT NULL,
 author_id INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'draft', version INT NOT NULL DEFAULT 1,
 review_note VARCHAR(1000) NOT NULL DEFAULT '', reviewer_id INT NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX(author_id,status), INDEX(status,id), INDEX(target_id),
 FOREIGN KEY(product_id) REFERENCES products(id) ON DELETE CASCADE,
 FOREIGN KEY(target_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB");
echo "Product submissions ready. Existing products are unchanged.\n";
