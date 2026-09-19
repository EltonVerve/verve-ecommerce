<?php
require __DIR__.'/../../config/config.php'; requireAdmin();
$action=is_string($_GET['action'] ?? null)?mb_substr(trim($_GET['action']),0,80):'';
$actor=max(0,(int)($_GET['actor'] ?? 0)); $page=max(1,(int)($_GET['page'] ?? 1)); $where=[]; $params=[];
if ($action!=='') { $where[]='a.action=?'; $params[]=$action; }
if ($actor) { $where[]='a.actor_id=?'; $params[]=$actor; }
$sql=' FROM admin_audit a LEFT JOIN users u ON u.id=a.actor_id'.($where?' WHERE '.implode(' AND ',$where):'');
$stmt=$pdo->prepare('SELECT COUNT(*)'.$sql); $stmt->execute($params); $total=(int)$stmt->fetchColumn(); $page=min($page,max(1,(int)ceil($total/50))); $offset=($page-1)*50;
$stmt=$pdo->prepare('SELECT a.*,u.full_name'.$sql." ORDER BY a.id DESC LIMIT 50 OFFSET $offset"); $stmt->execute($params); $events=$stmt->fetchAll();
$actions=$pdo->query('SELECT DISTINCT action FROM admin_audit ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
$actors=$pdo->query('SELECT DISTINCT u.id,u.full_name FROM admin_audit a JOIN users u ON u.id=a.actor_id ORDER BY u.full_name')->fetchAll();
$pageTitle='Admin activity'; require __DIR__.'/../../includes/admin/admin_header.php';
?>
<div class="admin-topbar"><h1>Admin activity</h1><span><?= $total ?> events</span></div><p class="hint">Changes recorded from the date this feature was installed. This history cannot be edited through admin.</p>
<form method="get" class="admin-card admin-filters"><label>Action<select name="action"><option value="">All actions</option><?php foreach ($actions as $value): ?><option <?= $action===$value?'selected':'' ?> value="<?= h($value) ?>"><?= h($value) ?></option><?php endforeach; ?></select></label><label>Staff member<select name="actor"><option value="0">Everyone</option><?php foreach ($actors as $member): ?><option value="<?= (int)$member['id'] ?>" <?= $actor===(int)$member['id']?'selected':'' ?>><?= h($member['full_name']) ?></option><?php endforeach; ?></select></label><button class="btn btn-primary">Filter</button></form>
<div class="admin-card"><table class="admin-table"><thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th></tr></thead><tbody><?php foreach ($events as $event): ?><tr><td><?= h($event['created_at']) ?></td><td><?= h($event['full_name'] ?? 'Former account / system') ?></td><td><?= h($event['action']) ?></td><td><?= h($event['entity_type']) ?> #<?= (int)$event['entity_id'] ?></td><td><details><summary>View changes</summary><pre class="audit-details"><?= h(json_encode(json_decode($event['details'],true),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre></details></td></tr><?php endforeach; ?><?php if (!$events): ?><tr><td colspan="5">No matching activity.</td></tr><?php endif; ?></tbody></table></div>
<div class="admin-toolbar"><?php if ($page>1): ?><a href="?<?= h(http_build_query(['page'=>$page-1,'action'=>$action,'actor'=>$actor])) ?>">Previous</a><?php endif; ?><?php if ($page*50<$total): ?><a href="?<?= h(http_build_query(['page'=>$page+1,'action'=>$action,'actor'=>$actor])) ?>">Next</a><?php endif; ?></div>
<?php require __DIR__.'/../../includes/admin/admin_footer.php'; ?>
