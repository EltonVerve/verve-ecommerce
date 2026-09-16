<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$messageId = (int) ($_GET['id'] ?? 0);
$message = findContactMessageAdmin($pdo, $messageId);

if (!$message) {
    setFlash('error', 'Message not found.');
    header('Location: ' . BASE_URL . '/pages/admin/messages.php');
    exit;
}

if ($message['status'] === 'new') {
    updateContactMessageStatus($pdo, $messageId, 'read');
    $message['status'] = 'read';
}

$pageTitle = 'Message from ' . $message['name'];
require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar">
  <h1>Message from <?= h($message['name']) ?></h1>
  <a href="<?= BASE_URL ?>/pages/admin/messages.php" class="btn btn-outline btn-sm">← Back to messages</a>
</div>

<div style="display:grid; grid-template-columns:1.4fr 1fr; gap:1.6rem; align-items:start;">
  <div class="admin-card">
    <h2>Message</h2>
    <p><?= nl2br(h($message['message'])) ?></p>
    <div class="divider"></div>
    <p class="muted small">Received <?= date('F j, Y \a\t g:ia', strtotime($message['created_at'])) ?></p>
  </div>

  <div>
    <div class="admin-card">
      <h2>Status</h2>
      <form action="<?= BASE_URL ?>/actions/admin/update_contact_message.php" method="post">
        <?= csrfField() ?>
        <input type="hidden" name="id" value="<?= (int) $message['id'] ?>">
        <select name="status" onchange="this.form.submit()">
          <?php foreach (getContactMessageStatuses() as $status): ?>
            <option value="<?= h($status) ?>" <?= $message['status'] === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
          <?php endforeach; ?>
        </select>
      </form>
    </div>

    <div class="admin-card">
      <h2>Contact details</h2>
      <p class="small">
        Type: <?= h(ucwords(str_replace('_', ' ', $message['enquiry_type']))) ?><br>
        Email: <a href="mailto:<?= h($message['email']) ?>"><?= h($message['email']) ?></a><br>
        <?php if ($message['phone']): ?>Phone: <?= h($message['phone']) ?><br><?php endif; ?>
        <?php if ($message['order_reference']): ?>Order reference: <?= h($message['order_reference']) ?><br><?php endif; ?>
        <?php if ($message['account_name']): ?>Account: <?= h($message['account_name']) ?> (<?= h($message['account_email']) ?>)<?php endif; ?>
      </p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
