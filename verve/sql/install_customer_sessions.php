<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . (getenv('VERVE_ENV') === 'production' ? '/../config/database.production.php' : '/../config/database.php');
$pdo->exec(file_get_contents(__DIR__ . '/customer_sessions.sql'));
$column = $pdo->query("SHOW COLUMNS FROM customer_sessions LIKE 'expires_at'")->fetch();
if (!$column) {
    // Old credentials have no issuance time; expire them rather than grant a new lifetime.
    $pdo->exec('ALTER TABLE customer_sessions ADD expires_at BIGINT NOT NULL DEFAULT 0');
}
echo "Customer session table ready.\n";
