<?php
/**
 * CHECKOUT PAGE
 * ---------------------------------------------------------
 * Collects (or reuses) a delivery address, shows the final
 * order summary, and lets the customer choose a payment
 * method before placing the order via actions/place_order.php.
 * Works for logged-in customers AND guests.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'Checkout';
$items = getCartItems($pdo);
if (!$items) {
    header('Location: ' . BASE_URL . '/pages/cart.php');
    exit;
}

$subtotal = getCartSubtotal($pdo);
$appliedCoupon = !empty($_SESSION['coupon_code']) ? findValidCoupon($pdo, $_SESSION['coupon_code']) : null;
$discount = 0.0;
if ($appliedCoupon) {
    $discount = $appliedCoupon['type'] === 'percent'
        ? round($subtotal * ((float) $appliedCoupon['value'] / 100), 2)
        : min($subtotal, (float) $appliedCoupon['value']);
}
$zones = deliveryZones($pdo);
$zoneId = filter_var($_GET['zone'] ?? ($zones[0]['id'] ?? 0), FILTER_VALIDATE_INT) ?: 0;
$zone = null;
try { $zone = deliveryQuote($pdo, $zoneId, $subtotal); } catch (RuntimeException $error) {}
$shipping = $zone['shipping'] ?? 0;
$total = max(0, $subtotal - $discount) + $shipping;

$prefill = ['full_name' => '', 'line1' => '', 'line2' => '', 'city' => '', 'state' => '', 'postal_code' => '', 'country' => '', 'phone' => ''];
$prefillEmail = '';
if (isCustomerLoggedIn()) {
    $user = findUserById($pdo, (int) $_SESSION['user_id']);
    $prefillEmail = $user['email'] ?? '';
    $prefill['full_name'] = $user['full_name'] ?? '';
    $prefill['phone'] = $user['phone'] ?? '';
    $existing = getDefaultAddress($pdo, (int) $_SESSION['user_id']);
    if ($existing) {
        $prefill = array_merge($prefill, $existing);
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell section">
  <h1>Checkout</h1>
  <div class="checkout-layout">
    <form action="<?= BASE_URL ?>/actions/place_order.php" method="post">
      <?= csrfField() ?>
      <div class="form-card" style="margin-bottom:1.5rem;">
  <h2 style="font-size:1.1rem;">Delivery location</h2>
  <label for="delivery-location">Delivery town or city</label>
  <input type="text" id="delivery-location" name="delivery_location" list="delivery-locations" value="<?= h($zone['name'] ?? '') ?>" maxlength="100" placeholder="e.g. Nairobi, CBD or Mombasa" autocomplete="off" required aria-describedby="delivery-help">
  <datalist id="delivery-locations"><?php foreach ($zones as $available): ?><option value="<?= h($available['name']) ?>"><?= (float) $available['fee'] === 0.0 ? 'Free delivery' : money((float) $available['fee']) ?></option><?php endforeach; ?></datalist>
  <p id="delivery-help" class="hint" aria-live="polite">Start typing and choose an area. Nairobi CBD delivery is free.</p>
  <noscript><p>JavaScript is required to preview delivery prices. Enable it before placing your order.</p></noscript>
</div>

      <div class="form-card" style="margin-bottom:1.5rem;">
        <h2 style="font-size:1.1rem;">Contact</h2>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" required value="<?= h($prefillEmail) ?>" <?= isCustomerLoggedIn() ? 'readonly' : '' ?>>
          <?php if (!isCustomerLoggedIn()): ?><p class="hint">Already have an account? <a href="<?= BASE_URL ?>/pages/login.php">Log in</a> for faster checkout.</p><?php endif; ?>
        </div>
      </div>

      <div class="form-card" style="margin-bottom:1.5rem;">
        <h2 style="font-size:1.1rem;">Delivery address</h2>
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" required value="<?= h($prefill['full_name']) ?>">
        </div>
        <div class="field">
          <label for="line1">Address line 1</label>
          <input type="text" id="line1" name="line1" required value="<?= h($prefill['line1']) ?>">
        </div>
        <div class="field">
          <label for="line2">Address line 2 (optional)</label>
          <input type="text" id="line2" name="line2" value="<?= h($prefill['line2'] ?? '') ?>">
        </div>
        <div class="form-grid">
          
          <div class="field">
            <label for="state">State / Region</label>
            <input type="text" id="state" name="state" value="<?= h($prefill['state'] ?? '') ?>">
          </div>
        </div>
        <div class="form-grid">
          <div class="field">
            <label for="postal_code">Postal code</label>
            <input type="text" id="postal_code" name="postal_code" value="<?= h($prefill['postal_code'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="country">Country</label>
            <input type="text" id="country" name="country" required readonly value="<?= h($zone['country'] ?? '') ?>">
          </div>
        </div>
        <div class="field">
          <label for="phone">Phone number</label>
          <input type="tel" id="phone" name="phone" required maxlength="30" autocomplete="tel" placeholder="e.g. +254712345678" value="<?= h($prefill['phone'] ?? '') ?>">
        </div>
      </div>

      <div class="form-card">
        <h2 style="font-size:1.1rem;">Payment method</h2>
        <div class="payment-options"><label class="payment-option"><input type="radio" name="payment_method" value="cash_on_delivery" checked><span>Cash on delivery - pay when your order arrives</span></label></div>
        <p class="hint">Payment is collected on delivery.</p>
      </div>

      <button id="place-order" disabled type="submit" class="btn btn-primary btn-block" style="margin-top:1.5rem;">Place order — <?= money($total) ?></button>
    </form>

    <div class="summary-card">
      <h3>Order summary</h3>
      <?php foreach ($items as $item): ?>
        <div class="order-mini-line">
          <span><?= (int) $item['quantity'] ?>× <?= h($item['product_name']) ?></span>
          <span><?= money((float) $item['unit_price'] * $item['quantity']) ?></span>
        </div>
      <?php endforeach; ?>
      <div class="summary-row" style="margin-top:.8rem;"><span>Subtotal</span><span><?= money($subtotal) ?></span></div>
      <?php if ($appliedCoupon): ?>
        <div class="summary-row"><span>Discount</span><span>&minus;<?= money($discount) ?></span></div>
      <?php endif; ?>
      <div class="summary-row"><span>Shipping</span><span id="delivery-fee" aria-live="polite"><?= $zone ? ($shipping > 0 ? money($shipping) : 'Free') : 'Select location' ?></span></div>
      <div class="summary-row total"><span>Total</span><span id="checkout-total" aria-live="polite"><?= money($total) ?><?= $zone ? '' : ' + delivery' ?></span></div>
    </div>
  </div>
</div>

<script type="application/json" id="delivery-pricing"><?= json_encode(['zones'=>$zones,'subtotal'=>$subtotal,'discount'=>$discount,'currency'=>STORE_CURRENCY_SYMBOL], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
<script src="<?= BASE_URL ?>/public/assets/js/checkout-delivery.js"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
