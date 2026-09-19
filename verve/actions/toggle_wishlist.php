<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/shop.php');
    exit;
}

verifyCsrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$redirect = is_string($_POST['redirect'] ?? null) && $_POST['redirect'] !== '' ? $_POST['redirect'] : (BASE_URL . '/pages/shop.php');

if (!isCustomerLoggedIn()) {
    setFlash('error', 'Please log in to save items to your wishlist.');
    header('Location: ' . BASE_URL . '/pages/login.php?redirect=' . urlencode($redirect));
    exit;
}

$product = getProductById($pdo, $productId);
if (!$product || !(int)$product['is_active']) {
    header('Location: ' . $redirect);
    exit;
}

$nowSaved = toggleWishlist($pdo, (int) $_SESSION['user_id'], $productId);
setFlash('success', $nowSaved ? $product['name'] . ' saved to your wishlist.' : $product['name'] . ' removed from your wishlist.');
header('Location: ' . $redirect);
exit;
