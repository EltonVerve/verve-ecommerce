<?php
// Production credentials belong in the host environment, never in source control.
$pdo = new PDO(
    'mysql:host=' . (getenv('VERVE_DB_HOST') ?: 'localhost') . ';dbname=' . (getenv('VERVE_DB_NAME') ?: 'verve') . ';charset=utf8mb4',
    getenv('VERVE_DB_USER'), getenv('VERVE_DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
);
