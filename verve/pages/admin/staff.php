<?php
require __DIR__.'/../../config/config.php'; requireAdmin();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    verifyCsrf(); limitAuthRequests($pdo,'profile',(string)$_SESSION['user_id']);
    $input=static fn($key)=>is_string($_POST[$key] ?? null)?trim($_POST[$key]):'';
    $actor=findUserById($pdo,(int)$_SESSION['user_id']);
    $password=is_string($_POST['current_password'] ?? null)?$_POST['current_password']:'';
    try {
        if (!$actor || !password_verify($password,$actor['password_hash'])) throw new RuntimeException('Enter your current admin password.');
        changeStaffAccess($pdo,$input('email'),$input('scope')); setFlash('success','Staff access updated.');
    } catch (RuntimeException $e) { setFlash('error',$e instanceof PDOException?'Unable to update staff access.':$e->getMessage()); }
    header('Location: staff.php'); exit;
}
$staff=$pdo->query("SELECT id,full_name,email,admin_scope FROM users WHERE role='admin' ORDER BY full_name,id")->fetchAll();
$pageTitle='Staff access'; require __DIR__.'/../../includes/admin/admin_header.php'; require __DIR__.'/../../includes/admin/admin_flash.php';
?>
<div class="admin-topbar"><h1>Staff access</h1></div>
<div class="admin-card"><h2>Who can access admin</h2><p class="hint">Owners have full access. Order staff can handle orders, assign riders, create product drafts with photos and submit them for approval. Staff can revise their own approved products through review. Publishing, refunds, returns, reports and access settings are owner-only.</p>
<table class="admin-table"><thead><tr><th>Name</th><th>Email</th><th>Access</th></tr></thead><tbody><?php foreach ($staff as $member): ?><tr><td><?= h($member['full_name']) ?><?= (int)$member['id']===(int)$_SESSION['user_id']?' (you)':'' ?></td><td><?= h($member['email']) ?></td><td><?= $member['admin_scope']==='owner'?'Owner':'Order staff' ?></td></tr><?php endforeach; ?></tbody></table></div>
<form method="post" class="admin-card"><?= csrfField() ?><h2>Grant or change access</h2><p class="hint">Use an existing account email. Your own access cannot be changed here.</p><div class="form-grid">
<div class="field"><label for="staff-email">Account email</label><input type="email" id="staff-email" name="email" required maxlength="190"></div>
<div class="field"><label for="staff-scope">Access level</label><select id="staff-scope" name="scope"><option value="orders">Order staff</option><option value="owner">Owner — full access</option><option value="customer">Customer — remove admin access</option></select></div>
<div class="field"><label for="staff-password">Your current admin password</label><input type="password" id="staff-password" name="current_password" autocomplete="current-password" required></div></div><button class="btn btn-primary">Save access</button></form>
<?php require __DIR__.'/../../includes/admin/admin_footer.php'; ?>
