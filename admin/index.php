<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/price.php';
require_once __DIR__ . '/includes/layout.php';

$nuevos = (int) db()->query("SELECT COUNT(*) FROM contact_requests WHERE status = 'nuevo'")->fetchColumn();
$totalServicios = (int) db()->query('SELECT COUNT(*) FROM services')->fetchColumn();
$totalResenas = (int) db()->query('SELECT COUNT(*) FROM reviews WHERE visible = 1')->fetchColumn();
$rate = get_exchange_rate();

admin_page_start('Panel', 'index');
?>

<div class="stat-grid">
    <div class="card"><strong><?= $nuevos ?></strong><span>Consultas nuevas</span></div>
    <div class="card"><strong><?= $totalServicios ?></strong><span>Servicios cargados</span></div>
    <div class="card"><strong><?= $totalResenas ?></strong><span>Reseñas visibles</span></div>
    <div class="card">
        <strong><?= $rate ? e(number_format((float) $rate['rate_effective'], 2, ',', '.')) : '—' ?></strong>
        <span>Cotización efectiva (ARS/USD)</span>
    </div>
</div>

<div class="grid grid--3">
    <a class="card" href="<?= e(url('/admin/services.php')) ?>">Editar servicios y precios →</a>
    <a class="card" href="<?= e(url('/admin/contacts.php')) ?>">Ver consultas recibidas →</a>
    <a class="card" href="<?= e(url('/admin/settings.php')) ?>">Configurar dólar, recargo y datos del negocio →</a>
</div>

<?php admin_page_end(); ?>
