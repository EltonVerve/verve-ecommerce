<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/database.php';
$names = ['headphones', 'smartwatch', 'speaker', 'denim-jacket', 'sneakers', 'coffee-set', 'pillow-cover', 'serum', 'yoga-mat'];
$pdo->beginTransaction();
try {
    foreach ($names as $name) {
        $image = $name . '.png';
        if (!is_file(__DIR__ . '/../public/assets/products/' . $image)) throw new RuntimeException('Missing photo: ' . $image);
        $pdo->prepare('UPDATE products SET image = ? WHERE image = ?')->execute([$image, $name . '.jpg']);
        $pdo->prepare('UPDATE product_images SET filename = ? WHERE filename = ?')->execute([$image, $name . '.jpg']);
    }
    $pdo->commit();
    foreach ($pdo->query('SELECT name, image FROM products') as $product) {
        echo $product['name'] . ': ' . (is_file(__DIR__ . '/../public/assets/products/' . $product['image']) ? 'photo ready' : 'MISSING PHOTO') . PHP_EOL;
    }
} catch (Throwable $error) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $error;
}
