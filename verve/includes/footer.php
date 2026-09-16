</main>

<footer class="site-footer" aria-label="Site footer">
  <div class="shell footer-shell">
    <div class="footer-top">
      <div class="footer-brand">
        <a class="brand" href="<?= BASE_URL ?>/index.php" aria-label="<?= h(SITE_NAME) ?> home"><?php require __DIR__ . '/brand-logo.php'; ?></a>
        <p><?= h(SITE_TAGLINE) ?> Quality goods, fair prices, and a checkout that doesn't get in your way.</p>
      </div>
      <nav aria-labelledby="footer-shop-title">
        <h3 id="footer-shop-title">Shop</h3>
        <ul>
          <li><a href="<?= BASE_URL ?>/pages/shop.php">All products</a></li>
          <?php $footerCategories = getAllCategories($pdo); ?>
          <?php foreach (array_slice($footerCategories, 0, 5) as $category): ?>
            <li><a href="<?= BASE_URL ?>/pages/shop.php?cat=<?= h($category['slug']) ?>"><?= h($category['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <nav aria-labelledby="footer-help-title">
        <h3 id="footer-help-title">Help</h3>
        <ul>
          <li><a href="<?= BASE_URL ?>/pages/contact.php">Contact us</a></li>
          <li><a href="<?= BASE_URL ?>/index.php#faq">FAQs</a></li>
          <li><a href="<?= BASE_URL ?>/pages/contact.php?topic=returns#contact-form">Returns &amp; exchanges</a></li>
          <li><a href="<?= BASE_URL ?>/pages/contact.php?topic=order_issue#contact-form">Order issue</a></li>
        </ul>
      </nav>
      <nav aria-labelledby="footer-account-title">
        <h3 id="footer-account-title">Account</h3>
        <ul>
          <?php if (isCustomerLoggedIn()): ?>
            <li><a href="<?= BASE_URL ?>/pages/account.php">My account</a></li>
            <li><a href="<?= BASE_URL ?>/actions/logout.php">Log out</a></li>
          <?php else: ?>
            <li><a href="<?= BASE_URL ?>/pages/login.php">Log in</a></li>
            <li><a href="<?= BASE_URL ?>/pages/register.php">Create an account</a></li>
          <?php endif; ?>
          <li><a href="<?= BASE_URL ?>/pages/cart.php">Your cart</a></li>
        </ul>
      </nav>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.</span>
      <a href="#main">Back to top ↑</a>
    </div>
  </div>
</footer>

<script src="<?= BASE_URL ?>/public/assets/js/main.js?v=<?= (int) filemtime(__DIR__ . '/../public/assets/js/main.js') ?>"></script>
</body>
</html>
