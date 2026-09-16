<?php
require_once __DIR__ . '/../config/config.php';
if (isCustomerLoggedIn()) { header('Location: ' . BASE_URL . '/pages/account.php'); exit; }

$pageTitle = 'Log In';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell">
  <div class="auth-wrap">
    <h1>Welcome back</h1>
    <p class="sub">Log in to view your orders and saved items.</p>
    <form action="<?= BASE_URL ?>/actions/login.php" method="post" class="form-card">
      <?= csrfField() ?>
      <input type="hidden" name="redirect" value="<?= h($_GET['redirect'] ?? '') ?>">
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>
        <p class="hint"><a href="<?= BASE_URL ?>/pages/forgot_password.php">Forgot your password?</a></p>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Log in</button>
    </form>
    <p class="auth-switch">New here? <a href="<?= BASE_URL ?>/pages/register.php">Create an account</a></p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
