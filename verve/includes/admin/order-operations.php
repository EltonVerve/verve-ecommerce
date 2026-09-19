<?php
$owner = adminScope($pdo) === 'owner';
$stmt = $pdo->prepare('SELECT * FROM order_dispatch WHERE order_id=?'); $stmt->execute([$orderId]); $dispatch=$stmt->fetch() ?: [];
$stmt = $pdo->prepare('SELECT r.*,i.product_name FROM order_returns r LEFT JOIN order_items i ON i.id=r.order_item_id WHERE r.order_id=? ORDER BY r.id DESC'); $stmt->execute([$orderId]); $returns=$stmt->fetchAll();
$stmt = $pdo->prepare('SELECT * FROM order_refunds WHERE order_id=? ORDER BY id DESC'); $stmt->execute([$orderId]); $refunds=$stmt->fetchAll();
?>
<div class="operations-grid">
<section class="admin-card"><h2>Delivery assignment</h2>
<p class="hint">Assign a rider here, then save the order status separately when it ships.</p>
<form method="post" action="<?= BASE_URL ?>/actions/admin/dispatch.php">
<?= csrfField() ?><input type="hidden" name="order_id" value="<?= $orderId ?>">
<fieldset class="dispatch-fields" <?= in_array($order['status'],['completed','cancelled'],true) ? 'disabled' : '' ?>>
<div class="form-grid">
<?php foreach (['rider'=>'Rider name','phone'=>'Rider phone','reference'=>'Delivery reference'] as $key=>$label): ?>
<div class="field"><label for="dispatch-<?= $key ?>"><?= $label ?></label><input id="dispatch-<?= $key ?>" name="<?= $key ?>" value="<?= h($dispatch[$key] ?? '') ?>" maxlength="<?= $key==='phone' ? 30 : 120 ?>" <?= $key!=='reference' ? 'required' : '' ?>></div>
<?php endforeach; ?>
<div class="field"><label for="dispatch-date">Dispatch time (Nairobi)</label><input id="dispatch-date" name="dispatched_at" type="datetime-local" value="<?= !empty($dispatch['dispatched_at']) ? h(date('Y-m-d\TH:i',strtotime($dispatch['dispatched_at']))) : '' ?>"></div>
</div>
</fieldset>
<?php if (!in_array($order['status'],['completed','cancelled'],true)): ?><button class="btn btn-primary">Save assignment</button><?php endif; ?>
</form></section>
<section class="admin-card"><h2>Received returns</h2><p class="hint">Record items only after receiving them. Restock is optional and only applies to resalable items.</p>
<?php foreach ($returns as $return): ?><p><strong><?= h($return['product_name'] ?? 'Deleted item') ?></strong> &times; <?= (int)$return['quantity'] ?> · <?= h($return['item_condition']) ?><?= $return['restocked'] ? ' · Restocked' : '' ?><br><?= h($return['reason']) ?><br><small><?= h($return['created_at']) ?></small></p><?php endforeach; ?>
<?php if (!$returns): ?><p class="muted">No returns recorded.</p><?php endif; ?>
<?php if ($owner && in_array($order['status'],['shipped','completed'],true)): ?>
<form method="post" action="<?= BASE_URL ?>/actions/admin/record_return.php"><?= csrfField() ?><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(32)) ?>">
<div class="field"><label for="return-item">Item</label><select id="return-item" name="item_id" required><?php foreach ($order['items'] as $item): ?><option value="<?= (int)$item['id'] ?>"><?= h($item['product_name']) ?> (ordered <?= (int)$item['quantity'] ?>)</option><?php endforeach; ?></select></div>
<div class="form-grid"><div class="field"><label for="return-quantity">Quantity received</label><input id="return-quantity" name="quantity" type="number" min="1" step="1" required></div><div class="field"><label for="return-condition">Condition</label><select id="return-condition" name="condition"><option value="resalable">Resalable</option><option value="damaged">Damaged</option></select></div></div>
<div class="field"><label for="return-reason">Reason</label><textarea id="return-reason" name="reason" maxlength="500" required></textarea></div>
<p><label><input type="checkbox" name="restock"> Add received resalable items back to stock</label></p><button class="btn btn-primary">Record received return</button></form>
<?php endif; ?></section>
<?php if ($owner): ?><section class="admin-card" id="refunds"><h2>Refund records</h2><p class="hint">This records money you have already returned to the customer; it does not send money or restock products.</p>
<?php foreach ($refunds as $refund): ?><p><strong><?= money((float)$refund['amount']) ?></strong> · <?= h($refund['reference']) ?><br><?= h($refund['reason']) ?><br><small><?= h($refund['created_at']) ?></small></p><?php endforeach; ?>
<?php if (!$refunds): ?><p class="muted">No itemised refunds recorded.</p><?php endif; ?>
<?php if (in_array($order['payment_status'],['collected','part_refunded'],true)): ?>
<?php $remainingRefund=round((float)$order['total']-array_sum(array_column($refunds,'amount')),2); ?>
<p>Available to refund: <strong><?= money($remainingRefund) ?></strong></p>
<form method="post" action="<?= BASE_URL ?>/actions/admin/record_refund.php"><?= csrfField() ?><input type="hidden" name="order_id" value="<?= $orderId ?>"><input type="hidden" name="request_key" value="<?= bin2hex(random_bytes(32)) ?>">
<div class="field"><label for="refund-amount">Amount returned (KES)</label><input id="refund-amount" name="amount" type="number" min="0.01" max="<?= h(number_format($remainingRefund,2,'.','')) ?>" step="0.01" required></div>
<div class="field"><label for="refund-reference">Payment / receipt reference</label><input id="refund-reference" name="reference" maxlength="120" required></div>
<div class="field"><label for="refund-reason">Reason</label><textarea id="refund-reason" name="reason" maxlength="500" required></textarea></div><button class="btn btn-primary">Record refund</button></form>
<?php endif; ?></section><?php endif; ?>
</div>
