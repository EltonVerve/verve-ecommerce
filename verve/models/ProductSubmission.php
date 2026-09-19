<?php
function productSubmission(PDO $pdo, int $productId, bool $lock=false): ?array {
    $stmt=$pdo->prepare('SELECT * FROM product_submissions WHERE product_id=?'.($lock?' FOR UPDATE':''));
    $stmt->execute([$productId]); return $stmt->fetch() ?: null;
}
function assertSubmissionEditable(PDO $pdo, ?array $submission): void {
    if (adminScope($pdo)==='owner') return;
    if (!$submission || (int)$submission['author_id']!==(int)$_SESSION['user_id'] || !in_array($submission['status'],['draft','returned'],true)) {
        throw new RuntimeException('Only your own draft or returned submissions can be edited.');
    }
}
function reviewProductSubmission(PDO $pdo, int $id, int $version, string $decision, string $note): void {
    if (adminScope($pdo)!=='owner') throw new RuntimeException('Only an owner can review products.');
    if (!in_array($decision,['publish','inactive','return'],true) || mb_strlen($note)>1000 || ($decision==='return' && trim($note)==='')) throw new RuntimeException('Choose a review decision and explain any requested changes.');
    $pdo->beginTransaction();
    try {
        $s=productSubmission($pdo,$id,true);
        if (!$s || $s['status']!=='pending' || (int)$s['version']!==$version) throw new RuntimeException('This submission changed or was already reviewed. Reload it first.');
        $draft=getProductById($pdo,$id);
        if (!$draft) throw new RuntimeException('Product not found.');
        $publishedId=$id;
        if ($decision!=='return') {
            if (!$draft['image'] || trim($draft['description'] ?? '')==='') throw new RuntimeException('A description and photo are required before approval.');
            if ($s['target_id']) {
                $publishedId=(int)$s['target_id'];
                $stmt=$pdo->prepare('SELECT * FROM products WHERE id=? FOR UPDATE'); $stmt->execute([$publishedId]); $live=$stmt->fetch();
                if (!$live) throw new RuntimeException('The original product no longer exists.');
                // Sales may have changed stock during review. Never overwrite live inventory with a draft snapshot.
                $draft['stock']=$live['stock']; $draft['slug']=$live['slug']; $draft['is_featured']=$live['is_featured'];
                $draft['is_active']=$decision==='publish'?1:0;
                updateProduct($pdo,$publishedId,$draft);
                $pdo->prepare('DELETE FROM product_images WHERE product_id=?')->execute([$publishedId]);
                foreach (getProductImages($pdo,$id) as $image) addProductImage($pdo,$publishedId,$image['filename'],(int)$image['sort_order']);
                replaceOptionGroups($pdo,$publishedId,getOptionGroupsForProduct($pdo,$id));
            } else {
                $pdo->prepare('UPDATE products SET is_active=? WHERE id=?')->execute([$decision==='publish'?1:0,$id]);
            }
        }
        $status=$decision==='return'?'returned':'approved';
        $pdo->prepare('UPDATE product_submissions SET status=?,review_note=?,reviewer_id=?,version=version+1 WHERE id=?')->execute([$status,trim($note),$_SESSION['user_id'],$s['id']]);
        auditAdmin($pdo,'product.review','product',$id,['decision'=>$decision,'note'=>trim($note),'published_id'=>$publishedId,'author_id'=>$s['author_id']]);
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function startProductRevision(PDO $pdo, int $id): int {
    $pdo->beginTransaction();
    try {
        $s=productSubmission($pdo,$id,true);
        if (!$s || $s['status']!=='approved' || (int)$s['author_id']!==(int)$_SESSION['user_id']) throw new RuntimeException('Choose one of your approved submissions.');
        $target=(int)($s['target_id'] ?: $id);
        $stmt=$pdo->prepare('SELECT * FROM products WHERE id=? FOR UPDATE'); $stmt->execute([$target]); $data=$stmt->fetch();
        if (!$data) throw new RuntimeException('Product not found.');
        $stmt=$pdo->prepare("SELECT product_id FROM product_submissions WHERE target_id=? AND author_id=? AND status IN ('draft','returned','pending') LIMIT 1");
        $stmt->execute([$target,$_SESSION['user_id']]);
        if ($existing=$stmt->fetchColumn()) { $pdo->commit(); return (int)$existing; }
        $data['slug']='review-'.bin2hex(random_bytes(16)); $data['is_active']=0;
        $draftId=createProduct($pdo,$data);
        foreach (getProductImages($pdo,$target) as $image) addProductImage($pdo,$draftId,$image['filename'],(int)$image['sort_order']);
        replaceOptionGroups($pdo,$draftId,getOptionGroupsForProduct($pdo,$target));
        $pdo->prepare('INSERT INTO product_submissions (product_id,target_id,author_id) VALUES (?,?,?)')->execute([$draftId,$target,$_SESSION['user_id']]);
        auditAdmin($pdo,'product.revision','product',$draftId,['target_id'=>$target]);
        $pdo->commit(); return $draftId;
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
