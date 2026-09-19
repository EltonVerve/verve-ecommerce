<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
if (($argv[1] ?? '') === 'worker') {
    $_SESSION['user_id'] = (int) $argv[2]; $_SESSION['user_role'] = 'customer';
    try {
        $result = createOrderFromCart($pdo, (int) $argv[2], ['full_name'=>'Stock test','line1'=>'Test address','line2'=>'','city'=>'Nairobi','state'=>'','postal_code'=>'','country'=>'Kenya','phone'=>'+254712345678','delivery_zone_id'=>(int) $argv[3]], 'stock@example.test', 'cash_on_delivery');
        echo 'ORDER:' . $result['id'];
    } catch (Throwable $error) { echo 'REJECTED'; }
    exit;
}
function assertOperation(bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); }
$ids = []; $product = null;
try {
    $zone = deliveryZones($pdo)[0] ?? null;
    assertOperation($zone !== null, 'Configure a delivery zone');
    $category = $pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn();
    $slug = 'stock-test-' . bin2hex(random_bytes(8));
    $pdo->prepare('INSERT INTO products (category_id,name,slug,price,stock,is_active) VALUES (?, ?, ?, 100, 1, 1)')->execute([$category,$slug,$slug]);
    $product = (int) $pdo->lastInsertId();
    foreach ([1,2] as $number) {
        $id = createUser($pdo, 'Stock test', $slug . $number . '@example.test', 'test-password-123'); $ids[] = $id;
        $pdo->prepare('INSERT INTO cart_items (user_id, product_id, quantity, unit_price) VALUES (?, ?, 1, 100)')->execute([$id,$product]);
    }
    $workers = [];
    foreach ($ids as $id) {
        $process = proc_open([PHP_BINARY, __FILE__, 'worker', (string) $id, (string) $zone['id']], [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes);
        if (!is_resource($process)) throw new RuntimeException('Unable to start concurrent checkout');
        fclose($pipes[0]); $workers[] = [$process,$pipes];
    }
    $success = 0;
    foreach ($workers as [$process,$pipes]) {
        $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
        $exit = proc_close($process);
        assertOperation($exit === 0 && $err === '', 'Checkout worker failed: ' . $err);
        if (str_contains($out,'ORDER:')) $success++;
    }
    assertOperation($success === 1, 'Exactly one concurrent checkout should succeed');
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id=?'); $stmt->execute([$product]);
    assertOperation((int) $stmt->fetchColumn() === 0, 'Stock cannot go negative');
    $stmt = $pdo->prepare('SELECT order_id FROM order_items WHERE product_id=?'); $stmt->execute([$product]); $order = (int) $stmt->fetchColumn();
    changeOrderOperation($pdo,$order,'payment','collected',0);
    changeOrderOperation($pdo,$order,'delivery','cancelled',0);
    changeOrderOperation($pdo,$order,'delivery','cancelled',0);
    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id=?'); $stmt->execute([$product]);
    assertOperation((int) $stmt->fetchColumn() === 1, 'Cancellation restores stock only once');
    $stmt = $pdo->prepare('SELECT payment_status FROM orders WHERE id=?'); $stmt->execute([$order]);
    assertOperation($stmt->fetchColumn() === 'collected', 'Cancellation must not silently refund payment');
    $_SESSION['user_id'] = $ids[0];
    $refundOrder = getOrderWithItemsAdmin($pdo,$order);
    recordOrderRefund($pdo,$order,(string)$refundOrder['total'],'Test refund','TEST-REFUND',bin2hex(random_bytes(32)));
    assertOperation(count(getOrderEvents($pdo,$order)) === 3, 'History records actual changes only');
    try { changeOrderOperation($pdo,$order,'delivery','pending',0); throw new LogicException('Cancelled order reopened'); } catch (RuntimeException $expected) {}
    assertOperation(getOrderWithItems($pdo,$order,999999999) === null, 'Tracking must enforce ownership');
    assertOperation(deliveryQuote($pdo,(int) $zone['id'],0)['shipping'] === (float) $zone['fee'], 'Delivery fee must be server calculated');
    if ($zone['free_over'] !== null) assertOperation(deliveryQuote($pdo,(int) $zone['id'],(float) $zone['free_over'])['shipping'] === 0.0, 'Free delivery boundary');
    echo "PASS: concurrent checkout, no overselling, one-time restock, independent payment, audit history, terminal cancellation, ownership and zone pricing.\n";
} finally {
    foreach ($ids as $id) {
        $pdo->prepare('DELETE FROM admin_audit WHERE actor_id=?')->execute([$id]);
        $pdo->prepare("DELETE FROM admin_audit WHERE entity_type='order' AND entity_id IN (SELECT id FROM orders WHERE user_id=?)")->execute([$id]);
        $pdo->prepare('DELETE FROM orders WHERE user_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    }
    if ($product) $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$product]);
}
