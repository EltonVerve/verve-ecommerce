<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/categories.php');
    exit;
}
verifyCsrf();

$id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$existing = $id ? getCategoryById($pdo, $id) : null;
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if (strlen($name) < 2) {
    setFlash('error', 'Please enter a category name.');
    header('Location: ' . BASE_URL . '/pages/admin/categories.php');
    exit;
}

$slug = generateUniqueCategorySlug($pdo, $name, $id);
$imageFilename = $existing['image'] ?? null;

if (!empty($_FILES['image']['name'])) {
    $tmpName = $_FILES['image']['tmp_name'] ?? '';
    $mimeType = is_string($tmpName) && is_file($tmpName) ? @mime_content_type($tmpName) : false;
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if ($mimeType === false || !isset($allowedTypes[$mimeType]) || ($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
        setFlash('error', 'Please upload a JPG, PNG, or WEBP image under 5MB.');
        header('Location: ' . BASE_URL . '/pages/admin/categories.php' . ($id ? '?edit=' . $id : ''));
        exit;
    }

    $extension = $allowedTypes[$mimeType];
    $imageFilename = 'category-' . $slug . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
    $targetPath = __DIR__ . '/../../public/assets/products/' . $imageFilename;

    if (!move_uploaded_file($tmpName, $targetPath)) {
        setFlash('error', 'Unable to upload the category image. Please try again.');
        header('Location: ' . BASE_URL . '/pages/admin/categories.php' . ($id ? '?edit=' . $id : ''));
        exit;
    }
} elseif (!$id && ($imageFilename === null || $imageFilename === '')) {
    $fallbackImage = 'category-' . $slug . '.png';
    if (!is_file(__DIR__ . '/../../public/assets/products/' . $fallbackImage)) {
        setFlash('error', 'Please upload a main photo for this category.');
        header('Location: ' . BASE_URL . '/pages/admin/categories.php');
        exit;
    }
    $imageFilename = $fallbackImage;
} elseif ($id && ($imageFilename === null || $imageFilename === '')) {
    $fallbackImage = 'category-' . $slug . '.png';
    if (is_file(__DIR__ . '/../../public/assets/products/' . $fallbackImage)) {
        $imageFilename = $fallbackImage;
    }
}

if ($id) {
    updateCategory($pdo, $id, $name, $slug, $description, $imageFilename);
    setFlash('success', 'Category updated.');
} else {
    createCategory($pdo, $name, $slug, $description, $imageFilename);
    setFlash('success', 'Category created.');
}

header('Location: ' . BASE_URL . '/pages/admin/categories.php');
exit;
