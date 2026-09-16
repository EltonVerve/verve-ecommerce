<?php
/**
 * LOGOUT ACTION
 * ---------------------------------------------------------
 * No form needed — just link to it directly, e.g.
 * <a href="actions/logout.php">Log out</a>
 * ---------------------------------------------------------
 */
require_once __DIR__ . '/../config/config.php';

revokeCustomerLogin($pdo);
$_SESSION = [];
session_destroy();
session_start();

header('Location: ' . BASE_URL . '/index.php');
exit;
