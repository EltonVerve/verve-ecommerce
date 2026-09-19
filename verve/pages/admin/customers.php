<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

$pageTitle = 'Customers';
$search = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
$customers = getAllCustomersWithStats($pdo, $search);

require __DIR__ . '/../../includes/admin/admin_header.php';
require __DIR__ . '/../../includes/admin/admin_flash.php';
?>

<div class="admin-topbar"><h1>Customers</h1></div>

<div class="admin-toolbar">
  <form class="admin-search" method="get">
    <input type="text" name="q" placeholder="Search by name or email…" value="<?= h($search) ?>">
    <button type="submit" class="btn btn-outline btn-sm">Search</button>
  </form>
  <span class="muted small"><?= count($customers) ?> customer<?= count($customers) === 1 ? '' : 's' ?></span>
</div>

<div class="admin-card">
  <table class="admin-table">
    <thead><tr><th>Name</th><th>Email</th><th>Orders</th><th>Total spent</th><th>Joined</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><a href="<?= BASE_URL ?>/pages/admin/customer_detail.php?id=<?= (int) $c['id'] ?>"><?= h($c['full_name']) ?></a></td>
          <td><?= h($c['email']) ?></td>
          <td><?= (int) $c['order_count'] ?></td>
          <td><?= money((float) $c['total_spent']) ?></td>
          <td><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
          <td>
            <form action="<?= BASE_URL ?>/actions/admin/promote_customer.php" method="post" onsubmit="return confirm('Make this account an admin?');">
              <?= csrfField() ?>
              <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
              <input type="password" name="current_password" required autocomplete="current-password" placeholder="Your admin password" aria-label="Your admin password"><button type="submit" class="btn btn-outline btn-sm">Make admin</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$customers): ?><tr><td colspan="6" class="muted">No customers found.</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
