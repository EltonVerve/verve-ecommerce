<?php
/**
 * CART PAGE
 * ---------------------------------------------------------
 * Shows every line in the visitor's cart (guest or logged in),
 * lets them change quantities or remove a line, and shows a
 * running order summary with an optional coupon code.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'My cart';
$accountSection = 'cart';
$user = isCustomerLoggedIn() ? findUserById($pdo, (int) $_SESSION['user_id']) : null;
$items = getCartItems($pdo);
$subtotal = getCartSubtotal($pdo);
$shipping = $items ? calculateShippingFee($subtotal) : 0;

$appliedCoupon = null;
if (!empty($_SESSION['coupon_code'])) {
    $appliedCoupon = findValidCoupon($pdo, $_SESSION['coupon_code']);
    if (!$appliedCoupon) unset($_SESSION['coupon_code']);
}
$discount = 0.0;
if ($appliedCoupon) {
    $discount = $appliedCoupon['type'] === 'percent'
        ? round($subtotal * ((float) $appliedCoupon['value'] / 100), 2)
        : min($subtotal, (float) $appliedCoupon['value']);
}
$total = max(0, $subtotal - $discount) + $shipping;

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell section customer-profile customer-cart">
  <header class="customer-profile-banner">
    <div><span class="customer-profile-eyebrow"><?= $user ? 'Your account' : 'Your shopping bag' ?></span><h1>My cart</h1><p>Review your finds and get ready for checkout.</p></div>
    <a class="btn customer-profile-shop" href="<?= BASE_URL ?>/pages/shop.php">Continue shopping &rarr;</a>
  </header>
  <div class="<?= $user ? 'account-layout' : 'guest-cart-layout' ?>">
  <?php if ($user) require __DIR__ . '/../includes/customer-account-nav.php'; ?>
  <div class="customer-cart-content">
  <div class="customer-account-heading"><h2>Your shopping bag</h2><span><?= (int) $cartCount ?> items</span></div>

  <?php if (!$items): ?>
    <div class="empty-state">
      <h3>Your cart is empty</h3>
      <p>Looks like you haven't added anything yet.</p>
      <a href="<?= BASE_URL ?>/pages/shop.php" class="btn btn-primary">Start shopping</a>
    </div>
  <?php else: ?>
    <div class="cart-layout">
      <div>
        <?php foreach ($items as $item): ?>
          <div class="cart-line">
            <a href="<?= BASE_URL ?>/pages/product.php?slug=<?= h($item['product_slug']) ?>">
              <img src="<?= h(productImageUrl($item['product_image'], $item['product_name'])) ?>" alt="">
            </a>
            <div>
              <div class="name"><a href="<?= BASE_URL ?>/pages/product.php?slug=<?= h($item['product_slug']) ?>"><?= h($item['product_name']) ?></a></div>
              <?php if ($item['options_display']): ?>
                <div class="opts"><?= h(implode(' · ', array_map(fn($k, $v) => "$k: $v", array_keys($item['options_display']), $item['options_display']))) ?></div>
              <?php endif; ?>
              <div class="line-actions">
                <form action="<?= BASE_URL ?>/actions/update_cart.php" method="post" class="qty-stepper" style="border:1px solid var(--line);">
                  <?= csrfField() ?>
                  <input type="hidden" name="cart_item_id" value="<?= (int) $item['id'] ?>">
                  <button type="button" data-step="down" aria-label="Decrease quantity">−</button>
                  <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="1" max="<?= (int) $item['stock'] ?>" onchange="this.form.submit()" aria-label="Quantity">
                  <button type="button" data-step="up" aria-label="Increase quantity">+</button>
                </form>
                <form action="<?= BASE_URL ?>/actions/remove_from_cart.php" method="post">
                  <?= csrfField() ?>
                  <input type="hidden" name="cart_item_id" value="<?= (int) $item['id'] ?>">
                  <button type="submit" class="remove-link">Remove</button>
                </form>
              </div>
            </div>
            <div class="line-total"><?= money((float) $item['unit_price'] * $item['quantity']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="summary-card">
        <h3>Order summary</h3>
        <div class="summary-row"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
        <?php if ($appliedCoupon): ?>
          <div class="summary-row"><span>Discount (<?= h($appliedCoupon['code']) ?>)</span><span>&minus;<?= money($discount) ?></span></div>
        <?php endif; ?>
        <div class="summary-row"><span>Estimated shipping</span><span><?= $shipping > 0 ? money($shipping) : 'Free' ?></span></div>
        <p class="hint">Delivery availability and final fee are confirmed for your selected area at checkout.</p>
        <div class="summary-row total"><span>Total</span><span><?= money($total) ?></span></div>

        <form action="<?= BASE_URL ?>/actions/apply_coupon.php" method="post" class="coupon-row">
          <?= csrfField() ?>
          <input type="text" name="coupon_code" placeholder="Coupon code" value="<?= h($appliedCoupon['code'] ?? '') ?>">
          <button type="submit" class="btn btn-outline btn-sm">Apply</button>
        </form>

        <a href="<?= BASE_URL ?>/pages/checkout.php" class="btn btn-primary btn-block"><?= isCustomerLoggedIn() ? 'Proceed to checkout' : 'Sign in to checkout' ?></a>
      </div>
    </div>
  <?php endif; ?>
  </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
