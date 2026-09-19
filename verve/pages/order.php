<?php
/**
 * ORDER DETAIL PAGE (account area)
 * ---------------------------------------------------------
 * Same layout as the receipt, but reached from "My account"
 * and restricted to orders that belong to the logged-in user.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$orderId = (int) ($_GET['id'] ?? 0);
$order = $orderId ? getOrderWithItems($pdo, $orderId, (int) $_SESSION['user_id']) : null;

$pageTitle = 'Order #' . $orderId;
require __DIR__ . '/../includes/header.php';

if (!$order) {
    echo '<div class="shell section text-center"><h1>Order not found</h1><a class="btn btn-primary" href="' . BASE_URL . '/pages/account.php">Back to account</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}
?>

<div class="shell section">
  <div class="receipt-box">
    <div class="receipt-header">
      <h1>Order #<?= (int) $order['id'] ?></h1>
      <p class="muted">Placed <?= date('F j, Y \a\t g:ia', strtotime($order['created_at'])) ?></p>
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
      <a href="<?= BASE_URL ?>/pages/account.php" class="btn btn-outline">Back to account</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
