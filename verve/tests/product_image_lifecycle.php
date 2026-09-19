<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
if (!function_exists('imagewebp')) throw new RuntimeException('Run with GD enabled: php -d extension=gd tests/product_image_lifecycle.php');
$filename = 'product-' . bin2hex(random_bytes(8)) . '.png';
$root = __DIR__ . '/../public/assets/products/';
$productId = null;
try {
    $image = imagecreatetruecolor(100, 80); imagepng($image, $root . $filename); imagedestroy($image);
    if (!optimizeUploadedProductImage($filename)) throw new RuntimeException('Optimization failed');
    $variant = $root . 'optimized/' . pathinfo($filename, PATHINFO_FILENAME) . '-600.webp';
    if (!is_file($variant) || getimagesize($variant)[0] !== 100) throw new RuntimeException('Variant missing or image upscaled');
    $category = $pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn();
    $pdo->prepare('INSERT INTO products (category_id,name,slug,price,image) VALUES (?, ?, ?, 1, ?)')->execute([$category,'Image lifecycle test',$filename,$filename]);
    $productId = (int) $pdo->lastInsertId();
    removeUnreferencedProductUpload($pdo,$filename);
    if (!is_file($root . $filename)) throw new RuntimeException('Referenced image was deleted');
    $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$productId]); $productId = null;
    removeUnreferencedProductUpload($pdo,$filename);
    clearstatcache();
    if (is_file($root . $filename) || is_file($variant)) throw new RuntimeException('Unreferenced upload or variant was not cleaned');
    echo "PASS: automatic WebP conversion, no upscaling, reference protection, original and variant cleanup.\n";
} finally {
    if ($productId) $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$productId]);
    removeUnreferencedProductUpload($pdo,$filename);
}
