<?php

declare(strict_types=1);

function admin_page_start(string $title, string $active = ''): void
{
    $nav = [
        'index' => ['/admin/', 'Panel'],
        'services' => ['/admin/services.php', 'Servicios'],
        'categories' => ['/admin/categories.php', 'Categorías'],
        'reviews' => ['/admin/reviews.php', 'Reseñas'],
        'contacts' => ['/admin/contacts.php', 'Consultas'],
        'bookings' => ['/admin/bookings.php', 'Turnos'],
        'booking-schedule' => ['/admin/booking-schedule.php', 'Horario de turnos'],
        'booking-blocks' => ['/admin/booking-blocks.php', 'Bloqueos'],
        'settings' => ['/admin/settings.php', 'Configuración'],
        'users' => ['/admin/users.php', 'Usuarios'],
        'sessions' => ['/admin/sessions.php', 'Sesiones'],
        'audit' => ['/admin/audit.php', 'Auditoría'],
        'profile' => ['/admin/profile.php', 'Mi perfil'],
    ];
    ?>
    <!doctype html>
    <html lang="es-AR">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title><?= e($title) ?> · Administración</title>
        <link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
    </head>
    <body class="admin-body">
    <div class="container">
        <header class="admin-header">
            <a href="<?= e(url('/admin/')) ?>" class="brand">Administración</a>
            <nav>
                <?php foreach ($nav as $key => [$href, $label]): ?>
                    <a href="<?= e(url($href)) ?>" class="<?= $active === $key ? 'is-active' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <a href="<?= e(url('/admin/logout.php')) ?>">Salir</a>
            </nav>
        </header>
        <?php foreach (flash_get() as $flash): ?>
            <p class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
        <?php endforeach; ?>
        <h1><?= e($title) ?></h1>
    <?php
}

function admin_page_end(): void
{
    ?>
    </div>
    </body>
    </html>
    <?php
}
