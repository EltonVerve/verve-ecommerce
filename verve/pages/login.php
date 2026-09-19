<?php
require_once __DIR__ . '/../config/config.php';
if (isCustomerLoggedIn()) { header('Location: ' . BASE_URL . '/pages/account.php'); exit; }

$pageTitle = 'Log In';
$passwordLogin = ($_GET['method'] ?? '') === 'password';
$emailCodesAvailable = customerCodeMailReady();
$prefillEmail = is_string($_SESSION['login_prefill_email'] ?? null) ? $_SESSION['login_prefill_email'] : '';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell">
  <div class="auth-wrap">
    <h1>Welcome back</h1>
    <p class="sub"><?= $passwordLogin ? 'Enter your password to finish signing in.' : ($emailCodesAvailable ? 'Enter your email to receive a sign-in code. No password to remember.' : 'Enter your email to get started.') ?></p>
    <form action="<?= BASE_URL ?>/actions/<?= $passwordLogin ? 'login.php' : 'request_login_code.php' ?>" method="post" class="form-card">
      <?= csrfField() ?>
      <div class="field">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" autocomplete="email" maxlength="150" value="<?= h($prefillEmail) ?>" required <?= $passwordLogin ? '' : 'autofocus' ?>>
      </div>
      <?php if ($passwordLogin): ?><div class="field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" autocomplete="current-password" required autofocus>
        <p class="hint"><a href="<?= BASE_URL ?>/pages/forgot_password.php">Forgot your password?</a></p>
      </div><?php endif; ?>
      <button type="submit" class="btn btn-primary btn-block"><?= $passwordLogin ? 'Sign in' : 'Continue' ?></button>
    </form>
    <?php if ($emailCodesAvailable): ?><p class="auth-switch"><a href="<?= BASE_URL ?>/pages/login.php<?= $passwordLogin ? '' : '?method=password' ?>"><?= $passwordLogin ? 'Sign in with an email code' : 'Use password instead' ?></a></p><?php endif; ?>
    <p class="auth-switch">New here? <a href="<?= BASE_URL ?>/pages/register.php">Create an account</a></p>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
