<?php
/**
 * PRODUCT DETAIL PAGE
 * ---------------------------------------------------------
 * Shows one product: gallery, price, variant pickers, an
 * add-to-cart form, reviews, and a "you might also like" rail.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

$slug = is_string($_GET['slug'] ?? null) ? $_GET['slug'] : '';
$product = $slug !== '' ? getProductBySlug($pdo, $slug) : null;

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product not found';
    require __DIR__ . '/../includes/header.php';
    echo '<div class="shell section text-center"><h1>Product not found</h1><p class="muted">It may have been removed or is no longer available.</p><a class="btn btn-primary" href="' . BASE_URL . '/pages/shop.php">Back to shop</a></div>';
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$images = getProductImages($pdo, (int) $product['id']);
$galleryImages = $images ? array_map(fn($img) => productImageUrl($img['filename'], $product['name']), $images) : [productImageUrl($product['image'], $product['name'])];
$optionGroups = getOptionGroupsForProduct($pdo, (int) $product['id']);
$reviews = getReviewsForProduct($pdo, (int) $product['id']);
$ratingBreakdown = getRatingBreakdown($pdo, (int) $product['id']);
$related = getRelatedProducts($pdo, (int) $product['category_id'], (int) $product['id'], 4);
$inWishlist = isCustomerLoggedIn() && isInWishlist($pdo, (int) $_SESSION['user_id'], (int) $product['id']);

$onSale = !empty($product['compare_at_price']) && (float) $product['compare_at_price'] > (float) $product['price'];
$stock = (int) $product['stock'];
$pageTitle = $product['name'];

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell section">
  <div class="pdp-layout">
    <div>
      <div class="pdp-gallery-main">
        <img src="<?= h($galleryImages[0]) ?>" alt="<?= h($product['name']) ?>" data-gallery-main>
      </div>
      <?php if (count($galleryImages) > 1): ?>
        <div class="pdp-thumbs">
          <?php foreach ($galleryImages as $i => $img): ?>
            <button type="button" data-gallery-thumb="<?= h($img) ?>" class="<?= $i === 0 ? 'active' : '' ?>">
              <img src="<?= h($img) ?>" alt="">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="pdp-info">
      <div class="breadcrumb">
        <a href="<?= BASE_URL ?>/pages/shop.php">Shop</a> /
        <a href="<?= BASE_URL ?>/pages/shop.php?cat=<?= h($product['category_slug']) ?>"><?= h($product['category_name']) ?></a>
      </div>
      <h1><?= h($product['name']) ?></h1>

      <?php if ($product['review_count'] > 0): ?>
        <a class="pdp-rating" href="#customer-reviews">
          <span class="stars"><?= renderStars((float) $product['avg_rating']) ?></span>
          <span><?= number_format((float) $product['avg_rating'], 1) ?> · <?= (int) $product['review_count'] ?> review<?= $product['review_count'] == 1 ? '' : 's' ?></span>
        </a>
      <?php endif; ?>

      <div class="pdp-price-row">
        <span class="price" id="displayPrice" data-base-price="<?= (float) $product['price'] ?>"><?= money((float) $product['price']) ?></span>
        <?php if ($onSale): ?><span class="price-compare"><?= money((float) $product['compare_at_price']) ?></span><?php endif; ?>
        <?php if ($product['sku']): ?><span class="muted small">SKU: <?= h($product['sku']) ?></span><?php endif; ?>
      </div>

      <?php if ($stock <= 0): ?>
        <div class="stock-line out">Out of stock</div>
      <?php elseif ($stock <= 5): ?>
        <div class="stock-line low">Only <?= $stock ?> left in stock</div>
      <?php else: ?>
        <div class="stock-line in">In stock and ready to ship</div>
      <?php endif; ?>

      <form action="<?= BASE_URL ?>/actions/add_to_cart.php" method="post" id="addToCartForm">
        <?= csrfField() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">

        <?php foreach ($optionGroups as $group): ?>
          <div class="option-group">
            <h4><?= h($group['name']) ?></h4>
            <div class="option-swatches" data-option-group="<?= (int) $group['id'] ?>">
              <?php foreach ($group['values'] as $i => $value): ?>
                <label>
                  <input type="radio" name="options[<?= (int) $group['id'] ?>]" value="<?= (int) $value['id'] ?>"
                         data-delta="<?= (float) $value['price_delta'] ?>" <?= $i === 0 ? 'checked' : '' ?>>
                  <span><?= h($value['label']) ?><?= $value['price_delta'] > 0 ? ' (+' . money((float) $value['price_delta']) . ')' : '' ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <div class="pdp-buy-row">
          <div class="qty-stepper">
            <button type="button" data-step="down" aria-label="Decrease quantity">−</button>
            <input type="number" name="quantity" value="1" min="1" max="<?= max(1, $stock) ?>" aria-label="Quantity">
            <button type="button" data-step="up" aria-label="Increase quantity">+</button>
          </div>
          <button type="submit" class="btn btn-primary" <?= $stock <= 0 ? 'disabled' : '' ?>>
            <?= $stock <= 0 ? 'Sold out' : 'Add to cart' ?>
          </button>
        </div>
      </form>

      <form action="<?= BASE_URL ?>/actions/toggle_wishlist.php" method="post" style="margin-top:.9rem;">
        <?= csrfField() ?>
        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
        <input type="hidden" name="redirect" value="<?= h($_SERVER['REQUEST_URI'] ?? '') ?>">
        <button type="submit" class="btn btn-ghost btn-sm">
          <?= $inWishlist ? '♥ Saved to wishlist' : '♡ Save to wishlist' ?>
        </button>
      </form>

      <div class="trust-badges">
        <div>🚚 Free shipping over <?= money(FREE_SHIPPING_THRESHOLD) ?></div>
        <div>↩︎ 30-day returns</div>
        <div>🔒 Secure checkout</div>
      </div>

      <div class="pdp-description">
        <h3>Description</h3>
        <p><?= nl2br(h($product['description'] ?? '')) ?></p>
      </div>
    </div>
  </div>

  <section class="reviews-section" id="customer-reviews" aria-labelledby="reviews-title">
    <div class="reviews-heading"><div><span class="nav-eyebrow">FROM THE COMMUNITY</span><h2 id="reviews-title">Customer reviews</h2></div><a class="btn btn-outline btn-sm" href="#write-review" id="open-review-form">Write a review</a></div>
    <div class="rating-summary">
      <div class="rating-big">
        <div class="num"><?= number_format((float) $product['avg_rating'], 1) ?></div>
        <div class="stars"><?= renderStars((float) $product['avg_rating']) ?></div>
        <div class="muted small"><?= (int) $product['review_count'] ?> review<?= $product['review_count'] == 1 ? '' : 's' ?></div>
      </div>
      <div>
        <?php $total = max(1, array_sum($ratingBreakdown)); ?>
        <?php for ($star = 5; $star >= 1; $star--): ?>
          <div class="rating-bar-row">
            <span><?= $star ?>★</span>
            <div class="rating-bar-track"><div class="rating-bar-fill" style="width:<?= round(($ratingBreakdown[$star] ?? 0) / $total * 100) ?>%"></div></div>
            <span class="muted"><?= $ratingBreakdown[$star] ?? 0 ?></span>
          </div>
        <?php endfor; ?>
      </div>
    </div>

    <details class="review-composer" id="write-review">
    <summary>Share your experience <span aria-hidden="true">+</span></summary>
    <form action="<?= BASE_URL ?>/actions/add_review.php" method="post" class="form-card">
      <h3>Write a review</h3>
      <p class="muted small">How was <?= h($product['name']) ?>? Your experience helps other shoppers choose.</p>
      <?= csrfField() ?>
      <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
      <div class="form-grid">
        <div class="field">
          <label for="reviewName">Your name</label>
          <input type="text" id="reviewName" name="author_name" required value="<?= isCustomerLoggedIn() ? h(findUserById($pdo, (int) $_SESSION['user_id'])['full_name'] ?? '') : '' ?>">
        </div>
        <div class="field">
          <label for="reviewRating">Rating</label>
          <select id="reviewRating" name="rating" required>
            <option value="5">★★★★★ Excellent</option>
            <option value="4">★★★★☆ Good</option>
            <option value="3">★★★☆☆ Average</option>
            <option value="2">★★☆☆☆ Below average</option>
            <option value="1">★☆☆☆☆ Poor</option>
          </select>
        </div>
      </div>
      <div class="field">
        <label for="reviewComment">Your review (optional)</label>
        <textarea id="reviewComment" name="comment" placeholder="What did you think?"></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Submit review</button>
    </form>
    </details>

    <?php foreach ($reviews as $review): ?>
      <div class="review-item">
        <div class="stars"><?= renderStars((float) $review['rating']) ?></div>
        <div class="author"><?= h($review['author_name']) ?></div>
        <div class="date"><?= date('F j, Y', strtotime($review['created_at'])) ?></div>
        <?php if ($review['comment']): ?><p><?= nl2br(h($review['comment'])) ?></p><?php endif; ?>
      </div>
    <?php endforeach; ?>
    <?php if (!$reviews): ?><p class="muted">No reviews yet — be the first to share your thoughts.</p><?php endif; ?>


  </section>

  <?php if ($related): ?>
    <div class="section-head" style="margin-top:3rem;">
      <h2>You might also like</h2>
    </div>
    <div class="product-grid">
      <?php foreach ($related as $product): ?>
        <?php require __DIR__ . '/../includes/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<script>
(function () {
  var form = document.getElementById('addToCartForm');
  var priceEl = document.getElementById('displayPrice');
  if (!form || !priceEl) return;
  var base = parseFloat(priceEl.dataset.basePrice || '0');
  function recalc() {
    var total = base;
    form.querySelectorAll('input[type=radio]:checked').forEach(function (input) {
      total += parseFloat(input.dataset.delta || '0');
    });
    priceEl.textContent = '<?= STORE_CURRENCY_SYMBOL ?>' + total.toFixed(2);
  }
  form.querySelectorAll('input[type=radio]').forEach(function (input) {
    input.addEventListener('change', recalc);
  });
  recalc();
})();
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
