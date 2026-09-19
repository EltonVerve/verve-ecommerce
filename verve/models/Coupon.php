<?php
/**
 * COUPON MODEL
 * ---------------------------------------------------------
 * Simple percentage/flat discount codes applied at checkout.
 * ---------------------------------------------------------
 */

function getAllCoupons(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM coupons ORDER BY is_active DESC, expires_at IS NULL DESC, expires_at ASC, code ASC");
    return $stmt->fetchAll();
}

function getCouponById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    $coupon = $stmt->fetch();
    return $coupon ?: null;
}

function createCoupon(PDO $pdo, string $code, string $type, float $value, bool $isActive, ?string $expiresAt = null): int {
    $stmt = $pdo->prepare("INSERT INTO coupons (code, type, value, is_active, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([strtoupper(trim($code)), $type, $value, $isActive ? 1 : 0, $expiresAt]);
    return (int) $pdo->lastInsertId();
}

function updateCoupon(PDO $pdo, int $id, string $code, string $type, float $value, bool $isActive, ?string $expiresAt = null): void {
    $stmt = $pdo->prepare("UPDATE coupons SET code = ?, type = ?, value = ?, is_active = ?, expires_at = ? WHERE id = ?");
    $stmt->execute([strtoupper(trim($code)), $type, $value, $isActive ? 1 : 0, $expiresAt, $id]);
}

function deleteCoupon(PDO $pdo, int $id): bool {
    $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

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
