<?php
function getOrderEvents(PDO $pdo, int $orderId): array {
    $stmt = $pdo->prepare('SELECT event_type, old_value, new_value, created_at FROM order_events WHERE order_id = ? ORDER BY id');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}
function changeOrderOperation(PDO $pdo, int $id, string $type, string $value, int $actor): void {
    $allowed = $type === 'delivery' ? ['pending','processing','shipped','completed','cancelled'] : ['unpaid','collected','refunded'];
    if (!in_array($type, ['delivery','payment'], true) || !in_array($value, $allowed, true)) throw new RuntimeException('Choose a valid status.');
    $column = $type === 'delivery' ? 'status' : 'payment_status';
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? FOR UPDATE'); $stmt->execute([$id]); $order = $stmt->fetch();
        if (!$order) throw new RuntimeException('Order not found.');
        $old = $order[$column];
        if ($old === $value) { $pdo->commit(); return; }
        if ($type === 'delivery') {
            $transitions = ['pending'=>['processing','cancelled'], 'paid'=>['processing','cancelled'], 'processing'=>['shipped','cancelled'], 'shipped'=>['completed'], 'completed'=>[], 'cancelled'=>[]];
            if (!in_array($value, $transitions[$old] ?? [], true)) throw new RuntimeException('That delivery transition is not allowed. Shipped or delivered returns need a separate return review.');
            if ($value === 'cancelled' && !(int) $order['stock_restored']) {
                $items = $pdo->prepare('SELECT product_id, SUM(quantity) AS qty FROM order_items WHERE order_id = ? AND product_id IS NOT NULL GROUP BY product_id ORDER BY product_id'); $items->execute([$id]);
                foreach ($items->fetchAll() as $item) $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$item['qty'], $item['product_id']]);
                $pdo->prepare('UPDATE orders SET stock_restored = 1 WHERE id = ?')->execute([$id]);
            }
        } else {
            if ($value === 'collected' && $order['status'] === 'cancelled') throw new RuntimeException('Cannot collect payment on a cancelled order.');
            if ($value === 'refunded' && $old !== 'collected') throw new RuntimeException('Only collected payments can be marked refunded.');
            if ($value === 'unpaid' && $old !== 'unknown') throw new RuntimeException('Use refunded to record money returned.');
            if ($old === 'refunded') throw new RuntimeException('Refunded payments cannot be reopened.');
        }
        $pdo->prepare("UPDATE orders SET $column = ? WHERE id = ?")->execute([$value, $id]);
        $pdo->prepare('INSERT INTO order_events (order_id, actor_id, event_type, old_value, new_value) VALUES (?, ?, ?, ?, ?)')->execute([$id, $actor, $type, $old, $value]);
        $pdo->commit();
    } catch (Throwable $error) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $error; }
}
function deliveryZones(PDO $pdo): array { return $pdo->query('SELECT * FROM delivery_zones WHERE active = 1 ORDER BY name')->fetchAll(); }
function deliveryQuote(PDO $pdo, int $zoneId, float $subtotal): array {
    $stmt = $pdo->prepare('SELECT * FROM delivery_zones WHERE id = ? AND active = 1'); $stmt->execute([$zoneId]); $zone = $stmt->fetch();
    if (!$zone) throw new RuntimeException('Please select an available delivery area.');
    $zone['shipping'] = $zone['free_over'] !== null && $subtotal >= (float) $zone['free_over'] ? 0.0 : (float) $zone['fee'];
    return $zone;
}
