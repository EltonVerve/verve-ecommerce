<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/messages.php');
    exit;
}
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);
$status = trim($_POST['status'] ?? '');
updateContactMessageStatus($pdo, $id, $status);

setFlash('success', 'Message status updated.');
header('Location: ' . BASE_URL . '/pages/admin/message_detail.php?id=' . $id);
exit;
