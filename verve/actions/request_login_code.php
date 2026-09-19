<?php
require_once __DIR__ . '/../config/config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/pages/login.php'); exit; }
verifyCsrf();
$email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    setFlash('error', 'Please enter a valid email address.');
    header('Location: ' . BASE_URL . '/pages/login.php'); exit;
}
if (!customerCodeMailReady()) {
    $_SESSION['login_prefill_email'] = $email;
    header('Location: ' . BASE_URL . '/pages/login.php?method=password'); exit;
}
limitAuthRequests($pdo, 'email-login-send', $email);
if (isset($_SESSION['login_code_sent_at']) && time() - (int) $_SESSION['login_code_sent_at'] < 60) {
    setFlash('error', 'Please wait one minute before requesting another code.');
    header('Location: ' . BASE_URL . '/pages/verify_login.php'); exit;
}
if (!empty($_SESSION['login_challenge'])) {
    $pdo->prepare('DELETE FROM customer_login_codes WHERE challenge = ?')->execute([$_SESSION['login_challenge']]);
}
$user = findUserByEmail($pdo, $email);
[$challenge, $code] = createCustomerLoginCode($pdo, $user);
$_SESSION['login_challenge'] = $challenge;
$_SESSION['login_email'] = $email;
$_SESSION['login_code_sent_at'] = time();
if ($user && $user['role'] === 'customer') {
    $sent = @mail($email, SITE_NAME . ' sign-in code', "Your sign-in code is: " . $code . "\n\nIt expires in 10 minutes. Do not share it. If you did not request it, ignore this email.", 'From: ' . getenv('VERVE_MAIL_FROM'));
    if (!$sent) {
        $pdo->prepare('DELETE FROM customer_login_codes WHERE challenge = ?')->execute([$challenge]);
        error_log('Customer sign-in email delivery failed.');
    }
}
header('Location: ' . BASE_URL . '/pages/verify_login.php');
exit;
