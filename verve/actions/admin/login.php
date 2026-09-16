<?php
require_once __DIR__ . '/../../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/login.php');
    exit;
}

verifyCsrf();

$email = trim(strtolower($_POST['email'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$user = attemptLogin($pdo, $email, $password);

if (!$user || $user['role'] !== 'admin') {
    setFlash('error', 'Incorrect email or password.');
    header('Location: ' . BASE_URL . '/pages/admin/login.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_name'] = $user['full_name'];
$_SESSION['user_role'] = $user['role'];

header('Location: ' . BASE_URL . '/pages/admin/dashboard.php');
exit;
