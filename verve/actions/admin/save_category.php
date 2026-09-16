<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/categories.php');
    exit;
}
verifyCsrf();

$id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (strlen($name) < 2) {
    setFlash('error', 'Please enter a category name.');
    header('Location: ' . BASE_URL . '/pages/admin/categories.php');
    exit;
}

$slug = generateUniqueCategorySlug($pdo, $name, $id);

if ($id) {
    updateCategory($pdo, $id, $name, $slug, $description);
    setFlash('success', 'Category updated.');
} else {
    createCategory($pdo, $name, $slug, $description);
    setFlash('success', 'Category created.');
}

header('Location: ' . BASE_URL . '/pages/admin/categories.php');
exit;
