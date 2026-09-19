<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$productId = !empty($_GET['id']) ? (int) $_GET['id'] : null;
$product = $productId ? getProductById($pdo, $productId) : null;
if ($productId && !$product) {
    setFlash('error', 'That product could not be found.');
    header('Location: ' . BASE_URL . '/pages/admin/products.php');
    exit;
}

$categories = getAllCategoriesWithCounts($pdo);
$optionGroups = $productId ? getOptionGroupsForProduct($pdo, $productId) : [];
$images = $productId ? getProductImages($pdo, $productId) : [];

$pageTitle = $product ? 'Edit Product' : 'New Product';
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar">
  <h1><?= $product ? 'Edit product' : 'New product' ?></h1>
  <a href="<?= BASE_URL ?>/pages/admin/products.php" class="btn btn-outline btn-sm">← Back to products</a>
</div>

<form action="<?= BASE_URL ?>/actions/admin/save_product.php" method="post" enctype="multipart/form-data" class="admin-card">
  <?= csrfField() ?>
  <?php if ($product): ?><input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>"><?php endif; ?>

  <div class="form-grid">
    <div class="field">
      <label for="name">Product name</label>
      <input type="text" id="name" name="name" required value="<?= h($product['name'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="category_id">Category</label>
      <select id="category_id" name="category_id" required>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat['id'] ?>" <?= ($product['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description"><?= h($product['description'] ?? '') ?></textarea>
  </div>

  <div class="form-grid">
    <div class="field">
      <label for="price">Price (<?= h(trim(STORE_CURRENCY_SYMBOL)) ?>)</label>
      <input type="number" id="price" name="price" step="0.01" min="0" required value="<?= h((string) ($product['price'] ?? '')) ?>">
    </div>
    <div class="field">
      <label for="compare_at_price">Compare-at price (optional)</label>
      <input type="number" id="compare_at_price" name="compare_at_price" step="0.01" min="0" value="<?= h((string) ($product['compare_at_price'] ?? '')) ?>">
      <p class="hint">Set this higher than the price to show a "Sale" badge.</p>
    </div>
  </div>

  <div class="form-grid">
    <div class="field">
      <label for="sku">SKU (optional)</label>
      <input type="text" id="sku" name="sku" value="<?= h($product['sku'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="stock">Stock quantity</label>
      <input type="number" id="stock" name="stock" min="0" required value="<?= h((string) ($product['stock'] ?? 0)) ?>">
    </div>
  </div>

  <div class="field">
    <label><input type="checkbox" name="is_featured" value="1" <?= !empty($product['is_featured']) ? 'checked' : '' ?> style="width:auto; margin-right:.4rem;"> Feature on homepage</label>
  </div>
  <div class="field">
    <label><input type="checkbox" name="is_active" value="1" <?= ($product['is_active'] ?? 1) ? 'checked' : '' ?> style="width:auto; margin-right:.4rem;"> Visible in store</label>
  </div>

  <div class="field">
    <label for="images">Product photos</label>
    <p class="hint">Choose multiple images at once or add more in another selection. Drag your main picture to the first position, or use Make main. Remove unwanted photos, then Save product to apply.</p>
    <input type="file" id="images" name="images[]" accept="image/png,image/jpeg,image/webp" multiple data-max-files="<?= min(20, (int) ini_get('max_file_uploads')) ?>">
    <input type="hidden" id="image-order" name="image_order" disabled>
    <p class="hint">JPG, PNG or WebP, up to 5 MB each. Your server's total upload limit is <?= h(ini_get('post_max_size')) ?>.</p>
    <div id="image-previews" class="product-image-previews">
      <?php foreach (productGalleryFiles($product, $images) as $position => $filename): ?>
        <div class="product-image-card <?= $position === 0 ? 'is-main' : '' ?>" data-key="<?= h('existing:' . $filename) ?>">
          <img src="<?= h(productImageUrl($filename, 'Product photo', 600)) ?>" alt="Product photo <?= $position + 1 ?>">
          <span class="image-position"><?= $position === 0 ? 'Main photo' : 'Photo ' . ($position + 1) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
    <p id="image-message" class="hint" role="status" aria-live="polite"></p>
    <noscript><p>Multiple uploads are supported. Enable JavaScript to preview and reorder images.</p></noscript>
  </div>
  <script src="<?= BASE_URL ?>/public/assets/js/product-images.js" defer></script>
  <hr class="divider">
  <h3 style="font-size:1rem;">Variants (optional)</h3>
  <p class="muted small">e.g. a "Size" group with values S / M / L, or a "Colour" group with values Black / White.</p>
  <div id="optionGroups">
    <?php foreach ($optionGroups as $gi => $group): ?>
      <div class="option-group-block">
        <div class="option-row">
          <input type="text" name="option_groups[<?= $gi ?>][name]" placeholder="Group name, e.g. Size" value="<?= h($group['name']) ?>">
          <span></span><span></span>
        </div>
        <?php foreach ($group['values'] as $vi => $value): ?>
          <div class="option-row">
            <input type="text" name="option_groups[<?= $gi ?>][values][<?= $vi ?>][label]" placeholder="Value, e.g. M" value="<?= h($value['label']) ?>">
            <input type="number" step="0.01" name="option_groups[<?= $gi ?>][values][<?= $vi ?>][price_delta]" placeholder="Price add-on" value="<?= h((string) $value['price_delta']) ?>">
            <button type="button" class="btn btn-ghost btn-sm remove-row">✕</button>
          </div>
        <?php endforeach; ?>
        <button type="button" class="btn btn-outline btn-sm add-value">+ Add value</button>
        <button type="button" class="btn btn-ghost btn-sm remove-group">Remove group</button>
      </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="btn btn-outline btn-sm" id="addGroup">+ Add variant group</button>

  <div style="margin-top:2rem;">
    <button type="submit" class="btn btn-primary">Save product</button>
  </div>
