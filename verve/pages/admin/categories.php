<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Categories';
$categories = getAllCategoriesWithCounts($pdo);
$editId = !empty($_GET['edit']) ? (int) $_GET['edit'] : null;
$editCategory = $editId ? getCategoryById($pdo, $editId) : null;

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar"><h1>Categories</h1></div>

<div style="display:grid; grid-template-columns:1fr 320px; gap:1.6rem; align-items:start;">
  <div class="admin-card">
    <table class="admin-table">
      <thead><tr><th>Name</th><th>Slug</th><th>Products</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($categories as $cat): ?>
          <tr>
            <td><?= h($cat['name']) ?></td>
            <td class="muted">/<?= h($cat['slug']) ?></td>
            <td><?= (int) $cat['product_count'] ?></td>
            <td>
              <a href="?edit=<?= (int) $cat['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
              <form action="<?= BASE_URL ?>/actions/admin/delete_category.php" method="post" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="admin-card">
    <h2><?= $editCategory ? 'Edit category' : 'New category' ?></h2>
    <form action="<?= BASE_URL ?>/actions/admin/save_category.php" method="post" enctype="multipart/form-data">
      <?= csrfField() ?>
      <?php if ($editCategory): ?><input type="hidden" name="id" value="<?= (int) $editCategory['id'] ?>"><?php endif; ?>
      <div class="field">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" required value="<?= h($editCategory['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= h($editCategory['description'] ?? '') ?></textarea>
      </div>
      <div class="field">
        <label for="image">Main photo</label>
        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp" <?= $editCategory ? '' : 'required' ?>>
        <?php if (!empty($editCategory['image'])): ?>
          <div style="margin-top:.75rem;">
            <img src="<?= h(productImageUrl($editCategory['image'], $editCategory['name'] ?? 'Category', 300)) ?>" alt="<?= h($editCategory['name'] ?? 'Category') ?>" width="120" height="120" style="border-radius:12px;object-fit:cover;">
          </div>
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary btn-block"><?= $editCategory ? 'Save changes' : 'Create category' ?></button>
      <?php if ($editCategory): ?><a href="<?= BASE_URL ?>/pages/admin/categories.php" class="btn btn-ghost btn-block" style="margin-top:.5rem;">Cancel</a><?php endif; ?>
    </form>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
