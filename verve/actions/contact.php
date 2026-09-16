<?php
/**
 * CONTACT FORM ACTION
 * ---------------------------------------------------------
 * Validates and stores a contact enquiry, with a light rate
 * limit (by session, then by hashed IP) to deter spam.
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method Not Allowed');
}

verifyCsrf();

$text = static fn(string $key): string => is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';

$enquiryType = $text('enquiry_type');
if (!in_array($enquiryType, getContactEnquiryTypes(), true)) $enquiryType = 'general';
$name = $text('name');
$email = strtolower($text('email'));
$phone = $text('phone');
$orderReference = $text('order_reference');
$message = $text('message');

function contactRedirect(): void {
    header('Location: ' . BASE_URL . '/pages/contact.php#contact-form');
    exit;
}

$errors = [];
if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Please enter your name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) $errors[] = 'Please enter a valid email address.';
if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) $errors[] = 'Please write a message between 10 and 5000 characters.';

if ($errors) {
    setFlash('error', $errors[0]);
    contactRedirect();
}

// Simple session-based rate limit, then a stricter per-IP check.
$now = time();
$windowStart = $now - CONTACT_RATE_LIMIT_WINDOW_SECONDS;
$recent = array_values(array_filter(
    $_SESSION['contact_accepted_timestamps'] ?? [],
    static fn($t) => is_int($t) && $t >= $windowStart
));
if (count($recent) >= CONTACT_RATE_LIMIT_MAX) {
    setFlash('error', 'You have reached the contact form limit. Please try again later.');
    contactRedirect();
}

$ipHash = hashContactIpAddress((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

try {
    $lock = acquireContactRateLimitLock($pdo, $ipHash);
    try {
        if (countRecentContactMessagesByIp($pdo, $ipHash) >= CONTACT_RATE_LIMIT_MAX) {
            setFlash('error', 'You have reached the contact form limit. Please try again later.');
            contactRedirect();
        }
        createContactMessage($pdo, [
            'user_id' => isLoggedIn() ? (int) $_SESSION['user_id'] : null,
            'enquiry_type' => $enquiryType,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'order_reference' => $orderReference,
            'message' => $message,
            'ip_hash' => $ipHash,
        ]);
    } finally {
        releaseContactRateLimitLock($pdo, $lock);
    }
} catch (Throwable $e) {
    error_log('Contact message storage failed: ' . $e->getMessage());
    setFlash('error', 'We could not save your message right now. Please try again later.');
    contactRedirect();
}

$recent[] = $now;
$_SESSION['contact_accepted_timestamps'] = $recent;

setFlash('success', "Thanks — your message has been received. We'll get back to you soon.");
contactRedirect();
