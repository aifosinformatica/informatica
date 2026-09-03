<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../includes/booking-equipment.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/includes/layout.php';

$id = (int) ($_GET['id'] ?? 0);
$booking = get_booking_admin_by_id($id);
if (!$booking) {
    http_response_code(404);
    admin_page_start('Turno no encontrado', 'bookings');
    echo '<p>El turno solicitado no existe.</p>';
    admin_page_end();
    exit;
}
$equipment = get_equipment_for_bookings([$id])[$id] ?? null;
$labels = [
    'equipment_type' => ['notebook' => 'Notebook', 'escritorio' => 'PC de escritorio', 'otro' => 'Otro'],
    'operating_system' => ['windows' => 'Windows', 'macos' => 'macOS', 'linux' => 'Linux', 'otro' => 'Otro', 'no_sabe' => 'No sabe'],
    'disk_type' => ['hdd' => 'HDD', 'ssd_sata' => 'SSD SATA', 'ssd_nvme' => 'SSD NVMe', 'otro' => 'Otro', 'no_sabe' => 'No sabe'],
];

admin_page_start('Detalle del turno', 'bookings');
?>
<p><a href="<?= e(url('/admin/bookings.php')) ?>">← Volver a turnos</a></p>

<section class="card booking-detail">
    <h2><?= e($booking['name']) ?></h2>
    <dl>
        <div><dt>Fecha</dt><dd><?= e(booking_datetime_label($booking)) ?></dd></div>
        <div><dt>Contacto</dt><dd><?= e($booking['email'] ?: '—') ?><?= $booking['whatsapp'] ? '<br>' . e($booking['whatsapp']) : '' ?></dd></div>
        <div><dt>Servicio</dt><dd><?= e($booking['service_name'] ?? '—') ?></dd></div>
        <div><dt>Motivo</dt><dd><?= e($booking['motivo'] ?: '—') ?></dd></div>
    </dl>
</section>

<section class="card booking-detail">
    <h2>Equipo</h2>
    <?php if (!$equipment): ?>
        <p>No se cargaron datos del equipo.</p>
    <?php else: ?>
        <dl>
            <div><dt>Tipo</dt><dd><?= e($labels['equipment_type'][$equipment['equipment_type']] ?? '—') ?></dd></div>
            <div><dt>Sistema</dt><dd><?= e($labels['operating_system'][$equipment['operating_system']] ?? '—') ?></dd></div>
            <div><dt>Disco</dt><dd><?= e($labels['disk_type'][$equipment['disk_type']] ?? '—') ?></dd></div>
            <div><dt>RAM</dt><dd><?= e(trim(($equipment['ram_amount'] ?? '') . ' ' . ($equipment['ram_type'] ?? '')) ?: '—') ?></dd></div>
            <div><dt>CPU</dt><dd><?= e($equipment['cpu'] ?: '—') ?></dd></div>
            <div><dt>Marca y modelo</dt><dd><?= e(trim(($equipment['brand'] ?? '') . ' ' . ($equipment['model'] ?? '')) ?: '—') ?></dd></div>
        </dl>
        <?php if ($equipment['photos']): ?>
            <h3>Fotos</h3>
            <div class="equipment-photos">
                <?php foreach ($equipment['photos'] as $photo): ?>
                    <a href="<?= e(url('/admin/booking-photo.php?id=' . (int) $photo['id'])) ?>" target="_blank" rel="noopener">
                        <img src="<?= e(url('/admin/booking-photo.php?id=' . (int) $photo['id'])) ?>" alt="<?= e($photo['original_name']) ?>" loading="lazy">
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php admin_page_end(); ?>

