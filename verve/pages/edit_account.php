<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();
$user = findUserById($pdo, (int) $_SESSION['user_id']);
$pageTitle = 'My profile';
$initial = mb_strtoupper(mb_substr(trim($user['full_name']), 0, 1));
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>
<div class="shell section customer-profile">
  <header class="customer-profile-banner">
    <div><span class="customer-profile-eyebrow">Your account</span><h1>My profile</h1><p>Keep your details up to date for your next visit.</p></div>
    <a class="btn customer-profile-shop" href="<?= BASE_URL ?>/pages/shop.php">Continue shopping &rarr;</a>
  </header>
  <div class="account-layout">
    <?php $accountSection = 'profile'; require __DIR__ . '/../includes/customer-account-nav.php'; ?>
    <form action="<?= BASE_URL ?>/actions/update_account.php" method="post" class="customer-profile-form">
      <?= csrfField() ?>
      <section class="form-card" aria-labelledby="personal-title">
        <header class="customer-profile-section-title"><span aria-hidden="true">01</span><div><h2 id="personal-title">Personal details</h2><p>Your name and contact information, all in one place.</p></div></header>
        <div class="field"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" autocomplete="name" minlength="2" maxlength="120" required value="<?= h($user['full_name']) ?>"></div>
        <div class="form-grid">
          <div class="field"><label for="email">Email address</label><input type="email" id="email" name="email" autocomplete="email" maxlength="150" required value="<?= h($user['email']) ?>"></div>
          <div class="field"><label for="phone">Phone number <span class="muted">(optional)</span></label><input type="tel" id="phone" name="phone" autocomplete="tel" maxlength="30" placeholder="Add your phone number" value="<?= h($user['phone'] ?? '') ?>"></div>
        </div>
      </section>
      <section class="form-card" aria-labelledby="security-title">
        <header class="customer-profile-section-title"><span aria-hidden="true">02</span><div><h2 id="security-title">Password &amp; security</h2><p>Leave these fields blank to keep your current password.</p></div></header>
        <div class="form-grid">
          <div class="field"><label for="new_password">New password</label><input type="password" id="new_password" name="new_password" minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-guidance"></div>
          <div class="field"><label for="password_confirm">Confirm new password</label><input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password"></div>
        </div>
        <p id="password-guidance" class="hint">Choose a unique passphrase with at least 12 characters. Maximum 72 bytes; accented letters and symbols may use more than one byte.</p>
      </section>
      <section class="form-card customer-profile-confirm" aria-labelledby="confirm-title">
        <header class="customer-profile-section-title"><span aria-hidden="true">03</span><div><h2 id="confirm-title">Save your changes</h2><p>Confirm it's you by entering your current password.</p></div></header>
        <div class="field"><label for="current_password">Current password</label><input type="password" id="current_password" name="current_password" autocomplete="current-password" required></div>
        <p class="hint"><a href="<?= BASE_URL ?>/pages/forgot_password.php">Forgot your password?</a></p>
        <div class="customer-profile-save"><span>Your changes are saved securely.</span><button type="submit" class="btn btn-primary">Save changes</button></div>
      </section>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
