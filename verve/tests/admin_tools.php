<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../config/config.php';
function checkTools(bool $ok,string $message): void { if (!$ok) throw new LogicException($message); }
function rejectsTools(callable $fn): void { try { $fn(); } catch (PDOException $e) { throw $e; } catch (RuntimeException $e) { return; } throw new LogicException('Unsafe operation was accepted'); }
$ids=[]; $orders=[]; $product=0;
try {
    $tag='admin-test-'.bin2hex(random_bytes(7));
    foreach (['owner','staff','customer'] as $name) $ids[$name]=createUser($pdo,$name.' '.$tag,$name.$tag.'@example.test','Test-password-123!');
    $pdo->prepare("UPDATE users SET role='admin',admin_scope='owner' WHERE id=?")->execute([$ids['owner']]);
    $_SESSION['user_id']=$ids['owner'];
    changeStaffAccess($pdo,'staff'.$tag.'@example.test','orders');
    checkTools(findUserById($pdo,$ids['staff'])['admin_scope']==='orders','Staff scope saved');
    rejectsTools(fn()=>changeStaffAccess($pdo,'owner'.$tag.'@example.test','customer'));
    $_SESSION['user_id']=$ids['staff'];
    rejectsTools(fn()=>changeStaffAccess($pdo,'customer'.$tag.'@example.test','owner'));
    checkTools(adminScope($pdo)==='orders','Scope loaded from database');
    foreach (['staff.php','record_return.php','record_refund.php','review_product.php','toggle_product.php','delivery.php','audit.php','dashboard.php'] as $route) checkTools(!adminRouteAllowed('orders',$route),'Staff denied '.$route);
    checkTools(adminRouteAllowed('orders','dispatch.php') && adminRouteAllowed('orders','orders.php'),'Order staff allowed operations');
    checkTools(!adminRouteAllowed('none','orders.php'),'Customer denied');
    $_SESSION['user_id']=$ids['owner'];
    $category=$pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn();
    $pdo->prepare('INSERT INTO products (category_id,name,slug,price,stock,is_active) VALUES (?,?,?,100,0,1)')->execute([$category,$tag,$tag]); $product=(int)$pdo->lastInsertId();
    $address=json_encode(['full_name'=>$tag,'phone'=>'+254712345678','city'=>'Nairobi','delivery_area'=>'Nairobi CBD']);
    foreach (['shipped','pending'] as $status) {
        $pdo->prepare('INSERT INTO orders (user_id,status,payment_status,subtotal,total,delivery_address) VALUES (?,?,?,200,200,?)')->execute([$ids['customer'],$status,$status==='shipped'?'collected':'unpaid',$address]);
        $orders[]=(int)$pdo->lastInsertId();
    }
    [$order,$pending]=$orders;
    $_SESSION['user_id']=$ids['staff'];
    $noteKey=bin2hex(random_bytes(32));
    addOrderStaffNote($pdo,$order,'Customer confirmed delivery. <script>test</script>',$noteKey);
    addOrderStaffNote($pdo,$order,'Customer confirmed delivery. <script>test</script>',$noteKey);
    checkTools(count(getOrderStaffNotes($pdo,$order))===1,'Repeated note submissions must not duplicate notes');
    checkTools(getOrderStaffNotes($pdo,$pending)===[],'Notes must stay on the selected order');
    rejectsTools(fn()=>addOrderStaffNote($pdo,$order,' ',bin2hex(random_bytes(32))));
    rejectsTools(fn()=>addOrderStaffNote($pdo,$order,str_repeat('a',1001),bin2hex(random_bytes(32))));
    rejectsTools(fn()=>addOrderStaffNote($pdo,0,'Missing order',bin2hex(random_bytes(32))));
    checkTools(adminRouteAllowed('orders','order_print.php') && adminRouteAllowed('orders','order_note.php'),'Staff can print and write notes');
    $_SESSION['user_id']=$ids['owner'];
    $pdo->prepare('INSERT INTO order_items (order_id,product_id,product_name,quantity,unit_price,line_total) VALUES (?,?,?,2,100,200)')->execute([$order,$product,$tag]); $item=(int)$pdo->lastInsertId();
    $key=bin2hex(random_bytes(32));
    recordReceivedReturn($pdo,$order,$item,1,'resalable',true,'Received unused',$key);
    recordReceivedReturn($pdo,$order,$item,1,'resalable',true,'Received unused',$key);
    $stmt=$pdo->prepare('SELECT stock FROM products WHERE id=?'); $stmt->execute([$product]); checkTools((int)$stmt->fetchColumn()===1,'Duplicate return must not restock twice');
    rejectsTools(fn()=>recordReceivedReturn($pdo,$order,$item,1,'damaged',true,'Broken',bin2hex(random_bytes(32))));
    rejectsTools(fn()=>recordReceivedReturn($pdo,$pending,$item,1,'resalable',true,'Wrong order',bin2hex(random_bytes(32))));
    recordReceivedReturn($pdo,$order,$item,1,'damaged',false,'Broken',bin2hex(random_bytes(32)));
    rejectsTools(fn()=>recordReceivedReturn($pdo,$order,$item,1,'resalable',true,'Too many',bin2hex(random_bytes(32))));
    $stmt->execute([$product]); checkTools((int)$stmt->fetchColumn()===1,'Damaged returns must not restock');
    rejectsTools(fn()=>recordOrderRefund($pdo,$pending,'1','Not collected','TEST',bin2hex(random_bytes(32))));
    rejectsTools(fn()=>changeOrderOperation($pdo,$order,'payment','refunded',$ids['owner']));
    $key=bin2hex(random_bytes(32));
    recordOrderRefund($pdo,$order,'50.25','Partial refund','TEST-PART',$key);
    recordOrderRefund($pdo,$order,'50.25','Partial refund','TEST-PART',$key);
    checkTools(getOrderWithItemsAdmin($pdo,$order)['payment_status']==='part_refunded','Partial refund status');
    rejectsTools(fn()=>changeOrderOperation($pdo,$order,'payment','collected',$ids['owner']));
    rejectsTools(fn()=>recordOrderRefund($pdo,$order,'149.76','Too much','TEST-OVER',bin2hex(random_bytes(32))));
    recordOrderRefund($pdo,$order,'149.75','Remainder','TEST-FULL',bin2hex(random_bytes(32)));
    checkTools(getOrderWithItemsAdmin($pdo,$order)['payment_status']==='refunded','Full refund boundary');
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM order_refunds WHERE order_id=?'); $stmt->execute([$order]); checkTools((int)$stmt->fetchColumn()===2,'Duplicate refund must not be recorded twice');
    rejectsTools(fn()=>recordOrderRefund($pdo,$order,'0.01','Extra','TEST',bin2hex(random_bytes(32))));
    saveOrderDispatch($pdo,$pending,'Test rider','+254712345678','TEST-DEL','2026-09-19T10:30');
    rejectsTools(fn()=>saveOrderDispatch($pdo,$pending,'Test rider','+254712345678','','2026-02-30T10:30'));
    changeOrderOperation($pdo,$pending,'delivery','cancelled',$ids['owner']);
    rejectsTools(fn()=>saveOrderDispatch($pdo,$pending,'Test rider','+254712345678','',''));
    $filters=array_fill_keys(['q','status','payment','area','from','to'],''); $filters['q']=$tag; $filters['area']='CBD'; $filters['payment']='refunded';
    $found=searchAdminOrders($pdo,$filters,1); checkTools($found['total']===1 && (int)$found['rows'][0]['id']===$order,'Combined search finds correct order');
    $filters['q']="' OR 1=1 --"; checkTools(searchAdminOrders($pdo,$filters,1)['total']===0,'Search parameters cannot inject SQL');
    $stmt=$pdo->prepare('SELECT COUNT(*) FROM admin_audit WHERE actor_id=?'); $stmt->execute([$ids['owner']]); checkTools((int)$stmt->fetchColumn()>=7,'Operations write audit events');
    changeStaffAccess($pdo,'staff'.$tag.'@example.test','customer'); $_SESSION['user_id']=$ids['staff']; checkTools(adminScope($pdo)==='none','Revoked staff lose access immediately');
    echo "PASS: staff permissions, role changes, idempotent returns/refunds, refund limits, restock rules, dispatch validation, order search and audit history.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($orders as $id) $pdo->prepare('DELETE FROM orders WHERE id=?')->execute([$id]);
    foreach ($ids as $id) { $pdo->prepare('DELETE FROM admin_audit WHERE actor_id=?')->execute([$id]); $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]); }
    if ($product) $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$product]);
}
