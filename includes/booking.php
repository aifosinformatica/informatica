<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Lógica del sistema de turnos: disponibilidad (horario semanal + bloqueos)
 * y reservas. Ver doc del plan para el modelo completo.
 */

function turno_duracion_min(): int
{
    return (int) setting('turno_duracion_min', '120');
}

function turno_dias_visibles(): int
{
    return (int) setting('turno_dias_visibles', '14');
}

/** Todo el horario semanal cargado (para el CRUD de admin/booking-schedule.php). */
function get_weekly_schedule(): array
{
    return db()->query('SELECT * FROM booking_schedule ORDER BY weekday, start_time')->fetchAll();
}

/** Rangos activos de un día de semana (0=domingo .. 6=sábado, como date('w')). */
function get_schedule_for_weekday(int $weekday): array
{
    $stmt = db()->prepare(
        'SELECT start_time, end_time FROM booking_schedule WHERE weekday = :weekday AND active = 1 ORDER BY start_time'
    );
    $stmt->execute(['weekday' => $weekday]);
    return $stmt->fetchAll();
}

/** Todos los bloqueos cargados (para el CRUD de admin/booking-blocks.php). */
function get_all_blocks(): array
{
    return db()->query('SELECT * FROM booking_blocks ORDER BY date, start_time')->fetchAll();
}

/** Bloqueos que aplican a una fecha puntual. */
function get_blocks_for_date(string $date): array
{
    $stmt = db()->prepare('SELECT id, start_time, end_time, reason FROM booking_blocks WHERE date = :date ORDER BY start_time');
    $stmt->execute(['date' => $date]);
    return $stmt->fetchAll();
}

/** Nombre del servicio elegido (o null si no existe/ya no es visible) para el <select> de /turnos. */
function get_service_name_for_booking(int $serviceId): ?string
{
    $stmt = db()->prepare('SELECT name FROM services WHERE id = :id AND visible = 1 LIMIT 1');
    $stmt->execute(['id' => $serviceId]);
    $name = $stmt->fetchColumn();
    return $name !== false ? (string) $name : null;
}

/** Reservas ya confirmadas ese día (para no ofrecer un horario ocupado). */
function get_bookings_for_date(string $date): array
{
    $stmt = db()->prepare('SELECT start_time, end_time FROM bookings WHERE date = :date');
    $stmt->execute(['date' => $date]);
    return $stmt->fetchAll();
}

/** Igual que get_bookings_for_date(), pero con los datos completos (para mostrar en el calendario de admin). */
function get_bookings_admin_for_date(string $date): array
{
    $stmt = db()->prepare(
        'SELECT bookings.*, services.name AS service_name FROM bookings
         LEFT JOIN services ON services.id = bookings.service_id
         WHERE date = :date ORDER BY start_time'
    );
    $stmt->execute(['date' => $date]);
    return $stmt->fetchAll();
}

/** Minutos desde medianoche de un "HH:MM" o "HH:MM:SS". */
function time_to_minutes(string $time): int
{
    [$h, $m] = array_map('intval', explode(':', $time));
    return $h * 60 + $m;
}

