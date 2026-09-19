<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
$pdo->exec(file_get_contents(__DIR__ . '/auth_rate_limits.sql'));
echo "Authentication rate limit table installed.\n";
