<?php
require __DIR__.'/../../config/config.php'; requireAdmin();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
verifyCsrf(); $id=(int)($_POST['id'] ?? 0);
try {
    reviewProductSubmission($pdo,$id,(int)($_POST['version'] ?? 0),is_string($_POST['decision'] ?? null)?$_POST['decision']:'',is_string($_POST['note'] ?? null)?trim($_POST['note']):'');
    setFlash('success','Review decision saved.');
} catch (RuntimeException $e) { setFlash('error',$e instanceof PDOException?'Unable to save review.':$e->getMessage()); }
header('Location: '.BASE_URL.'/pages/admin/product_submission.php?id='.$id); exit;
