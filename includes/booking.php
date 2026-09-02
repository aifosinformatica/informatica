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
    $stmt = db()->prepare('SELECT start_time, end_time FROM booking_blocks WHERE date = :date');
    $stmt->execute(['date' => $date]);
    return $stmt->fetchAll();
}

/** Reservas ya confirmadas ese día (para no ofrecer un horario ocupado). */
function get_bookings_for_date(string $date): array
{
    $stmt = db()->prepare('SELECT start_time, end_time FROM bookings WHERE date = :date');
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
 * @param array{google_sub:string,name:string,email:string,whatsapp:?string,motivo:?string,date:string,start_time:string} $data
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

    $endMinutes = time_to_minutes($start) + turno_duracion_min();
    $booking = [
        'date' => $date,
        'start_time' => $start . ':00',
        'end_time' => minutes_to_time($endMinutes) . ':00',
        'google_sub' => $data['google_sub'],
        'name' => trim($data['name']),
        'email' => trim($data['email']),
        'whatsapp' => trim((string) ($data['whatsapp'] ?? '')) ?: null,
        'motivo' => trim((string) ($data['motivo'] ?? '')) ?: null,
    ];

    try {
        $stmt = db()->prepare(
            'INSERT INTO bookings (date, start_time, end_time, google_sub, name, email, whatsapp, motivo)
             VALUES (:date, :start_time, :end_time, :google_sub, :name, :email, :whatsapp, :motivo)'
        );
        $stmt->execute($booking);
        $booking['id'] = (int) db()->lastInsertId();
        $booking['payment_status'] = 'simulado';
        return ['ok' => true, 'booking' => $booking];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Ese horario ya fue tomado por otra persona, elegí otro.'];
        }
        throw $e;
    }
}

/** Turnos para el panel de admin. */
function get_bookings_admin(bool $onlyUpcoming = true): array
{
    $sql = 'SELECT * FROM bookings';
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
        'SELECT * FROM bookings WHERE google_sub = :sub AND date >= CURDATE() ORDER BY date, start_time'
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
