<?php
/**
 * REGISTER ACTION
 * ---------------------------------------------------------
 * Handles the POST from pages/register.php. Never shows HTML —
 * it either redirects back with an error, or creates the
 * account and redirects into the site as a logged-in user.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/register.php');
    exit;
}

verifyCsrf();

$fullName = trim($_POST['full_name'] ?? '');
$email    = trim(strtolower($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$confirm  = (string) ($_POST['password_confirm'] ?? '');

$errors = [];
if (strlen($fullName) < 2) $errors[] = 'Please enter your full name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
if ($password !== $confirm) $errors[] = "Passwords don't match.";
if (empty($errors) && findUserByEmail($pdo, $email)) $errors[] = 'An account with that email already exists.';

if (!empty($errors)) {
    setFlash('error', $errors[0]);
    header('Location: ' . BASE_URL . '/pages/register.php');
    exit;
}

$userId = createUser($pdo, $fullName, $email, $password);

if (!empty($_SESSION['guest_id'])) {
    mergeGuestCartIntoUser($pdo, $userId, $_SESSION['guest_id']);
}

$_SESSION['user_id']   = $userId;
$_SESSION['user_name'] = $fullName;
$_SESSION['user_role'] = 'customer';
session_regenerate_id(true);
rememberCustomer($pdo, findUserById($pdo, $userId));

setFlash('success', 'Welcome to ' . SITE_NAME . ', ' . $fullName . '!');
header('Location: ' . BASE_URL . '/pages/account.php');
exit;
