<?php
/**
 * PROMOTE CUSTOMER ACTION
 * ---------------------------------------------------------
 * Upgrades a customer account to an admin account. A logged-in
 * admin cannot demote/promote their own account through this
 * form — that's a deliberate safeguard against ever locking
 * every admin out at once.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/customers.php');
    exit;
}
verifyCsrf();

$id = (int) ($_POST['id'] ?? 0);

if ($id === (int) $_SESSION['user_id']) {
    setFlash('error', 'You cannot change your own role.');
    header('Location: ' . BASE_URL . '/pages/admin/customers.php');
    exit;
}

setUserRole($pdo, $id, 'admin');
setFlash('success', 'That account is now an admin.');
header('Location: ' . BASE_URL . '/pages/admin/customers.php');
exit;
