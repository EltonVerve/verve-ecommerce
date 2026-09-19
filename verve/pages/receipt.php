<?php
/**
 * ORDER RECEIPT PAGE
 * ---------------------------------------------------------
 * Shown right after checkout. Reads the order id (and, for
 * guests, a private access token) from the query string.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

$orderId = (int) ($_GET['order'] ?? 0);
$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : null;
$order = $orderId ? getAccessibleOrder($pdo, $orderId, $token) : null;

$pageTitle = 'Order Confirmation';
require __DIR__ . '/../includes/header.php';

if (!$order) {
    echo '<div class="shell section text-center"><h1>Order not found</h1><p class="muted">We could not find that order, or you don\'t have access to view it.</p><a class="btn btn-primary" href="' . BASE_URL . '/pages/shop.php">Continue shopping</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="shell section">
  <div class="receipt-box">
    <div class="receipt-header">
      <div class="check-icon">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 13l4 4L19 7"/></svg>
      </div>
      <h1>Thank you for your order!</h1>
      <p class="muted">Order #<?= (int) $order['id'] ?> · Placed <?= date('F j, Y \a\t g:ia', strtotime($order['created_at'])) ?></p>
      <span class="status-pill status-<?= h($order['status']) ?>"><?= h(ucfirst($order['status'])) ?></span>
    </div>

    <?php require __DIR__ . '/../includes/order-tracking.php'; ?>
    <table class="data-table">
      <thead><tr><th>Item</th><th>Qty</th><th style="text-align:right;">Total</th></tr></thead>
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
      <div class="summary-row"><span>Discount</span><span>&minus;<?= money((float) $order['discount_total']) ?></span></div>
    <?php endif; ?>
    <div class="summary-row"><span>Shipping</span><span><?= (float) $order['shipping_fee'] > 0 ? money((float) $order['shipping_fee']) : 'Free' ?></span></div>
    <div class="summary-row total"><span>Total</span><span><?= money((float) $order['total']) ?></span></div>

    <?php if ($order['address']): ?>
      <div class="divider"></div>
      <h3 style="font-size:1rem;">Delivery address</h3>
      <p class="muted small">
        <?= h($order['address']['full_name'] ?? '') ?><br>
        <?= h($order['address']['line1'] ?? '') ?><?= !empty($order['address']['line2']) ? ', ' . h($order['address']['line2']) : '' ?><br>
        <?= h($order['address']['city'] ?? '') ?><?= !empty($order['address']['state']) ? ', ' . h($order['address']['state']) : '' ?> <?= h($order['address']['postal_code'] ?? '') ?><br>
        <?= h($order['address']['country'] ?? '') ?>
      </p>
    <?php endif; ?>

    <div class="text-center" style="margin-top:2rem;">
      <?php if (isCustomerLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/pages/account.php?tab=orders" class="btn btn-outline">View order history</a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/pages/shop.php" class="btn btn-primary">Continue shopping</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
