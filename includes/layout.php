<?php

declare(strict_types=1);

require_once __DIR__ . '/whatsapp.php';

function seo_head(string $title, string $description, string $canonicalPath = ''): void
{
    $siteName = setting('nombre_comercial', 'Servicio Técnico');
    $fullTitle = $title . ' | ' . $siteName;
    $canonical = APP_URL . $canonicalPath;
    // Mientras el sitio viva en una subcarpeta temporal (BASE_PATH no vacío,
    // ej. la demo en /informatica) se bloquea la indexación: es una URL de
    // paso, no la definitiva, y no queremos que Google la indexe por error.
    $robots = BASE_PATH !== '' ? 'noindex, nofollow' : 'index, follow';
    ?>
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta name="robots" content="<?= e($robots) ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:locale" content="es_AR">
    <?php
}

function json_ld(array $data): void
{
    echo '<script type="application/ld+json">'
        . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        . '</script>';
}

/** Bloque LocalBusiness reutilizable, para incluir una vez por página relevante. */
function json_ld_local_business(): void
{
    json_ld([
        '@context' => 'https://schema.org',
        '@type' => 'ComputerRepair',
        'name' => setting('nombre_comercial', ''),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => setting('direccion', ''),
            'addressLocality' => 'Caseros',
            'addressRegion' => 'Buenos Aires',
            'addressCountry' => 'AR',
        ],
        'telephone' => whatsapp_display(),
        'openingHoursSpecification' => [
            '@type' => 'OpeningHoursSpecification',
            'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
            'opens' => '08:00',
            'closes' => '20:00',
        ],
        'url' => APP_URL . '/',
    ]);
}

function page_start(string $title, string $description, string $activePath = ''): void
{
    ?><!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php seo_head($title, $description, $activePath); ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Unbounded:wght@600;700;800&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
<link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Ir al contenido</a>
<?php render_header($activePath); ?>
<main id="main">
<?php foreach (flash_get() as $flash): ?>
    <div class="container">
        <p class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></p>
    </div>
<?php endforeach; ?>
<?php
}

function page_end(): void
{
    ?></main>
<?php render_footer(); ?>
<script src="<?= e(url('/assets/js/main.js')) ?>" defer></script>
</body>
</html>
<?php
}

function render_header(string $activePath = ''): void
{
    $nav = [
        '/' => 'Inicio',
        '/reparacion-pc' => 'Reparación PC',
        '/desarrollo-web' => 'Desarrollo web',
        '/servicio' => 'Cómo trabajamos',
        '/contacto' => 'Contacto',
    ];
    ?>
    <header class="site-header">
        <div class="container site-header__row">
            <a href="<?= e(url('/')) ?>" class="brand">
                <span class="brand__mark" aria-hidden="true">ST</span>
                <span class="brand__text">
                    <strong><?= e(setting('nombre_comercial', '')) ?></strong>
                    <span><?= e(setting('direccion', '') ? 'Caseros · El Palomar' : '') ?></span>
                </span>
            </a>
            <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav" id="navToggle">
                <span></span><span></span><span></span>
                <span class="sr-only">Menú</span>
            </button>
            <nav class="site-nav" id="site-nav">
                <?php foreach ($nav as $href => $label): ?>
                    <a href="<?= e(url($href)) ?>" class="<?= $activePath === $href ? 'is-active' : '' ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
                <span class="nav__status" id="navStatus"><span class="led" aria-hidden="true"></span><span id="navStatusText">Consultando horario…</span></span>
                <a href="<?= e(wa_link()) ?>" class="btn btn--primary btn--sm" target="_blank" rel="noopener">WhatsApp</a>
            </nav>
        </div>
    </header>
    <?php
}

function render_footer(): void
{
    ?>
    <footer class="site-footer">
        <div class="container">
            <div class="site-footer__grid">
                <div>
                    <h4><?= e(setting('nombre_comercial', '')) ?></h4>
                    <p><?= e(setting('direccion', '')) ?></p>
                    <p><?= e(setting('horario', '')) ?></p>
                    <p>Trabajos habituales en <?= e(setting('tiempo_estimado', '24/48 hs')) ?>.</p>
                </div>
                <div>
                    <h4>Contacto</h4>
                    <p><a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">WhatsApp</a></p>
                    <p><a href="tel:<?= e(whatsapp_number()) ?>">Llamar</a></p>
                    <?php if (setting('instagram')): ?>
                        <p><a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">Instagram</a></p>
                    <?php endif; ?>
                </div>
                <div>
                    <h4>Sitio</h4>
                    <p><a href="<?= e(url('/politica-de-privacidad')) ?>">Política de privacidad</a></p>
                    <p><a href="<?= e(url('/servicio')) ?>">Cómo trabajamos</a></p>
                    <p><a href="<?= e(url('/contacto')) ?>">Contacto</a></p>
                </div>
            </div>
            <div class="site-footer__legal">
                <span>© <?= date('Y') ?> <?= e(setting('nombre_comercial', '')) ?></span>
                <span id="footerStatus">Consultando horario…</span>
            </div>
        </div>
    </footer>
    <div class="mobile-cta-bar">
        <a href="<?= e(wa_link()) ?>" class="btn btn--primary" target="_blank" rel="noopener">WhatsApp</a>
        <a href="tel:<?= e(whatsapp_number()) ?>" class="btn btn--ghost">Llamar</a>
    </div>
    <?php
}
