<?php
/**
 * ADDRESS MODEL
 * ---------------------------------------------------------
 * Every database query about shipping addresses lives here.
 * ---------------------------------------------------------
 */

// The address a customer used most recently / marked as default.
// Used to pre-fill the checkout form for returning customers.
function getDefaultAddress(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("
        SELECT * FROM addresses WHERE user_id = ?
        ORDER BY is_default DESC, id DESC LIMIT 1
    ");
    $stmt->execute([$userId]);
    $address = $stmt->fetch();
    return $address ?: null;
}

// Saves a new address for a user and returns its ID. Called from
// the checkout action every time someone places an order.
function saveAddress(PDO $pdo, int $userId, array $data): int {
    $stmt = $pdo->prepare("
        INSERT INTO addresses (user_id, full_name, line1, line2, city, state, postal_code, country, phone, is_default)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");
    $stmt->execute([
        $userId,
        $data['full_name'],
        $data['line1'],
        $data['line2'] ?: null,
        $data['city'],
        $data['state'] ?: null,
        $data['postal_code'] ?: null,
        $data['country'],
        $data['phone'] ?: null,
    ]);
    return (int) $pdo->lastInsertId();
}

function getAddressById(PDO $pdo, int $addressId, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$addressId, $userId]);
    $address = $stmt->fetch();
    return $address ?: null;
}