function minutes_to_time(int $minutes): string
{
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

/**
 * Horarios de inicio disponibles para reservar en una fecha dada, como "HH:MM".
 * Resta del horario semanal los bloqueos y las reservas ya existentes por
 * SOLAPAMIENTO de rango (no por igualdad exacta de horario) — así, si la
 * duración del turno cambia con el tiempo, un turno viejo de 120 min sigue
 * bloqueando bien la grilla nueva de 60 min que se solape con él.
 */
function generate_slots_for_date(string $date): array
{
    $weekday = (int) date('w', strtotime($date));
    $ranges = get_schedule_for_weekday($weekday);
    if (!$ranges) {
        return [];
    }

    $duration = turno_duracion_min();
    if ($duration <= 0) {
        return [];
    }

    $blocks = get_blocks_for_date($date);
    $busy = get_bookings_for_date($date);

    $isToday = $date === date('Y-m-d');
    $nowMinutes = $isToday ? ((int) date('H') * 60 + (int) date('i')) : -1;

    $slots = [];
    foreach ($ranges as $range) {
        $start = time_to_minutes($range['start_time']);
        $end = time_to_minutes($range['end_time']);

        for ($slotStart = $start; $slotStart + $duration <= $end; $slotStart += $duration) {
            $slotEnd = $slotStart + $duration;

            if ($isToday && $slotStart <= $nowMinutes) {
                continue;
            }

            $overlaps = false;
            foreach ($blocks as $block) {
                // Bloqueo de día completo: start_time/end_time NULL.
                $blockStart = $block['start_time'] !== null ? time_to_minutes($block['start_time']) : 0;
                $blockEnd = $block['end_time'] !== null ? time_to_minutes($block['end_time']) : 24 * 60;
                if ($slotStart < $blockEnd && $blockStart < $slotEnd) {
                    $overlaps = true;
                    break;
                }
            }
            if (!$overlaps) {
                foreach ($busy as $booking) {
                    $bookingStart = time_to_minutes($booking['start_time']);
                    $bookingEnd = time_to_minutes($booking['end_time']);
                    if ($slotStart < $bookingEnd && $bookingStart < $slotEnd) {
                        $overlaps = true;
                        break;
                    }
                }
            }

            if (!$overlaps) {
                $slots[] = minutes_to_time($slotStart);
            }
        }
    }

    return $slots;
}

/**
 * Igual que generate_slots_for_date(), pero para el calendario rápido de
 * admin (/admin/booking-calendar.php): en vez de devolver solo los horarios
 * libres, devuelve TODA la grilla del día con el estado de cada uno ('free',
 * 'booked' u 'blocked') y el detalle (el turno o el bloqueo correspondiente),
 * para poder reservar o bloquear con un click, o ver quién ocupa cada uno.
 *
 * @return array<int, array{start:string, end:string, status:string, detail:?array}>
 */
function get_admin_slots_for_date(string $date): array
{
    $weekday = (int) date('w', strtotime($date));
    $ranges = get_schedule_for_weekday($weekday);
    if (!$ranges) {
        return [];
    }

    $duration = turno_duracion_min();
    if ($duration <= 0) {
        return [];
    }

    $blocks = get_blocks_for_date($date);
    $bookings = get_bookings_admin_for_date($date);

    $isToday = $date === date('Y-m-d');
    $nowMinutes = $isToday ? ((int) date('H') * 60 + (int) date('i')) : -1;

    $slots = [];
    foreach ($ranges as $range) {
        $start = time_to_minutes($range['start_time']);
        $end = time_to_minutes($range['end_time']);

        for ($slotStart = $start; $slotStart + $duration <= $end; $slotStart += $duration) {
            $slotEnd = $slotStart + $duration;
            if ($isToday && $slotStart <= $nowMinutes) {
                continue;
            }

            $status = 'free';
            $detail = null;

            foreach ($blocks as $block) {
                $blockStart = $block['start_time'] !== null ? time_to_minutes($block['start_time']) : 0;
                $blockEnd = $block['end_time'] !== null ? time_to_minutes($block['end_time']) : 24 * 60;
                if ($slotStart < $blockEnd && $blockStart < $slotEnd) {
                    $status = 'blocked';
                    $detail = $block;
                    break;
                }
            }

            if ($status === 'free') {
                foreach ($bookings as $booking) {
                    $bookingStart = time_to_minutes($booking['start_time']);
                    $bookingEnd = time_to_minutes($booking['end_time']);
                    if ($slotStart < $bookingEnd && $bookingStart < $slotEnd) {
                        $status = 'booked';
                        $detail = $booking;
                        break;
                    }
                }
            }

            $slots[] = [
                'start' => minutes_to_time($slotStart),
                'end' => minutes_to_time($slotEnd),
                'status' => $status,
                'detail' => $detail,
            ];
        }
    }

    return $slots;
}

/** @return array<string, array<int, string>> fecha ("Y-m-d") => horarios disponibles */
function get_available_slots(): array
{
    $days = turno_dias_visibles();
    $result = [];
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("+{$i} day"));
        $slots = generate_slots_for_date($date);
        if ($slots) {
            $result[$date] = $slots;
        }
    }
    return $result;
}

