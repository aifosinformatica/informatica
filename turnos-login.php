<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/google_oauth.php';

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

redirect(google_auth_url($state));
