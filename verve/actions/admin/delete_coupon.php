<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/coupons.php');
    exit;
}

verifyCsrf();

$id = !empty($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    setFlash('error', 'Coupon not found.');
    header('Location: ' . BASE_URL . '/pages/admin/coupons.php');
    exit;
}

deleteCoupon($pdo, $id);
setFlash('success', 'Coupon deleted.');

header('Location: ' . BASE_URL . '/pages/admin/coupons.php');
exit;
