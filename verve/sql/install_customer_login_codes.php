<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . (getenv('VERVE_ENV') === 'production' ? '/../config/database.production.php' : '/../config/database.php');
$pdo->exec(file_get_contents(__DIR__ . '/customer_login_codes.sql'));
echo "Customer login code table ready.\n";
