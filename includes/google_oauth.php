<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Login de clientes con Google para el sistema de turnos (ver includes/booking.php).
 * Implementado a mano (Authorization Code flow, por HTTP directo) porque el
 * sitio no usa Composer/vendor — nada de esto depende de la librería oficial
 * de Google.
 */

const GOOGLE_OAUTH_SCOPE = 'openid email profile';
const GOOGLE_AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';
const GOOGLE_TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
const GOOGLE_USERINFO_ENDPOINT = 'https://www.googleapis.com/oauth2/v3/userinfo';

function google_redirect_uri(): string
{
    return absolute_url('/turnos-callback.php');
}

/** Arma la URL a la que hay que mandar al usuario para que inicie sesión con Google. */
function google_auth_url(string $state): string
{
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => google_redirect_uri(),
        'response_type' => 'code',
        'scope' => GOOGLE_OAUTH_SCOPE,
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account',
    ];

    return GOOGLE_AUTH_ENDPOINT . '?' . http_build_query($params);
}

/**
 * Llamada POST/GET genérica, usada tanto para el intercambio de código como
 * para pedir los datos del usuario. Devuelve el body decodeado como array, o
 * null si algo falló (red, timeout, respuesta no-2xx, JSON inválido).
 *
 * Igual que includes/price.php con la cotización del dólar: prefiere cURL,
 * pero cae a file_get_contents() con stream context si el hosting no tiene
 * la extensión curl habilitada.
 */
function google_http_request(string $url, ?array $post = null, ?string $bearerToken = null): ?array
{
    $headers = ['Accept: application/json'];
    if ($bearerToken !== null) {
        $headers[] = 'Authorization: Bearer ' . $bearerToken;
    }

    $body = function_exists('curl_init')
        ? google_http_curl($url, $post, $headers)
        : google_http_streams($url, $post, $headers);

    if ($body === null) {
        return null;
    }

    $decoded = json_decode($body, true);
    return is_array($decoded) ? $decoded : null;
}

/** @param string[] $headers */
function google_http_curl(string $url, ?array $post, array $headers): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $headers,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false || $curlError !== '') {
        error_log('google_oauth: error de red (curl) — ' . $curlError);
        return null;
    }
    if ($status < 200 || $status >= 300) {
        error_log('google_oauth: respuesta HTTP ' . $status . ' — ' . $body);
        return null;
    }

    return (string) $body;
}

/** @param string[] $headers */
function google_http_streams(string $url, ?array $post, array $headers): ?string
{
    if (!ini_get('allow_url_fopen')) {
        error_log('google_oauth: ni curl ni allow_url_fopen están disponibles, no se puede llamar a Google.');
        return null;
    }

    $options = ['timeout' => 10, 'ignore_errors' => true, 'header' => implode("\r\n", $headers)];
    if ($post !== null) {
        $options['method'] = 'POST';
        $options['content'] = http_build_query($post);
        $options['header'] .= "\r\nContent-Type: application/x-www-form-urlencoded";
    }

    $ctx = stream_context_create(['http' => $options, 'https' => $options]);
    $body = @file_get_contents($url, false, $ctx);
    if ($body === false) {
        error_log('google_oauth: error de red (streams) llamando a ' . $url);
        return null;
    }

    $status = 0;
    if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $status = (int) $m[1];
    }
    if ($status < 200 || $status >= 300) {
        error_log('google_oauth: respuesta HTTP ' . $status . ' — ' . $body);
        return null;
    }

    return $body;
}

/** Intercambia el "code" que manda Google en el callback por un access_token. */
function google_exchange_code(string $code): ?array
{
    return google_http_request(GOOGLE_TOKEN_ENDPOINT, [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => google_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]);
}

/** @return array{sub:string,email:string,name:string}|null */
function google_fetch_userinfo(string $accessToken): ?array
{
    $data = google_http_request(GOOGLE_USERINFO_ENDPOINT, null, $accessToken);
    if ($data === null || empty($data['sub']) || empty($data['email'])) {
        return null;
    }

    return [
        'sub' => (string) $data['sub'],
        'email' => (string) $data['email'],
        'name' => (string) ($data['name'] ?? $data['email']),
    ];
}
