<?php
/**
 * PASSWORD RESET MODEL
 * ---------------------------------------------------------
 * Handles the "forgot password" token lifecycle: create one,
 * check if one is still valid, and clean it up after use.
 * ---------------------------------------------------------
 */

// Creates a fresh reset token for a user. Any of their older
// tokens are deleted first, so only the newest link ever works.
function createResetToken(PDO $pdo, int $userId): string {
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?");
    $stmt->execute([$userId]);

    $token = bin2hex(random_bytes(32)); // 64 random hex characters — unguessable
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $token, $expiresAt]);

    return $token;
}

// Looks up a token and returns the associated user IF the token
// exists AND hasn't expired yet.
function findValidReset(PDO $pdo, string $token): ?array {
    $stmt = $pdo->prepare("
        SELECT pr.*, u.email, u.full_name
        FROM password_resets pr
        JOIN users u ON u.id = pr.user_id
        WHERE pr.token = ? AND pr.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    return $reset ?: null;
}

function deleteResetToken(PDO $pdo, string $token): void {
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
}

function updateUserPassword(PDO $pdo, int $userId, string $newPlainPassword): void {
    $hash = password_hash($newPlainPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$hash, $userId]);
}
