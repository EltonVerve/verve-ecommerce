<?php
/**
 * DATABASE CONNECTION
 * ---------------------------------------------------------
 * Every page that needs to talk to MySQL includes this file.
 * It creates one $pdo object that the rest of the code reuses.
 *
 * We use PDO (PHP Data Objects) with prepared statements —
 * a way of sending queries that keeps user input (like a
 * login form) completely separate from the SQL command
 * itself. That's what stops SQL injection attacks.
 * ---------------------------------------------------------
 */

// ---- Update these 4 lines to match your local setup ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'verve');
define('DB_USER', 'root');
define('DB_PASS', '');        // XAMPP/WAMP default is usually an empty password
// ----------------------------------------------------------

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
