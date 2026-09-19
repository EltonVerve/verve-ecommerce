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
limitAuthRequests($pdo, 'reset', $email);
$user = findUserByEmail($pdo, $email);

unset($_SESSION['demo_reset_link']);
if ($user && getenv('VERVE_MAIL_FROM') && filter_var(getenv('VERVE_MAIL_FROM'), FILTER_VALIDATE_EMAIL)) {
    $token = createResetToken($pdo, (int) $user['id']);
    $resetLink = BASE_URL . '/pages/reset_password.php?token=' . $token;
    if (!mail($user['email'], 'Reset your Verve password', "Use this link within 30 minutes:\n" . $resetLink, 'From: ' . getenv('VERVE_MAIL_FROM'))) {
        error_log('Password reset email delivery failed.');
    }
}
setFlash('success', 'If that email has an account, a reset link has been sent to it.');
header('Location: ' . BASE_URL . '/pages/forgot_password.php');
exit;
