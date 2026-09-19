<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();
$user = findUserById($pdo, (int) $_SESSION['user_id']);
$pageTitle = 'My profile';
$initial = mb_strtoupper(mb_substr(trim($user['full_name']), 0, 1));
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>
<div class="profile-banner">
  <div><span class="profile-eyebrow">Account settings</span><h1>My profile</h1><p>A little about you. Everything you need to keep your account up to date.</p></div>
  <span class="profile-role"><?= $user['admin_scope']==='owner' ? 'Owner' : 'Order staff' ?></span>
</div>
<div class="admin-profile-layout">
  <aside class="admin-card profile-summary" aria-label="Account summary">
    <div class="profile-avatar" aria-hidden="true"><?= h($initial) ?></div>
    <h2><?= h($user['full_name']) ?></h2>
    <p class="profile-email"><?= h($user['email']) ?></p>
    <dl>
      <div><dt>Member since</dt><dd><?= h(date('M j, Y', strtotime($user['created_at']))) ?></dd></div>
      <div><dt>Phone number</dt><dd><?= h($user['phone'] ?: 'Not added yet') ?></dd></div>
      <div><dt>Account role</dt><dd><?= $user['admin_scope']==='owner' ? 'Owner' : 'Order staff' ?></dd></div>
    </dl>
    <div class="profile-security-note"><strong>Your account, protected</strong><p>For your security, your session ends after 30 minutes of inactivity.</p></div>
  </aside>
  <form class="profile-editor" method="post" action="<?= BASE_URL ?>/actions/admin/update_profile.php">
    <?= csrfField() ?>
    <section class="admin-card profile-section" aria-labelledby="personal-title">
      <header><span class="profile-section-number" aria-hidden="true">01</span><div><h2 id="personal-title">Personal details</h2><p>Your name and contact information.</p></div></header>
      <div class="profile-fields">
        <div class="field profile-field-wide"><label for="full_name">Full name</label><input id="full_name" name="full_name" autocomplete="name" minlength="2" maxlength="120" required value="<?= h($user['full_name']) ?>"></div>
        <div class="field"><label for="email">Email address</label><input id="email" type="email" name="email" autocomplete="email" maxlength="150" required value="<?= h($user['email']) ?>"></div>
        <div class="field"><label for="phone">Phone number <span class="muted">(optional)</span></label><input id="phone" type="tel" name="phone" autocomplete="tel" maxlength="30" placeholder="Add your phone number" value="<?= h($user['phone']) ?>"></div>
      </div>
    </section>
    <section class="admin-card profile-section" aria-labelledby="password-title">
      <header><span class="profile-section-number" aria-hidden="true">02</span><div><h2 id="password-title">Password &amp; security</h2><p>Leave these fields blank to keep your current password.</p></div></header>
      <div class="profile-fields">
        <div class="field"><label for="new_password">New password</label><input id="new_password" type="password" name="new_password" minlength="12" maxlength="72" autocomplete="new-password" aria-describedby="password-guidance"></div>
        <div class="field"><label for="password_confirm">Confirm new password</label><input id="password_confirm" type="password" name="password_confirm" autocomplete="new-password"></div>
      </div>
      <p id="password-guidance" class="profile-hint">Use a unique passphrase of at least 12 characters. Maximum 72 bytes; accented letters and symbols can use more than one byte.</p>
    </section>
    <section class="admin-card profile-section profile-save" aria-labelledby="confirm-title">
      <header><span class="profile-section-number" aria-hidden="true">03</span><div><h2 id="confirm-title">Confirm your changes</h2><p>Enter your current password to save securely.</p></div></header>
      <div class="field"><label for="current_password">Current password</label><input id="current_password" type="password" name="current_password" required autocomplete="current-password"></div>
      <div class="profile-save-actions"><span>Changes apply when you save.</span><button class="btn btn-primary" type="submit">Save changes</button></div>
    </section>
  </form>
</div>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
