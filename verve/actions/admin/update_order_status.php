<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/orders.php');
    exit;
}
verifyCsrf();

$orderId = (int) ($_POST['order_id'] ?? 0);
$status = is_string($_POST['status'] ?? null) ? trim($_POST['status']) : '';
$type = ($_POST['operation'] ?? '') === 'payment' ? 'payment' : 'delivery';
try {
    changeOrderOperation($pdo, $orderId, $type, $status, (int) $_SESSION['user_id']);
    setFlash('success', 'Order updated.');
} catch (RuntimeException $error) { setFlash('error', $error instanceof PDOException ? 'Unable to update the order.' : $error->getMessage()); }
header('Location: ' . BASE_URL . '/pages/admin/order_detail.php?id=' . $orderId);
exit;
