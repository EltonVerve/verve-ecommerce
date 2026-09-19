<?php
require __DIR__ . '/../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();
$id = (int) ($_POST['order_id'] ?? 0);
$input = static fn($key) => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
try {
    saveOrderDispatch($pdo,$id,$input('rider'),$input('phone'),$input('reference'),$input('dispatched_at'));
    setFlash('success','Delivery assignment saved.');
} catch (RuntimeException $e) { setFlash('error',$e instanceof PDOException ? 'Unable to save delivery assignment.' : $e->getMessage()); }
header('Location: ' . BASE_URL . '/pages/admin/order_detail.php?id=' . $id);
exit;
