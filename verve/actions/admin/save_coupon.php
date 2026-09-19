<?php
require_once __DIR__ . '/../../config/config.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/admin/coupons.php');
    exit;
}

verifyCsrf();

$id = !empty($_POST['id']) ? (int) $_POST['id'] : null;
$code = strtoupper(trim((string) ($_POST['code'] ?? '')));
$type = $_POST['type'] ?? 'percent';
$value = (float) ($_POST['value'] ?? 0);
$expiresAt = trim((string) ($_POST['expires_at'] ?? ''));
$isActive = isset($_POST['is_active']) ? true : false;

if ($code === '') {
    setFlash('error', 'Please enter a coupon code.');
    header('Location: ' . BASE_URL . '/pages/admin/coupons.php' . ($id ? '?edit=' . $id : ''));
    exit;
}

if (!in_array($type, ['percent', 'flat'], true)) {
    setFlash('error', 'Coupon type is invalid.');
    header('Location: ' . BASE_URL . '/pages/admin/coupons.php' . ($id ? '?edit=' . $id : ''));
    exit;
}

if ($value <= 0) {
    setFlash('error', 'Coupon value must be greater than zero.');
    header('Location: ' . BASE_URL . '/pages/admin/coupons.php' . ($id ? '?edit=' . $id : ''));
    exit;
}

if ($expiresAt !== '') {
    $date = DateTime::createFromFormat('Y-m-d', $expiresAt);
    if (!$date || $date->format('Y-m-d') !== $expiresAt) {
        setFlash('error', 'Expiry date must be a valid date in YYYY-MM-DD format.');
        header('Location: ' . BASE_URL . '/pages/admin/coupons.php' . ($id ? '?edit=' . $id : ''));
        exit;
    }
    $expiresAt = $date->format('Y-m-d');
} else {
    $expiresAt = null;
}

$duplicateSql = "SELECT id FROM coupons WHERE UPPER(code) = ? AND id != ?";
$duplicateStmt = $pdo->prepare($duplicateSql);
$duplicateStmt->execute([$code, $id ?? 0]);
if ($duplicateStmt->fetch()) {
    setFlash('error', 'That coupon code already exists.');
    header('Location: ' . BASE_URL . '/pages/admin/coupons.php' . ($id ? '?edit=' . $id : ''));
    exit;
}

if ($id) {
    updateCoupon($pdo, $id, $code, $type, $value, $isActive, $expiresAt);
    setFlash('success', 'Coupon updated.');
} else {
    createCoupon($pdo, $code, $type, $value, $isActive, $expiresAt);
    setFlash('success', 'Coupon created.');
}

header('Location: ' . BASE_URL . '/pages/admin/coupons.php');
exit;
