<?php
/**
 * PRODUCT CARD PARTIAL
 * ---------------------------------------------------------
 * Expects a $product array (from any of the Product model
 * queries) to already be set before this file is included.
 * Used on the homepage, shop grid, and "related products".
 * ---------------------------------------------------------
 */
$onSale = !empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['price'];
$outOfStock = (int) $product['stock'] <= 0;
$rating = (float) ($product['avg_rating'] ?? 0);
$reviewCount = (int) ($product['review_count'] ?? 0);
$inWishlist = isCustomerLoggedIn() && wishlistCardContains($pdo, (int) $_SESSION['user_id'], (int) $product['id']);
?>
<div class="product-card">
  <div class="thumb-wrap">
    <?php if ($outOfStock): ?><span class="badge badge-out">Sold out</span>
    <?php elseif ($onSale): ?><span class="badge badge-sale">Sale</span>
    <?php elseif (!empty($product['is_featured'])): ?><span class="badge">Featured</span>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/pages/product.php?slug=<?= h($product['slug']) ?>">
      <img src="<?= h(productImageUrl($product['image'], $product['name'], 600)) ?>" alt="<?= h($product['name']) ?>" loading="lazy" decoding="async">
    </a>
    <form action="<?= BASE_URL ?>/actions/toggle_wishlist.php" method="post">
      <?= csrfField() ?>
      <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
      <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
      <button type="submit" class="wishlist-btn <?= $inWishlist ? 'is-active' : '' ?>" aria-label="<?= $inWishlist ? 'Remove from wishlist' : 'Save to wishlist' ?>">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="<?= $inWishlist ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8Z"/></svg>
      </button>
    </form>
  </div>
  <div class="product-card-body">
  <div class="cat-label"><?= h($product['category_name']) ?></div>
  <h3><a href="<?= BASE_URL ?>/pages/product.php?slug=<?= h($product['slug']) ?>"><?= h($product['name']) ?></a></h3>
  <?php if ($reviewCount > 0): ?>
    <div class="stars" aria-label="<?= number_format($rating, 1) ?> out of 5 stars"><?= renderStars($rating) ?> <span class="muted small">(<?= $reviewCount ?>)</span></div>
  <?php endif; ?>
  <div class="price-row">
    <span class="price"><?= money((float) $product['price']) ?></span>
    <?php if ($onSale): ?><span class="price-compare"><?= money((float) $product['compare_at_price']) ?></span><?php endif; ?>
  </div>
  <?php if (!$outOfStock): ?>
    <form action="<?= BASE_URL ?>/actions/add_to_cart.php" method="post">
      <?= csrfField() ?>
      <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
      <input type="hidden" name="quantity" value="1">
      <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
      <button type="submit" class="quick-add"><span>Add to cart</span><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 7h12l2 14H4L6 7Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg></button>
    </form>
  <?php else: ?>
    <button class="quick-add" disabled>Sold out</button>
  <?php endif; ?>
  </div>
</div>
