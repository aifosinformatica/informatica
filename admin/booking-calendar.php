<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/booking.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/services.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'quick_block') {
        $date = (string) ($_POST['date'] ?? '');
        $start = (string) ($_POST['start_time'] ?? '');
        $end = (string) ($_POST['end_time'] ?? '');
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && preg_match('/^\d{2}:\d{2}$/', $start) && preg_match('/^\d{2}:\d{2}$/', $end)) {
            db()->prepare(
                'INSERT INTO booking_blocks (date, start_time, end_time, reason) VALUES (:date, :start, :end, :reason)'
            )->execute(['date' => $date, 'start' => $start, 'end' => $end, 'reason' => 'Bloqueado desde el calendario']);
            flash_set('ok', "Horario de {$start} bloqueado.");
        } else {
            flash_set('error', 'No se pudo bloquear ese horario.');
        }
    } elseif ($action === 'unblock') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM booking_blocks WHERE id = :id')->execute(['id' => $id]);
        flash_set('ok', 'Bloqueo levantado.');
    } elseif ($action === 'cancel') {
        $id = (int) ($_POST['id'] ?? 0);
        cancel_booking($id);
        flash_set('ok', 'Turno cancelado. El horario vuelve a estar disponible.');
    } elseif ($action === 'admin_book') {
        $result = create_admin_booking([
            'date' => (string) ($_POST['date'] ?? ''),
            'start_time' => (string) ($_POST['start_time'] ?? ''),
            'name' => (string) ($_POST['name'] ?? ''),
            'whatsapp' => (string) ($_POST['whatsapp'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'service_id' => $_POST['service_id'] ?? null,
            'motivo' => (string) ($_POST['motivo'] ?? ''),
        ]);
        if ($result['ok']) {
            // Si el admin cargó un mail, le mandamos igual la confirmación automática;
            // si no (típico de una consulta que llegó solo por teléfono), no hay a quién mandarla.
            if (!empty($result['booking']['email'])) {
                send_booking_client_confirmation($result['booking']);
            }
            flash_set('ok', 'Turno reservado para ' . $result['booking']['name'] . ' el ' . booking_datetime_label($result['booking']) . '.');
        } else {
            flash_set('error', $result['error']);
        }
    }
    redirect('/admin/booking-calendar.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : ''));
}

$days = (int) ($_GET['days'] ?? turno_dias_visibles());
if ($days < 1) {
    $days = 1;
}
if ($days > 60) {
    $days = 60;
}

$serviceGroups = get_services_for_booking_select();

$dayGrid = [];
for ($i = 0; $i < $days; $i++) {
    $date = date('Y-m-d', strtotime("+{$i} day"));
    $slots = get_admin_slots_for_date($date);
    if ($slots) {
        $dayGrid[$date] = $slots;
    }
}

admin_page_start('Calendario rápido', 'booking-calendar');
?>

<p>
    Vista rápida de <?= e($days) ?> días para bloquear un horario puntual (1 click) o cargar un turno de una consulta que
    llegó por teléfono, WhatsApp o mail. Para feriados o vacaciones largas usá <a href="<?= e(url('/admin/booking-blocks.php')) ?>">Bloqueos</a>.
</p>

<p class="cal-range">
    Ver:
    <?php foreach ([7, 14, 30] as $opt): ?>
        <a href="<?= e(url('/admin/booking-calendar.php')) ?>?days=<?= $opt ?>" class="<?= $days === $opt ? 'is-active' : '' ?>"><?= $opt ?> días</a>
    <?php endforeach; ?>
</p>

<?php if (!$dayGrid): ?>
    <p>No hay horario semanal configurado. <a href="<?= e(url('/admin/booking-schedule.php')) ?>">Configurar horario</a>.</p>
<?php endif; ?>

<div class="cal-grid">
    <?php foreach ($dayGrid as $date => $slots): ?>
        <div class="cal-day">
            <h3><?= e(format_date_es($date)) ?></h3>
            <div class="cal-day__slots">
                <?php foreach ($slots as $slot): ?>
                    <?php if ($slot['status'] === 'free'): ?>
                        <div class="cal-slot cal-slot--free">
                            <span class="cal-slot__time"><?= e($slot['start']) ?>–<?= e($slot['end']) ?></span>
                            <div class="cal-slot__actions">
                                <button type="button" class="cal-slot__book-btn"
                                    data-date="<?= e($date) ?>" data-start="<?= e($slot['start']) ?>"
                                    data-label="<?= e(format_date_es($date) . ' a las ' . $slot['start'] . ' hs') ?>">Reservar</button>
                                <form method="post">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="quick_block">
                                    <input type="hidden" name="date" value="<?= e($date) ?>">
                                    <input type="hidden" name="start_time" value="<?= e($slot['start']) ?>">
                                    <input type="hidden" name="end_time" value="<?= e($slot['end']) ?>">
                                    <button type="submit" class="cal-slot__block-btn" title="Bloquear este horario">🔒</button>
                                </form>
                            </div>
                        </div>
                    <?php elseif ($slot['status'] === 'booked'): ?>
                        <div class="cal-slot cal-slot--booked">
                            <span class="cal-slot__time"><?= e($slot['start']) ?>–<?= e($slot['end']) ?></span>
                            <span class="cal-slot__label">
                                <?= e($slot['detail']['name']) ?><?= $slot['detail']['source'] === 'admin' ? ' · manual' : '' ?>
                                <?php if ($slot['detail']['whatsapp']): ?><br><?= e($slot['detail']['whatsapp']) ?><?php endif; ?>
                                <?php if ($slot['detail']['service_name']): ?><br><?= e($slot['detail']['service_name']) ?><?php endif; ?>
                            </span>
                            <form method="post" onsubmit="return confirm('¿Cancelar este turno? El horario vuelve a estar disponible.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel">
                                <input type="hidden" name="id" value="<?= (int) $slot['detail']['id'] ?>">
                                <button type="submit" class="cal-slot__cancel-btn">Cancelar</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="cal-slot cal-slot--blocked">
                            <span class="cal-slot__time"><?= e($slot['start']) ?>–<?= e($slot['end']) ?></span>
                            <span class="cal-slot__label"><?= e($slot['detail']['reason'] ?: 'Bloqueado') ?></span>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="unblock">
                                <input type="hidden" name="id" value="<?= (int) $slot['detail']['id'] ?>">
                                <button type="submit" class="cal-slot__unblock-btn">Desbloquear</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<dialog id="adminBookModal" class="cal-modal">
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_book">
        <input type="hidden" name="date" id="bookDate">
        <input type="hidden" name="start_time" id="bookStart">
        <h2>Reservar turno</h2>
        <p><strong id="bookSummary"></strong></p>
        <label>Nombre <input type="text" name="name" required></label>
        <label>Teléfono <input type="tel" name="whatsapp" required></label>
        <label>Email (opcional) <input type="email" name="email"></label>
        <?php if ($serviceGroups): ?>
            <label>Servicio (opcional)
                <select name="service_id">
                    <option value="">No especificado</option>
                    <?php foreach ($serviceGroups as $groupLabel => $items): ?>
                        <optgroup label="<?= e($groupLabel) ?>">
                            <?php foreach ($items as $item): ?>
                                <option value="<?= (int) $item['id'] ?>"><?= e($item['label']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label>Motivo (opcional) <textarea name="motivo" rows="2"></textarea></label>
        <div class="cal-modal__actions">
            <button type="button" class="btn btn--ghost" id="adminBookCancel">Cancelar</button>
            <button type="submit" class="btn btn--primary">Reservar turno</button>
        </div>
    </form>
</dialog>

<script>
(function () {
  "use strict";
  var modal = document.getElementById("adminBookModal");
  if (!modal) return;
  var dateInput = document.getElementById("bookDate");
  var startInput = document.getElementById("bookStart");
  var summary = document.getElementById("bookSummary");
  var cancelBtn = document.getElementById("adminBookCancel");

  document.querySelectorAll(".cal-slot__book-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      dateInput.value = btn.getAttribute("data-date");
      startInput.value = btn.getAttribute("data-start");
      summary.textContent = btn.getAttribute("data-label");
      modal.showModal();
    });
  });
  if (cancelBtn) {
    cancelBtn.addEventListener("click", function () { modal.close(); });
  }
})();
</script>

<?php admin_page_end(); ?>