/**
 * Crea una reserva, revalidando disponibilidad en el momento (evita que dos
 * personas reserven el mismo horario si llegaron a la página en simultáneo).
 * La unicidad real la garantiza además la clave uk_bookings_slot en la base.
 *
 * @param array{google_sub:string,name:string,email:string,whatsapp:?string,motivo:?string,date:string,start_time:string,service_id?:mixed} $data
 * @return array{ok:bool,error?:string,booking?:array}
 */
function create_booking(array $data): array
{
    $date = $data['date'];
    $start = substr($data['start_time'], 0, 5);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $start)) {
        return ['ok' => false, 'error' => 'Horario inválido, elegí de nuevo.'];
    }

    $available = generate_slots_for_date($date);
    if (!in_array($start, $available, true)) {
        return ['ok' => false, 'error' => 'Ese horario ya no está disponible, elegí otro.'];
    }

    // El servicio es opcional (el cliente puede no saber todavía qué necesita).
    // Si mandan un id que no corresponde a un servicio visible (formulario
    // manipulado, servicio borrado entre que cargó la página y envió el form),
    // se guarda como "sin elegir" en lugar de rechazar todo el turno.
    $serviceId = null;
    $serviceName = null;
    $rawServiceId = (int) ($data['service_id'] ?? 0);
    if ($rawServiceId > 0) {
        $serviceName = get_service_name_for_booking($rawServiceId);
        if ($serviceName !== null) {
            $serviceId = $rawServiceId;
        }
    }

    $endMinutes = time_to_minutes($start) + turno_duracion_min();
    $booking = [
        'date' => $date,
        'start_time' => $start . ':00',
        'end_time' => minutes_to_time($endMinutes) . ':00',
        'google_sub' => $data['google_sub'],
        'source' => 'cliente',
        'name' => trim($data['name']),
        'email' => trim($data['email']),
        'whatsapp' => trim((string) ($data['whatsapp'] ?? '')) ?: null,
        'service_id' => $serviceId,
        'motivo' => trim((string) ($data['motivo'] ?? '')) ?: null,
    ];

    try {
        $stmt = db()->prepare(
            'INSERT INTO bookings (date, start_time, end_time, google_sub, source, name, email, whatsapp, service_id, motivo)
             VALUES (:date, :start_time, :end_time, :google_sub, :source, :name, :email, :whatsapp, :service_id, :motivo)'
        );
        $stmt->execute($booking);
        $booking['id'] = (int) db()->lastInsertId();
        $booking['payment_status'] = 'simulado';
        $booking['service_name'] = $serviceName;
        return ['ok' => true, 'booking' => $booking];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Ese horario ya fue tomado por otra persona, elegí otro.'];
        }
        throw $e;
    }
}

/**
 * Turno cargado a mano por el admin (consulta que llegó por teléfono,
 * WhatsApp o mail): no requiere login de Google, así que google_sub queda
 * NULL y el turno no aparece en "Mis turnos" del cliente ni se autocancela
 * — se administra solo desde el panel (ver /admin/booking-calendar.php).
 *
 * @param array{name:string,whatsapp:string,email?:string,motivo?:string,service_id?:mixed,date:string,start_time:string} $data
 * @return array{ok:bool,error?:string,booking?:array}
 */
