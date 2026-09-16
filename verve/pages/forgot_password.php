<?php
require_once __DIR__ . '/../config/config.php';

$pageTitle = 'Forgot Password';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell">
  <div class="auth-wrap">
    <h1>Reset your password</h1>
    <p class="sub">We'll send a reset link to your email address.</p>
    <form action="<?= BASE_URL ?>/actions/forgot_password.php" method="post" class="form-card">
      <?= csrfField() ?>
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" required autofocus>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Send reset link</button>
    </form>
    <?php if (!empty($_SESSION['demo_reset_link'])): ?>
      <div class="flash-banner flash-success" style="margin-top:1rem;">
        Demo mode (no mail server configured) — your reset link:
        <a href="<?= h($_SESSION['demo_reset_link']) ?>"><?= h($_SESSION['demo_reset_link']) ?></a>
      </div>
      <?php unset($_SESSION['demo_reset_link']); ?>
    <?php endif; ?>
    <p class="auth-switch"><a href="<?= BASE_URL ?>/pages/login.php">Back to log in</a></p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
