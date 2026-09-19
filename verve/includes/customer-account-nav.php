<?php // Expects the authenticated $user and $accountSection from the page. ?>
<aside class="customer-profile-summary">
  <div class="customer-profile-avatar" aria-hidden="true"><?= h(mb_strtoupper(mb_substr(trim($user['full_name']), 0, 1))) ?></div>
  <h2><?= h($user['full_name']) ?></h2>
  <p class="customer-profile-email"><?= h($user['email']) ?></p>
  <p class="customer-profile-joined">Member since <?= h(date('M Y', strtotime($user['created_at']))) ?></p>
  <nav class="account-nav" aria-label="My account">
    <?php foreach (['orders' => ['Order history', '/pages/account.php?tab=orders'], 'wishlist' => ['Wishlist', '/pages/account.php?tab=wishlist'], 'cart' => ['My cart', '/pages/cart.php'], 'profile' => ['My profile', '/pages/edit_account.php']] as $section => [$label, $path]): ?>
      <a href="<?= BASE_URL . $path ?>" <?= $accountSection === $section ? 'class="active" aria-current="page"' : '' ?>><?= h($label) ?><?= $section === 'cart' ? ' (' . (int) $cartCount . ')' : '' ?></a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/actions/logout.php">Log out</a>
  </nav>
</aside>
