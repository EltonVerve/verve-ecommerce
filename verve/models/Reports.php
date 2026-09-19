<?php
function getPeriodReports(PDO $pdo, int $days): array {
    $days = in_array($days, [7, 30, 90], true) ? $days : 30;
    $today = new DateTimeImmutable($pdo->query('SELECT CURRENT_DATE()')->fetchColumn());
    $start = $today->modify('-' . ($days - 1) . ' days');
    $end = $today->modify('+1 day');
    $params = [$start->format('Y-m-d'), $end->format('Y-m-d')];
    $queries = [
        'sales' => "SELECT DATE(created_at) AS day, COUNT(*) AS orders, SUM(total) AS value FROM orders WHERE created_at >= ? AND created_at < ? AND status <> 'cancelled' GROUP BY DATE(created_at) ORDER BY day",
        'products' => "SELECT oi.product_name, SUM(oi.quantity) AS units, SUM(oi.line_total) AS value FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.created_at >= ? AND o.created_at < ? AND o.status <> 'cancelled' GROUP BY oi.product_name ORDER BY units DESC, oi.product_name LIMIT 10",
        'categories' => "SELECT COALESCE(c.name, 'Deleted / uncategorized product') AS name, SUM(oi.line_total) AS value FROM order_items oi JOIN orders o ON o.id = oi.order_id LEFT JOIN products p ON p.id = oi.product_id LEFT JOIN categories c ON c.id = p.category_id WHERE o.created_at >= ? AND o.created_at < ? AND o.status <> 'cancelled' GROUP BY c.id, c.name ORDER BY value DESC, name",
        'statuses' => 'SELECT status, COUNT(*) AS orders, SUM(total) AS value FROM orders WHERE created_at >= ? AND created_at < ? GROUP BY status ORDER BY status',
    ];
    $report = ['start' => $params[0], 'end' => $today->format('Y-m-d')];
    foreach ($queries as $key => $sql) {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $report[$key] = $stmt->fetchAll();
    }
    $byDay = array_column($report['sales'], null, 'day');
    $report['sales'] = [];
    for ($day = $start; $day < $end; $day = $day->modify('+1 day')) {
        $date = $day->format('Y-m-d');
        $report['sales'][] = $byDay[$date] ?? ['day' => $date, 'orders' => 0, 'value' => 0];
    }
    return $report;
}
