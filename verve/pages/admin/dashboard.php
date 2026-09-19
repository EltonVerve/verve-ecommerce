<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Dashboard';
$stats = getDashboardStats($pdo);
$sales = getSalesOverTime($pdo, 14);
$recentOrders = getRecentOrders($pdo, 6);
$lowStock = getLowStockProducts($pdo, lowStockThreshold($pdo));
$topSelling = getTopSellingProducts($pdo, 5);

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar">
  <h1>Dashboard</h1>
  <a href="<?= BASE_URL ?>/pages/admin/product_form.php" class="btn btn-primary btn-sm">+ New product</a>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="label">Revenue</div><div class="value"><?= money($stats['revenue']) ?></div></div>
  <div class="stat-card"><div class="label">Orders</div><div class="value"><?= $stats['orders'] ?></div></div>
  <div class="stat-card"><div class="label">Pending orders</div><div class="value"><?= $stats['pending'] ?></div></div>
  <div class="stat-card"><div class="label">Active products</div><div class="value"><?= $stats['products'] ?></div></div>
  <div class="stat-card"><div class="label">Customers</div><div class="value"><?= $stats['customers'] ?></div></div>
</div>

<div class="admin-card">
  <h2>Revenue — last 14 days</h2>
  <canvas id="salesChart" height="90"></canvas>
</div>

<div style="display:grid; grid-template-columns:1.4fr 1fr; gap:1.6rem;">
  <div class="admin-card">
    <h2>Recent orders</h2>
    <table class="admin-table">
      <thead><tr><th>Order</th><th>Customer</th><th>Status</th><th>Total</th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $order): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/pages/admin/order_detail.php?id=<?= (int) $order['id'] ?>">#<?= (int) $order['id'] ?></a></td>
            <td><?= h($order['customer_name']) ?></td>
            <td><span class="status-pill status-<?= h($order['status']) ?>"><?= h(ucfirst($order['status'])) ?></span></td>
            <td><?= money((float) $order['total']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recentOrders): ?><tr><td colspan="4" class="muted">No orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <h2>Low stock</h2><a href="stock.php">View all alerts and set threshold</a>
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Stock</th></tr></thead>
      <tbody>
        <?php foreach ($lowStock as $p): ?>
          <tr>
            <td><a href="<?= BASE_URL ?>/pages/admin/product_form.php?id=<?= (int) $p['id'] ?>"><?= h($p['name']) ?></a></td>
            <td><?= (int) $p['stock'] ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$lowStock): ?><tr><td colspan="2" class="muted">All good — nothing running low.</td></tr><?php endif; ?>
      </tbody>
    </table>

    <h2 style="margin-top:1.6rem;">Top sellers</h2>
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Sold</th></tr></thead>
      <tbody>
        <?php foreach ($topSelling as $p): ?>
          <tr><td><?= h($p['product_name']) ?></td><td><?= (int) $p['total_sold'] ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topSelling): ?><tr><td colspan="2" class="muted">No sales yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('salesChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($sales['labels']) ?>,
    datasets: [{
      label: 'Revenue (<?= h(trim(STORE_CURRENCY_SYMBOL)) ?>)',
      data: <?= json_encode($sales['values']) ?>,
      borderColor: '#087F78',
      backgroundColor: 'rgba(8,127,120,.12)',
      pointBackgroundColor: '#087F78',
      pointBorderColor: '#FFFFFF',
      fill: true,
      tension: .3,
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
