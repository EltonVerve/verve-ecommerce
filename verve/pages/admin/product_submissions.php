<?php
require __DIR__.'/../../config/config.php'; requireAdmin();
$owner=adminScope($pdo)==='owner';
$status=is_string($_GET['status'] ?? null)?$_GET['status']:($owner?'pending':'');
$where=[]; $params=[];
if (!$owner) { $where[]='s.author_id=?'; $params[]=$_SESSION['user_id']; }
if (in_array($status,['draft','pending','returned','approved'],true)) { $where[]='s.status=?'; $params[]=$status; }
$sql=' FROM product_submissions s JOIN products p ON p.id=s.product_id LEFT JOIN users u ON u.id=s.author_id'.($where?' WHERE '.implode(' AND ',$where):'');
$stmt=$pdo->prepare('SELECT COUNT(*)'.$sql); $stmt->execute($params); $total=(int)$stmt->fetchColumn();
$page=max(1,min((int)($_GET['page'] ?? 1),max(1,(int)ceil($total/25)))); $offset=($page-1)*25;
$stmt=$pdo->prepare('SELECT s.*,p.name,u.full_name'.$sql." ORDER BY s.updated_at DESC,s.id DESC LIMIT 25 OFFSET $offset"); $stmt->execute($params); $rows=$stmt->fetchAll();
$pageTitle=$owner?'Product approvals':'My submissions';
require __DIR__.'/../../includes/admin/admin_header.php'; require __DIR__.'/../../includes/admin/admin_flash.php';
?>
<div class="admin-topbar"><h1><?= h($pageTitle) ?></h1><?php if (!$owner): ?><a class="btn btn-primary" href="product_form.php">Add product</a><?php endif; ?></div>
<p class="hint">Drafts and pending products are hidden. Returned products can be corrected and resubmitted. Approved products can have revisions submitted without changing the current shop listing.</p>
<form method="get" class="admin-toolbar"><label>Status <select name="status"><option value="">All</option><?php foreach (['draft','pending','returned','approved'] as $value): ?><option value="<?= $value ?>" <?= $status===$value?'selected':'' ?>><?= ucfirst($value) ?></option><?php endforeach; ?></select></label><button class="btn btn-outline">Filter</button><span><?= $total ?> submissions</span></form>
<div class="admin-card"><table class="admin-table"><thead><tr><th>Product</th><th>Submitted by</th><th>Status</th><th>Updated</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $row): ?><tr><td><?= h($row['name']) ?><?= $row['target_id']?' (revision)':'' ?></td><td><?= h($row['full_name'] ?? 'Former staff') ?></td><td><?= h(ucfirst($row['status'])) ?></td><td><?= h($row['updated_at']) ?></td><td><a class="btn btn-outline btn-sm" href="product_submission.php?id=<?= (int)$row['product_id'] ?>">View</a></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5">No submissions found.</td></tr><?php endif; ?></tbody></table></div>
<div class="admin-toolbar"><?php if ($page>1): ?><a href="?<?= h(http_build_query(['status'=>$status,'page'=>$page-1])) ?>">Previous</a><?php endif; ?><?php if ($page*25<$total): ?><a href="?<?= h(http_build_query(['status'=>$status,'page'=>$page+1])) ?>">Next</a><?php endif; ?></div>
<?php require __DIR__.'/../../includes/admin/admin_footer.php'; ?>
