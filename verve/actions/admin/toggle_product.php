<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/products.php');
    exit;
}
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);
$isActive = (bool) ($_POST['is_active'] ?? false);
toggleProductActive($pdo, $id, $isActive);

header('Location: ' . BASE_URL . '/pages/admin/products.php');
exit;
