<?php
function productGalleryFiles(?array $product, array $images): array {
    $files = [];
    if (!empty($product['image'])) $files[] = $product['image'];
    foreach ($images as $image) if (!in_array($image['filename'], $files, true)) $files[] = $image['filename'];
    return $files;
}

function orderedProductGallery(array $existing, array $uploads, $submitted, array $removed = []): array {
    foreach ($removed as $file) if (!is_string($file) || !in_array($file, $existing, true)) throw new RuntimeException('Invalid image removal.');
    $existing = array_values(array_diff($existing, $removed));
    $allowed = [];
    // Filenames are mapped exclusively from this product and successful uploads.
    foreach ($existing as $file) $allowed['existing:' . $file] = $file;
    foreach ($uploads as $index => $file) $allowed['new:' . $index] = $file;
    if ($submitted === null) return array_values($allowed);
    if (!is_string($submitted)) throw new RuntimeException('Invalid image order.');
    $keys = json_decode($submitted, true);
    if (!is_array($keys) || count($keys) !== count($allowed)) throw new RuntimeException('Images changed. Reload the product and try again.');
    $ordered = [];
    foreach ($keys as $key) {
        if (!is_string($key) || !array_key_exists($key, $allowed)) throw new RuntimeException('Invalid image selection.');
        $ordered[] = $allowed[$key];
        unset($allowed[$key]);
    }
    return $ordered;
}

function optimizeUploadedProductImage(string $filename): bool {
    if (!function_exists('imagewebp')) return false;
    $root = __DIR__ . '/../public/assets/products/';
    $info = getimagesize($root . $filename);
    if (!$info || $info[0] * $info[1] > 12000000) throw new RuntimeException('Image dimensions are too large. Maximum 12 megapixels.');
    $source = imagecreatefromstring(file_get_contents($root . $filename));
    if (!$source) throw new RuntimeException('Cannot read this image.');
    if (!is_dir($root . 'optimized')) mkdir($root . 'optimized', 0755);
    try {
        foreach ([600,1600] as $width) {
            $scale = min(1, $width / $info[0]);
            $image = imagecreatetruecolor(max(1,(int) round($info[0]*$scale)), max(1,(int) round($info[1]*$scale)));
            imagealphablending($image, false); imagesavealpha($image, true);
            imagecopyresampled($image,$source,0,0,0,0,imagesx($image),imagesy($image),$info[0],$info[1]);
            $ok = imagewebp($image,$root . 'optimized/' . pathinfo($filename, PATHINFO_FILENAME) . '-' . $width . '.webp',85);
            imagedestroy($image);
            if (!$ok) throw new RuntimeException('Image optimization failed.');
        }
    } finally { imagedestroy($source); }
    return true;
}

function removeUnreferencedProductUpload(PDO $pdo, string $filename): void {
    // Only generated uploads, never seed assets or paths supplied by a browser.
    if (!preg_match('/^product-[a-f0-9]{16}\.(jpg|png|webp)$/D', $filename)) return;
    foreach (['SELECT 1 FROM products WHERE image = ? LIMIT 1','SELECT 1 FROM product_images WHERE filename = ? LIMIT 1','SELECT 1 FROM categories WHERE image = ? LIMIT 1'] as $sql) {
        $stmt = $pdo->prepare($sql); $stmt->execute([$filename]); if ($stmt->fetchColumn()) return;
    }
    $root = realpath(__DIR__ . '/../public/assets/products');
    if (!$root) return;
    $paths = [$root . DIRECTORY_SEPARATOR . $filename];
    foreach ([600,1600] as $size) $paths[] = $root . '/optimized/' . pathinfo($filename,PATHINFO_FILENAME) . '-' . $size . '.webp';
    foreach ($paths as $path) {
        $resolved = realpath($path);
        if ($resolved && str_starts_with($resolved, $root . DIRECTORY_SEPARATOR) && is_file($resolved)) @unlink($resolved);
    }
}
