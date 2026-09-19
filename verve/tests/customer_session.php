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
    $stmt = $pdo->prepare('SELECT expires_at FROM customer_sessions WHERE token_hash = ?');
    $stmt->execute([hash('sha256', $token)]);
    checkSession(abs((int) $stmt->fetchColumn() - (time() + 30 * 86400)) <= 2, 'Login must renew for 30 days');
    $pdo->prepare('UPDATE customer_sessions SET expires_at = ? WHERE token_hash = ?')->execute([time() - 1, hash('sha256', $token)]);
    restoreCustomerLogin($pdo);
    checkSession(!isCustomerLoggedIn(), 'Expired token must invalidate even an active PHP session');
    rememberCustomer($pdo, $user);
    $token = $_COOKIE['verve_customer'];
    restoreCustomerLogin($pdo);
    unset($_COOKIE['verve_customer']);
    restoreCustomerLogin($pdo);
    checkSession(!isCustomerLoggedIn(), 'Missing persistent cookie must not leave an authenticated session');
    $_COOKIE['verve_customer'] = $token;
    restoreCustomerLogin($pdo);
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
    $_SESSION = ['user_id' => $id, 'user_role' => 'admin', 'admin_last_activity' => 1000];
    enforceAdminIdleTimeout(2799);
    checkSession(isLoggedIn() && $_SESSION['admin_last_activity'] === 2799, 'Active admin session must renew');
    enforceAdminIdleTimeout(4599);
    checkSession(!isLoggedIn(), 'Admin must sign in again after 30 minutes idle');
    $_SESSION = ['user_id' => $id, 'user_role' => 'admin'];
    enforceAdminIdleTimeout(4600);
    checkSession(!isLoggedIn(), 'Legacy admin session must sign in again');
    echo "PASS: 30-day renewal, server expiry, missing cookie, restoration, order ownership, logout, password invalidation and admin idle timeout.\n";
} finally {
    $pdo->rollBack();
}
