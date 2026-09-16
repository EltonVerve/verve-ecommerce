<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/forgot_password.php');
    exit;
}

verifyCsrf();

$token    = (string) ($_POST['token'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$confirm  = (string) ($_POST['password_confirm'] ?? '');

$reset = findValidReset($pdo, $token);

if (!$reset) {
    setFlash('error', 'That reset link is no longer valid. Please request a new one.');
    header('Location: ' . BASE_URL . '/pages/forgot_password.php');
    exit;
}

if (strlen($password) < 8) {
    setFlash('error', 'Password must be at least 8 characters.');
    header('Location: ' . BASE_URL . '/pages/reset_password.php?token=' . urlencode($token));
    exit;
}

if ($password !== $confirm) {
    setFlash('error', "Passwords don't match.");
    header('Location: ' . BASE_URL . '/pages/reset_password.php?token=' . urlencode($token));
    exit;
}

updateUserPassword($pdo, $reset['user_id'], $password);
deleteResetToken($pdo, $token);

setFlash('success', 'Your password has been updated. You can log in now.');
header('Location: ' . BASE_URL . '/pages/login.php');
exit;
