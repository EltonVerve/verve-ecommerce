<?php
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
}

verifyCsrf();

$code = trim((string) ($_POST['coupon_code'] ?? ''));

if ($code === '') {
    unset($_SESSION['coupon_code']);
    setFlash('success', 'Coupon removed.');
} else {
    $coupon = findValidCoupon($pdo, $code);
    if (!$coupon) {
        setFlash('error', 'That coupon code is invalid or has expired.');
    } else {
        $_SESSION['coupon_code'] = $coupon['code'];
        setFlash('success', 'Coupon "' . $coupon['code'] . '" applied.');
    }
}

header('Location: ' . BASE_URL . '/pages/cart.php');
exit;
