<?php
/**
 * SHOP PAGE
 * ---------------------------------------------------------
 * The main catalogue: category filter, price range, in-stock
 * toggle, text search and sort — all read from the query
 * string so results are shareable/bookmarkable links.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

$catSlug = is_string($_GET['cat'] ?? null) ? $_GET['cat'] : '';
$query = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$minPrice = is_string($_GET['min_price'] ?? null) ? $_GET['min_price'] : '';
$maxPrice = is_string($_GET['max_price'] ?? null) ? $_GET['max_price'] : '';
$inStockOnly = !empty($_GET['in_stock']);
$sort = is_string($_GET['sort'] ?? null) ? $_GET['sort'] : '';

$activeCategory = $catSlug !== '' ? getCategoryBySlug($pdo, $catSlug) : null;

$products = getShopProducts($pdo, [
    'category' => $catSlug,
    'q' => $query,
    'min_price' => $minPrice,
    'max_price' => $maxPrice,
    'in_stock_only' => $inStockOnly,
    'sort' => $sort,
    'page' => filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1,
], $pagination);
$categories = getCategoriesWithProductCounts($pdo);
$priceBounds = getPriceBounds($pdo);

$pageTitle = $activeCategory ? $activeCategory['name'] : ($query !== '' ? 'Search: ' . $query : 'Shop All');

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/flash.php';

// Helper to keep the current filters when linking to a sort option
// or clearing a single filter.
function shopUrlWith(array $overrides): string {
    $params = array_merge($_GET, $overrides);
    if (!array_key_exists('page', $overrides)) unset($params['page']);
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($params[$k]);
    }
    return BASE_URL . '/pages/shop.php?' . http_build_query($params);
}
?>

<?php if ($activeCategory): ?>
<?php require __DIR__ . '/../includes/category-hero.php'; ?>
<?php elseif ($query === ''): ?>
<?php require __DIR__ . '/../includes/shop-hero.php'; ?>
<?php else: ?>
<div class="page-banner">
  <h1><?= h($pageTitle) ?></h1>
  <?php if ($activeCategory && $activeCategory['description']): ?>
    <p class="muted"><?= h($activeCategory['description']) ?></p>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="shell section" id="shop-products">
  <div class="shop-layout">
    <aside class="filters-panel">
      <form method="get" id="filterForm">
        <?php if ($query !== ''): ?><input type="hidden" name="q" value="<?= h($query) ?>"><?php endif; ?>

        <div class="filter-group">
          <h4>Category</h4>
          <label>
            <input type="radio" name="cat" value="" onchange="this.form.submit()" <?= $catSlug === '' ? 'checked' : '' ?>>
            All products
          </label>
          <?php foreach ($categories as $cat): ?>
            <label>
              <input type="radio" name="cat" value="<?= h($cat['slug']) ?>" onchange="this.form.submit()" <?= $catSlug === $cat['slug'] ? 'checked' : '' ?>>
              <?= h($cat['name']) ?> <span class="muted">(<?= (int) $cat['product_count'] ?>)</span>
            </label>
          <?php endforeach; ?>
        </div>

        <div class="filter-group">
          <h4>Price</h4>
          <div class="price-range-inputs">
            <input type="number" name="min_price" placeholder="<?= (int) $priceBounds['min'] ?>" value="<?= h($minPrice) ?>" min="0" step="1">
            <span>–</span>
            <input type="number" name="max_price" placeholder="<?= (int) $priceBounds['max'] ?>" value="<?= h($maxPrice) ?>" min="0" step="1">
          </div>
          <button type="submit" class="btn btn-outline btn-sm" style="margin-top:.7rem;">Apply</button>
        </div>

        <div class="filter-group">
          <label>
            <input type="checkbox" name="in_stock" value="1" onchange="this.form.submit()" <?= $inStockOnly ? 'checked' : '' ?>>
            In stock only
          </label>
        </div>
        <input type="hidden" name="sort" value="<?= h($sort) ?>">
      </form>
    </aside>

    <div>
      <?php if ($query !== '' || $catSlug !== '' || $inStockOnly || $minPrice !== '' || $maxPrice !== ''): ?>
        <div class="active-chips">
          <?php if ($query !== ''): ?><span class="chip"><?= h($query) ?> <a href="<?= shopUrlWith(['q' => null]) ?>">&times;</a></span><?php endif; ?>
          <?php if ($activeCategory): ?><span class="chip"><?= h($activeCategory['name']) ?> <a href="<?= shopUrlWith(['cat' => null]) ?>">&times;</a></span><?php endif; ?>
          <?php if ($inStockOnly): ?><span class="chip">In stock <a href="<?= shopUrlWith(['in_stock' => null]) ?>">&times;</a></span><?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="shop-toolbar">
        <span class="result-count"><?= $pagination['total'] ?> products<?= $pagination['total'] ? ' · Page ' . $pagination['page'] . ' of ' . $pagination['pages'] : '' ?></span>
        <form method="get" id="sortForm">
          <?php foreach (['cat' => $catSlug, 'q' => $query, 'min_price' => $minPrice, 'max_price' => $maxPrice, 'in_stock' => $inStockOnly ? '1' : ''] as $k => $v): ?>
            <?php if ($v !== ''): ?><input type="hidden" name="<?= $k ?>" value="<?= h($v) ?>"><?php endif; ?>
          <?php endforeach; ?>
          <select name="sort" class="sort-select" onchange="this.form.submit()">
            <option value="" <?= $sort === '' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Top rated</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A–Z</option>
          </select>
        </form>
      </div>

      <?php if (!$products): ?>
        <div class="empty-state">
          <h3>No products match those filters</h3>
          <p>Try widening your price range or clearing a filter.</p>
          <a href="<?= BASE_URL ?>/pages/shop.php" class="btn btn-outline">Clear filters</a>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $product): ?>
            <?php require __DIR__ . '/../includes/product-card.php'; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <?php if ($pagination['pages'] > 1): ?>
        <nav class="shop-pagination" aria-label="Product pages">
          <?php if ($pagination['page'] > 1): ?><a class="btn btn-outline btn-sm" rel="prev" href="<?= h(shopUrlWith(['page' => $pagination['page'] - 1])) ?>#shop-products">&larr; Previous</a><?php endif; ?>
          <span>Page <?= $pagination['page'] ?> of <?= $pagination['pages'] ?></span>
          <?php if ($pagination['page'] < $pagination['pages']): ?><a class="btn btn-primary btn-sm" rel="next" href="<?= h(shopUrlWith(['page' => $pagination['page'] + 1])) ?>#shop-products">Next &rarr;</a><?php endif; ?>
        </nav>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
