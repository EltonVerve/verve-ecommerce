<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/categories.php');
    exit;
}
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);
if (!deleteCategory($pdo, $id)) {
    setFlash('error', 'That category still has products in it. Move or delete them first.');
} else {
    setFlash('success', 'Category deleted.');
}

header('Location: ' . BASE_URL . '/pages/admin/categories.php');
exit;
