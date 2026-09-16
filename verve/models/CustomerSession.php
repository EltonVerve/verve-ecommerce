<?php
// Persistent customer credentials are random; only their hashes live in MySQL.
function customerCookie(string $value, int $expires): void {
    setcookie('verve_customer', $value, [
        'expires' => $expires,
        'path' => (parse_url(BASE_URL, PHP_URL_PATH) ?: '') . '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function revokeCustomerLogin(PDO $pdo): void {
    $token = $_COOKIE['verve_customer'] ?? '';
    if (is_string($token) && preg_match('/^[a-f0-9]{64}$/D', $token)) {
        $pdo->prepare('DELETE FROM customer_sessions WHERE token_hash = ?')->execute([hash('sha256', $token)]);
    }
    customerCookie('', time() - 3600);
    unset($_COOKIE['verve_customer']);
}

function rememberCustomer(PDO $pdo, array $user): void {
    revokeCustomerLogin($pdo);
    $token = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO customer_sessions (token_hash, user_id, password_fingerprint) VALUES (?, ?, ?)')
        ->execute([hash('sha256', $token), $user['id'], hash('sha256', $user['password_hash'])]);
    customerCookie($token, time() + 400 * 86400);
    $_COOKIE['verve_customer'] = $token;
}

function restoreCustomerLogin(PDO $pdo): void {
    $token = $_COOKIE['verve_customer'] ?? '';
    if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return;
    $stmt = $pdo->prepare('SELECT u.*, s.password_fingerprint FROM customer_sessions s JOIN users u ON u.id = s.user_id WHERE s.token_hash = ?');
    $stmt->execute([hash('sha256', $token)]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'customer' || !hash_equals($user['password_fingerprint'], hash('sha256', $user['password_hash']))) {
        revokeCustomerLogin($pdo);
        unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_role']);
        return;
    }
    if (!isCustomerLoggedIn()) {
        session_regenerate_id(true);
        if (!empty($_SESSION['guest_id'])) mergeGuestCartIntoUser($pdo, (int) $user['id'], $_SESSION['guest_id']);
    }
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['full_name'];
    $_SESSION['user_role'] = 'customer';
    customerCookie($token, time() + 400 * 86400);
}
