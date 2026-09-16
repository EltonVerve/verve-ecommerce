<?php
/**
 * DASHBOARD MODEL
 * ---------------------------------------------------------
 * All the "big picture" queries for the admin dashboard live
 * here — nothing about individual products or orders (that's
 * Product.php / Order.php), just aggregates and summaries.
 * ---------------------------------------------------------
 */

// The headline numbers at the top of the dashboard.
function getDashboardStats(PDO $pdo): array {
    $revenue = $pdo->query("SELECT COALESCE(SUM(total),0) AS total FROM orders WHERE status != 'cancelled'")->fetch()['total'];
    $orderCount = $pdo->query("SELECT COUNT(*) AS c FROM orders")->fetch()['c'];
    $pendingCount = $pdo->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'")->fetch()['c'];
    $productCount = $pdo->query("SELECT COUNT(*) AS c FROM products WHERE is_active = 1")->fetch()['c'];
    $customerCount = $pdo->query("SELECT COUNT(*) AS c FROM users WHERE role = 'customer'")->fetch()['c'];

    return [
        'revenue'   => (float) $revenue,
        'orders'    => (int) $orderCount,
        'pending'   => (int) $pendingCount,
        'products'  => (int) $productCount,
        'customers' => (int) $customerCount,
    ];
}

// Revenue per day for the last N days — used to draw the chart.
// Fills in $0 for days with no orders, so the line doesn't skip
// gaps or look misleading.
function getSalesOverTime(PDO $pdo, int $days = 14): array {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS d, SUM(total) AS revenue
        FROM orders
        WHERE created_at >= (NOW() - INTERVAL ? DAY) AND status != 'cancelled'
        GROUP BY DATE(created_at)
    ");
    $stmt->bindValue(1, $days, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();

    $byDate = [];
    foreach ($rows as $row) {
        $byDate[$row['d']] = (float) $row['revenue'];
    }

    $labels = [];
    $values = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('M j', strtotime($date));
        $values[] = $byDate[$date] ?? 0;
    }

    return ['labels' => $labels, 'values' => $values];
}

// The most recent orders, with the customer's name attached.
function getRecentOrders(PDO $pdo, int $limit = 6): array {
    $stmt = $pdo->prepare("
        SELECT o.*, COALESCE(u.full_name, JSON_UNQUOTE(JSON_EXTRACT(o.delivery_address, '$.full_name')), 'Guest') AS customer_name
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id
        ORDER BY o.created_at DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// Products running low on stock.
function getLowStockProducts(PDO $pdo, int $threshold = 5): array {
    $stmt = $pdo->prepare("
        SELECT * FROM products
        WHERE is_active = 1 AND stock <= ?
        ORDER BY stock ASC
        LIMIT 8
    ");
    $stmt->execute([$threshold]);
    return $stmt->fetchAll();
}

// Best-selling products by total quantity ordered — reads from
// order_items since that's the permanent record of what was
// actually bought (not affected by products being edited later).
function getTopSellingProducts(PDO $pdo, int $limit = 5): array {
    $stmt = $pdo->prepare("
        SELECT product_name, SUM(quantity) AS total_sold, SUM(line_total) AS total_revenue
        FROM order_items
        GROUP BY product_name
        ORDER BY total_sold DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
