<?php
require_once __DIR__ . '/../config/config.php';
if (isCustomerLoggedIn()) { header('Location: ' . BASE_URL . '/pages/account.php'); exit; }
if (empty($_SESSION['login_challenge'])) { header('Location: ' . BASE_URL . '/pages/login.php'); exit; }
$pageTitle = 'Check your email';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>
<div class="shell"><div class="auth-wrap">
<h1>Check your email</h1>
<p class="sub">If <?= h($_SESSION['login_email']) ?> belongs to a customer account, you will receive a six-digit code. It expires in 10 minutes.</p>
<form class="form-card" method="post" action="<?= BASE_URL ?>/actions/verify_login_code.php">
<?= csrfField() ?>
<div class="field"><label for="code">Sign-in code</label><input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus></div>
<button type="submit" class="btn btn-primary btn-block">Sign in</button>
</form>
<form method="post" action="<?= BASE_URL ?>/actions/request_login_code.php" style="margin-top:1rem">
<?= csrfField() ?><input type="hidden" name="email" value="<?= h($_SESSION['login_email']) ?>">
<button type="submit" class="btn btn-outline btn-block">Send another code</button>
</form>
<p class="hint">Allow a minute for delivery and check your spam folder.</p>
<p class="auth-switch"><a href="<?= BASE_URL ?>/pages/login.php">Use another email</a> &middot; <a href="<?= BASE_URL ?>/pages/login.php?method=password">Use password instead</a></p>
</div></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
