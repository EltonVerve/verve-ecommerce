<?php
/**
 * LOGIN ACTION
 * ---------------------------------------------------------
 * Handles the POST from pages/login.php.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/login.php?method=password');
    exit;
}

verifyCsrf();

$email = is_string($_POST['email'] ?? null) ? trim(strtolower($_POST['email'])) : '';
$password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
$_SESSION['login_prefill_email'] = $email;
$redirect = BASE_URL . '/pages/account.php';

limitAuthRequests($pdo, 'login', $email);
$user = attemptLogin($pdo, $email, $password);

if (!$user || $user['role'] !== 'customer') {
    setFlash('error', 'That email and password combination is incorrect.');
    header('Location: ' . BASE_URL . '/pages/login.php?method=password');
    exit;
}

// ---- Merge their guest cart into their account cart ----
// If they added items to the cart before logging in, those rows
// are tagged with a session_id instead of a user_id. Now that we
// know who they are, fold those rows into their account cart.
if (!empty($_SESSION['guest_id'])) {
    mergeGuestCartIntoUser($pdo, (int) $user['id'], $_SESSION['guest_id']);
}

session_regenerate_id(true);
$_SESSION['password_fingerprint'] = hash('sha256', $user['password_hash']);
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];
rememberCustomer($pdo, $user);
unset($_SESSION['login_prefill_email'], $_SESSION['csrf_token']);

setFlash('success', 'Welcome back, ' . $user['full_name'] . '.');
header('Location: ' . $redirect);
exit;
