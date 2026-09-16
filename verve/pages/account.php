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

$pageTitle = 'My Account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell section">
  <h1>My Account</h1>
  <p class="muted">Welcome back, <?= h($user['full_name'] ?? '') ?>.</p>

  <div class="account-layout">
    <nav class="account-nav">
      <a href="?tab=orders" class="<?= $tab === 'orders' ? 'active' : '' ?>">Order history</a>
      <a href="<?= BASE_URL ?>/pages/cart.php">My cart (<?= (int) $cartCount ?>)</a>
      <a href="?tab=wishlist" class="<?= $tab === 'wishlist' ? 'active' : '' ?>">Wishlist (<?= count($wishlist) ?>)</a>
      <a href="<?= BASE_URL ?>/pages/edit_account.php">Edit profile</a>
      <a href="<?= BASE_URL ?>/actions/logout.php">Log out</a>
    </nav>

    <div>
      <?php if ($tab === 'orders'): ?>
        <h2>Order history</h2>
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
