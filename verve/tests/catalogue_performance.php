<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
$pdo->beginTransaction();
try {
    $category = $pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn();
    if (!$category) throw new RuntimeException('A test category is required');
    $prefix = 'pagination-' . bin2hex(random_bytes(8));
    $stmt = $pdo->prepare('INSERT INTO products (category_id, name, slug, price, stock, is_active) VALUES (?, ?, ?, 10, 5, 1)');
    for ($i = 0; $i < 25; $i++) $stmt->execute([$category, $prefix . '-' . $i, $prefix . '-' . $i]);
    $first = getShopProducts($pdo, ['q' => $prefix, 'page' => 1], $one);
    $second = getShopProducts($pdo, ['q' => $prefix, 'page' => 2], $two);
    if (count($first) !== 24 || count($second) !== 1 || $one['total'] !== 25 || $two['pages'] !== 2) throw new RuntimeException('Pagination counts failed');
    if (array_intersect(array_column($first, 'id'), array_column($second, 'id'))) throw new RuntimeException('Pages overlap');
    getShopProducts($pdo, ['q' => $prefix, 'page' => 999], $last);
    if ($last['page'] !== 2) throw new RuntimeException('Out-of-range page must clamp');
    getShopProducts($pdo, ['q' => $prefix, 'min_price' => 11], $empty);
    if ($empty['total'] !== 0) throw new RuntimeException('Count must honor filters');
    echo "PASS: pagination, stable ordering, bounds and filter counts.\n";
} finally { $pdo->rollBack(); }
