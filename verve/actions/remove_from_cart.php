<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
}

verifyCsrf();

$cartItemId = (int) ($_POST['cart_item_id'] ?? 0);
removeCartItem($pdo, $cartItemId);

setFlash('success', 'Item removed from your cart.');
header('Location: ' . BASE_URL . '/pages/cart.php');
exit;