</form>

<script>
(function () {
  let groupIndex = <?= count($optionGroups) ?>;
  const container = document.getElementById('optionGroups');

  function makeGroup() {
    const gi = groupIndex++;
    const div = document.createElement('div');
    div.className = 'option-group-block';
    div.innerHTML = `
      <div class="option-row">
        <input type="text" name="option_groups[${gi}][name]" placeholder="Group name, e.g. Size">
        <span></span><span></span>
      </div>
      <button type="button" class="btn btn-outline btn-sm add-value">+ Add value</button>
      <button type="button" class="btn btn-ghost btn-sm remove-group">Remove group</button>
    `;
    container.appendChild(div);
    wireGroup(div, gi);
  }

  function wireGroup(div, gi) {
    let valueIndex = div.querySelectorAll('.option-row').length - 1;
    div.querySelector('.add-value').addEventListener('click', () => {
      const vi = valueIndex++;
      const row = document.createElement('div');
      row.className = 'option-row';
      row.innerHTML = `
        <input type="text" name="option_groups[${gi}][values][${vi}][label]" placeholder="Value, e.g. M">
        <input type="number" step="0.01" name="option_groups[${gi}][values][${vi}][price_delta]" placeholder="Price add-on">
        <button type="button" class="btn btn-ghost btn-sm remove-row">✕</button>
      `;
      div.insertBefore(row, div.querySelector('.add-value'));
      row.querySelector('.remove-row').addEventListener('click', () => row.remove());
    });
    div.querySelector('.remove-group').addEventListener('click', () => div.remove());
    div.querySelectorAll('.remove-row').forEach(btn => btn.addEventListener('click', () => btn.closest('.option-row').remove()));
  }

  document.querySelectorAll('.option-group-block').forEach((div, i) => wireGroup(div, i));
  document.getElementById('addGroup').addEventListener('click', makeGroup);
})();
</script>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
