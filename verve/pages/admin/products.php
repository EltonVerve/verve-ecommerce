<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Products';
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$products = getAllProductsAdmin($pdo, $search);

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar">
  <h1>Products</h1>
  <a href="<?= BASE_URL ?>/pages/admin/product_form.php" class="btn btn-primary btn-sm">+ New product</a>
</div>

<div class="admin-toolbar">
  <form class="admin-search" method="get">
    <input type="text" name="q" placeholder="Search products…" value="<?= h($search) ?>">
    <button type="submit" class="btn btn-outline btn-sm">Search</button>
  </form>
  <span class="muted small"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></span>
</div>

<div class="admin-card">
  <table class="admin-table">
    <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><img class="thumb" src="<?= h(productImageUrl($p['image'], $p['name'])) ?>" alt=""></td>
          <td><a href="<?= BASE_URL ?>/pages/admin/product_form.php?id=<?= (int) $p['id'] ?>"><?= h($p['name']) ?></a></td>
          <td><?= h($p['category_name']) ?></td>
          <td><?= money((float) $p['price']) ?></td>
          <td><?= (int) $p['stock'] ?></td>
          <td>
            <form action="<?= BASE_URL ?>/actions/admin/toggle_product.php" method="post">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <input type="hidden" name="is_active" value="<?= (int) $p['is_active'] ? 0 : 1 ?>">
              <button type="submit" class="badge-dot <?= $p['is_active'] ? 'on' : 'off' ?>" style="background:none;border:none;cursor:pointer;">
                <?= $p['is_active'] ? 'Active' : 'Hidden' ?>
              </button>
            </form>
          </td>
          <td>
            <a href="<?= BASE_URL ?>/pages/admin/product_form.php?id=<?= (int) $p['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form action="<?= BASE_URL ?>/actions/admin/delete_product.php" method="post" style="display:inline;" onsubmit="return confirm('Delete this product? This cannot be undone.');">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$products): ?><tr><td colspan="7" class="muted">No products found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
