<?php
require_once __DIR__ . '/../config/config.php';

if (!isCustomerLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/account.php');
    exit;
}

verifyCsrf();

$userId = (int) $_SESSION['user_id'];
$fullName = trim($_POST['full_name'] ?? '');
$email = trim(strtolower($_POST['email'] ?? ''));
$phone = trim($_POST['phone'] ?? '');
$currentPassword = (string) ($_POST['current_password'] ?? '');
$newPassword = (string) ($_POST['new_password'] ?? '');

$user = findUserById($pdo, $userId);

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    setFlash('error', 'Your current password was incorrect.');
} elseif (strlen($fullName) < 2) {
    setFlash('error', 'Please enter your full name.');
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', 'Please enter a valid email address.');
} elseif (emailBelongsToAnotherUser($pdo, $email, $userId)) {
    setFlash('error', 'That email address is already in use.');
} elseif ($newPassword !== '' && strlen($newPassword) < 8) {
    setFlash('error', 'New password must be at least 8 characters.');
} else {
    updateUserProfile($pdo, $userId, $fullName, $email, $phone);
    if ($newPassword !== '') {
        updateUserPassword($pdo, $userId, $newPassword);
    }
    $_SESSION['user_name'] = $fullName;
    setFlash('success', 'Your account details were updated.');
}

header('Location: ' . BASE_URL . '/pages/edit_account.php');
exit;
