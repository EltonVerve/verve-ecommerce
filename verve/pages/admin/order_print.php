<?php
require __DIR__ . '/../../config/config.php';
requireAdmin();
$order = getOrderWithItemsAdmin($pdo, (int)($_GET['id'] ?? 0));
if (!$order) { http_response_code(404); exit('Order not found.'); }
$address=$order['address'] ?? [];
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Packing slip #<?= (int)$order['id'] ?> · <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/public/assets/css/order-print.css">
</head><body>
<div class="print-actions"><button type="button" onclick="window.print()">Print packing slip</button><a href="order_detail.php?id=<?= (int)$order['id'] ?>">Back to order</a></div>
<header><h1><?= h(SITE_NAME) ?></h1><h2>Packing slip #<?= (int)$order['id'] ?></h2><p>Order placed: <?= h($order['created_at']) ?></p><p>Delivery: <?= h(ucfirst($order['status'])) ?> · Payment: <?= h(ucwords(str_replace('_',' ',$order['payment_status']))) ?></p></header>
<section><h2>Deliver to</h2><p><?= h($address['full_name'] ?? $order['customer_name']) ?><br><?= h($order['customer_phone'] ?? '') ?><br><?= h($address['line1'] ?? '') ?> <?= h($address['line2'] ?? '') ?><br><?= h($address['city'] ?? '') ?> <?= h($address['state'] ?? '') ?> <?= h($address['postal_code'] ?? '') ?><br><?= h($address['country'] ?? '') ?></p></section>
<table><thead><tr><th>Product / options</th><th>Quantity</th><th>Packed</th></tr></thead><tbody>
<?php foreach ($order['items'] as $item): ?><tr><td><?= h($item['product_name']) ?><?php foreach ($item['options_display'] ?? [] as $key=>$value): ?><br><small><?= h($key) ?>: <?= h($value) ?></small><?php endforeach; ?></td><td><?= (int)$item['quantity'] ?></td><td>&#x2610;</td></tr><?php endforeach; ?>
</tbody></table>
<p>Order total: <strong><?= money((float)$order['total']) ?></strong></p>
<?php if ($order['payment_status']==='unpaid' && $order['status']!=='cancelled'): ?><p><strong>Cash to collect: <?= money((float)$order['total']) ?></strong></p><?php else: ?><p>Check the current payment record before collecting any money.</p><?php endif; ?>
<?php if ($order['status']==='cancelled'): ?><p><strong>CANCELLED — DO NOT DISPATCH</strong></p><?php endif; ?>
<footer><p>Packed by: ____________________ &nbsp; Checked by: ____________________</p><p>This packing slip is not a payment receipt.</p></footer>
</body></html>
