<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Coupons';
$coupons = getAllCoupons($pdo);
$editId = !empty($_GET['edit']) ? (int) $_GET['edit'] : null;
$editCoupon = $editId ? getCouponById($pdo, $editId) : null;

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar">
  <h1>Coupons</h1>
</div>

<div style="display:grid; grid-template-columns:1.4fr .9fr; gap:1.6rem; align-items:start;">
  <div class="admin-card">
    <table class="admin-table">
      <thead>
        <tr>
          <th>Code</th>
          <th>Type</th>
          <th>Value</th>
          <th>Status</th>
          <th>Expires</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($coupons as $coupon): ?>
          <tr>
            <td><strong><?= h($coupon['code']) ?></strong></td>
            <td><?= h($coupon['type'] === 'percent' ? 'Percent' : 'Flat') ?></td>
            <td><?= $coupon['type'] === 'percent' ? h((string) $coupon['value']) . '%' : money((float) $coupon['value']) ?></td>
            <td><span class="badge-dot <?= (int) $coupon['is_active'] ? 'on' : 'off' ?>"><?= (int) $coupon['is_active'] ? 'Active' : 'Inactive' ?></span></td>
            <td><?= $coupon['expires_at'] ? h($coupon['expires_at']) : 'Never' ?></td>
            <td>
              <a href="?edit=<?= (int) $coupon['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
              <form action="<?= BASE_URL ?>/actions/admin/delete_coupon.php" method="post" style="display:inline;" onsubmit="return confirm('Delete this coupon?');">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $coupon['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$coupons): ?><tr><td colspan="6" class="muted">No coupons yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <h2><?= $editCoupon ? 'Edit coupon' : 'New coupon' ?></h2>
    <form action="<?= BASE_URL ?>/actions/admin/save_coupon.php" method="post">
      <?= csrfField() ?>
      <?php if ($editCoupon): ?><input type="hidden" name="id" value="<?= (int) $editCoupon['id'] ?>"><?php endif; ?>

      <div class="field">
        <label for="code">Coupon code</label>
        <input type="text" id="code" name="code" required value="<?= h($editCoupon['code'] ?? '') ?>" style="text-transform:uppercase;">
      </div>

      <div class="field">
        <label for="type">Type</label>
        <select id="type" name="type">
          <option value="percent" <?= (($editCoupon['type'] ?? 'percent') === 'percent') ? 'selected' : '' ?>>Percent</option>
          <option value="flat" <?= (($editCoupon['type'] ?? 'percent') === 'flat') ? 'selected' : '' ?>>Flat amount</option>
        </select>
      </div>

      <div class="field">
        <label for="value">Value</label>
        <input type="number" id="value" name="value" step="0.01" min="0.01" required value="<?= h((string) ($editCoupon['value'] ?? '')) ?>">
      </div>

      <div class="field">
        <label for="expires_at">Expiry date</label>
        <input type="date" id="expires_at" name="expires_at" value="<?= h($editCoupon['expires_at'] ?? '') ?>">
      </div>

      <div class="field">
        <label>
          <input type="checkbox" name="is_active" value="1" <?= !empty($editCoupon['is_active']) ? 'checked' : '' ?> style="width:auto; margin-right:.4rem;">
          Active
        </label>
      </div>

      <button type="submit" class="btn btn-primary btn-block"><?= $editCoupon ? 'Save changes' : 'Create coupon' ?></button>
      <?php if ($editCoupon): ?><a href="<?= BASE_URL ?>/pages/admin/coupons.php" class="btn btn-ghost btn-block" style="margin-top:.5rem;">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
