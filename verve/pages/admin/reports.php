<?php
/**
 * REPORTS PAGE
 * ---------------------------------------------------------
 * A simple sales report: revenue over a chosen period, top
 * products, and order status breakdown. Everything here reads
 * from order_items / orders — nothing is precomputed, so the
 * numbers are always current.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Reports';
$days = max(7, min(90, (int) ($_GET['days'] ?? 30)));
$sales = getSalesOverTime($pdo, $days);
$topSelling = getTopSellingProducts($pdo, 10);

$statusBreakdown = $pdo->query("SELECT status, COUNT(*) AS c, COALESCE(SUM(total),0) AS revenue FROM orders GROUP BY status")->fetchAll();
$totalRevenue = array_sum($sales['values']);
$categoryRevenue = $pdo->query("
    SELECT c.name, COALESCE(SUM(oi.line_total), 0) AS revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN categories c ON c.id = p.category_id
    GROUP BY c.name
    ORDER BY revenue DESC
")->fetchAll();

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar">
  <h1>Reports</h1>
  <form method="get">
    <select name="days" class="sort-select" onchange="this.form.submit()">
      <option value="7" <?= $days === 7 ? 'selected' : '' ?>>Last 7 days</option>
      <option value="30" <?= $days === 30 ? 'selected' : '' ?>>Last 30 days</option>
      <option value="90" <?= $days === 90 ? 'selected' : '' ?>>Last 90 days</option>
    </select>
  </form>
</div>

<div class="admin-card">
  <h2>Revenue — last <?= $days ?> days (<?= money($totalRevenue) ?> total)</h2>
  <canvas id="reportChart" height="90"></canvas>
</div>

<div style="display:grid; grid-template-columns:1fr 1fr; gap:1.6rem;">
  <div class="admin-card">
    <h2>Top-selling products</h2>
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Units sold</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($topSelling as $p): ?>
          <tr><td><?= h($p['product_name']) ?></td><td><?= (int) $p['total_sold'] ?></td><td><?= money((float) $p['total_revenue']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topSelling): ?><tr><td colspan="3" class="muted">No sales yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <h2>Revenue by category</h2>
    <table class="admin-table">
      <thead><tr><th>Category</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($categoryRevenue as $row): ?>
          <tr><td><?= h($row['name']) ?></td><td><?= money((float) $row['revenue']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$categoryRevenue): ?><tr><td colspan="2" class="muted">No sales yet.</td></tr><?php endif; ?>
      </tbody>
    </table>

    <h2 style="margin-top:1.6rem;">Orders by status</h2>
    <table class="admin-table">
      <thead><tr><th>Status</th><th>Count</th><th>Revenue</th></tr></thead>
      <tbody>
        <?php foreach ($statusBreakdown as $row): ?>
          <tr>
            <td><span class="status-pill status-<?= h($row['status']) ?>"><?= h(ucfirst($row['status'])) ?></span></td>
            <td><?= (int) $row['c'] ?></td>
            <td><?= money((float) $row['revenue']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('reportChart'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($sales['labels']) ?>,
    datasets: [{
      label: 'Revenue (<?= h(trim(STORE_CURRENCY_SYMBOL)) ?>)',
      data: <?= json_encode($sales['values']) ?>,
      backgroundColor: '#B4502C',
      borderRadius: 4,
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
