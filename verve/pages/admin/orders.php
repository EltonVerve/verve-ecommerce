<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Orders';
$statusFilter = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$filters=[];
foreach (['q','status','payment','area','from','to'] as $key) $filters[$key]=is_string($_GET[$key] ?? null) ? mb_substr(trim($_GET[$key]),0,150) : '';
$result=searchAdminOrders($pdo,$filters,max(1,(int)($_GET['page'] ?? 1)));
$orders=$result['rows'];
$queues = $pdo->query("SELECT SUM(status='pending') AS pending, SUM(status='processing') AS processing, SUM(status='shipped') AS shipped, SUM(status='completed' AND payment_status='unpaid') AS collection FROM orders")->fetch();

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar"><h1>Orders</h1></div>
<nav class="order-work-queues" aria-label="Order work queues">
<?php foreach (['pending'=>['New orders',['status'=>'pending']], 'processing'=>['To pack',['status'=>'processing']], 'shipped'=>['Out for delivery',['status'=>'shipped']], 'collection'=>['Delivered, awaiting cash',['status'=>'completed','payment'=>'unpaid']]] as $key=>[$label,$query]): ?>
<a class="stat-card" href="?<?= h(http_build_query($query)) ?>"><span class="label"><?= h($label) ?></span><strong class="value"><?= (int)$queues[$key] ?></strong></a>
<?php endforeach; ?>
</nav>
<p class="hint">Open an order to confirm customer details, save its status, assign a rider, print a packing slip or add a handover note. Queue counts cover all orders.</p>

<div class="admin-toolbar">
  <form method="get" class="admin-filters">
    <label>Customer, phone or order<input name="q" value="<?= h($filters['q']) ?>" placeholder="Name, email, phone or #"></label>
    <label>Delivery status<select name="status" class="sort-select">
      <option value="">All statuses</option>
      <?php foreach (getOrderStatusOptions() as $status): ?>
        <option value="<?= h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
      <?php endforeach; ?>
    </select></label>
    <label>Payment<select name="payment"><option value="">All payments</option><?php foreach (['unpaid','collected','part_refunded','refunded','unknown'] as $payment): ?><option value="<?= $payment ?>" <?= $filters['payment']===$payment ? 'selected' : '' ?>><?= h(ucwords(str_replace('_',' ',$payment))) ?></option><?php endforeach; ?></select></label>
    <label>Delivery area<input name="area" value="<?= h($filters['area']) ?>"></label>
    <label>From<input type="date" name="from" value="<?= h($filters['from']) ?>"></label><label>To<input type="date" name="to" value="<?= h($filters['to']) ?>"></label>
    <button class="btn btn-primary">Apply filters</button><a class="btn btn-outline" href="orders.php">Reset</a>
  </form>
  <span class="muted small"><?= $result['total'] ?> orders · Page <?= $result['page'] ?></span>
</div>

<div class="admin-card">
  <table class="admin-table">
    <thead><tr><th>Order</th><th>Customer</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($orders as $order): ?>
        <tr>
          <td>#<?= (int) $order['id'] ?></td>
          <td><?= h($order['customer_name']) ?><br><span class="muted small"><?= h($order['customer_email']) ?></span></td>
          <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
          <td><span class="status-pill status-<?= h($order['status']) ?>"><?= h(ucfirst($order['status'])) ?></span><br><span class="muted small"><?= h(ucwords(str_replace('_',' ',$order['payment_status']))) ?></span></td>
          <td><?= money((float) $order['total']) ?></td>
          <td><a href="<?= BASE_URL ?>/pages/admin/order_detail.php?id=<?= (int) $order['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="6" class="muted">No orders found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<div class="admin-toolbar"><?php if ($result['page']>1): ?><a class="btn btn-outline" href="?<?= h(http_build_query(array_merge($filters,['page'=>$result['page']-1]))) ?>">Previous</a><?php endif; ?><?php if ($result['page']*25<$result['total']): ?><a class="btn btn-outline" href="?<?= h(http_build_query(array_merge($filters,['page'=>$result['page']+1]))) ?>">Next</a><?php endif; ?></div>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
