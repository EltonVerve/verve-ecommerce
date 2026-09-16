<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
function checkSession(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$pdo->beginTransaction();
try {
    $id = createUser($pdo, 'Session Test', bin2hex(random_bytes(10)) . '@example.test', 'test-password');
    $user = findUserById($pdo, $id);
    rememberCustomer($pdo, $user);
    $token = $_COOKIE['verve_customer'];
    $_SESSION = [];
    restoreCustomerLogin($pdo);
    checkSession(isCustomerLoggedIn() && (int) $_SESSION['user_id'] === $id, 'Login must survive loss of PHP session');
    checkSession(getCartOwner() === ['user_id', $user['id']], 'Restored login must use account cart');
    $pdo->prepare('INSERT INTO orders (user_id, subtotal, total) VALUES (?, 10, 10)')->execute([$id]);
    $orderId = (int) $pdo->lastInsertId();
    checkSession(count(getOrdersForUser($pdo, $id)) === 1, 'Order history must include customer orders');
    checkSession(getOrderWithItems($pdo, $orderId, $id) !== null, 'Customer must be able to open their order');
    checkSession(getOrderWithItems($pdo, $orderId, 0) === null, 'Other customers must not access the order');
    revokeCustomerLogin($pdo);
    $_SESSION = [];
    $_COOKIE['verve_customer'] = $token;
    restoreCustomerLogin($pdo);
    checkSession(!isCustomerLoggedIn(), 'Revoked token must not restore login');
    rememberCustomer($pdo, $user);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash('changed-password', PASSWORD_DEFAULT), $id]);
    $_SESSION = [];
    restoreCustomerLogin($pdo);
    checkSession(!isCustomerLoggedIn(), 'Changed password must invalidate persistent login');
    echo "PASS: session restoration, account cart ownership, order history and ownership, logout revocation, password invalidation.\n";
} finally {
    $pdo->rollBack();
}
