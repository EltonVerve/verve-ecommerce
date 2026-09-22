<?php
/**
 * SITE HEADER
 * ---------------------------------------------------------
 * Included at the top of every public page with:
 *   require_once __DIR__ . '/../includes/header.php';
 *
 * Expects config/config.php to already be loaded (for $pdo,
 * isLoggedIn(), getCartCount(), etc.)
 * ---------------------------------------------------------
 */
$cartCount = getCartCount($pdo);
$currentPage = basename($_SERVER['PHP_SELF']);
$shopCategories = getAllCategories($pdo);
$headerSearch = $currentPage === 'shop.php' && is_string($_GET['q'] ?? null)
    ? trim(substr($_GET['q'], 0, 100)) : '';
$headerCustomer = isCustomerLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' · ' . SITE_NAME : SITE_NAME . ' — ' . SITE_TAGLINE ?></title>
<meta name="description" content="<?= h(SITE_NAME) ?> — <?= h(SITE_TAGLINE) ?> Shop electronics, fashion, home goods and more.">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/../public/assets/css/style.css') ?>">
<link rel="icon" type="image/svg+xml" href="<?= BASE_URL ?>/public/assets/brand/rada-cart-icon.svg">
</head>
<body>

<a href="#main" class="skip-link">Skip to content</a>

<div class="announce-bar">
  <div class="shell announce-inner">
    <span>Free shipping on orders over <?= money(FREE_SHIPPING_THRESHOLD) ?></span>
    <span class="announce-sep">·</span>
    <span>New arrivals dropping weekly</span>
  </div>
</div>

<header id="siteHeader" class="site-header rada-navigation">
  <div class="shell header-row">
    <button class="nav-toggle" id="navToggle" aria-expanded="false" aria-controls="mobileNav" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>

    <a href="<?= BASE_URL ?>/index.php" class="brand" aria-label="<?= h(SITE_NAME) ?> home">
      <?php require __DIR__ . '/brand-logo.php'; ?>
      <span class="brand-caption">GREAT FINDS. ON YOUR RADAR.</span>
    </a>

    <nav class="main-nav" aria-label="Primary">
      <details class="category-menu" id="categoryMenu">
        <summary><span aria-hidden="true">&#8862;</span> Explore categories <span class="category-chevron" aria-hidden="true">&#8964;</span></summary>
        <div class="category-mega">
          <div class="category-mega-intro"><span class="nav-eyebrow">FIND YOUR EVERYDAY</span><h2>A little of<br>everything you love.</h2><a href="<?= BASE_URL ?>/pages/shop.php">Explore the whole store &rarr;</a></div>
          <div class="category-mega-grid">
            <?php foreach ($shopCategories as $category): ?>
              <a href="<?= BASE_URL ?>/pages/shop.php?cat=<?= h($category['slug']) ?>" <?= $currentPage === 'shop.php' && ($_GET['cat'] ?? '') === $category['slug'] ? 'aria-current="page"' : '' ?>>
                <img src="<?= h(productImageUrl($category['image'], $category['name'], 600)) ?>" alt="" width="56" height="56" loading="lazy">
                <span><?= h($category['name']) ?></span><span aria-hidden="true">&nearr;</span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </details>
      <a href="<?= BASE_URL ?>/pages/shop.php" <?= $currentPage === 'shop.php' && empty($_GET['cat']) ? 'aria-current="page"' : '' ?>>All Products</a>
      <a href="<?= BASE_URL ?>/pages/shop.php?sort=newest">New arrivals <span class="nav-new-dot" aria-hidden="true"></span></a>
      <a href="<?= BASE_URL ?>/pages/account.php?tab=orders" <?= in_array($currentPage, ['account.php', 'order.php'], true) ? 'aria-current="page"' : '' ?>>My orders</a>
      <a href="<?= BASE_URL ?>/pages/contact.php" <?= $currentPage === 'contact.php' ? 'aria-current="page"' : '' ?>>Contact</a>
    </nav>

    <form class="header-search" action="<?= BASE_URL ?>/pages/shop.php" method="get" role="search" data-suggest-url="<?= BASE_URL ?>/pages/search-suggestions.php">
      <input type="search" name="q" placeholder="Search products…" value="<?= h($headerSearch) ?>" aria-label="Search products" autocomplete="off" id="headerSearchInput">
      <button type="submit" aria-label="Search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
      </button>
      <div id="searchSuggestions" class="search-suggestions" hidden></div>
    </form>

    <div class="header-actions">
      <?php if ($headerCustomer): ?>
        <a href="<?= BASE_URL ?>/pages/account.php" class="icon-link" aria-label="My account" title="My account">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"/></svg>
        </a>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/pages/login.php" class="icon-link" aria-label="Log in" title="Log in">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 20c1.5-4 5-6 8-6s6.5 2 8 6"/></svg>
        </a>
      <?php endif; ?>
      <a href="<?= BASE_URL ?>/pages/cart.php" class="icon-link cart-link" aria-label="Cart, <?= (int) $cartCount ?> items" title="Cart">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 4h2l2.4 12.2a2 2 0 0 0 2 1.6h8.2a2 2 0 0 0 2-1.6L21 8H6"/><circle cx="9" cy="21" r="1.4"/><circle cx="18" cy="21" r="1.4"/></svg>
        <span>Cart</span>
        <span class="cart-badge"><?= (int) $cartCount ?></span>
      </a>
    </div>
  </div>

  <nav id="mobileNav" class="mobile-nav" aria-label="Mobile" hidden>
    <span class="nav-eyebrow">YOUR EVERYDAY, DISCOVERED</span>
    <form action="<?= BASE_URL ?>/pages/shop.php" method="get" role="search" class="mobile-search">
      <input type="search" name="q" placeholder="Search products…" value="<?= h($headerSearch) ?>" aria-label="Search products">
    </form>
    <a href="<?= BASE_URL ?>/pages/shop.php" <?= $currentPage === 'shop.php' && empty($_GET['cat']) ? 'aria-current="page"' : '' ?>>All Products</a>
    <?php foreach ($shopCategories as $category): ?>
      <a href="<?= BASE_URL ?>/pages/shop.php?cat=<?= h($category['slug']) ?>" <?= $currentPage === 'shop.php' && ($_GET['cat'] ?? '') === $category['slug'] ? 'aria-current="page"' : '' ?>><?= h($category['name']) ?></a>
    <?php endforeach; ?>
    <a href="<?= BASE_URL ?>/pages/contact.php" <?= $currentPage === 'contact.php' ? 'aria-current="page"' : '' ?>>Contact</a>
    <hr>
    <a href="<?= BASE_URL ?>/pages/account.php?tab=orders">My orders</a>
    <?php if ($headerCustomer): ?>
      <a href="<?= BASE_URL ?>/pages/account.php">My account</a>
      <a href="<?= BASE_URL ?>/actions/logout.php">Log out</a>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/pages/login.php">Log in</a>
      <a href="<?= BASE_URL ?>/pages/register.php">Create an account</a>
    <?php endif; ?>
  </nav>
</header>

<main id="main">
