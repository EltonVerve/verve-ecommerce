<?php
/**
 * HOMEPAGE
 * ---------------------------------------------------------
 * Lives here in pages/, same as every other page. The file
 * at the project root (index.php) is just a thin loader that
 * pulls this in.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'Home';
$categories = getAllCategories($pdo);
$featured = getFeaturedProducts($pdo, 8);
$newArrivals = getNewArrivals($pdo, 8);

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<?php require __DIR__ . '/../includes/home-carousel.php'; ?>

<section class="shell section" aria-labelledby="category-heading">
  <div class="section-head"><h2 id="category-heading">Shop by category</h2></div>
  <div class="category-grid">
    <?php foreach ($categories as $category): ?>
      <a class="category-card" href="<?= BASE_URL ?>/pages/shop.php?cat=<?= h($category['slug']) ?>">
        <img src="<?= h(productImageUrl($category['image'], $category['name'])) ?>" alt="" width="600" height="600" loading="lazy">
        <span class="category-card-name"><?= h($category['name']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="shell">
    <div class="section-head">
      <h2>Customer favourites</h2>
      <a class="see-all" href="<?= BASE_URL ?>/pages/shop.php?sort=rating">See all →</a>
    </div>
    <div class="product-grid">
      <?php foreach ($featured as $product): ?>
        <?php require __DIR__ . '/../includes/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section" style="background:var(--paper); border-top:1px solid var(--line); border-bottom:1px solid var(--line);">
  <div class="shell" style="display:grid; grid-template-columns:repeat(3,1fr); gap:2rem; text-align:center; padding:1rem 0;">
    <div>
      <h3 style="font-size:1.05rem;">Free shipping</h3>
      <p class="muted small">On every order over <?= money(FREE_SHIPPING_THRESHOLD) ?>.</p>
    </div>
    <div>
      <h3 style="font-size:1.05rem;">Easy returns</h3>
      <p class="muted small">30 days to change your mind, no questions asked.</p>
    </div>
    <div>
      <h3 style="font-size:1.05rem;">Secure checkout</h3>
      <p class="muted small">Your details are encrypted and never shared.</p>
    </div>
  </div>
</section>

<?php if ($newArrivals): ?>
<section class="section" id="new-arrivals">
  <div class="shell">
    <div class="section-head">
      <h2>New arrivals</h2>
      <a class="see-all" href="<?= BASE_URL ?>/pages/shop.php?sort=newest">See all →</a>
    </div>
    <div class="product-grid">
      <?php foreach ($newArrivals as $product): ?>
        <?php require __DIR__ . '/../includes/product-card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/../includes/faq.php'; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
