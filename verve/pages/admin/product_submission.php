<?php
require __DIR__.'/../../config/config.php'; requireAdmin();
$id=(int)($_GET['id'] ?? 0); $s=productSubmission($pdo,$id); $owner=adminScope($pdo)==='owner';
if (!$s || (!$owner && (int)$s['author_id']!==(int)$_SESSION['user_id'])) { http_response_code(404); exit('Submission not found.'); }
$product=getProductById($pdo,$id); $author=findUserById($pdo,(int)$s['author_id']);
$reviewer=$s['reviewer_id']?findUserById($pdo,(int)$s['reviewer_id']):null;
$category=$pdo->prepare('SELECT name FROM categories WHERE id=?'); $category->execute([$product['category_id']]);
$pageTitle='Review product'; require __DIR__.'/../../includes/admin/admin_header.php'; require __DIR__.'/../../includes/admin/admin_flash.php';
?>
<div class="admin-topbar"><h1><?= h($product['name']) ?></h1><a class="btn btn-outline" href="product_submissions.php">Back to submissions</a></div>
<section class="admin-card"><p><strong><?= h(ucfirst($s['status'])) ?></strong> · Submitted by <?= h($author['full_name'] ?? 'Former staff') ?></p>
<p>Category: <?= h($category->fetchColumn()) ?> · Price: <?= money((float)$product['price']) ?> · Stock: <?= (int)$product['stock'] ?></p>
<p>SKU: <?= h($product['sku'] ?: 'None') ?> · Compare-at price: <?= $product['compare_at_price']!==null?money((float)$product['compare_at_price']):'None' ?></p>
<p><?= nl2br(h($product['description'])) ?></p>
<div class="product-image-previews"><?php foreach (productGalleryFiles($product,getProductImages($pdo,$id)) as $index=>$file): ?><div class="product-image-card"><img src="<?= h(productImageUrl($file,'Product photo',600)) ?>" alt="<?= $index===0?'Main photo':'Additional photo' ?>"><span><?= $index===0?'Main photo':'Photo '.($index+1) ?></span></div><?php endforeach; ?></div>
<?php foreach (getOptionGroupsForProduct($pdo,$id) as $group): ?><p><strong><?= h($group['name']) ?>:</strong> <?php foreach ($group['values'] as $value): ?><?= h($value['label']) ?> (<?= money((float)$value['price_delta']) ?>) <?php endforeach; ?></p><?php endforeach; ?>
<?php if ($s['target_id']): $live=getProductById($pdo,(int)$s['target_id']); ?><p class="hint">Revision of product #<?= (int)$s['target_id'] ?>. Current name: <?= h($live['name']) ?>. Current price: <?= money((float)$live['price']) ?>. Approval replaces its product details and photos, preserves its URL and current stock (<?= (int)$live['stock'] ?>).</p><?php endif; ?>
<?php if ($s['review_note']!==''): ?><p><strong>Admin feedback:</strong> <?= nl2br(h($s['review_note'])) ?></p><?php endif; ?>
<?php if ($reviewer): ?><p class="hint">Last reviewed by <?= h($reviewer['full_name']) ?></p><?php endif; ?>
<?php if ($owner || in_array($s['status'],['draft','returned'],true)): ?><a class="btn btn-outline" href="product_form.php?id=<?= $id ?>">Edit product details</a><?php endif; ?>
<?php if (!$owner && $s['status']==='approved'): ?><form method="post" action="<?= BASE_URL ?>/actions/admin/product_revision.php"><?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>"><button class="btn btn-primary">Create a revision</button></form><?php endif; ?>
</section>
<?php if ($owner && $s['status']==='pending'): ?>
<form method="post" action="<?= BASE_URL ?>/actions/admin/review_product.php" class="admin-card"><?= csrfField() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="version" value="<?= (int)$s['version'] ?>"><h2>Admin decision</h2>
<div class="field"><label for="review-note">Review note (required when returning)</label><textarea id="review-note" name="note" maxlength="1000" rows="3"></textarea></div>
<div class="admin-toolbar"><button class="btn btn-primary" name="decision" value="publish">Approve and publish</button><button class="btn btn-outline" name="decision" value="inactive">Approve, keep inactive</button><button class="btn btn-outline" name="decision" value="return">Return for corrections</button></div></form>
<?php endif; ?>
<?php require __DIR__.'/../../includes/admin/admin_footer.php'; ?>
