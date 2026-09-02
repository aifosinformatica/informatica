<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/google_oauth.php';

$code = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');
$expectedState = $_SESSION['oauth_state'] ?? null;
unset($_SESSION['oauth_state']);

if ($code === '' || !is_string($expectedState) || $expectedState === '' || !hash_equals($expectedState, $state)) {
    flash_set('error', 'No se pudo iniciar sesión con Google, intentá de nuevo.');
    redirect('/turnos');
}

$tokenResponse = google_exchange_code($code);
$accessToken = $tokenResponse['access_token'] ?? null;
if (!is_string($accessToken)) {
    flash_set('error', 'No se pudo iniciar sesión con Google, intentá de nuevo.');
    redirect('/turnos');
}

$userinfo = google_fetch_userinfo($accessToken);
if ($userinfo === null) {
    flash_set('error', 'No se pudo obtener los datos de tu cuenta de Google, intentá de nuevo.');
    redirect('/turnos');
}

$_SESSION['booking_user'] = $userinfo;
redirect('/turnos');
