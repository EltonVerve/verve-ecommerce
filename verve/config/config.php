<?php
/**
 * SITE-WIDE SETTINGS
 * ---------------------------------------------------------
 * Constants used across the whole site, so if something
 * changes (site name, folder path) we edit it in one place.
 * ---------------------------------------------------------
 */

define('SITE_NAME', 'Verve');
define('SITE_TAGLINE', 'Everyday things, well made.');
define('CURRENCY', 'KES');
define('STORE_CURRENCY_SYMBOL', 'KSh ');

// Change this if your project folder isn't at the site root.
// Do not include a trailing slash.
// e.g. 'http://localhost/verve'
define('BASE_URL', 'http://localhost/verve-ecommerce/verve');

define('FREE_SHIPPING_THRESHOLD', 75.00);
define('FLAT_SHIPPING_FEE', 6.99);

// Every page starts the session here so login/cart state
// works everywhere. session_start() must run before ANY
// HTML is output, which is why it's the very first thing.
if (session_status() === PHP_SESSION_NONE) {
    $adminRequest = strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/') !== false;
    session_name($adminRequest ? 'verve_admin_session' : 'verve_store_session');
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
        'path' => (parse_url(BASE_URL, PHP_URL_PATH) ?: '') . '/',
    ]);
    session_start();
}

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// ---- Auto-load every model file ----
// Instead of writing require_once for Product.php, Category.php,
// Cart.php, etc. on every page, this loop pulls in everything
// inside models/ automatically. Add a new file to that folder
// and it's instantly available everywhere — no extra step.
foreach (glob(__DIR__ . '/../models/*.php') as $modelFile) {
    require_once $modelFile;
}

if (session_name() === 'verve_store_session') {
    restoreCustomerLogin($pdo);
}
