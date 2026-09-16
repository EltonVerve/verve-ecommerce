<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Messages';
$statusFilter = is_string($_GET['status'] ?? null) ? $_GET['status'] : '';
$messages = getContactMessagesAdmin($pdo, $statusFilter);

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar"><h1>Messages</h1></div>

<div class="admin-toolbar">
  <form method="get">
    <select name="status" class="sort-select" onchange="this.form.submit()">
      <option value="">All statuses</option>
      <?php foreach (getContactMessageStatuses() as $status): ?>
        <option value="<?= h($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>><?= h(ucfirst($status)) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <span class="muted small"><?= count($messages) ?> message<?= count($messages) === 1 ? '' : 's' ?></span>
</div>

<div class="admin-card">
  <table class="admin-table">
    <thead><tr><th>From</th><th>Type</th><th>Preview</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
      <?php foreach ($messages as $m): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/pages/admin/message_detail.php?id=<?= (int) $m['id'] ?>"><?= h($m['name']) ?></a><br><span class="muted small"><?= h($m['email']) ?></span></td>
          <td><?= h(ucwords(str_replace('_', ' ', $m['enquiry_type']))) ?></td>
          <td class="muted small"><?= h($m['message_preview']) ?>…</td>
          <td><span class="status-pill status-<?= $m['status'] === 'new' ? 'pending' : ($m['status'] === 'replied' ? 'completed' : ($m['status'] === 'archived' ? 'cancelled' : 'processing')) ?>"><?= h(ucfirst($m['status'])) ?></span></td>
          <td><?= date('M j, Y', strtotime($m['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$messages): ?><tr><td colspan="5" class="muted">No messages found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
