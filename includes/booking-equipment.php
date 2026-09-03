<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

const BOOKING_PHOTO_MAX_COUNT = 5;
const BOOKING_PHOTO_MAX_BYTES = 8 * 1024 * 1024;

/** Guarda la ficha opcional y sus fotos. Devuelve advertencias no fatales. */
function save_booking_equipment(int $bookingId, array $data, array $files = []): array
{
    $allowed = [
        'equipment_type' => ['notebook', 'escritorio', 'otro'],
        'operating_system' => ['windows', 'macos', 'linux', 'otro', 'no_sabe'],
        'disk_type' => ['hdd', 'ssd_sata', 'ssd_nvme', 'otro', 'no_sabe'],
    ];
    $values = [];
    foreach ($allowed as $field => $options) {
        $value = (string) ($data[$field] ?? '');
        $values[$field] = in_array($value, $options, true) ? $value : null;
    }
    foreach (['ram_type' => 40, 'ram_amount' => 40, 'cpu' => 160, 'brand' => 100, 'model' => 160] as $field => $max) {
        $value = trim((string) ($data[$field] ?? ''));
        $values[$field] = $value !== '' ? mb_substr($value, 0, $max) : null;
    }

    $photos = normalize_booking_photos($files['equipment_photos'] ?? []);
    if (!array_filter($values, static fn ($value) => $value !== null) && !$photos && empty($data['existing_equipment_id'])) {
        return [];
    }

    $pdo = db();
    $pdo->beginTransaction();
    $storedPaths = [];
    $warnings = [];
    try {
        $bookingStmt = $pdo->prepare('SELECT google_sub, source FROM bookings WHERE id = :id');
        $bookingStmt->execute(['id' => $bookingId]);
        $booking = $bookingStmt->fetch();
        if (!$booking) {
            throw new RuntimeException('Turno inexistente.');
        }

        $equipmentId = (int) ($data['existing_equipment_id'] ?? 0);
        if ($equipmentId > 0) {
            $existingStmt = $pdo->prepare(
                'SELECT id FROM customer_equipment
                 WHERE id = :id AND (:is_admin = 1 OR owner_google_sub = :owner)'
            );
            $existingStmt->execute([
                'id' => $equipmentId,
                'is_admin' => $booking['source'] === 'admin' ? 1 : 0,
                'owner' => $booking['google_sub'],
            ]);
            $equipmentId = (int) ($existingStmt->fetchColumn() ?: 0);
        }

        if ($equipmentId === 0) {
            $stmt = $pdo->prepare(
            'INSERT INTO customer_equipment
                (owner_google_sub, equipment_type, operating_system, disk_type, ram_type, ram_amount, cpu, brand, model)
             VALUES
                (:owner_google_sub, :equipment_type, :operating_system, :disk_type, :ram_type, :ram_amount, :cpu, :brand, :model)'
            );
            $stmt->execute(['owner_google_sub' => $booking['google_sub']] + $values);
            $equipmentId = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('UPDATE bookings SET equipment_id = :equipment_id WHERE id = :booking_id')
            ->execute(['equipment_id' => $equipmentId, 'booking_id' => $bookingId]);

        $directory = APP_ROOT . '/storage/booking-photos';
        if ($photos && !is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo crear el directorio de fotos.');
        }

        $photoStmt = $pdo->prepare(
            'INSERT INTO booking_photos (booking_id, stored_name, original_name, mime_type, file_size)
             VALUES (:booking_id, :stored_name, :original_name, :mime_type, :file_size)'
        );
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        foreach (array_slice($photos, 0, BOOKING_PHOTO_MAX_COUNT) as $photo) {
            if ($photo['error'] !== UPLOAD_ERR_OK) {
                $warnings[] = 'Una foto no pudo subirse.';
                continue;
            }
            if ($photo['size'] <= 0 || $photo['size'] > BOOKING_PHOTO_MAX_BYTES) {
                $warnings[] = 'Una foto supera el máximo de 8 MB.';
                continue;
            }
            $mime = $finfo->file($photo['tmp_name']) ?: '';
            if (!isset($mimeMap[$mime])) {
                $warnings[] = 'Una foto tiene un formato no admitido.';
                continue;
            }
            $storedName = bin2hex(random_bytes(20)) . '.' . $mimeMap[$mime];
            $path = $directory . '/' . $storedName;
            if (!move_uploaded_file($photo['tmp_name'], $path)) {
                $warnings[] = 'Una foto no pudo guardarse.';
                continue;
            }
            $storedPaths[] = $path;
            $photoStmt->execute([
                'booking_id' => $bookingId,
                'stored_name' => $storedName,
                'original_name' => mb_substr(basename((string) $photo['name']), 0, 255),
                'mime_type' => $mime,
                'file_size' => (int) $photo['size'],
            ]);
        }
        if (count($photos) > BOOKING_PHOTO_MAX_COUNT) {
            $warnings[] = 'Se guardaron solamente las primeras 5 fotos.';
        }
        $pdo->commit();
        return array_values(array_unique($warnings));
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        foreach ($storedPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        return ['El turno se reservó, pero no se pudo guardar la ficha del equipo.'];
    }
}

/** Convierte la estructura múltiple de $_FILES en una lista simple. */
function normalize_booking_photos(array $upload): array
{
    if (!isset($upload['name'])) {
        return [];
    }
    if (!is_array($upload['name'])) {
        return [$upload];
    }
    $result = [];
    foreach ($upload['name'] as $index => $name) {
        if (($upload['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $result[] = [
            'name' => $name,
            'type' => $upload['type'][$index] ?? '',
            'tmp_name' => $upload['tmp_name'][$index] ?? '',
            'error' => $upload['error'][$index] ?? UPLOAD_ERR_NO_FILE,
            'size' => (int) ($upload['size'][$index] ?? 0),
        ];
    }
    return $result;
}

function get_equipment_for_bookings(array $bookingIds): array
{
    $ids = array_values(array_filter(array_map('intval', $bookingIds)));
    if (!$ids) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT equipment.*, bookings.id AS booking_id
         FROM bookings INNER JOIN customer_equipment equipment ON equipment.id = bookings.equipment_id
         WHERE bookings.id IN ($placeholders)"
    );
    $stmt->execute($ids);
    $equipment = [];
    foreach ($stmt->fetchAll() as $row) {
        $row['photos'] = [];
        $equipment[(int) $row['booking_id']] = $row;
    }
    if (!$equipment) {
        return [];
    }
    $photoStmt = db()->prepare("SELECT * FROM booking_photos WHERE booking_id IN ($placeholders) ORDER BY id");
    $photoStmt->execute($ids);
    foreach ($photoStmt->fetchAll() as $photo) {
        $equipment[(int) $photo['booking_id']]['photos'][] = $photo;
    }
    return $equipment;
}

/** Elimina del disco las fotos antes de que el borrado en cascada quite sus filas. */
function delete_booking_equipment_files(int $bookingId): void
{
    $stmt = db()->prepare(
        'SELECT stored_name FROM booking_photos WHERE booking_id = :booking_id'
    );
    $stmt->execute(['booking_id' => $bookingId]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $storedName) {
        $path = APP_ROOT . '/storage/booking-photos/' . basename((string) $storedName);
        if (is_file($path)) {
            unlink($path);
        }
    }
}

/** Equipos que el cliente puede reutilizar en un nuevo turno. */
function get_customer_equipment_options(?string $googleSub = null, bool $all = false): array
{
    if ($all) {
        return db()->query(
            "SELECT equipment.*, MAX(bookings.name) AS owner_name
             FROM customer_equipment equipment
             LEFT JOIN bookings ON bookings.equipment_id = equipment.id
             GROUP BY equipment.id ORDER BY equipment.updated_at DESC"
        )->fetchAll();
    }
    if (!$googleSub) {
        return [];
    }
    $stmt = db()->prepare('SELECT * FROM customer_equipment WHERE owner_google_sub = :owner ORDER BY updated_at DESC');
    $stmt->execute(['owner' => $googleSub]);
    return $stmt->fetchAll();
}

function equipment_option_label(array $equipment): string
{
    $parts = array_filter([$equipment['brand'] ?? null, $equipment['model'] ?? null]);
    $label = $parts ? implode(' ', $parts) : ucfirst((string) ($equipment['equipment_type'] ?: 'Equipo'));
    if (!empty($equipment['owner_name'])) {
        $label .= ' · ' . $equipment['owner_name'];
    }
    return $label . ' (#' . (int) $equipment['id'] . ')';
}
