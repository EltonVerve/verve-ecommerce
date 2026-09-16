<?php
/**
 * SAVE PRODUCT ACTION
 * ---------------------------------------------------------
 * Handles BOTH creating a new product and updating an existing
 * one — if product_id is present in the form, it's an update.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/products.php');
    exit;
}

verifyCsrf();

$productId = !empty($_POST['product_id']) ? (int) $_POST['product_id'] : null;
$existing = $productId ? getProductById($pdo, $productId) : null;

$name = trim($_POST['name'] ?? '');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$compareAtPrice = trim($_POST['compare_at_price'] ?? '') !== '' ? (float) $_POST['compare_at_price'] : null;
$sku = trim($_POST['sku'] ?? '') ?: null;
$stock = (int) ($_POST['stock'] ?? 0);
$isFeatured = isset($_POST['is_featured']) ? 1 : 0;
$isActive = isset($_POST['is_active']) ? 1 : 0;

$errors = [];
if (strlen($name) < 2) $errors[] = 'Please enter a product name.';
if ($price < 0) $errors[] = 'Price cannot be negative.';
if ($categoryId <= 0) $errors[] = 'Please choose a category.';
if ($stock < 0) $errors[] = 'Stock cannot be negative.';

if ($errors) {
    setFlash('error', $errors[0]);
    header('Location: ' . BASE_URL . '/pages/admin/product_form.php' . ($productId ? "?id=$productId" : ''));
    exit;
}

// ---- Image uploads (optional — keeps the existing image if none provided) ----
$imageFilename = $existing['image'] ?? null;
$uploadedImages = [];
$imageFiles = $_FILES['images'] ?? null;

if ($imageFiles && is_array($imageFiles['name'])) {
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($imageFiles['name'] as $index => $originalName) {
        if ($imageFiles['error'][$index] === UPLOAD_ERR_NO_FILE) continue;
        $tmpName = $imageFiles['tmp_name'][$index];
        $mimeType = @mime_content_type($tmpName);
        if ($imageFiles['error'][$index] !== UPLOAD_ERR_OK || !isset($allowedTypes[$mimeType]) || $imageFiles['size'][$index] > 5 * 1024 * 1024) {
            setFlash('error', 'Each image must be a JPG, PNG or WEBP under 5MB.');
            header('Location: ' . BASE_URL . '/pages/admin/product_form.php' . ($productId ? "?id=$productId" : ''));
            exit;
        }
        $filename = 'product-' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mimeType];
        if (move_uploaded_file($tmpName, __DIR__ . '/../../public/assets/products/' . $filename)) {
            $uploadedImages[] = $filename;
        }
    }
}
if ($imageFilename === null && $uploadedImages) {
    $imageFilename = $uploadedImages[0];
}

// ---- Slug: keep existing on edit unless the name changed ----
if ($existing && $existing['name'] === $name) {
    $slug = $existing['slug'];
} else {
    $slug = generateUniqueSlug($pdo, $name, $productId);
}

$data = [
    'category_id' => $categoryId,
    'name' => $name,
    'slug' => $slug,
    'description' => $description,
    'price' => $price,
    'compare_at_price' => $compareAtPrice,
    'sku' => $sku,
    'image' => $imageFilename,
    'stock' => $stock,
    'is_featured' => $isFeatured,
    'is_active' => $isActive,
];

if ($productId) {
    updateProduct($pdo, $productId, $data);
} else {
    $productId = createProduct($pdo, $data);
}

foreach ($uploadedImages as $i => $filename) {
    addProductImage($pdo, $productId, $filename, $i);
}

// ---- Variant groups: rebuild from the form each save ----
$groups = [];
foreach (($_POST['option_groups'] ?? []) as $group) {
    $groups[] = ['name' => $group['name'] ?? '', 'values' => $group['values'] ?? []];
}
replaceOptionGroups($pdo, $productId, $groups);

setFlash('success', 'Product saved.');
header('Location: ' . BASE_URL . '/pages/admin/products.php');
exit;
