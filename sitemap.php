<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';

header('Content-Type: application/xml; charset=utf-8');

$pages = ['/', '/reparacion-pc', '/desarrollo-web', '/servicio', '/contacto', '/politica-de-privacidad'];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as $path) {
    echo '  <url><loc>' . e(APP_URL . $path) . '</loc></url>' . "\n";
}
echo '</urlset>';
