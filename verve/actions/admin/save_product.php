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
$staffProduct = adminScope($pdo) !== 'owner';
$productReturnPage = $staffProduct ? 'product_submissions.php' : 'products.php';

$productId = !empty($_POST['product_id']) ? (int) $_POST['product_id'] : null;
$existing = $productId ? getProductById($pdo, $productId) : null;
if ($productId && !$existing) { http_response_code(404); exit('Product not found.'); }
$submission = $productId ? productSubmission($pdo, $productId) : null;
try { if ($productId) assertSubmissionEditable($pdo, $submission); }
catch (RuntimeException $e) { http_response_code(403); exit(h($e->getMessage())); }
$existingImages = $productId ? productGalleryFiles($existing, getProductImages($pdo, $productId)) : [];

$name = trim($_POST['name'] ?? '');
$categoryId = (int) ($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$price = (float) ($_POST['price'] ?? 0);
$compareAtPrice = trim($_POST['compare_at_price'] ?? '') !== '' ? (float) $_POST['compare_at_price'] : null;
$sku = trim($_POST['sku'] ?? '') ?: null;
$stock = (int) ($_POST['stock'] ?? 0);
$isFeatured = isset($_POST['is_featured']) ? 1 : 0;
$isActive = isset($_POST['is_active']) ? 1 : 0;
if ($staffProduct || ($submission && ($submission['status'] !== 'approved' || $submission['target_id']))) { $isActive=0; $isFeatured=0; }
$submitForReview = $staffProduct && ($_POST['workflow'] ?? '') === 'submit';

$errors = [];
if (strlen($name) < 2) $errors[] = 'Please enter a product name.';
if ($price < 0) $errors[] = 'Price cannot be negative.';
if ($categoryId <= 0) $errors[] = 'Please choose a category.';
if ($stock < 0) $errors[] = 'Stock cannot be negative.';
if (mb_strlen($name)>150 || mb_strlen($sku ?? '')>60 || !is_finite($price) || $price>99999999.99 || $stock>2147483647 || ($compareAtPrice!==null && (!is_finite($compareAtPrice) || $compareAtPrice<0 || $compareAtPrice>99999999.99))) $errors[]='Product details exceed the allowed limits.';
$categoryCheck=$pdo->prepare('SELECT id FROM categories WHERE id=?'); $categoryCheck->execute([$categoryId]);
if (!$categoryCheck->fetchColumn()) $errors[]='Choose an existing category.';
if ($submitForReview && $description==='') $errors[]='Add a description before submitting for review.';

if ($errors) {
    setFlash('error', $errors[0]);
    header('Location: ' . BASE_URL . '/pages/admin/product_form.php' . ($productId ? "?id=$productId" : ''));
    exit;
}

// ---- Image uploads (optional — keeps the existing image if none provided) ----
$imageFilename = $existing['image'] ?? null;
$uploadedImages = [];
$uploadsCommitted = false;
register_shutdown_function(function () use ($pdo, &$uploadedImages, &$uploadsCommitted) {
    if (!$uploadsCommitted) foreach ($uploadedImages as $file) {
        try { removeUnreferencedProductUpload($pdo, $file); } catch (Throwable $error) { error_log('Upload cleanup failed.'); }
    }
});
$imageFiles = $_FILES['images'] ?? null;

if ($imageFiles && is_array($imageFiles['name'])) {
    $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    foreach ($imageFiles['name'] as $index => $originalName) {
        if ($imageFiles['error'][$index] === UPLOAD_ERR_NO_FILE) continue;
        $tmpName = $imageFiles['tmp_name'][$index];
        $mimeType = is_string($tmpName) && is_file($tmpName) ? @mime_content_type($tmpName) : false;
        if ($imageFiles['error'][$index] !== UPLOAD_ERR_OK || !isset($allowedTypes[$mimeType]) || $imageFiles['size'][$index] > 5 * 1024 * 1024) {
            setFlash('error', 'Each image must be a JPG, PNG or WEBP under 5MB.');
            header('Location: ' . BASE_URL . '/pages/admin/product_form.php' . ($productId ? "?id=$productId" : ''));
            exit;
        }
        $filename = 'product-' . bin2hex(random_bytes(8)) . '.' . $allowedTypes[$mimeType];
        if (move_uploaded_file($tmpName, __DIR__ . '/../../public/assets/products/' . $filename)) {
            $uploadedImages[$index] = $filename;
        } else {
            setFlash('error', 'Unable to upload an image. Please try again.');
            header('Location: ' . BASE_URL . '/pages/admin/product_form.php' . ($productId ? "?id=$productId" : ''));
            exit;
        }
    }
}
try {
    $removedImages = json_decode(is_string($_POST['removed_images'] ?? null) ? $_POST['removed_images'] : '[]', true);
    if (!is_array($removedImages)) throw new RuntimeException('Invalid image removal.');
    $orderedImages = orderedProductGallery($existingImages, $uploadedImages, $_POST['image_order'] ?? null, $removedImages);
    foreach ($uploadedImages as $filename) {
        $info = getimagesize(__DIR__ . '/../../public/assets/products/' . $filename);
        if (!$info || $info[0] * $info[1] > 12000000) throw new RuntimeException('Images must be valid and no larger than 12 megapixels.');
        optimizeUploadedProductImage($filename);
    }
} catch (RuntimeException $error) {
    setFlash('error', $error->getMessage());
    header('Location: ' . BASE_URL . '/pages/admin/product_form.php' . ($productId ? "?id=$productId" : ''));
    exit;
}
$imageFilename = $orderedImages[0] ?? null;
if ($submitForReview && !$imageFilename) {
    setFlash('error','Add at least one photo before submitting for review.');
    header('Location: ' . BASE_URL . '/pages/admin/product_form.php' . ($productId ? "?id=$productId" : '')); exit;
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

try {
$pdo->beginTransaction();
if ($productId) {
    $lockedSubmission=productSubmission($pdo,$productId,true);
    assertSubmissionEditable($pdo,$lockedSubmission);
    if ($lockedSubmission && (int)($_POST['submission_version'] ?? 0)!==(int)$lockedSubmission['version']) throw new RuntimeException('This submission changed. Reload it before saving.');
    if ($lockedSubmission && ($lockedSubmission['status']!=='approved' || $lockedSubmission['target_id'])) $data['is_active']=0;
}
$auditBefore = null;
if ($productId) {
    $stmt = $pdo->prepare('SELECT price,stock FROM products WHERE id=? FOR UPDATE');
    $stmt->execute([$productId]); $auditBefore = $stmt->fetch();
}
if ($productId) {
    updateProduct($pdo, $productId, $data);
} else {
    $productId = createProduct($pdo, $data);
    if ($staffProduct) $pdo->prepare('INSERT INTO product_submissions (product_id,author_id) VALUES (?,?)')->execute([$productId,$_SESSION['user_id']]);
}

$pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
foreach ($orderedImages as $i => $filename) {
    addProductImage($pdo, $productId, $filename, $i);
}

// ---- Variant groups: rebuild from the form each save ----
$groups = [];
foreach (($_POST['option_groups'] ?? []) as $group) {
    $groups[] = ['name' => $group['name'] ?? '', 'values' => $group['values'] ?? []];
}
replaceOptionGroups($pdo, $productId, $groups);
if ($staffProduct) {
    $pdo->prepare('UPDATE product_submissions SET status=?,version=version+1 WHERE product_id=?')->execute([$submitForReview?'pending':'draft',$productId]);
    auditAdmin($pdo,$submitForReview?'product.submitted':'product.draft','product',$productId);
} elseif ($submission) {
    $pdo->prepare('UPDATE product_submissions SET version=version+1 WHERE product_id=?')->execute([$productId]);
}
auditAdmin($pdo, $existing ? 'product.updated' : 'product.created', 'product', $productId, ['before'=>$auditBefore, 'after'=>['price'=>$data['price'],'stock'=>$data['stock']]]);
$pdo->commit();
$uploadsCommitted = true;
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Product save failed: ' . $error->getMessage());
    setFlash('error', $error instanceof RuntimeException && !($error instanceof PDOException) ? $error->getMessage() : 'Unable to save this product. Please try again.');
    header('Location: ' . BASE_URL . '/pages/admin/' . $productReturnPage);
    exit;
}

foreach ($removedImages as $filename) removeUnreferencedProductUpload($pdo, $filename);
setFlash('success', $uploadedImages && !function_exists('imagewebp') ? 'Product saved. Automatic optimization requires GD/WebP on the server; original photos are available.' : 'Product saved.');
if ($staffProduct) setFlash('success',$submitForReview?'Product submitted for admin approval.':'Draft saved. It is hidden from the store.');
header('Location: ' . BASE_URL . '/pages/admin/' . $productReturnPage);
exit;
