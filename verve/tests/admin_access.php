<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__.'/../config/config.php';
if (!in_array(parse_url(BASE_URL,PHP_URL_HOST),['localhost','127.0.0.1'],true)) throw new RuntimeException('HTTP tests require a local test installation.');
function accessCheck(bool $ok,string $message): void { if (!$ok) throw new LogicException($message); }
$ids=[]; $cookies=[]; $order=0;
try {
    $tag=bin2hex(random_bytes(8)); $password=bin2hex(random_bytes(18));
    foreach (['owner','orders'] as $scope) {
        $email=$scope.$tag.'@example.test'; $id=createUser($pdo,'HTTP '.$scope,$email,$password); $ids[]=$id;
        $pdo->prepare("UPDATE users SET role='admin',admin_scope=? WHERE id=?")->execute([$scope,$id]);
        if (!$order) {
            $pdo->prepare("INSERT INTO orders (user_id,subtotal,total,status,payment_status) VALUES (?,100,100,'shipped','collected')")->execute([$id]); $order=(int)$pdo->lastInsertId();
        }
        $cookie=tempnam(sys_get_temp_dir(),'verve-admin-'); $cookies[]=$cookie;
        $request=static function(string $path,?array $post=null) use ($cookie): array {
            $ch=curl_init(BASE_URL.$path); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_TIMEOUT=>15,CURLOPT_FOLLOWLOCATION=>false]);
            if ($post!==null) curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($post)]);
            $html=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); $error=curl_error($ch); curl_close($ch);
            if ($html===false) throw new RuntimeException('Local HTTP request failed: '.$error);
            return [$code,$html];
        };
        [$code,$html]=$request('/pages/admin/login.php'); accessCheck($code===200,'Login page renders');
        preg_match('/name="csrf_token" value="([^"]+)"/',$html,$match); accessCheck(isset($match[1]),'Login CSRF present');
        [$code]=$request('/actions/admin/login.php',['csrf_token'=>$match[1],'email'=>$email,'password'=>$password]); accessCheck($code===302,'Staff can log in');
        foreach (['orders.php','order_detail.php?id='.$order,'order_print.php?id='.$order,'profile.php'] as $page) {
            [$code,$html]=$request('/pages/admin/'.$page); accessCheck($code===200 && !str_contains($html,'Something went wrong') && !str_contains($html,'Warning:'),'Page renders: '.$scope.' '.$page);
        }
        foreach (['dashboard.php','stock.php','staff.php','audit.php','delivery.php'] as $page) {
            [$code,$html]=$request('/pages/admin/'.$page); accessCheck($code===($scope==='owner'?200:403),'Permission on '.$scope.' '.$page);
        }
        foreach (['record_return.php','record_refund.php','save_product.php','promote_customer.php'] as $action) {
            $post=$scope==='orders'?['csrf_token'=>$match[1],'order_id'=>$order]:[];
            [$code]=$request('/actions/admin/'.$action,$post); accessCheck($code===403,'Missing CSRF or staff privilege blocks '.$action);
        }
        if ($scope==='orders') {
            $note='Private handover <script>alert(1)</script>';
            $post=['csrf_token'=>$match[1],'order_id'=>$order,'note'=>$note,'request_key'=>bin2hex(random_bytes(32))];
            [$code]=$request('/actions/admin/order_note.php',$post); accessCheck($code===302,'Staff can save an internal note');
            [$code,$html]=$request('/pages/admin/order_detail.php?id='.$order);
            accessCheck(str_contains($html,h($note)) && !str_contains($html,$note),'Notes render escaped');
            [$code,$html]=$request('/pages/admin/order_print.php?id='.$order);
            accessCheck(!str_contains($html,'Private handover'),'Packing slips exclude internal notes');
            [$code]=$request('/actions/admin/order_note.php',['order_id'=>$order,'note'=>'No CSRF']); accessCheck($code===403,'Note action requires CSRF');
            [$code,$html]=$request('/pages/admin/orders.php');
            accessCheck(!str_contains($html,'>Staff access</a>') && !str_contains($html,'>Products</a>'),'Restricted navigation omits owner links');
            $pdo->prepare("UPDATE users SET role='customer' WHERE id=?")->execute([$id]);
            [$code]=$request('/pages/admin/orders.php'); accessCheck($code===302,'Revocation blocks existing session');
        }
    }
    echo "PASS: owner/staff HTTP login, rendered admin screens, restricted navigation, direct-action permissions, CSRF and access revocation.\n";
} finally {
    if ($order) $pdo->prepare("DELETE FROM admin_audit WHERE entity_type='order' AND entity_id=?")->execute([$order]);
    if ($order) $pdo->prepare('DELETE FROM orders WHERE id=?')->execute([$order]);
    foreach ($ids as $id) $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    foreach ($cookies as $cookie) if (is_file($cookie)) unlink($cookie);
}
