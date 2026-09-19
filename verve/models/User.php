<?php
/**
 * USER MODEL
 * ---------------------------------------------------------
 * Every database query about user accounts lives here.
 * Passwords are never handled as plain text outside this file
 * except at the moment they're first typed into the form.
 * ---------------------------------------------------------
 */

function findUserByEmail(PDO $pdo, string $email): ?array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function findUserById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function updateUserProfile(PDO $pdo, int $userId, string $fullName, string $email, string $phone): void {
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
    $stmt->execute([$fullName, $email, $phone !== '' ? $phone : null, $userId]);
}

function emailBelongsToAnotherUser(PDO $pdo, string $email, int $userId): bool {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    return (bool) $stmt->fetch();
}

// Creates a new customer account. Returns the new user's ID.
// password_hash() is PHP's built-in, well-tested way to scramble
// a password so not even we can read it back, only verify a guess.
function createUser(PDO $pdo, string $fullName, string $email, string $plainPassword): int {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("
        INSERT INTO users (full_name, email, password_hash, role)
        VALUES (?, ?, ?, 'customer')
    ");
    $stmt->execute([$fullName, $email, $hash]);
    return (int) $pdo->lastInsertId();
}

// Checks a login attempt. Returns the user row if the email exists
// AND the password matches, otherwise null — deliberately vague
// about WHICH part was wrong, so we don't tell attackers whether
// an email is registered.
function attemptLogin(PDO $pdo, string $email, string $plainPassword): ?array {
    $user = findUserByEmail($pdo, $email);
    if (!$user) return null;
    if (!password_verify($plainPassword, $user['password_hash'])) return null;
    return $user;
}

/**
 * ---------------------------------------------------------
 * ADMIN FUNCTIONS
 * ---------------------------------------------------------
 */

// Every customer account, with their order count and total spend
// attached — so the admin list is useful at a glance.
function getAllCustomersWithStats(PDO $pdo, string $search = ''): array {
    $sql = "
        SELECT u.*,
               COUNT(o.id) AS order_count,
               COALESCE(SUM(CASE WHEN o.status != 'cancelled' THEN o.total ELSE 0 END), 0) AS total_spent
        FROM users u
        LEFT JOIN orders o ON o.user_id = u.id
        WHERE u.role = 'customer'
    ";
    $params = [];
    if ($search !== '') {
        $sql .= " AND (u.full_name LIKE ? OR u.email LIKE ?)";
        $params[] = '%' . $search . '%';
        $params[] = '%' . $search . '%';
    }
    $sql .= " GROUP BY u.id ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Promotes/demotes an account between customer and admin. The
// action file calling this is responsible for blocking an admin
// from changing their OWN role.
function setUserRole(PDO $pdo, int $userId, string $role): void {
    if (!in_array($role, ['customer', 'admin'], true)) return;
    $user = findUserById($pdo, $userId);
    if (!$user) throw new RuntimeException('Account not found.');
    changeStaffAccess($pdo, $user['email'], $role === 'admin' ? 'owner' : 'customer');
}
