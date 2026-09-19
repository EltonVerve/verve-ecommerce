<?php
require __DIR__.'/../../config/config.php'; requireAdmin();
if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }
verifyCsrf();
try { $id=startProductRevision($pdo,(int)($_POST['id'] ?? 0)); header('Location: '.BASE_URL.'/pages/admin/product_form.php?id='.$id); }
catch (RuntimeException $e) { setFlash('error',$e instanceof PDOException?'Unable to start revision.':$e->getMessage()); header('Location: '.BASE_URL.'/pages/admin/product_submissions.php'); }
exit;
