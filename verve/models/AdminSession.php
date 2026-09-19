<?php
function enforceAdminIdleTimeout(int $now): void {
    if (empty($_SESSION['user_id'])) return;
    $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? 0);
    if ($lastActivity <= 0 || $now - $lastActivity >= 1800) {
        $_SESSION = [];
        session_regenerate_id(true);
        return;
    }
    $_SESSION['admin_last_activity'] = $now;
}
