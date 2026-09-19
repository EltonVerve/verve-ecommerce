<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$customer = $id ? findUserById($pdo, $id) : null;
if (!$customer || $customer['role'] !== 'customer') {
    http_response_code(404);
    exit('Customer not found.');
}
$activity = getCustomerActivity($pdo, (int) $customer['id']);
$pageTitle = 'Customer details';
require __DIR__ . '/../../includes/admin/admin_header.php';
?>
<div class="admin-topbar"><h1>Customer details</h1><a class="btn btn-outline" href="<?= BASE_URL ?>/pages/admin/customers.php">Back to customers</a></div>
<div class="admin-card">
  <h2><?= h($customer['full_name']) ?></h2>
  <dl class="customer-info">
    <div><dt>Customer ID</dt><dd>#<?= (int) $customer['id'] ?></dd></div>
    <div><dt>Email address</dt><dd><?= h($customer['email']) ?></dd></div>
    <div><dt>Phone number</dt><dd><?= h($customer['phone'] ?: 'Not provided') ?></dd></div>
    <div><dt>Date joined</dt><dd><?= h(date('M j, Y, g:i a', strtotime($customer['created_at']))) ?></dd></div>
  </dl>
  <p><?= (int) $activity['summary']['order_count'] ?> orders &middot; <?= money((float) $activity['summary']['order_value']) ?> in non-cancelled orders</p>
</div>
<p class="muted">Latest 100 entries per section. Wishlist and cart show currently saved items; removed items and browsing history are not recorded.</p>
<div class="admin-card"><h2>Orders</h2>
<table class="admin-table"><thead><tr><th>Order</th><th>Date</th><th>Status</th><th>Total</th></tr></thead><tbody>
<?php foreach ($activity['orders'] as $row): ?>
<tr><td><a href="<?= BASE_URL ?>/pages/admin/order_detail.php?id=<?= (int) $row['id'] ?>">#<?= (int) $row['id'] ?></a></td><td><?= h($row['created_at']) ?></td><td><?= h($row['status']) ?></td><td><?= money((float) $row['total']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$activity['orders']): ?><tr><td colspan="4">No orders yet.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="admin-card"><h2>Wishlist</h2>
<table class="admin-table"><thead><tr><th>Product</th><th>Price</th><th>Added</th></tr></thead><tbody>
<?php foreach ($activity['wishlist'] as $row): ?>
<tr><td><?= h($row['name']) ?><?= $row['is_active'] ? '' : ' (unavailable)' ?></td><td><?= money((float) $row['price']) ?></td><td><?= h($row['created_at']) ?></td></tr>
<?php endforeach; ?>
<?php if (!$activity['wishlist']): ?><tr><td colspan="3">No saved products.</td></tr><?php endif; ?>
</tbody></table></div>
<div class="admin-card"><h2>Current cart</h2>
<?php foreach ($activity['cart'] as $row): ?><p><?= h($row['name']) ?> &times; <?= (int) $row['quantity'] ?> <span class="muted">Added <?= h($row['created_at']) ?></span></p><?php endforeach; ?>
<?php if (!$activity['cart']): ?><p>Cart is empty.</p><?php endif; ?>
</div>
<div class="admin-card"><h2>Reviews</h2>
<?php foreach ($activity['reviews'] as $row): ?><article><h3><?= h($row['name']) ?> &middot; <?= (int) $row['rating'] ?>/5</h3><p><?= h($row['comment']) ?></p><p class="muted"><?= h($row['created_at']) ?></p></article><?php endforeach; ?>
<?php if (!$activity['reviews']): ?><p>No reviews yet.</p><?php endif; ?>
</div>
<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
