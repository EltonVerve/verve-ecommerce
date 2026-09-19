<?php
// Only call from an authenticated admin page. Each query is scoped to one account.
function getCustomerActivity(PDO $pdo, int $userId): array {
    $queries = [
        'orders' => 'SELECT id, status, total, payment_method, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100',
        'wishlist' => 'SELECT p.id, p.name, p.price, p.is_active, w.created_at FROM wishlist_items w JOIN products p ON p.id = w.product_id WHERE w.user_id = ? ORDER BY w.created_at DESC LIMIT 100',
        'cart' => 'SELECT p.name, c.quantity, c.created_at FROM cart_items c JOIN products p ON p.id = c.product_id WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 100',
        'reviews' => 'SELECT p.name, r.rating, r.comment, r.created_at FROM reviews r JOIN products p ON p.id = r.product_id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 100',
    ];
    $activity = [];
    foreach ($queries as $key => $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userId]);
        $activity[$key] = $stmt->fetchAll();
    }
    $stmt = $pdo->prepare("SELECT COUNT(*) AS order_count, COALESCE(SUM(CASE WHEN status <> 'cancelled' THEN total ELSE 0 END), 0) AS order_value FROM orders WHERE user_id = ?");
    $stmt->execute([$userId]);
    $activity['summary'] = $stmt->fetch();
    return $activity;
}
