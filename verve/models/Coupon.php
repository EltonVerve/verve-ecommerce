<?php
/**
 * COUPON MODEL
 * ---------------------------------------------------------
 * Simple percentage/flat discount codes applied at checkout.
 * ---------------------------------------------------------
 */

// Looks up a code and returns it only if it's active and not expired.
function findValidCoupon(PDO $pdo, string $code): ?array {
    $stmt = $pdo->prepare("
        SELECT * FROM coupons
        WHERE code = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at >= CURDATE())
    ");
    $stmt->execute([strtoupper(trim($code))]);
    $coupon = $stmt->fetch();
    return $coupon ?: null;
}
