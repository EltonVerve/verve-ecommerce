<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/database.php';
$pdo->exec(file_get_contents(__DIR__ . '/customer_sessions.sql'));
echo "Customer session table ready.\n";
