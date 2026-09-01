<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session.php';

start_public_session_if_needed();

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

/**
 * Antepone BASE_PATH a una ruta interna del sitio (una que empieza con "/"),
 * para que assets y links funcionen igual si el sitio vive en la raíz de un
 * dominio o en una subcarpeta (ver BASE_PATH en config/config.php). Usar
 * siempre esta función para armar hrefs/srcs internos, nunca escribir la
 * ruta a mano.
 */
function url(string $path = '/'): string
{
    return BASE_PATH . $path;
}

/**
 * Redirige manteniendo el protocolo y host actuales (no el de APP_URL), para que
 * nunca se pierda una cookie "secure" saltando de https a http (o viceversa) —
 * pasa en entornos como Laragon, que sirven el mismo sitio por los dos protocolos.
 */
function redirect(string $path): never
{
    if (str_starts_with($path, 'http')) {
        header('Location: ' . $path);
        exit;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? parse_url(APP_URL, PHP_URL_HOST);

    header('Location: ' . $scheme . '://' . $host . url($path));
    exit;
}

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** @return array<int, array{type:string, message:string}> */
function flash_get(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Lee un valor de la tabla settings, con caché en memoria por request.
 */
function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        foreach (db()->query('SELECT `key`, `value` FROM settings')->fetchAll() as $row) {
            $cache[$row['key']] = $row['value'];
        }
    }
    return $cache[$key] ?? $default;
}

function slugify(string $text): string
{
    $text = trim($text);
    $translit = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
    $text = $translit !== false ? $translit : $text;
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/** Honeypot anti-spam: si viene completo, el envío es un bot. */
function is_spam_submission(): bool
{
    return !empty($_POST['website']);
}
