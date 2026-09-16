<?php
/**
 * ADD TO CART ACTION
 * ---------------------------------------------------------
 * Handles the POST from the product page's buy form, and the
 * "Add to cart" quick-add button on product cards.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/shop.php');
    exit;
}

verifyCsrf();

$productId = (int) ($_POST['product_id'] ?? 0);
$quantity  = max(1, (int) ($_POST['quantity'] ?? 1));
$options   = is_array($_POST['options'] ?? null) ? $_POST['options'] : [];
$redirect  = is_string($_POST['redirect'] ?? null) && $_POST['redirect'] !== '' ? $_POST['redirect'] : (BASE_URL . '/pages/cart.php');

$product = getProductById($pdo, $productId);
if (!$product) {
    setFlash('error', "That product couldn't be found.");
    header('Location: ' . BASE_URL . '/pages/shop.php');
    exit;
}

// A "quick add" from a listing page has no options selected. If the
// product actually needs a variant choice (e.g. Size), send the
// shopper to the product page instead of guessing for them.
$requiresOptions = (bool) getOptionGroupsForProduct($pdo, $productId);
if ($requiresOptions && !$options) {
    header('Location: ' . BASE_URL . '/pages/product.php?slug=' . urlencode($product['slug']));
    exit;
}

$cleanOptions = [];
foreach ($options as $groupId => $valueId) {
    if (is_scalar($valueId)) $cleanOptions[(int) $groupId] = (int) $valueId;
}

try {
    addToCart($pdo, $productId, $cleanOptions, $quantity);
} catch (RuntimeException $error) {
    setFlash('error', $error->getMessage());
    header('Location: ' . BASE_URL . '/pages/product.php?slug=' . urlencode($product['slug']));
    exit;
}

setFlash('success', $product['name'] . ' was added to your cart.');
header('Location: ' . $redirect);
exit;
