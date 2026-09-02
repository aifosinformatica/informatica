<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/includes/layout.php';

$dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $weekday = (int) ($_POST['weekday'] ?? 0);
        $start = (string) ($_POST['start_time'] ?? '');
        $end = (string) ($_POST['end_time'] ?? '');

        if ($start === '' || $end === '' || $start >= $end) {
            flash_set('error', 'El horario "desde" tiene que ser anterior al "hasta".');
        } else {
            db()->prepare(
                'INSERT INTO booking_schedule (weekday, start_time, end_time) VALUES (:weekday, :start, :end)'
            )->execute(['weekday' => $weekday, 'start' => $start, 'end' => $end]);
            flash_set('ok', 'Rango horario agregado.');
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE booking_schedule SET active = 1 - active WHERE id = :id')->execute(['id' => $id]);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM booking_schedule WHERE id = :id')->execute(['id' => $id]);
        flash_set('ok', 'Rango horario eliminado.');
    }
    redirect('/admin/booking-schedule.php');
}

$horarios = get_weekly_schedule();

admin_page_start('Horario de turnos', 'booking-schedule');
?>

<p>Horario semanal recurrente que se usa para generar los horarios disponibles en <?= e(url('/turnos')) ?>. Podés cargar varios rangos por día (por ejemplo, mañana y tarde con un corte al mediodía). Los bloqueos puntuales (feriados, imprevistos) se cargan aparte en <a href="<?= e(url('/admin/booking-blocks.php')) ?>">Bloqueos</a>.</p>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Día</th><th>Desde</th><th>Hasta</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php if (!$horarios): ?>
            <tr><td colspan="5">Todavía no hay ningún horario cargado — no se va a poder reservar ningún turno hasta que cargues al menos uno.</td></tr>
        <?php endif; ?>
        <?php foreach ($horarios as $rango): ?>
            <tr>
                <td><?= e($dias[(int) $rango['weekday']]) ?></td>
                <td><?= e(substr($rango['start_time'], 0, 5)) ?></td>
                <td><?= e(substr($rango['end_time'], 0, 5)) ?></td>
                <td><span class="badge <?= $rango['active'] ? 'badge--on' : 'badge--off' ?>"><?= $rango['active'] ? 'Activo' : 'Inactivo' ?></span></td>
                <td class="actions">
                    <form method="post" onsubmit="return confirm('¿Activar/desactivar este rango?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $rango['id'] ?>">
                        <button type="submit">Activar/desactivar</button>
                    </form>
                    <form method="post" onsubmit="return confirm('¿Eliminar este rango horario?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $rango['id'] ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2>Agregar rango horario</h2>
<form method="post" class="form form--wide">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-row form-row--2">
        <label>Día de la semana
            <select name="weekday">
                <?php foreach ($dias as $num => $nombre): ?>
                    <option value="<?= $num ?>"><?= e($nombre) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>
    <div class="form-row form-row--2">
        <label>Desde <input type="time" name="start_time" required></label>
        <label>Hasta <input type="time" name="end_time" required></label>
    </div>
    <button type="submit" class="btn btn--primary">Agregar</button>
</form>

<?php admin_page_end(); ?>
