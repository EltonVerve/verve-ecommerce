<?php
/**
 * WISHLIST MODEL
 * ---------------------------------------------------------
 * A simple "save for later" list, tied to a logged-in
 * customer's account (guests are asked to log in before they
 * can save an item — no session-based wishlist, unlike the
 * cart, since a wishlist is meant to persist long-term).
 * ---------------------------------------------------------
 */

function wishlistCardContains(PDO $pdo, int $userId, int $productId): bool {
    static $ids = [];
    if (!isset($ids[$userId])) {
        $stmt = $pdo->prepare('SELECT product_id FROM wishlist_items WHERE user_id = ?');
        $stmt->execute([$userId]);
        $ids[$userId] = array_fill_keys($stmt->fetchAll(PDO::FETCH_COLUMN), true);
    }
    return isset($ids[$userId][$productId]);
}

function isInWishlist(PDO $pdo, int $userId, int $productId): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM wishlist_items WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    return (bool) $stmt->fetchColumn();
}

function toggleWishlist(PDO $pdo, int $userId, int $productId): bool {
    if (isInWishlist($pdo, $userId, $productId)) {
        $stmt = $pdo->prepare("DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return false; // now removed
    }
    $stmt = $pdo->prepare("INSERT INTO wishlist_items (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$userId, $productId]);
    return true; // now added
}

function getWishlistForUser(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name, c.slug AS category_slug
        FROM wishlist_items w
        JOIN products p ON p.id = w.product_id
        JOIN categories c ON c.id = p.category_id
        WHERE w.user_id = ? AND p.is_active = 1
        ORDER BY w.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}
