<?php
require_once __DIR__ . '/../config/config.php';

$token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
$reset = $token !== '' ? findValidReset($pdo, $token) : null;

$pageTitle = 'Reset Password';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell">
  <div class="auth-wrap">
    <h1>Choose a new password</h1>
    <?php if (!$reset): ?>
      <p class="sub">This reset link is invalid or has expired.</p>
      <div class="text-center"><a class="btn btn-primary" href="<?= BASE_URL ?>/pages/forgot_password.php">Request a new link</a></div>
    <?php else: ?>
      <p class="sub">For <?= h($reset['email']) ?></p>
      <form action="<?= BASE_URL ?>/actions/reset_password.php" method="post" class="form-card">
        <?= csrfField() ?>
        <input type="hidden" name="token" value="<?= h($token) ?>">
        <div class="field">
          <label for="password">New password</label>
          <input type="password" id="password" name="password" required minlength="8" autofocus>
        </div>
        <div class="field">
          <label for="password_confirm">Confirm new password</label>
          <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Update password</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
