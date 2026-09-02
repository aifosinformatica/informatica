<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $date = (string) ($_POST['date'] ?? '');
        $start = trim((string) ($_POST['start_time'] ?? ''));
        $end = trim((string) ($_POST['end_time'] ?? ''));
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            flash_set('error', 'Elegí una fecha válida.');
        } elseif (($start === '') !== ($end === '')) {
            flash_set('error', 'Completá "desde" y "hasta", o dejá los dos vacíos para bloquear el día completo.');
        } else {
            db()->prepare(
                'INSERT INTO booking_blocks (date, start_time, end_time, reason) VALUES (:date, :start, :end, :reason)'
            )->execute([
                'date' => $date,
                'start' => $start !== '' ? $start : null,
                'end' => $end !== '' ? $end : null,
                'reason' => $reason !== '' ? $reason : null,
            ]);
            flash_set('ok', 'Bloqueo agregado.');
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM booking_blocks WHERE id = :id')->execute(['id' => $id]);
        flash_set('ok', 'Bloqueo eliminado.');
    }
    redirect('/admin/booking-blocks.php');
}

$bloqueos = get_all_blocks();

admin_page_start('Bloqueos', 'booking-blocks');
?>

<p>Bloqueos puntuales sobre el <a href="<?= e(url('/admin/booking-schedule.php')) ?>">horario semanal</a>: feriados, imprevistos, o cualquier horario que quieras sacar de <?= e(url('/turnos')) ?> sin tocar el horario habitual. Dejá "Desde"/"Hasta" vacíos para bloquear el día completo.</p>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Horario</th><th>Motivo</th><th></th></tr></thead>
        <tbody>
        <?php if (!$bloqueos): ?>
            <tr><td colspan="4">No hay bloqueos cargados.</td></tr>
        <?php endif; ?>
        <?php foreach ($bloqueos as $bloqueo): ?>
            <tr>
                <td><?= e(date('d/m/Y', strtotime($bloqueo['date']))) ?></td>
                <td>
                    <?php if ($bloqueo['start_time'] === null): ?>
                        Día completo
                    <?php else: ?>
                        <?= e(substr($bloqueo['start_time'], 0, 5)) ?> a <?= e(substr($bloqueo['end_time'], 0, 5)) ?>
                    <?php endif; ?>
                </td>
                <td><?= e($bloqueo['reason'] ?: '—') ?></td>
                <td class="actions">
                    <form method="post" onsubmit="return confirm('¿Eliminar este bloqueo?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $bloqueo['id'] ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2>Agregar bloqueo</h2>
<form method="post" class="form form--wide">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <label>Fecha <input type="date" name="date" required></label>
    <div class="form-row form-row--2">
        <label>Desde (opcional) <input type="time" name="start_time"></label>
        <label>Hasta (opcional) <input type="time" name="end_time"></label>
    </div>
    <label>Motivo (opcional) <input type="text" name="reason" placeholder="Ej: feriado, viaje, imprevisto"></label>
    <button type="submit" class="btn btn--primary">Agregar bloqueo</button>
</form>

<?php admin_page_end(); ?>
