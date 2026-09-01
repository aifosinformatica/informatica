<?php

declare(strict_types=1);

// OJO con el orden: esto tiene que cargar ANTES que includes/helpers.php, porque
// helpers.php arranca la sesión pública apenas nota que no hay ninguna sesión activa.
// Si helpers.php se cargara primero, el panel terminaría usando la sesión pública
// (basada en archivos) en vez de la propia (basada en la base de datos).

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session.php';
require_once __DIR__ . '/DbSessionHandler.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(build_session_cookie_params());
    session_set_save_handler(new DbSessionHandler(), true);
    session_name('admin_sess');
    session_start();
}

require_once __DIR__ . '/../../includes/helpers.php';

function log_login_attempt(?int $adminId, string $usernameAttempted, string $action): void
{
    db()->prepare(
        'INSERT INTO login_audit (admin_id, username_attempted, action, ip, user_agent)
         VALUES (:admin_id, :username, :action, :ip, :ua)'
    )->execute([
        'admin_id' => $adminId,
        'username' => $usernameAttempted,
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
    ]);
}
