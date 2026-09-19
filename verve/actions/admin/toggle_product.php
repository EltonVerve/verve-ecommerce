<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/products.php');
    exit;
}
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);
$isActive = (bool) ($_POST['is_active'] ?? false);
$pdo->beginTransaction();
try {
    $stmt=$pdo->prepare('SELECT is_active FROM products WHERE id=? FOR UPDATE'); $stmt->execute([$id]); $before=$stmt->fetchColumn();
    toggleProductActive($pdo, $id, $isActive);
    if ($before !== false) auditAdmin($pdo,'product.visibility','product',$id,['before'=>(bool)$before,'after'=>$isActive]);
    $pdo->commit();
} catch (Throwable $e) { $pdo->rollBack(); throw $e; }

header('Location: ' . BASE_URL . '/pages/admin/products.php');
exit;
