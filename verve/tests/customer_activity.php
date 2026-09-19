<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
function checkActivity(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$pdo->beginTransaction();
try {
    $id = createUser($pdo, 'Activity Test', bin2hex(random_bytes(10)) . '@example.test', 'test-password-123');
    $other = createUser($pdo, 'Other Test', bin2hex(random_bytes(10)) . '@example.test', 'test-password-456');
    $pdo->prepare("INSERT INTO orders (user_id, subtotal, total, status) VALUES (?, 10, 10, 'pending'), (?, 20, 20, 'cancelled'), (?, 99, 99, 'pending')")->execute([$id, $id, $other]);
    $product = $pdo->query('SELECT id FROM products LIMIT 1')->fetchColumn();
    if ($product) $pdo->prepare('INSERT INTO wishlist_items (user_id, product_id) VALUES (?, ?)')->execute([$id, $product]);
    $activity = getCustomerActivity($pdo, $id);
    checkActivity(count($activity['orders']) === 2, 'Orders must be scoped to the selected customer');
    checkActivity((float) $activity['summary']['order_value'] === 10.0, 'Cancelled orders must be excluded from order value');
    checkActivity(count(getCustomerActivity($pdo, $other)['wishlist']) === 0, 'Wishlists must be isolated');
    if ($product) checkActivity(count($activity['wishlist']) === 1, 'Saved product must appear');
    $token = createResetToken($pdo, $id);
    checkActivity(findValidReset($pdo, $token) !== null, 'Reset token must resolve');
    $stmt = $pdo->prepare('SELECT token FROM password_resets WHERE user_id = ?');
    $stmt->execute([$id]);
    checkActivity($stmt->fetchColumn() === hash('sha256', $token), 'Only the reset token hash may be stored');
    updateUserPassword($pdo, $id, 'new-test-password-123');
    checkActivity(findValidReset($pdo, $token) === null, 'Password changes must invalidate reset tokens');
    checkActivity(password_verify('new-test-password-123', findUserById($pdo, $id)['password_hash']), 'Password must be updated');
    echo "PASS: customer activity isolation, cancelled totals, wishlist, reset hashing and password invalidation.\n";
} finally {
    $pdo->rollBack();
}
