<?php

declare(strict_types=1);

/**
 * Parámetros de cookie compartidos por la sesión pública y la del panel.
 * Cada realm (público / admin) los usa con su propio nombre de sesión.
 */
function build_session_cookie_params(): array
{
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    return [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ];
}

/**
 * Sesión del sitio público (CSRF, mensajes flash). Basada en archivos, como cualquier
 * sesión PHP normal — liviana, sin ida y vuelta a la base en cada visita.
 * No hace nada si ya hay una sesión activa (por ejemplo, la del panel de admin).
 */
function start_public_session_if_needed(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params(build_session_cookie_params());
        session_name('inf_session');
        session_start();
    }
}
