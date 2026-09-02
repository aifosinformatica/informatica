<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

unset($_SESSION['booking_user']);

redirect('/turnos');
