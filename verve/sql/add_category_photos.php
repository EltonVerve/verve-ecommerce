<?php
// Run once with PHP CLI; safe to repeat without replacing existing photos.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/database.php';
$categories = [
    ['Electronics', 'electronics', 'Phones, audio, and everyday gadgets'],
    ['Fashion', 'fashion', 'Clothing, shoes and accessories'],
    ['Home & Living', 'home-living', 'Furniture, decor and kitchenware'],
    ['Beauty & Personal Care', 'beauty', 'Skincare, haircare and grooming'],
    ['Sports & Outdoors', 'sports', 'Fitness gear and outdoor equipment'],
    ['Books & Stationery', 'books-stationery', 'Good reads, notebooks and desk essentials'],
    ['Bags & Travel', 'bags-travel', 'Backpacks, luggage and travel essentials'],
    ['Toys & Games', 'toys-games', 'Playtime favourites and games to share'],
];
$pdo->beginTransaction();
try {
    foreach ($categories as [$name, $slug, $description]) {
        $image = 'category-' . $slug . '.png';
        if (!is_file(__DIR__ . '/../public/assets/products/' . $image)) throw new RuntimeException('Missing category image: ' . $image);
        $stmt = $pdo->prepare('INSERT INTO categories (name, slug, description, image) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE image = IF(image IS NULL OR image = "", VALUES(image), image)');
        $stmt->execute([$name, $slug, $description, $image]);
    }
    $pdo->commit();
    echo "Eight categories ready with photos.\n";
} catch (Throwable $error) {
    $pdo->rollBack();
    throw $error;
}
