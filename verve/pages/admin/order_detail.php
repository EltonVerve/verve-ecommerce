<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$orderId = (int) ($_GET['id'] ?? 0);
$order = $orderId ? getOrderWithItemsAdmin($pdo, $orderId) : null;

if (!$order) {
    setFlash('error', 'Order not found.');
    header('Location: ' . BASE_URL . '/pages/admin/orders.php');
    exit;
}

$pageTitle = 'Order #' . $orderId;
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar">
  <h1>Order #<?= (int) $order['id'] ?></h1>
  <a href="<?= BASE_URL ?>/pages/admin/orders.php" class="btn btn-outline btn-sm">← Back to orders</a>
</div>

<div style="display:grid; grid-template-columns:1.4fr 1fr; gap:1.6rem; align-items:start;">
  <div class="admin-card">
    <h2>Items</h2>
    <table class="admin-table">
      <thead><tr><th>Product</th><th>Qty</th><th style="text-align:right;">Total</th></tr></thead>
      <tbody>
        <?php foreach ($order['items'] as $item): ?>
          <tr>
            <td>
              <?= h($item['product_name']) ?>
              <?php if ($item['options_display']): ?>
                <div class="muted small"><?= h(implode(' · ', array_map(fn($k, $v) => "$k: $v", array_keys($item['options_display']), $item['options_display']))) ?></div>
              <?php endif; ?>
            </td>
            <td><?= (int) $item['quantity'] ?></td>
            <td style="text-align:right;"><?= money((float) $item['line_total']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="divider"></div>
    <div class="summary-row"><span>Subtotal</span><span><?= money((float) $order['subtotal']) ?></span></div>
    <?php if ((float) $order['discount_total'] > 0): ?>
      <div class="summary-row"><span>Discount<?= $order['coupon_code'] ? ' (' . h($order['coupon_code']) . ')' : '' ?></span><span>&minus;<?= money((float) $order['discount_total']) ?></span></div>
    <?php endif; ?>
    <div class="summary-row"><span>Shipping</span><span><?= money((float) $order['shipping_fee']) ?></span></div>
    <div class="summary-row total"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>
  </div>

  <div>
    <div class="admin-card">
      <h2>Status</h2>
      <form action="<?= BASE_URL ?>/actions/admin/update_order_status.php" method="post">
        <?= csrfField() ?>
        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
        <div class="field">
          <select name="status" onchange="this.form.submit()">
            <?php foreach (getOrderStatusOptions() as $status): ?>
              <option value="<?= h($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>

    <div class="admin-card">
      <h2>Customer</h2>
      <p><?= h($order['customer_name']) ?><br>
      <?= h($order['customer_email']) ?><br>
      <?= h($order['customer_phone'] ?? '') ?></p>
      <p class="muted small">Payment method: <?= h(ucwords(str_replace('_', ' ', $order['payment_method'] ?? ''))) ?></p>
    </div>

    <?php if ($order['address']): ?>
      <div class="admin-card">
        <h2>Delivery address</h2>
        <p class="small">
          <?= h($order['address']['full_name'] ?? '') ?><br>
          <?= h($order['address']['line1'] ?? '') ?><?= !empty($order['address']['line2']) ? ', ' . h($order['address']['line2']) : '' ?><br>
          <?= h($order['address']['city'] ?? '') ?><?= !empty($order['address']['state']) ? ', ' . h($order['address']['state']) : '' ?> <?= h($order['address']['postal_code'] ?? '') ?><br>
          <?= h($order['address']['country'] ?? '') ?>
        </p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
