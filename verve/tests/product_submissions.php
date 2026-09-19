<?php
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require __DIR__.'/../config/config.php';
if (!in_array(parse_url(BASE_URL,PHP_URL_HOST),['localhost','127.0.0.1'],true)) throw new RuntimeException('Run against a local test site.');
function checkSubmission(bool $ok,string $message): void { if (!$ok) throw new LogicException($message); }
function rejectSubmission(callable $fn): void { try { $fn(); } catch (PDOException $e) { throw $e; } catch (RuntimeException $e) { return; } throw new LogicException('Operation should have been denied'); }
$users=[]; $products=[]; $cookies=[]; $files=[]; $upload=tempnam(sys_get_temp_dir(),'product-test-');
file_put_contents($upload,base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aWQAAAABJRU5ErkJggg=='));
try {
    $tag='submission-'.bin2hex(random_bytes(8)); $password=bin2hex(random_bytes(16));
    foreach (['owner','orders','other'] as $scope) {
        $id=createUser($pdo,$scope.$tag,$scope.$tag.'@example.test',$password); $users[$scope]=$id;
        $pdo->prepare("UPDATE users SET role='admin',admin_scope=? WHERE id=?")->execute([$scope==='owner'?'owner':'orders',$id]);
    }
    $cookie=tempnam(sys_get_temp_dir(),'submission-cookie-'); $cookies[]=$cookie;
    $request=static function(string $path,?array $post=null) use ($cookie): array {
        $ch=curl_init(BASE_URL.$path); curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$cookie,CURLOPT_COOKIEJAR=>$cookie,CURLOPT_TIMEOUT=>20]);
        if ($post!==null) curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post]);
        $body=curl_exec($ch); $code=curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
        return [$code,$body];
    };
    [$code,$html]=$request('/pages/admin/login.php'); preg_match('/name="csrf_token" value="([^"]+)"/',$html,$match); $csrf=$match[1];
    [$code]=$request('/actions/admin/login.php',['csrf_token'=>$csrf,'email'=>'orders'.$tag.'@example.test','password'=>$password]); checkSubmission($code===302,'Staff login');
    $category=$pdo->query('SELECT id FROM categories LIMIT 1')->fetchColumn();
    $post=['csrf_token'=>$csrf,'name'=>$tag,'category_id'=>$category,'description'=>'Review test','price'=>'125.00','stock'=>'8','is_active'=>'1','is_featured'=>'1','workflow'=>'submit','images[0]'=>new CURLFile($upload,'image/png','test.png')];
    [$code]=$request('/actions/admin/save_product.php',$post); checkSubmission($code===302,'Staff upload request');
    $stmt=$pdo->prepare('SELECT product_id FROM product_submissions WHERE author_id=?'); $stmt->execute([$users['orders']]); $id=(int)$stmt->fetchColumn();
    checkSubmission($id>0,'Submission created with uploaded photo'); $products[]=$id;
    $product=getProductById($pdo,$id); $files[]=$product['image'];
    checkSubmission($product['image'] && !(int)$product['is_active'] && !(int)$product['is_featured'],'Staff cannot publish or feature a draft');
    checkSubmission(getProductBySlug($pdo,$product['slug'])===null,'Pending product not visible in storefront');
    checkSubmission(productSubmission($pdo,$id)['status']==='pending','Submitted for review');
    foreach (['product_submissions.php','product_submission.php?id='.$id] as $page) { [$code]=$request('/pages/admin/'.$page); checkSubmission($code===200,'Staff page renders'); }
    [$code]=$request('/actions/admin/review_product.php',['csrf_token'=>$csrf,'id'=>$id,'decision'=>'publish']); checkSubmission($code===403,'Staff cannot approve');
    [$code]=$request('/pages/admin/product_form.php?id='.$id); checkSubmission($code===403,'Pending submission locked for staff');
    $_SESSION['user_id']=$users['other']; rejectSubmission(fn()=>assertSubmissionEditable($pdo,productSubmission($pdo,$id)));
    $_SESSION['user_id']=$users['owner']; $s=productSubmission($pdo,$id);
    reviewProductSubmission($pdo,$id,(int)$s['version'],'return','Please correct the description.');
    unset($post['images[0]']); $post['product_id']=$id; $post['submission_version']=productSubmission($pdo,$id)['version']; $post['description']='Corrected description';
    [$code]=$request('/actions/admin/save_product.php',$post); checkSubmission($code===302 && productSubmission($pdo,$id)['status']==='pending','Staff can correct and resubmit');
    $s=productSubmission($pdo,$id); reviewProductSubmission($pdo,$id,(int)$s['version'],'inactive','Approved for later.');
    checkSubmission(!(int)getProductById($pdo,$id)['is_active'],'Approve inactive keeps product hidden');
    rejectSubmission(fn()=>reviewProductSubmission($pdo,$id,(int)$s['version'],'publish',''));
    $_SESSION['user_id']=$users['orders']; rejectSubmission(fn()=>assertSubmissionEditable($pdo,productSubmission($pdo,$id)));
    $revision=startProductRevision($pdo,$id); $products[]=$revision;
    checkSubmission(startProductRevision($pdo,$id)===$revision,'Repeated revision request reuses open draft');
    $post['product_id']=$revision; $post['submission_version']=productSubmission($pdo,$revision)['version']; $post['price']='175'; $post['stock']='999';
    [$code]=$request('/actions/admin/save_product.php',$post); checkSubmission($code===302,'Revision submitted');
    checkSubmission((float)getProductById($pdo,$id)['price']===125.0,'Revision does not change live product before approval');
    $pdo->prepare('UPDATE products SET stock=3 WHERE id=?')->execute([$id]);
    $_SESSION['user_id']=$users['owner']; $s=productSubmission($pdo,$revision);
    reviewProductSubmission($pdo,$revision,(int)$s['version'],'publish','Ready');
    $live=getProductById($pdo,$id);
    checkSubmission((float)$live['price']===175.0 && (int)$live['stock']===3 && (int)$live['is_active']===1,'Revision updates original, preserves current stock and publishes');
    checkSubmission(!(int)getProductById($pdo,$revision)['is_active'],'Revision copy stays hidden');
    [$code]=$request('/actions/admin/save_product.php',$post); checkSubmission($code===403,'Stale staff edit cannot overwrite approved revision');
    echo "PASS: staff uploads, ownership, hidden submissions, return/resubmit, owner review, inactive approval, duplicate reviews, revision isolation and stock preservation.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    foreach ($users as $id) {
        $stmt=$pdo->prepare('SELECT product_id FROM product_submissions WHERE author_id=?'); $stmt->execute([$id]);
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $productId) { $products[]=(int)$productId; $p=getProductById($pdo,(int)$productId); if ($p && $p['image']) $files[]=$p['image']; }
        $pdo->prepare('DELETE FROM product_submissions WHERE author_id=?')->execute([$id]);
        $pdo->prepare('DELETE FROM admin_audit WHERE actor_id=?')->execute([$id]);
    }
    foreach (array_unique($products) as $id) $pdo->prepare('DELETE FROM products WHERE id=?')->execute([$id]);
    foreach (array_unique($files) as $file) removeUnreferencedProductUpload($pdo,$file);
    foreach ($users as $id) $pdo->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
    foreach ($cookies as $file) if (is_file($file)) unlink($file);
    if (is_file($upload)) unlink($upload);
}
