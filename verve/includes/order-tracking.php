<?php // Include only after the calling page has authorized access to $order. ?>
<section class="form-card" style="margin:1.5rem 0;">
<h2>Order progress</h2>
<p>Delivery: <strong><?= h($order['status'] === 'completed' ? 'Delivered' : ucfirst($order['status'])) ?></strong> &middot; Payment: <strong><?= h(ucfirst($order['payment_status'] ?? 'unknown')) ?></strong></p>
<?php if (!empty($order['address']['delivery_area'])): ?><p>Delivery area: <?= h($order['address']['delivery_area']) ?></p><?php endif; ?>
<ol>
<li>Order placed &mdash; <?= h($order['created_at']) ?></li>
<?php foreach (getOrderEvents($pdo, (int) $order['id']) as $event): ?>
<li><?= h(ucfirst($event['event_type'])) ?>: <?= h($event['new_value'] === 'completed' ? 'Delivered' : ucfirst($event['new_value'])) ?> &mdash; <?= h($event['created_at']) ?></li>
<?php endforeach; ?>
</ol>
</section>
