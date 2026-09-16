<?php
/**
 * FORGOT PASSWORD ACTION
 * ---------------------------------------------------------
 * Always shows the same success message whether or not the
 * email is actually registered — this stops someone from using
 * this form to check which emails have accounts on the site.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/forgot_password.php');
    exit;
}

verifyCsrf();

$email = trim(strtolower($_POST['email'] ?? ''));
$user = findUserByEmail($pdo, $email);

if ($user) {
    $token = createResetToken($pdo, $user['id']);
    $resetLink = BASE_URL . '/pages/reset_password.php?token=' . $token;

    // ---- Where real email sending would happen ----
    // e.g. using PHPMailer + SMTP, or an API-based service.
    // For now we log it and flash a link so the flow is fully
    // testable without a mail server configured.
    error_log('[password reset] ' . $email . ' -> ' . $resetLink);
    $_SESSION['demo_reset_link'] = $resetLink;
}

setFlash('success', 'If that email has an account, a reset link has been sent to it.');
header('Location: ' . BASE_URL . '/pages/forgot_password.php');
exit;
