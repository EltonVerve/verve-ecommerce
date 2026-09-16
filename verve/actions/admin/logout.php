<?php
require_once __DIR__ . '/../../config/config.php';

$_SESSION = [];
session_destroy();
session_start();

header('Location: ' . BASE_URL . '/pages/admin/login.php');
exit;
