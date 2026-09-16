<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Orders';
$statusFilter = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$orders = getAllOrdersAdmin($pdo, $statusFilter);

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar"><h1>Orders</h1></div>

<div class="admin-toolbar">
  <form method="get">
    <select name="status" class="sort-select" onchange="this.form.submit()">
      <option value="">All statuses</option>
      <?php foreach (getOrderStatusOptions() as $status): ?>
        <option value="<?= h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <span class="muted small"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?></span>
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
          <td><span class="status-pill status-<?= h($order['status']) ?>"><?= h(ucfirst($order['status'])) ?></span></td>
          <td><?= money((float) $order['total']) ?></td>
          <td><a href="<?= BASE_URL ?>/pages/admin/order_detail.php?id=<?= (int) $order['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$orders): ?><tr><td colspan="6" class="muted">No orders found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
