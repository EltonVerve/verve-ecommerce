<?php
/**
 * ACCOUNT PAGE
 * ---------------------------------------------------------
 * The logged-in customer's home base: order history and
 * saved (wishlist) items. Editing profile details lives in
 * edit_account.php, linked from the sidebar here.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';
requireLogin();

$user = findUserById($pdo, (int) $_SESSION['user_id']);
$orders = getOrdersForUser($pdo, (int) $_SESSION['user_id']);
$wishlist = getWishlistForUser($pdo, (int) $_SESSION['user_id']);
$tab = in_array($_GET['tab'] ?? '', ['orders', 'wishlist'], true) ? $_GET['tab'] : 'orders';

$accountSection = $tab;
$pageTitle = $tab === 'orders' ? 'Order history' : 'Wishlist';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell section customer-profile customer-account">
  <header class="customer-profile-banner">
    <div><span class="customer-profile-eyebrow">Your account</span><h1><?= h($pageTitle) ?></h1><p><?= $tab === 'orders' ? 'Track your purchases and revisit your favourites.' : 'Your favourite finds, saved for another day.' ?></p></div>
    <a class="btn customer-profile-shop" href="<?= BASE_URL ?>/pages/shop.php">Continue shopping &rarr;</a>
  </header>

  <div class="account-layout">
    <?php require __DIR__ . '/../includes/customer-account-nav.php'; ?>

    <div class="customer-account-content">
      <?php if ($tab === 'orders'): ?>
        <div class="customer-account-heading"><h2>Your orders</h2><span><?= count($orders) ?> orders</span></div>
        <?php if (!$orders): ?>
          <div class="empty-state">
            <h3>No orders yet</h3>
            <p>When you place an order, it'll show up here.</p>
            <a href="<?= BASE_URL ?>/pages/shop.php" class="btn btn-primary">Start shopping</a>
          </div>
        <?php else: ?>
          <div class="order-history-table" role="region" aria-label="Order history" tabindex="0">
          <table class="data-table">
            <thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr>
                  <td>#<?= (int) $order['id'] ?></td>
                  <td><?= date('M j, Y', strtotime($order['created_at'])) ?></td>
                  <td><span class="status-pill status-<?= h($order['status']) ?>"><?= h(ucfirst($order['status'])) ?></span></td>
                  <td><?= money((float) $order['total']) ?></td>
                  <td><a href="<?= BASE_URL ?>/pages/order.php?id=<?= (int) $order['id'] ?>" class="btn btn-outline btn-sm">View</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="customer-account-heading"><h2>Saved for later</h2><span><?= count($wishlist) ?> items</span></div>
        <?php if (!$wishlist): ?>
          <div class="empty-state">
            <h3>Your wishlist is empty</h3>
            <p>Tap the heart icon on any product to save it for later.</p>
            <a href="<?= BASE_URL ?>/pages/shop.php" class="btn btn-primary">Browse products</a>
          </div>
        <?php else: ?>
          <div class="product-grid">
            <?php foreach ($wishlist as $product): ?>
              <?php require __DIR__ . '/../includes/product-card.php'; ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
