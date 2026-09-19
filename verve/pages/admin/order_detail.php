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
  <a href="<?= BASE_URL ?>/pages/admin/order_print.php?id=<?= (int)$order['id'] ?>" class="btn btn-primary btn-sm" target="_blank" rel="noopener">Print packing slip</a>
  <a href="<?= BASE_URL ?>/pages/admin/orders.php" class="btn btn-outline btn-sm">← Back to orders</a>
</div>

<div class="operations-grid">
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
      <p class="hint">Delivery and cash collection are recorded separately. Completed means delivered.</p>
      <form action="<?= BASE_URL ?>/actions/admin/update_order_status.php" method="post">
        <?= csrfField() ?>
        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
        <div class="field">
          <label for="order-status">Order status</label>
          <select id="order-status" name="status" aria-describedby="order-status-help">
            <?php foreach (array_unique(array_merge([$order['status']], ['pending','processing','shipped','completed','cancelled'])) as $status): ?>
              <option value="<?= h($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <p id="order-status-help" class="hint">Choose a status, then save to apply the change.</p>
        <button type="submit" class="btn btn-primary">Save status</button>
      </form>
      <hr class="divider"><h2>Payment collection</h2>
      <p>Current: <?= h(ucwords(str_replace('_',' ',$order['payment_status']))) ?></p>
      <?php if (!in_array($order['payment_status'], ['refunded','part_refunded'], true)): ?>
      <form action="<?= BASE_URL ?>/actions/admin/update_order_status.php" method="post">
        <?= csrfField() ?><input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>"><input type="hidden" name="operation" value="payment">
        <label for="payment-status">Payment status</label><select id="payment-status" name="status">
        <?php foreach (array_unique([$order['payment_status'], 'unpaid','collected']) as $payment): ?><option value="<?= h($payment) ?>" <?= $payment === $order['payment_status'] ? 'selected' : '' ?>><?= h(ucwords(str_replace('_',' ',$payment))) ?></option><?php endforeach; ?>
        </select><p class="hint">Mark collected only after receiving the full payment. Owners can record refunds below.</p>
        <button class="btn btn-primary" type="submit">Save payment</button>
      </form>
      <?php endif; ?>
      <?php require __DIR__ . '/../../includes/order-tracking.php'; ?>
    </div>

    <div class="admin-card">
      <h2>Customer</h2>
      <p><?= h($order['customer_name']) ?><br>
      <?= h($order['customer_email']) ?><br>
      <?= h($order['customer_phone'] ?? '') ?></p>
      <div class="admin-toolbar">
      <?php $contactPhone=preg_replace('/[^0-9+]/','',$order['customer_phone'] ?? ''); ?>
      <?php if (preg_match('/^\+?[0-9]{9,15}$/D',$contactPhone)): ?><a class="btn btn-outline btn-sm" href="tel:<?= h($contactPhone) ?>">Call customer</a><?php endif; ?>
      <?php if (filter_var($order['customer_email'],FILTER_VALIDATE_EMAIL)): ?><a class="btn btn-outline btn-sm" href="mailto:<?= h($order['customer_email']) ?>">Email customer</a><?php endif; ?>
      </div>
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

<?php require __DIR__ . '/../../includes/admin/order-operations.php'; ?>
<section class="admin-card" id="staff-notes">
  <h2>Internal order notes</h2><p class="hint">For staff handovers, customer call outcomes and delivery issues. These notes are visible only in admin. Latest 100 notes shown.</p>
  <form method="post" action="<?= BASE_URL ?>/actions/admin/order_note.php">
    <?= csrfField() ?><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(32)) ?>">
    <div class="field"><label for="staff-note">Add a note</label><textarea id="staff-note" name="note" maxlength="1000" rows="3" required></textarea></div>
    <button class="btn btn-primary">Save note</button>
  </form>
  <?php $staffNotes=getOrderStaffNotes($pdo,$orderId); ?>
  <?php foreach ($staffNotes as $note): $details=json_decode($note['details'],true); ?>
  <article class="order-staff-note"><strong><?= h($note['full_name'] ?? 'Former staff member') ?></strong> <span class="muted small"><?= h($note['created_at']) ?></span><p><?= nl2br(h($details['note'] ?? '')) ?></p></article>
  <?php endforeach; ?>
  <?php if (!$staffNotes): ?><p class="muted">No internal notes yet.</p><?php endif; ?>
</section>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
