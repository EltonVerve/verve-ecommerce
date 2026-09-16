<?php
/**
 * SEARCH SUGGESTIONS (JSON)
 * ---------------------------------------------------------
 * Called via fetch() from main.js as the visitor types in the
 * header search box. Returns a small JSON payload, not a full
 * HTML page.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (mb_strlen($query) < 2) {
    echo json_encode(['products' => []]);
    exit;
}

$rows = getProductSearchSuggestions($pdo, $query);
$products = array_map(static function (array $row): array {
    return [
        'name' => $row['name'],
        'category' => $row['category_name'],
        'price' => money((float) $row['price']),
        'image' => productImageUrl($row['image'], $row['name']),
        'url' => BASE_URL . '/pages/product.php?slug=' . rawurlencode($row['slug']),
        'unavailable' => (int) $row['stock'] <= 0,
    ];
}, $rows);

echo json_encode(['products' => $products]);
