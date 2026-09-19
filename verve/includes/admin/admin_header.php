<?php
/**
 * ADMIN LAYOUT — HEADER
 * ---------------------------------------------------------
 * Included at the top of every page in pages/admin/ (except
 * login.php, which has its own standalone design).
 * Expects requireAdmin() to have already run on the page.
 * ---------------------------------------------------------
 */
$adminCurrentPage = basename($_SERVER['PHP_SELF']);
$staffScope = adminScope($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? h($pageTitle) . ' · Admin' : 'Admin' ?> · <?= h(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css?v=<?= (int) filemtime(__DIR__ . '/../../public/assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/../../public/assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-sidebar-top">
      <a class="brand" href="<?= BASE_URL ?>/pages/admin/<?= $staffScope === 'owner' ? 'dashboard.php' : 'orders.php' ?>"><?php require __DIR__ . '/../brand-logo.php'; ?></a>
      <nav aria-label="Admin navigation">
        <?php if ($staffScope === 'owner'): ?>
        <a href="<?= BASE_URL ?>/pages/admin/dashboard.php" class="<?= $adminCurrentPage === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        <a href="<?= BASE_URL ?>/pages/admin/products.php" class="<?= in_array($adminCurrentPage, ['products.php', 'product_form.php'], true) ? 'active' : '' ?>">Products</a>
        <a href="<?= BASE_URL ?>/pages/admin/categories.php" class="<?= $adminCurrentPage === 'categories.php' ? 'active' : '' ?>">Categories</a>
        <a href="<?= BASE_URL ?>/pages/admin/coupons.php" class="<?= $adminCurrentPage === 'coupons.php' ? 'active' : '' ?>">Coupons</a>
        <a href="<?= BASE_URL ?>/pages/admin/stock.php" class="<?= $adminCurrentPage === 'stock.php' ? 'active' : '' ?>">Low stock</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/pages/admin/orders.php" class="<?= in_array($adminCurrentPage, ['orders.php', 'order_detail.php'], true) ? 'active' : '' ?>">Orders</a>
        <?php if ($staffScope === 'owner'): ?>
        <a href="<?= BASE_URL ?>/pages/admin/messages.php" class="<?= in_array($adminCurrentPage, ['messages.php', 'message_detail.php'], true) ? 'active' : '' ?>">Messages</a>
        <a href="<?= BASE_URL ?>/pages/admin/customers.php" class="<?= in_array($adminCurrentPage, ['customers.php', 'customer_detail.php'], true) ? 'active' : '' ?>">Customers</a>
        <a href="<?= BASE_URL ?>/pages/admin/reports.php" class="<?= $adminCurrentPage === 'reports.php' ? 'active' : '' ?>">Reports</a>
        <a href="<?= BASE_URL ?>/pages/admin/delivery.php" class="<?= $adminCurrentPage === 'delivery.php' ? 'active' : '' ?>">Delivery areas</a>
        <a href="<?= BASE_URL ?>/pages/admin/staff.php" class="<?= $adminCurrentPage === 'staff.php' ? 'active' : '' ?>">Staff access</a>
        <a href="<?= BASE_URL ?>/pages/admin/audit.php" class="<?= $adminCurrentPage === 'audit.php' ? 'active' : '' ?>">Admin activity</a>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/pages/admin/profile.php" class="<?= $adminCurrentPage === 'profile.php' ? 'active' : '' ?>">My profile</a>
      </nav>
    </div>
    <div class="sidebar-foot">
      <div class="sidebar-account">Signed in as<br><strong style="color:#fff;"><?= h($_SESSION['user_name'] ?? '') ?></strong></div>
      <a href="<?= BASE_URL ?>/actions/admin/logout.php">Log out</a>
      <a href="<?= BASE_URL ?>/index.php">← View store</a>
    </div>
  </aside>
  <script src="<?= BASE_URL ?>/public/assets/js/admin-sidebar.js?v=<?= (int) filemtime(__DIR__ . '/../../public/assets/js/admin-sidebar.js') ?>" data-storage-key="<?= h(BASE_URL . ':admin-sidebar:' . (int) ($_SESSION['user_id'] ?? 0)) ?>"></script>
  <div class="admin-main">
