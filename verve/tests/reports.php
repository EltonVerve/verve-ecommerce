<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
function checkReport(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$pdo->beginTransaction();
try {
    $before = getPeriodReports($pdo, 7);
    $stmt = $pdo->prepare('INSERT INTO orders (subtotal, total, status, created_at) VALUES (?, ?, ?, ?)');
    foreach ([['pending', $before['start'], 17], ['cancelled', $before['start'], 23], ['pending', '2000-01-01', 99]] as [$status, $date, $value]) {
        $stmt->execute([$value, $value, $status, $date . ' 00:00:00']);
        $id = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO order_items (order_id, product_name, quantity, unit_price, line_total) VALUES (?, ?, 1, ?, ?)')->execute([$id, 'Report test deleted product', $value, $value]);
    }
    $after = getPeriodReports($pdo, 7);
    checkReport(count($after['sales']) === 7, 'Exactly seven calendar days must be displayed');
    checkReport((float) array_sum(array_column($after['sales'], 'value')) - (float) array_sum(array_column($before['sales'], 'value')) === 17.0, 'Daily values must include boundary and exclude cancelled/out-of-range orders');
    checkReport((float) array_sum(array_column($after['categories'], 'value')) - (float) array_sum(array_column($before['categories'], 'value')) === 17.0, 'Categories must preserve deleted products and exclude cancelled/out-of-range orders');
    checkReport((float) array_sum(array_column($after['statuses'], 'value')) - (float) array_sum(array_column($before['statuses'], 'value')) === 40.0, 'Status totals must include cancellations within the period');
    checkReport(count(getPeriodReports($pdo, 90)['sales']) === 90, 'Ninety-day report must contain ninety days');
    checkReport(count(getPeriodReports($pdo, -1)['sales']) === 30, 'Invalid period must default to thirty days');
    echo "PASS: date ranges, boundary inclusion, cancellations, deleted product categories, and status totals.\n";
} finally {
    $pdo->rollBack();
}
