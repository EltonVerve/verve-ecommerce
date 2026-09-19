<?php
require_once __DIR__ . '/../config/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/pages/login.php'); exit; }
verifyCsrf();
$challenge = $_SESSION['login_challenge'] ?? '';
if (!$challenge) { header('Location: ' . BASE_URL . '/pages/login.php'); exit; }
limitAuthRequests($pdo, 'email-login-verify', $_SESSION['login_email'] ?? '');
$code = is_string($_POST['code'] ?? null) ? trim($_POST['code']) : '';
$user = consumeCustomerLoginCode($pdo, $challenge, $code);
if (!$user) {
    setFlash('error', 'The code is incorrect, expired, or has reached its attempt limit. Request a new code if needed.');
    header('Location: ' . BASE_URL . '/pages/verify_login.php'); exit;
}
if (!empty($_SESSION['guest_id'])) mergeGuestCartIntoUser($pdo, (int) $user['id'], $_SESSION['guest_id']);
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = 'customer';
$_SESSION['password_fingerprint'] = hash('sha256', $user['password_hash']);
unset($_SESSION['login_challenge'], $_SESSION['login_email'], $_SESSION['login_code_sent_at'], $_SESSION['csrf_token']);
rememberCustomer($pdo, $user);
header('Location: ' . BASE_URL . '/pages/account.php');
exit;
