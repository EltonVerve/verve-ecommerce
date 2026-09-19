<?php
function searchAdminOrders(PDO $pdo, array $filters, int $page): array {
    $where=[]; $params=[];
    foreach (['status'=>'o.status','payment'=>'o.payment_status'] as $key=>$column) if ($filters[$key] !== '') { $where[]="$column=?"; $params[]=$filters[$key]; }
    if ($filters['q'] !== '') {
        $where[]="(CAST(o.id AS CHAR)=? OR u.full_name LIKE ? OR u.email LIKE ? OR o.contact_email LIKE ? OR u.phone LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address,'$.full_name')) LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address,'$.phone')) LIKE ?)";
        $params[] = ltrim($filters['q'],'#');
        for ($i=0;$i<6;$i++) $params[]='%'.$filters['q'].'%';
    }
    if ($filters['area'] !== '') { $where[]="(JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address,'$.city')) LIKE ? OR JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address,'$.delivery_area')) LIKE ?)"; $params[]='%'.$filters['area'].'%'; $params[]='%'.$filters['area'].'%'; }
    foreach (['from'=>'>=','to'=>'<='] as $key=>$operator) {
        $date=DateTimeImmutable::createFromFormat('!Y-m-d',$filters[$key]);
        if ($date && $date->format('Y-m-d')===$filters[$key]) { $where[]="o.created_at $operator ?"; $params[]=$filters[$key].($key==='from'?' 00:00:00':' 23:59:59'); }
    }
    $sql=' FROM orders o LEFT JOIN users u ON u.id=o.user_id'.($where?' WHERE '.implode(' AND ',$where):'');
    $stmt=$pdo->prepare('SELECT COUNT(*)'.$sql); $stmt->execute($params); $total=(int)$stmt->fetchColumn();
    $page=max(1,min($page,max(1,(int)ceil($total/25)))); $offset=($page-1)*25;
    $stmt=$pdo->prepare("SELECT o.*,COALESCE(u.full_name,JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address,'$.full_name')),'Guest') customer_name,COALESCE(o.contact_email,u.email,'') customer_email".$sql." ORDER BY o.created_at DESC,o.id DESC LIMIT 25 OFFSET $offset"); $stmt->execute($params);
    return ['rows'=>$stmt->fetchAll(),'total'=>$total,'page'=>$page];
}
function auditAdmin(PDO $pdo, string $action, string $type, int $id, array $details = []): void {
    $pdo->prepare('INSERT INTO admin_audit (actor_id,action,entity_type,entity_id,details) VALUES (?,?,?,?,?)')->execute([$_SESSION['user_id'] ?? null,$action,$type,$id,json_encode($details,JSON_THROW_ON_ERROR)]);
}
function adminScope(PDO $pdo): string {
    $user = findUserById($pdo,(int) ($_SESSION['user_id'] ?? 0));
    return $user && $user['role'] === 'admin' ? $user['admin_scope'] : 'none';
}
function changeStaffAccess(PDO $pdo, string $email, string $scope): void {
    if (!in_array($scope,['owner','orders','customer'],true)) throw new RuntimeException('Choose a valid access level.');
    $pdo->beginTransaction();
    try {
        // Serialize access changes so at least the acting owner always remains.
        $pdo->query("SELECT id FROM users WHERE role='admin' ORDER BY id FOR UPDATE")->fetchAll();
        $actor=findUserById($pdo,(int)$_SESSION['user_id']);
        if (!$actor || $actor['role']!=='admin' || $actor['admin_scope']!=='owner') throw new RuntimeException('Owner access is required.');
        $stmt=$pdo->prepare('SELECT * FROM users WHERE email=? FOR UPDATE'); $stmt->execute([$email]); $user=$stmt->fetch();
        if (!$user) throw new RuntimeException('No account has that email. Ask the staff member to register first.');
        if ((int)$user['id']===(int)$_SESSION['user_id']) throw new RuntimeException('You cannot change your own access.');
        $role=$scope==='customer'?'customer':'admin';
        $pdo->prepare('UPDATE users SET role=?,admin_scope=? WHERE id=?')->execute([$role,$scope==='customer'?'orders':$scope,$user['id']]);
        auditAdmin($pdo,'staff.access','user',(int)$user['id'],['before'=>['role'=>$user['role'],'scope'=>$user['admin_scope']],'after'=>['role'=>$role,'scope'=>$scope]]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function adminRouteAllowed(string $scope, string $route): bool {
    if ($scope === 'owner') return true;
    return $scope === 'orders' && in_array($route,['orders.php','order_detail.php','order_print.php','order_note.php','update_order_status.php','dispatch.php','profile.php','update_profile.php','logout.php'],true);
}
function addOrderStaffNote(PDO $pdo, int $orderId, string $note, string $requestKey): void {
    $note = trim($note);
    if ($note === '' || mb_strlen($note) > 1000 || !preg_match('/^[a-f0-9]{64}$/D', $requestKey)) {
        throw new RuntimeException('Enter an internal note of up to 1,000 characters.');
    }
    $pdo->beginTransaction();
    try {
        lockOperationsOrder($pdo, $orderId);
        // The order lock serializes retries without introducing another table.
        $stmt = $pdo->prepare("SELECT id FROM admin_audit WHERE entity_type='order' AND entity_id=? AND action='order.note' AND JSON_UNQUOTE(JSON_EXTRACT(details,'$.request_key'))=? LIMIT 1");
        $stmt->execute([$orderId, $requestKey]);
        if (!$stmt->fetchColumn()) auditAdmin($pdo, 'order.note', 'order', $orderId, ['note'=>$note,'request_key'=>$requestKey]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function getOrderStaffNotes(PDO $pdo, int $orderId): array {
    $stmt = $pdo->prepare("SELECT a.details,a.created_at,u.full_name FROM admin_audit a LEFT JOIN users u ON u.id=a.actor_id WHERE a.entity_type='order' AND a.entity_id=? AND a.action='order.note' ORDER BY a.id DESC LIMIT 100");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}
function lowStockThreshold(PDO $pdo): int {
    return (int) $pdo->query("SELECT setting_value FROM store_settings WHERE setting_key='low_stock_threshold'")->fetchColumn();
}
function lockOperationsOrder(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id=? FOR UPDATE'); $stmt->execute([$id]);
    $order = $stmt->fetch(); if (!$order) throw new RuntimeException('Order not found.'); return $order;
}
function recordReceivedReturn(PDO $pdo, int $id, int $itemId, int $quantity, string $condition, bool $restock, string $reason, string $key): void {
    if ($quantity < 1 || !in_array($condition,['resalable','damaged'],true) || trim($reason)==='' || mb_strlen($reason)>500 || !preg_match('/^[a-f0-9]{64}$/D',$key)) throw new RuntimeException('Enter a valid quantity, condition and reason.');
    if ($restock && $condition !== 'resalable') throw new RuntimeException('Damaged items cannot be restocked.');
    $pdo->beginTransaction();
    try {
        $order = lockOperationsOrder($pdo,$id);
        $dupe = $pdo->prepare('SELECT id FROM order_returns WHERE request_key=?'); $dupe->execute([$key]); if ($dupe->fetchColumn()) { $pdo->commit(); return; }
        if (!in_array($order['status'],['shipped','completed'],true) || $order['stock_restored']) throw new RuntimeException('Returns apply only to shipped or delivered orders.');
        $stmt = $pdo->prepare('SELECT * FROM order_items WHERE id=? AND order_id=?'); $stmt->execute([$itemId,$id]); $item = $stmt->fetch();
        if (!$item) throw new RuntimeException('Choose an item from this order.');
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(quantity),0) FROM order_returns WHERE order_item_id=?'); $stmt->execute([$itemId]);
        if ($quantity + (int) $stmt->fetchColumn() > (int) $item['quantity']) throw new RuntimeException('Return quantity exceeds the unreturned quantity.');
        if ($restock) {
            if (!$item['product_id']) throw new RuntimeException('This product was deleted and cannot be restocked.');
            $stmt = $pdo->prepare('UPDATE products SET stock=stock+? WHERE id=?'); $stmt->execute([$quantity,$item['product_id']]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('Product unavailable for restocking.');
        }
        $pdo->prepare('INSERT INTO order_returns (order_id,order_item_id,quantity,item_condition,restocked,reason,actor_id,request_key) VALUES (?,?,?,?,?,?,?,?)')->execute([$id,$itemId,$quantity,$condition,$restock?1:0,$reason,(int) $_SESSION['user_id'],$key]);
        auditAdmin($pdo,'return.received','order',$id,['item_id'=>$itemId,'quantity'=>$quantity,'condition'=>$condition,'restocked'=>$restock,'reason'=>$reason]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function recordOrderRefund(PDO $pdo, int $id, string $amount, string $reason, string $reference, string $key): void {
    if (!preg_match('/^[0-9]{1,7}(\.[0-9]{1,2})?$/D',$amount) || (float)$amount<=0 || trim($reason)==='' || mb_strlen($reason)>500 || trim($reference)==='' || mb_strlen($reference)>120 || !preg_match('/^[a-f0-9]{64}$/D',$key)) throw new RuntimeException('Enter a valid refund amount, reason and payment reference.');
    $pdo->beginTransaction();
    try {
        $order = lockOperationsOrder($pdo,$id);
        $stmt = $pdo->prepare('SELECT id FROM order_refunds WHERE request_key=?'); $stmt->execute([$key]); if ($stmt->fetchColumn()) { $pdo->commit(); return; }
        if (!in_array($order['payment_status'],['collected','part_refunded'],true)) throw new RuntimeException('Only confirmed collected payments can be refunded.');
        $stmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM order_refunds WHERE order_id=?'); $stmt->execute([$id]);
        $cents = (int) round((float)$amount*100); $refunded = (int) round((float)$stmt->fetchColumn()*100); $total = (int) round((float)$order['total']*100);
        if ($refunded+$cents>$total) throw new RuntimeException('Refund exceeds the remaining collected amount.');
        $pdo->prepare('INSERT INTO order_refunds (order_id,amount,reason,reference,actor_id,request_key) VALUES (?,?,?,?,?,?)')->execute([$id,$amount,$reason,$reference,(int)$_SESSION['user_id'],$key]);
        $status = $refunded+$cents===$total ? 'refunded' : 'part_refunded';
        $pdo->prepare('UPDATE orders SET payment_status=? WHERE id=?')->execute([$status,$id]);
        $pdo->prepare("INSERT INTO order_events (order_id,actor_id,event_type,old_value,new_value) VALUES (?,?,'payment',?,?)")->execute([$id,$_SESSION['user_id'],$order['payment_status'],$status]);
        auditAdmin($pdo,'refund.recorded','order',$id,['amount'=>$amount,'reference'=>$reference,'reason'=>$reason]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function saveOrderDispatch(PDO $pdo, int $id, string $rider, string $phone, string $reference, string $date): void {
    $parsed = $date !== '' ? DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$date) : null;
    if (trim($rider)==='' || mb_strlen($rider)>120 || !preg_match('/^\+?[0-9]{9,15}$/D',$phone) || mb_strlen($reference)>120 || ($date!=='' && (!$parsed || $parsed->format('Y-m-d\TH:i')!==$date))) throw new RuntimeException('Enter a rider, valid phone number and valid dispatch time.');
    $pdo->beginTransaction();
    try {
        $order = lockOperationsOrder($pdo,$id);
        if (in_array($order['status'],['cancelled','completed'],true)) throw new RuntimeException('Dispatch details cannot change on a closed order.');
        $stmt = $pdo->prepare('SELECT * FROM order_dispatch WHERE order_id=?'); $stmt->execute([$id]); $before=$stmt->fetch();
        $pdo->prepare('INSERT INTO order_dispatch (order_id,rider,phone,reference,dispatched_at) VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE rider=VALUES(rider),phone=VALUES(phone),reference=VALUES(reference),dispatched_at=VALUES(dispatched_at)')->execute([$id,$rider,$phone,$reference,$parsed?$parsed->format('Y-m-d H:i:s'):null]);
        auditAdmin($pdo,'dispatch.updated','order',$id,['before'=>$before,'rider'=>$rider,'phone'=>$phone,'reference'=>$reference,'dispatched_at'=>$date]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
