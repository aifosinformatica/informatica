<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/booking.php';
require_once __DIR__ . '/includes/booking-equipment.php';
require_once __DIR__ . '/includes/mailer.php';
require_once __DIR__ . '/includes/google_oauth.php';
require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/layout.php';

$bookingUser = $_SESSION['booking_user'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesión expirada, volvé a intentar.');
        redirect('/turnos');
    }

    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'cancel_own') {
        if ($bookingUser === null) {
            redirect('/turnos');
        }
        $id = (int) ($_POST['id'] ?? 0);
        if (cancel_own_booking($id, $bookingUser['sub'])) {
            flash_set('ok', 'Turno cancelado. El horario vuelve a estar disponible para otra persona.');
        } else {
            flash_set('error', 'No pudimos cancelar ese turno.');
        }
        redirect('/turnos');
    }

    // action === 'create'
    if ($bookingUser === null) {
        flash_set('error', 'Iniciá sesión con Google para reservar un turno.');
        redirect('/turnos');
    }

    $result = create_booking([
        'google_sub' => $bookingUser['sub'],
        'name' => $bookingUser['name'],
        'email' => $bookingUser['email'],
        'whatsapp' => (string) ($_POST['whatsapp'] ?? ''),
        'service_id' => $_POST['service_id'] ?? null,
        'motivo' => (string) ($_POST['motivo'] ?? ''),
        'date' => (string) ($_POST['date'] ?? ''),
        'start_time' => (string) ($_POST['start_time'] ?? ''),
    ]);

    if (!$result['ok']) {
        flash_set('error', $result['error']);
        redirect('/turnos');
    }

    $equipmentWarnings = save_booking_equipment((int) $result['booking']['id'], $_POST, $_FILES);

    send_booking_admin_notification($result['booking']);
    send_booking_client_confirmation($result['booking']);

    flash_set('ok', 'Turno reservado para el ' . booking_datetime_label($result['booking']) . '. Te mandamos la confirmación por mail.');
    foreach ($equipmentWarnings as $warning) {
        flash_set('error', $warning);
    }
    redirect('/turnos');
}

$slotsByDate = $bookingUser ? get_available_slots() : [];
$ownBookings = $bookingUser ? get_own_bookings($bookingUser['sub']) : [];
$serviceGroups = $bookingUser ? get_services_for_booking_select() : [];
$equipmentOptions = $bookingUser ? get_customer_equipment_options($bookingUser['sub']) : [];
// Si se llega desde la ficha de un servicio (ej. /turnos?service=reparacion-pc-cambio-de-bateria),
// ese servicio queda preseleccionado en el combo.
$preselectedServiceId = $bookingUser ? get_bookable_service_id_by_slug((string) ($_GET['service'] ?? '')) : null;

page_start(
    'Pedí tu turno online',
    'Reservá un turno para reparación de PC o notebook en Caseros. Elegís el horario, confirmás con tu cuenta de Google y listo.',
    '/turnos'
);
?>

<section class="section">
    <div class="container">
        <div class="page-intro">
            <span class="eyebrow"><span class="dot"></span>Turnos</span>
            <h1>Reservá tu turno</h1>
            <p>Elegí un horario disponible y confirmá con tu cuenta de Google. Atendemos <strong>únicamente con turno previo</strong>, no es un local a la calle.</p>
        </div>

        <?php if ($bookingUser === null): ?>
            <a href="<?= e(url('/turnos-login.php')) ?>" class="btn btn--primary">Iniciar sesión con Google</a>
            <p style="margin-top:14px;color:var(--text-muted);">¿Preferís coordinar por WhatsApp? <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Escribinos</a>.</p>
        <?php else: ?>
            <p>Hola, <strong><?= e($bookingUser['name']) ?></strong> · <a href="<?= e(url('/turnos-logout.php')) ?>">Cerrar sesión</a></p>

            <?php if ($ownBookings): ?>
                <h2 style="margin-top:32px;">Mis turnos</h2>
                <div class="grid grid--3">
                    <?php foreach ($ownBookings as $own): ?>
                        <div class="card reveal">
                            <p><strong><?= e(booking_datetime_label($own)) ?></strong></p>
                            <?php if (!empty($own['service_name'])): ?><p><?= e($own['service_name']) ?></p><?php endif; ?>
                            <?php if ($own['motivo']): ?><p style="color:var(--text-muted);"><?= e($own['motivo']) ?></p><?php endif; ?>
                            <form method="post" onsubmit="return confirm('¿Cancelar este turno? El horario queda libre para otra persona.');">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="cancel_own">
                                <input type="hidden" name="id" value="<?= (int) $own['id'] ?>">
                                <button type="submit" class="btn btn--ghost btn--sm">Cancelar turno</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h2 style="margin-top:32px;">Horarios disponibles</h2>
            <?php if (!$slotsByDate): ?>
                <p>No hay horarios disponibles por ahora. <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">Escribinos por WhatsApp</a> y lo coordinamos.</p>
            <?php else: ?>
                <form method="post" enctype="multipart/form-data" id="turnoForm" class="form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="date" id="turnoDate" value="">
                    <input type="hidden" name="start_time" id="turnoStartTime" value="">

                    <?php foreach ($slotsByDate as $date => $times): ?>
                        <div class="slot-day reveal">
                            <h4><?= e(format_date_es($date)) ?></h4>
                            <div class="slot-grid">
                                <?php foreach ($times as $time): ?>
                                    <button type="button" class="slot-btn" data-date="<?= e($date) ?>" data-time="<?= e($time) ?>"><?= e($time) ?> hs</button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div id="turnoConfirm" class="card" hidden>
                        <p>Turno elegido: <strong id="turnoResumen"></strong></p>
                        <label>WhatsApp <input type="tel" name="whatsapp" required placeholder="11 5555-5555"></label>
                        <?php if ($serviceGroups): ?>
                            <label>¿Ya sabés qué necesitás? (opcional)
                                <select name="service_id">
                                    <option value="">No estoy seguro, que me asesoren</option>
                                    <?php foreach ($serviceGroups as $groupLabel => $items): ?>
                                        <optgroup label="<?= e($groupLabel) ?>">
                                            <?php foreach ($items as $item): ?>
                                                <option value="<?= (int) $item['id'] ?>" <?= $preselectedServiceId === $item['id'] ? 'selected' : '' ?>><?= e($item['label']) ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                        <?php endif; ?>
                        <label>Motivo de la consulta (opcional) <textarea name="motivo" rows="3" placeholder="Ej: no enciende, pantalla rota, lento..."></textarea></label>
                        <?php require __DIR__ . '/includes/booking-equipment-fields.php'; ?>
                        <button type="submit" class="btn btn--primary">Confirmar turno</button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php page_end(); ?>