function create_admin_booking(array $data): array
{
    $date = $data['date'] ?? '';
    $start = substr((string) ($data['start_time'] ?? ''), 0, 5);

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !preg_match('/^\d{2}:\d{2}$/', $start)) {
        return ['ok' => false, 'error' => 'Horario inválido, elegí de nuevo.'];
    }

    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        return ['ok' => false, 'error' => 'Falta el nombre del cliente.'];
    }
    $whatsapp = trim((string) ($data['whatsapp'] ?? ''));
    if ($whatsapp === '') {
        return ['ok' => false, 'error' => 'Falta el teléfono del cliente.'];
    }

    $available = generate_slots_for_date($date);
    if (!in_array($start, $available, true)) {
        return ['ok' => false, 'error' => 'Ese horario ya no está disponible, elegí otro.'];
    }

    $serviceId = null;
    $serviceName = null;
    $rawServiceId = (int) ($data['service_id'] ?? 0);
    if ($rawServiceId > 0) {
        $serviceName = get_service_name_for_booking($rawServiceId);
        if ($serviceName !== null) {
            $serviceId = $rawServiceId;
        }
    }

    $endMinutes = time_to_minutes($start) + turno_duracion_min();
    $booking = [
        'date' => $date,
        'start_time' => $start . ':00',
        'end_time' => minutes_to_time($endMinutes) . ':00',
        'google_sub' => null,
        'source' => 'admin',
        'name' => $name,
        'email' => trim((string) ($data['email'] ?? '')) ?: null,
        'whatsapp' => $whatsapp,
        'service_id' => $serviceId,
        'motivo' => trim((string) ($data['motivo'] ?? '')) ?: null,
    ];

    try {
        $stmt = db()->prepare(
            'INSERT INTO bookings (date, start_time, end_time, google_sub, source, name, email, whatsapp, service_id, motivo)
             VALUES (:date, :start_time, :end_time, :google_sub, :source, :name, :email, :whatsapp, :service_id, :motivo)'
        );
        $stmt->execute($booking);
        $booking['id'] = (int) db()->lastInsertId();
        $booking['payment_status'] = 'simulado';
        $booking['service_name'] = $serviceName;
        return ['ok' => true, 'booking' => $booking];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Ese horario ya fue tomado, elegí otro.'];
        }
        throw $e;
    }
}

/** Turnos para el panel de admin. */
function get_bookings_admin(bool $onlyUpcoming = true): array
{
    $sql = 'SELECT bookings.*, services.name AS service_name FROM bookings
            LEFT JOIN services ON services.id = bookings.service_id';
    if ($onlyUpcoming) {
        $sql .= ' WHERE date >= CURDATE()';
    }
    $sql .= ' ORDER BY date, start_time';
    return db()->query($sql)->fetchAll();
}

/** Turnos a futuro del cliente logueado (para "Mis turnos" en /turnos.php). */
function get_own_bookings(string $googleSub): array
{
    $stmt = db()->prepare(
        'SELECT bookings.*, services.name AS service_name FROM bookings
         LEFT JOIN services ON services.id = bookings.service_id
         WHERE google_sub = :sub AND date >= CURDATE() ORDER BY date, start_time'
    );
    $stmt->execute(['sub' => $googleSub]);
    return $stmt->fetchAll();
}

/** Cancelación desde el panel de admin: sin restricciones. */
function cancel_booking(int $id): void
{
    db()->prepare('DELETE FROM bookings WHERE id = :id')->execute(['id' => $id]);
}

/**
 * Cancelación por el propio cliente: solo puede cancelar lo suyo.
 * Devuelve false si el id no existía o no era de ese google_sub (por ejemplo,
 * si alguien manipuló el id en el formulario).
 */
function cancel_own_booking(int $id, string $googleSub): bool
{
    $stmt = db()->prepare('DELETE FROM bookings WHERE id = :id AND google_sub = :sub');
    $stmt->execute(['id' => $id, 'sub' => $googleSub]);
    return $stmt->rowCount() > 0;
}

/** Usada por admin/settings.php para bloquear el cambio de duración de turno mientras haya reservas a futuro. */
function has_future_bookings(): bool
{
    return (int) db()->query('SELECT COUNT(*) FROM bookings WHERE date >= CURDATE()')->fetchColumn() > 0;
}
