<?php
require __DIR__ . '/../../config/config.php';
requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }
verifyCsrf();
$id = (int) ($_POST['order_id'] ?? 0);
$note = is_string($_POST['note'] ?? null) ? $_POST['note'] : '';
$key = is_string($_POST['request_key'] ?? null) ? $_POST['request_key'] : '';
try {
    addOrderStaffNote($pdo, $id, $note, $key);
    setFlash('success', 'Internal note saved.');
} catch (RuntimeException $e) {
    setFlash('error', $e instanceof PDOException ? 'Unable to save the note.' : $e->getMessage());
}
header('Location: ' . BASE_URL . '/pages/admin/order_detail.php?id=' . $id . '#staff-notes');
exit;
