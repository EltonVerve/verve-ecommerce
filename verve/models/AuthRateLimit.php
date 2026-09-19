<?php
// Shared database counters survive fresh browser sessions. Raw IPs are not stored.
function limitAuthRequests(PDO $pdo, string $scope, string $identity = ''): void {
    $window = (int) floor(time() / 900);
    foreach (['ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') => 30, 'account:' . strtolower($identity) => 10] as $key => $limit) {
        $hash = hash('sha256', $scope . ':' . $key . ':' . $window);
        $pdo->prepare('INSERT INTO auth_rate_limits (bucket, attempts, expires_at) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE attempts = attempts + 1')
            ->execute([$hash, ($window + 1) * 900]);
        $stmt = $pdo->prepare('SELECT attempts FROM auth_rate_limits WHERE bucket = ?');
        $stmt->execute([$hash]);
        if ((int) $stmt->fetchColumn() > $limit) {
            http_response_code(429);
            header('Retry-After: ' . (($window + 1) * 900 - time()));
            exit('Too many attempts. Please try again in 15 minutes.');
        }
    }
    $pdo->exec('DELETE FROM auth_rate_limits WHERE expires_at < ' . time());
}
