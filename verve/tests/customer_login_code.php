<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../config/config.php';
function checkCode(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$id = createUser($pdo, 'Code Test', bin2hex(random_bytes(12)) . '@example.test', 'test-password-123');
$challenges = [];
try {
    $user = findUserById($pdo, $id);
    [$challenge, $code] = createCustomerLoginCode($pdo, $user); $challenges[] = $challenge;
    checkCode(consumeCustomerLoginCode($pdo, $challenge, '000000') === null, 'Wrong code must fail');
    checkCode((int) consumeCustomerLoginCode($pdo, $challenge, $code)['id'] === $id, 'Correct code must authenticate');
    checkCode(consumeCustomerLoginCode($pdo, $challenge, $code) === null, 'Code must be single-use');
    [$challenge, $code] = createCustomerLoginCode($pdo, $user); $challenges[] = $challenge;
    for ($i = 0; $i < 5; $i++) checkCode(consumeCustomerLoginCode($pdo, $challenge, '000000') === null, 'Wrong guess must fail');
    checkCode(consumeCustomerLoginCode($pdo, $challenge, $code) === null, 'Five guesses must exhaust a code');
    [$challenge, $code] = createCustomerLoginCode($pdo, $user); $challenges[] = $challenge;
    $pdo->prepare('UPDATE customer_login_codes SET expires_at = ? WHERE challenge = ?')->execute([time() - 1, $challenge]);
    checkCode(consumeCustomerLoginCode($pdo, $challenge, $code) === null, 'Expired code must fail');
    [$challenge, $code] = createCustomerLoginCode($pdo, null); $challenges[] = $challenge;
    checkCode(consumeCustomerLoginCode($pdo, $challenge, $code) === null, 'Unknown account must not authenticate');
    $admin = $user; $admin['role'] = 'admin';
    [$challenge, $code] = createCustomerLoginCode($pdo, $admin); $challenges[] = $challenge;
    checkCode(consumeCustomerLoginCode($pdo, $challenge, $code) === null, 'Admin must not authenticate through customer codes');
    [$challenge, $code] = createCustomerLoginCode($pdo, $user); $challenges[] = $challenge;
    updateUserPassword($pdo, $id, 'changed-password-123');
    checkCode(consumeCustomerLoginCode($pdo, $challenge, $code) === null, 'Password change must invalidate outstanding code');
    echo "PASS: correct code, wrong guesses, replay, expiry, attempt limit, unknown account, admin isolation, password invalidation.\n";
} finally {
    foreach ($challenges as $challenge) $pdo->prepare('DELETE FROM customer_login_codes WHERE challenge = ?')->execute([$challenge]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
}
