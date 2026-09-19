<?php
require __DIR__ . '/../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();
$id = (int) ($_POST['order_id'] ?? 0);
$input = static fn($key) => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
try {
    recordOrderRefund($pdo,$id,$input('amount'),$input('reason'),$input('reference'),$input('request_key'));
    setFlash('success','Refund recorded.');
} catch (RuntimeException $e) { setFlash('error',$e instanceof PDOException ? 'Unable to record refund.' : $e->getMessage()); }
header('Location: ' . BASE_URL . '/pages/admin/order_detail.php?id=' . $id);
exit;
