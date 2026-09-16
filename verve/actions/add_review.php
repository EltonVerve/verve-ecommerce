<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/shop.php');
    exit;
}

verifyCsrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$authorName = trim((string) ($_POST['author_name'] ?? ''));
$rating = (int) ($_POST['rating'] ?? 0);
$comment = trim((string) ($_POST['comment'] ?? ''));

$product = getProductById($pdo, $productId);
if (!$product) {
    header('Location: ' . BASE_URL . '/pages/shop.php');
    exit;
}
$redirect = BASE_URL . '/pages/product.php?slug=' . urlencode($product['slug']);

if ($authorName === '' || $rating < 1 || $rating > 5) {
    setFlash('error', 'Please enter your name and a rating between 1 and 5.');
    header('Location: ' . $redirect . '#write-review');
    exit;
}

createReview($pdo, $productId, isCustomerLoggedIn() ? (int) $_SESSION['user_id'] : null, mb_substr($authorName, 0, 120), $rating, mb_substr($comment, 0, 2000));

setFlash('success', 'Thanks for your review!');
header('Location: ' . $redirect . '#customer-reviews');
exit;
