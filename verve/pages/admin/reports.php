<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();
$pageTitle = 'Reports';
$inputDays = filter_var($_GET['days'] ?? 30, FILTER_VALIDATE_INT);
$days = in_array($inputDays, [7, 30, 90], true) ? $inputDays : 30;
$report = getPeriodReports($pdo, $days);
$sections = [
    'sales' => ['Daily order summary', ['day' => 'Date', 'orders' => 'Orders', 'value' => 'Order value'], 'Non-cancelled orders, including pending cash on delivery. Includes shipping and order discounts. This is order value, not confirmed cash collected.'],
    'products' => ['Top 10 products', ['product_name' => 'Product', 'units' => 'Units ordered', 'value' => 'Item value'], 'Ranked by units in non-cancelled orders, grouped by the product name recorded on the order. Values exclude shipping and order-level discounts.'],
    'categories' => ['Order items by category', ['name' => 'Category', 'value' => 'Item value'], 'Non-cancelled orders using current product categories. Values exclude shipping and order-level discounts.'],
    'statuses' => ['Orders by status', ['status' => 'Status', 'orders' => 'Orders', 'value' => 'Order value'], 'Current status of orders placed in this period, including cancelled orders. Values are not confirmed cash collected.'],
];
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/reports.css">
<div class="reports-hero report-controls">
  <div class="reports-hero-top">
    <div><span class="reports-eyebrow">Store performance</span><h1>Reports &amp; insights</h1><p>See how your store is doing, one report at a time.</p></div>
    <button class="btn reports-print-button" type="button" data-print-report="all"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 9V3h12v6M6 17H3V9h18v8h-3M6 14h12v7H6z"/><path d="M17 12h1"/></svg>Print all reports</button>
  </div>
  <div class="reports-filter-bar">
  <form method="get" class="reports-period-form">
    <label for="report-days">Reporting period</label>
    <select id="report-days" name="days" class="sort-select">
      <?php foreach ([7, 30, 90] as $option): ?><option value="<?= $option ?>" <?= $days === $option ? 'selected' : '' ?>>Last <?= $option ?> days</option><?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Update reports</button>
  </form>
  <span class="reports-date-range"><?= h(date('M j, Y', strtotime($report['start']))) ?> &ndash; <?= h(date('M j, Y', strtotime($report['end']))) ?></span>
  </div>
</div>
<main class="reports-document">
<header class="report-heading reports-print-heading">
  <h2><?= h(SITE_NAME) ?> &middot; Business reports</h2>
  <p>Period: <?= h($report['start']) ?> to <?= h($report['end']) ?> (inclusive, store database dates)</p>
  <p class="small muted">Generated <?= h($pdo->query('SELECT NOW()')->fetchColumn()) ?> &middot; Currency: <?= h(CURRENCY) ?></p>
</header>
<div class="reports-meta report-controls"><span>4 reports &middot; <?= h(CURRENCY) ?> &middot; Last <?= $days ?> days</span><span>Print individually below or save as PDF.</span></div>
<noscript><p>Use your browser's Print command to print all reports. Individual buttons require JavaScript.</p></noscript>
<?php foreach ($sections as $key => [$title, $columns, $note]): ?>
<section class="admin-card report-section" id="report-<?= h($key) ?>">
  <div class="report-section-heading"><h2><?= h($title) ?></h2><button class="btn btn-outline btn-sm report-controls" type="button" data-print-report="<?= h($key) ?>">Print <?= h(strtolower($title)) ?></button></div>
  <p class="small muted"><?= h($note) ?></p>
  <?php if ($key === 'sales'): ?>
    <p><strong><?= money((float) array_sum(array_column($report['sales'], 'value'))) ?></strong> &middot; <?= (int) array_sum(array_column($report['sales'], 'orders')) ?> non-cancelled orders</p>
    <div class="report-chart"><canvas id="reportChart" height="90" role="img" aria-label="Daily order values for the selected period"></canvas></div>
  <?php endif; ?>
  <table class="admin-table<?= $key === 'sales' ? ' daily-print-table' : '' ?>">
    <thead><tr><?php foreach ($columns as $label): ?><th scope="col"><?= h($label) ?></th><?php endforeach; ?></tr></thead>
    <tbody>
      <?php foreach ($report[$key] as $row): ?><tr><?php foreach ($columns as $field => $label): ?><td><?= $field === 'value' ? money((float) $row[$field]) : h((string) $row[$field]) ?></td><?php endforeach; ?></tr><?php endforeach; ?>
      <?php if (!$report[$key]): ?><tr><td colspan="<?= count($columns) ?>">No records in this period.</td></tr><?php endif; ?>
    </tbody>
    <tfoot><tr><th scope="row"><?= $key === 'products' ? 'Top 10 subtotal' : ($key === 'statuses' ? 'Total including cancelled' : 'Total') ?></th>
      <?php foreach (array_slice($columns, 1, null, true) as $field => $label): ?><td><?= $field === 'value' ? money((float) array_sum(array_column($report[$key], $field))) : (int) array_sum(array_column($report[$key], $field)) ?></td><?php endforeach; ?>
    </tr></tfoot>
  </table>
</section>
<?php endforeach; ?>
</main>
<script src="<?= BASE_URL ?>/public/assets/js/reports.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
if (typeof Chart !== 'undefined') {
  new Chart(document.getElementById('reportChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($report['sales'], 'day')) ?>,
      datasets: [{ label: 'Order value', data: <?= json_encode(array_map('floatval', array_column($report['sales'], 'value'))) ?>, backgroundColor: '#087F78', borderRadius: 4 }] },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
  });
}
</script>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
