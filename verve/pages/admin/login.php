<?php
require_once __DIR__ . '/../../config/config.php';
if (isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: ' . BASE_URL . '/pages/admin/' . (adminScope($pdo) === 'owner' ? 'dashboard.php' : 'orders.php'));
    exit;
}
$pageTitle = 'Admin Login';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login · <?= h(SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/style.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/admin.css?v=<?= (int) filemtime(__DIR__ . '/../../public/assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<div class="login-shell">
  <div class="login-card">
    <div class="brand" style="color:var(--ink);"><?php require __DIR__ . '/../../includes/brand-logo.php'; ?></div>
    <p class="text-center muted small" style="margin-bottom:1.5rem;">Admin panel</p>
    <?php require __DIR__ . '/../../includes/admin/admin_flash.php'; ?>
    <form action="<?= BASE_URL ?>/actions/admin/login.php" method="post">
      <?= csrfField() ?>
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
  </div>
</div>
</body>
</html>
