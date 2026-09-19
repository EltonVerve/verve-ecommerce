<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$user = findUserById($pdo, (int) $_SESSION['user_id']);
$pageTitle = 'Edit Profile';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';
?>

<div class="shell section">
  <div class="account-layout">
    <nav class="account-nav">
      <a href="<?= BASE_URL ?>/pages/account.php?tab=orders">Order history</a>
      <a href="<?= BASE_URL ?>/pages/account.php?tab=wishlist">Wishlist</a>
      <a href="<?= BASE_URL ?>/pages/edit_account.php" class="active">Edit profile</a>
      <a href="<?= BASE_URL ?>/actions/logout.php">Log out</a>
    </nav>

    <div style="max-width:520px;">
      <h1>Edit profile</h1>
      <form action="<?= BASE_URL ?>/actions/update_account.php" method="post" class="form-card">
        <?= csrfField() ?>
        <div class="field">
          <label for="full_name">Full name</label>
          <input type="text" id="full_name" name="full_name" required value="<?= h($user['full_name']) ?>">
        </div>
        <div class="field">
          <label for="email">Email address</label>
          <input type="email" id="email" name="email" required value="<?= h($user['email']) ?>">
        </div>
        <div class="field">
          <label for="phone">Phone number</label>
          <input type="tel" id="phone" name="phone" value="<?= h($user['phone'] ?? '') ?>">
        </div>
        <hr class="divider">
        <p class="muted small">Leave the new password fields blank to keep your current password.</p>
        <div class="field">
          <label for="new_password">New password (12–72 bytes)</label>
          <input type="password" id="new_password" name="new_password" minlength="12" maxlength="72" autocomplete="new-password">
        </div>
        <div class="field">
          <label for="password_confirm">Confirm new password</label><input type="password" id="password_confirm" name="password_confirm" autocomplete="new-password"></div><div class="field"><label for="current_password">Current password (required to save changes)</label>
          <input type="password" id="current_password" name="current_password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Save changes</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
