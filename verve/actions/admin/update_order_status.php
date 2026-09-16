<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/orders.php');
    exit;
}
verifyCsrf();

$orderId = (int) ($_POST['order_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
updateOrderStatus($pdo, $orderId, $status);

setFlash('success', 'Order status updated.');
header('Location: ' . BASE_URL . '/pages/admin/order_detail.php?id=' . $orderId);
exit;
