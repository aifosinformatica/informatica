<?php

declare(strict_types=1);

/**
 * Carga config/.env y expone la configuración de la app.
 * No se conecta a la base de datos acá (ver includes/db.php).
 */

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function env_load(string $path): array
{
    $vars = [];
    if (!is_file($path)) {
        return $vars;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $vars[trim($key)] = trim($value);
    }
    return $vars;
}

$GLOBALS['__env'] = env_load(__DIR__ . '/.env');

function env(string $key, ?string $default = null): ?string
{
    return $GLOBALS['__env'][$key] ?? $default;
}

if (env('DB_NAME') === null) {
    http_response_code(500);
    die(
        'Falta config/.env. Copiá config/.env.example como config/.env y completá los datos de conexión '
        . '(ver doc/Idea.md > INSTALACIÓN).'
    );
}

define('APP_ENV', env('APP_ENV', 'production'));
define('APP_URL', rtrim((string) env('APP_URL', ''), '/'));
define('APP_TIMEZONE', env('APP_TIMEZONE', 'America/Argentina/Buenos_Aires'));

/**
 * Subcarpeta donde vive el sitio, sacada directamente de APP_URL — no hace
 * falta configurarla aparte. Si APP_URL es "https://tudominio.com.ar", el
 * sitio vive en la raíz y BASE_PATH queda vacío (comportamiento de siempre).
 * Si APP_URL es "https://demos.aifosinformatica.com.ar/informatica" (una demo
 * temporal en subcarpeta), BASE_PATH queda en "/informatica" y todas las URLs
 * internas (assets, links, redirecciones) lo anteponen automáticamente
 * mediante la función url() de includes/helpers.php.
 */
define('BASE_PATH', rtrim((string) (parse_url(APP_URL, PHP_URL_PATH) ?? ''), '/'));

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASS', env('DB_PASS', ''));

// Sistema de turnos: login con Google (ver includes/google_oauth.php) y
// envío de mails por SMTP (ver includes/mailer.php).
define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int) env('SMTP_PORT', '587'));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_FROM_NAME', env('SMTP_FROM_NAME', 'Servicio Técnico'));

define('INSTALL_LOCK_FILE', __DIR__ . '/installed.lock');
define('IS_LOCAL', APP_ENV === 'local' || APP_ENV === 'development');

date_default_timezone_set(APP_TIMEZONE);

if (IS_LOCAL) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

// El arranque de sesión NO va acá: el sitio público y el panel de admin usan
// realms de sesión distintos (ver includes/session.php y admin/includes/session.php).

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
