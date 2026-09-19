<?php
require __DIR__ . '/../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();
$id = (int) ($_POST['order_id'] ?? 0);
$input = static fn($key) => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
try {
    $quantity = filter_var($_POST['quantity'] ?? '', FILTER_VALIDATE_INT);
    recordReceivedReturn($pdo,$id,(int)($_POST['item_id'] ?? 0),$quantity === false ? 0 : $quantity,$input('condition'),isset($_POST['restock']),$input('reason'),$input('request_key'));
    setFlash('success','Received return recorded.');
} catch (RuntimeException $e) { setFlash('error',$e instanceof PDOException ? 'Unable to record return.' : $e->getMessage()); }
header('Location: ' . BASE_URL . '/pages/admin/order_detail.php?id=' . $id);
exit;
