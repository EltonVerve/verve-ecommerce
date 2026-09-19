<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/products.php');
    exit;
}
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);
$pdo->beginTransaction();
try {
    $stmt=$pdo->prepare('SELECT name,price,stock FROM products WHERE id=? FOR UPDATE'); $stmt->execute([$id]); $before=$stmt->fetch();
    deleteProduct($pdo, $id);
    if ($before) auditAdmin($pdo,'product.deleted','product',$id,['before'=>$before]);
    $pdo->commit();
} catch (Throwable $e) { $pdo->rollBack(); throw $e; }

setFlash('success', 'Product deleted.');
header('Location: ' . BASE_URL . '/pages/admin/products.php');
exit;
