<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
}

verifyCsrf();

$cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
$quantity   = (int) ($_POST['quantity'] ?? 1);

try {
    updateCartItemQuantity($pdo, $cartItemId, $quantity);
} catch (RuntimeException $error) {
    setFlash('error', $error->getMessage());
}

header('Location: ' . BASE_URL . '/pages/cart.php');
exit;
