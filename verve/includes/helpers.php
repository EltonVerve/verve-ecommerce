<?php
/**
 * HELPER FUNCTIONS
 * ---------------------------------------------------------
 * Small reusable functions so we're not repeating the same
 * logic (like "is someone logged in?") on every page.
 * ---------------------------------------------------------
 */

// True/false — is someone currently logged in at all?
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

// True/false — is a customer (not admin) currently logged in?
function isCustomerLoggedIn(): bool {
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'customer';
}

// Stop the page and send a guest to the login page.
// Used at the top of pages like "My Account" or "Checkout".
function requireLogin(): void {
    if (!isCustomerLoggedIn()) {
        header('Location: ' . BASE_URL . '/pages/login.php');
        exit;
    }
}
function customerLoginDestination(): string {
    $checkout = !empty($_SESSION['checkout_after_login']);
    unset($_SESSION['checkout_after_login']);
    return BASE_URL . ($checkout ? '/pages/checkout.php' : '/pages/account.php');
}

// Same idea, but for the admin section. Blocks both guests AND
// logged-in customers — only role='admin' accounts get through.
// Every file in pages/admin/ and actions/admin/ starts with this.
function requireAdmin(): void {
    if (!isLoggedIn() || ($_SESSION['user_role'] ?? '') !== 'admin') {
        header('Location: ' . BASE_URL . '/pages/admin/login.php');
        exit;
    }
    global $pdo;
    if (!adminRouteAllowed(adminScope($pdo), basename($_SERVER['SCRIPT_NAME'] ?? ''))) {
        http_response_code(403);
        exit('Your staff account does not have permission to access this section.');
    }
}

// Guests get a cart too — this gives every visitor a unique
// ID (stored in their session) so their cart is theirs alone,
// even before they create an account.
function getGuestSessionId(): string {
    if (!isset($_SESSION['guest_id'])) {
        $_SESSION['guest_id'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['guest_id'];
}

// Escapes text before printing it into HTML — prevents
// XSS attacks (someone typing <script> into a form field).
// Wrap ANY user-supplied or database text with this when
// echoing it into a page.
function h(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Formats a number as a price, e.g. 12 -> KSh 12.00
function money(float $amount): string {
    return STORE_CURRENCY_SYMBOL . number_format($amount, 2);
}

// Turns "Wireless Over-Ear Headphones" into "wireless-over-ear-headphones"
function slugify(string $text): string {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

// Resolves a product/category image to a real file, or a
// clean placeholder if none has been uploaded yet.
function productImageUrl(?string $filename, string $label = 'Product', int $width = 1600): string {
    if ($filename) {
        $variant = 'optimized/' . pathinfo(basename($filename), PATHINFO_FILENAME) . '-' . ($width <= 600 ? '600' : '1600') . '.webp';
        $original = __DIR__ . '/../public/assets/products/' . $filename;
        $optimized = __DIR__ . '/../public/assets/products/' . $variant;
        if (is_file($optimized) && is_file($original) && filemtime($optimized) >= filemtime($original)) {
            return BASE_URL . '/public/assets/products/' . $variant;
        }
        $path = __DIR__ . '/../public/assets/products/' . $filename;
        if (is_file($path)) {
            return BASE_URL . '/public/assets/products/' . rawurlencode($filename);
        }
    }
    $text = rawurlencode($label);
    return "https://placehold.co/600x600/f2f1ee/1c1b1a?text={$text}&font=raleway";
}

// Counts how many items are in the current user's (or guest's) cart.
// Used to show the little number badge on the cart icon.
function getCartCount(PDO $pdo): int {
    if (isCustomerLoggedIn()) {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart_items WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) AS total FROM cart_items WHERE session_id = ?");
        $stmt->execute([getGuestSessionId()]);
    }
    return (int) $stmt->fetch()['total'];
}

// All categories, used in the header nav and shop filters.
function getAllCategories(PDO $pdo): array {
    return $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
}

/**
 * ---------------------------------------------------------
 * CSRF PROTECTION
 * ---------------------------------------------------------
 * CSRF = Cross-Site Request Forgery. Without this, a malicious
 * site could trick a logged-in visitor's browser into silently
 * submitting a form on YOUR site (e.g. "change my email") just
 * by loading a hidden form on their page.
 *
 * The fix: every form on our site includes a random token that
 * only our server knows. When the form is submitted, we check
 * the token matches. A malicious outside site can't know it.
 * ---------------------------------------------------------
 */

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function verifyCsrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (!is_string($submitted) || $submitted === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        die('Security check failed. Please go back and try again.');
    }
}

/**
 * ---------------------------------------------------------
 * FLASH MESSAGES
 * ---------------------------------------------------------
 * A "flash" message survives exactly one redirect, then
 * disappears. Perfect for "Account created!" or "Wrong
 * password" messages after a form submits and redirects.
 * ---------------------------------------------------------
 */

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

// Renders a row of star icons for a rating (1-5). Used on
// product cards and product detail pages.
function renderStars(float $rating): string {
    $rating = max(0, min(5, $rating));
    $full = (int) floor($rating);
    $half = ($rating - $full) >= 0.5 ? 1 : 0;
    $empty = 5 - $full - $half;
    $out = str_repeat('★', $full);
    if ($half) $out .= '½';
    $out .= str_repeat('☆', $empty);
    return $out;
}
