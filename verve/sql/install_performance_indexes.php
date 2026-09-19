<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . (getenv('VERVE_ENV') === 'production' ? '/../config/database.production.php' : '/../config/database.php');
$indexes = [
    'products' => ['idx_active_created' => 'is_active, created_at, id', 'idx_active_price' => 'is_active, price, id'],
    'orders' => ['idx_created_status' => 'created_at, status', 'idx_user_created' => 'user_id, created_at'],
    'cart_items' => ['idx_guest_session' => 'session_id'],
];
foreach ($indexes as $table => $definitions) {
    $existing = array_column($pdo->query("SHOW INDEX FROM `$table`")->fetchAll(), 'Key_name');
    foreach ($definitions as $name => $columns) {
        if (!in_array($name, $existing, true)) $pdo->exec("CREATE INDEX `$name` ON `$table` ($columns)");
    }
}
echo "Performance indexes ready.\n";
