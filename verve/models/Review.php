<?php
/**
 * REVIEW MODEL
 * ---------------------------------------------------------
 * Star ratings and comments left by customers on a product.
 * ---------------------------------------------------------
 */

function getReviewsForProduct(PDO $pdo, int $productId): array {
    $stmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY created_at DESC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

function createReview(PDO $pdo, int $productId, ?int $userId, string $authorName, int $rating, string $comment): int {
    $rating = max(1, min(5, $rating));
    $stmt = $pdo->prepare("
        INSERT INTO reviews (product_id, user_id, author_name, rating, comment)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$productId, $userId, $authorName, $rating, $comment !== '' ? $comment : null]);
    return (int) $pdo->lastInsertId();
}

// A quick breakdown of how many 5★, 4★, 3★... reviews a product
// has, used to draw the little rating bar chart on the PDP.
function getRatingBreakdown(PDO $pdo, int $productId): array {
    $stmt = $pdo->prepare("SELECT rating, COUNT(*) AS c FROM reviews WHERE product_id = ? GROUP BY rating");
    $stmt->execute([$productId]);
    $counts = array_fill(1, 5, 0);
    foreach ($stmt->fetchAll() as $row) {
        $counts[(int) $row['rating']] = (int) $row['c'];
    }
    return $counts;
}
