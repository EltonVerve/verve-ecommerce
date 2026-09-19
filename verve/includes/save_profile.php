<?php
// Shared self-service handler. Account identity always comes from the session.
$adminProfile = session_name() === 'verve_admin_session';
$destination = BASE_URL . ($adminProfile ? '/pages/admin/profile.php' : '/pages/edit_account.php');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $destination);
    exit;
}
verifyCsrf();
$text = static fn(string $key): string => is_string($_POST[$key] ?? null) ? $_POST[$key] : '';
$name = trim($text('full_name'));
$email = strtolower(trim($text('email')));
$phone = trim($text('phone'));
$password = $text('new_password');
$userId = (int) $_SESSION['user_id'];
$user = findUserById($pdo, $userId);
limitAuthRequests($pdo, 'profile', (string) $userId);
if (!$user || !password_verify($text('current_password'), $user['password_hash'])) {
    setFlash('error', 'Your current password was incorrect.');
} elseif (mb_strlen($name) < 2 || mb_strlen($name) > 120 || strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($phone) > 30) {
    setFlash('error', 'Enter a name of 2–120 characters, a valid email (up to 150 characters), and a phone number of up to 30 characters.');
} elseif ($password !== '' && (strlen($password) < 12 || strlen($password) > 72 || $password !== $text('password_confirm'))) {
    setFlash('error', 'New passwords must match and contain 12–72 bytes.');
} elseif (emailBelongsToAnotherUser($pdo, $email, $userId)) {
    setFlash('error', 'That email address is already in use.');
} else {
    try {
        $pdo->beginTransaction();
        updateUserProfile($pdo, $userId, $name, $email, $phone);
        if ($password !== '') updateUserPassword($pdo, $userId, $password);
        $pdo->commit();
        $_SESSION['user_name'] = $name;
        if ($password !== '') {
            $fresh = findUserById($pdo, $userId);
            $_SESSION['password_fingerprint'] = hash('sha256', $fresh['password_hash']);
            if (!$adminProfile) rememberCustomer($pdo, $fresh);
        }
        session_regenerate_id(true);
        setFlash('success', 'Your profile has been updated.');
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Profile update failed: ' . $error->getMessage());
        setFlash('error', 'Unable to save your profile. Please try again.');
    }
}
header('Location: ' . $destination);
exit;
