<?php
require_once __DIR__ . '/../config/config.php';
if (isCustomerLoggedIn()) { header('Location: ' . BASE_URL . '/pages/account.php'); exit; }

$pageTitle = 'Create Account';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell">
  <div class="auth-wrap">
    <h1>Create your account</h1>
    <p class="sub">Faster checkout, order history and saved items.</p>
    <form action="<?= BASE_URL ?>/actions/register.php" method="post" class="form-card">
      <?= csrfField() ?>
      <div class="field">
        <label for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" required autofocus>
      </div>
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" required minlength="8">
        <p class="hint">At least 8 characters.</p>
      </div>
      <div class="field">
        <label for="password_confirm">Confirm password</label>
        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create account</button>
    </form>
    <p class="auth-switch">Already have an account? <a href="<?= BASE_URL ?>/pages/login.php">Log in</a></p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
