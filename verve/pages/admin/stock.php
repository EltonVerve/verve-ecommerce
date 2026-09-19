<?php
require __DIR__.'/../../config/config.php'; requireAdmin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf(); $threshold=filter_var($_POST['threshold'] ?? '',FILTER_VALIDATE_INT);
    if ($threshold===false || $threshold<0 || $threshold>100000) setFlash('error','Enter a threshold between 0 and 100,000.');
    else {
        $pdo->beginTransaction();
        try { $before=lowStockThreshold($pdo); $pdo->prepare("UPDATE store_settings SET setting_value=? WHERE setting_key='low_stock_threshold'")->execute([$threshold]); auditAdmin($pdo,'stock.threshold','settings',0,['before'=>$before,'after'=>$threshold]); $pdo->commit(); setFlash('success','Stock threshold saved.'); }
        catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    }
    header('Location: stock.php'); exit;
}
$threshold=lowStockThreshold($pdo); $page=max(1,(int)($_GET['page'] ?? 1));
$stmt=$pdo->prepare('SELECT COUNT(*) FROM products WHERE is_active=1 AND stock<=?'); $stmt->execute([$threshold]); $total=(int)$stmt->fetchColumn();
$page=min($page,max(1,(int)ceil($total/50))); $offset=($page-1)*50;
$stmt=$pdo->prepare("SELECT id,name,stock FROM products WHERE is_active=1 AND stock<=? ORDER BY stock,id LIMIT 50 OFFSET $offset"); $stmt->execute([$threshold]); $products=$stmt->fetchAll();
$pageTitle='Low stock'; require __DIR__.'/../../includes/admin/admin_header.php'; require __DIR__.'/../../includes/admin/admin_flash.php';
?>
<div class="admin-topbar"><h1>Low stock</h1><span><?= $total ?> products need attention</span></div>
<form method="post" class="admin-card admin-filters"><?= csrfField() ?><label>Alert when stock is at or below<input type="number" name="threshold" min="0" max="100000" value="<?= $threshold ?>" required></label><button class="btn btn-primary">Save threshold</button></form>
<div class="admin-card"><table class="admin-table"><thead><tr><th>Product</th><th>Available</th><th></th></tr></thead><tbody><?php foreach ($products as $product): ?><tr><td><?= h($product['name']) ?></td><td><?= (int)$product['stock'] ?></td><td><a class="btn btn-outline btn-sm" href="product_form.php?id=<?= (int)$product['id'] ?>">Update stock</a></td></tr><?php endforeach; ?><?php if (!$products): ?><tr><td colspan="3">No active products are running low.</td></tr><?php endif; ?></tbody></table></div>
<div class="admin-toolbar"><?php if ($page>1): ?><a href="?page=<?= $page-1 ?>">Previous</a><?php endif; ?><?php if ($page*50<$total): ?><a href="?page=<?= $page+1 ?>">Next</a><?php endif; ?></div>
<?php require __DIR__.'/../../includes/admin/admin_footer.php'; ?>
