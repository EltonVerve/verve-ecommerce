<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();
$user = findUserById($pdo, (int) $_SESSION['user_id']);
$pageTitle = 'My profile';
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>
<div class="admin-topbar"><h1>My profile</h1></div>
<form class="admin-card" style="max-width:600px" method="post" action="<?= BASE_URL ?>/actions/admin/update_profile.php">
<?= csrfField() ?>
<div class="field"><label for="full_name">Full name</label><input id="full_name" name="full_name" maxlength="120" required value="<?= h($user['full_name']) ?>"></div>
<div class="field"><label for="email">Email</label><input id="email" type="email" name="email" maxlength="150" required value="<?= h($user['email']) ?>"></div>
<div class="field"><label for="phone">Phone</label><input id="phone" type="tel" name="phone" maxlength="30" value="<?= h($user['phone']) ?>"></div>
<p>Leave the new password blank to keep your current password.</p>
<div class="field"><label for="new_password">New password (12–72 bytes)</label><input id="new_password" type="password" name="new_password" minlength="12" maxlength="72" autocomplete="new-password"></div>
<div class="field"><label for="password_confirm">Confirm new password</label><input id="password_confirm" type="password" name="password_confirm" autocomplete="new-password"></div>
<div class="field"><label for="current_password">Current password (required)</label><input id="current_password" type="password" name="current_password" required autocomplete="current-password"></div>
<button class="btn btn-primary" type="submit">Save changes</button>
</form>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
