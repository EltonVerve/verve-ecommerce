<?php
function customerCodeMailReady(): bool {
    $from = getenv('VERVE_MAIL_FROM') ?: '';
    return !str_contains($from, "\r") && !str_contains($from, "\n") && (bool) filter_var($from, FILTER_VALIDATE_EMAIL);
}

function createCustomerLoginCode(PDO $pdo, ?array $user): array {
    $challenge = bin2hex(random_bytes(32));
    $code = (string) random_int(100000, 999999);
    $customer = $user && $user['role'] === 'customer' ? $user : null;
    $pdo->prepare('INSERT INTO customer_login_codes (challenge, user_id, code_hash, password_fingerprint, expires_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$challenge, $customer['id'] ?? null, password_hash($code, PASSWORD_DEFAULT), $customer ? hash('sha256', $customer['password_hash']) : '', time() + 600]);
    $pdo->exec('DELETE FROM customer_login_codes WHERE expires_at < ' . time());
    return [$challenge, $code];
}

// Lock and consume atomically: concurrent submissions cannot reuse a code.
function consumeCustomerLoginCode(PDO $pdo, string $challenge, string $code): ?array {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT * FROM customer_login_codes WHERE challenge = ? FOR UPDATE');
        $stmt->execute([$challenge]);
        $row = $stmt->fetch();
        $user = null;
        if ($row && (int) $row['expires_at'] > time() && (int) $row['attempts'] < 5) {
            $pdo->prepare('UPDATE customer_login_codes SET attempts = attempts + 1 WHERE challenge = ?')->execute([$challenge]);
            if (preg_match('/^[0-9]{6}$/D', $code) && password_verify($code, $row['code_hash'])) {
                $candidate = $row['user_id'] ? findUserById($pdo, (int) $row['user_id']) : null;
                if ($candidate && $candidate['role'] === 'customer' && hash_equals($row['password_fingerprint'], hash('sha256', $candidate['password_hash']))) $user = $candidate;
                $pdo->prepare('DELETE FROM customer_login_codes WHERE challenge = ?')->execute([$challenge]);
            }
        }
        $pdo->commit();
        return $user;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}
