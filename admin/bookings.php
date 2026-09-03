<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');
    if ($action === 'cancel') {
        cancel_booking((int) ($_POST['id'] ?? 0));
        flash_set('ok', 'Turno cancelado. El horario vuelve a estar disponible.');
    }
    redirect('/admin/bookings.php');
}

$turnos = get_bookings_admin();

admin_page_start('Turnos', 'bookings');
?>

<p>
    Horario semanal: <a href="<?= e(url('/admin/booking-schedule.php')) ?>">configurar</a> ·
    Bloqueos puntuales: <a href="<?= e(url('/admin/booking-blocks.php')) ?>">configurar</a> ·
    Reservar/bloquear rápido: <a href="<?= e(url('/admin/booking-calendar.php')) ?>">calendario</a>
</p>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Fecha</th><th>Horario</th><th>Nombre</th><th>Contacto</th><th>Servicio</th><th>Motivo</th><th>Pago</th><th></th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$turnos): ?>
            <tr><td colspan="8">Todavía no hay turnos reservados.</td></tr>
        <?php endif; ?>
        <?php foreach ($turnos as $turno): ?>
            <tr>
                <td><?= e(date('d/m/Y', strtotime($turno['date']))) ?></td>
                <td><?= e(substr($turno['start_time'], 0, 5)) ?> a <?= e(substr($turno['end_time'], 0, 5)) ?></td>
                <td><?= e($turno['name']) ?><?php if ($turno['source'] === 'admin'): ?> <span class="badge badge--off">Manual</span><?php endif; ?></td>
                <td>
                    <?= e($turno['email'] ?: '—') ?>
                    <?php if ($turno['whatsapp']): ?><br><?= e($turno['whatsapp']) ?><?php endif; ?>
                </td>
                <td><?= e($turno['service_name'] ?? '—') ?></td>
                <td><?= e($turno['motivo'] ?: '—') ?></td>
                <td><span class="badge <?= $turno['payment_status'] === 'pagado' ? 'badge--on' : 'badge--off' ?>"><?= e(ucfirst($turno['payment_status'])) ?></span></td>
                <td class="actions">
                    <form method="post" onsubmit="return confirm('¿Cancelar este turno? El horario vuelve a estar disponible.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="id" value="<?= (int) $turno['id'] ?>">
                        <button type="submit">Cancelar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php admin_page_end(); ?>
