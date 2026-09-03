<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT stored_name, mime_type FROM booking_photos WHERE id = :id');
$stmt->execute(['id' => $id]);
$photo = $stmt->fetch();
$path = $photo ? APP_ROOT . '/storage/booking-photos/' . basename((string) $photo['stored_name']) : '';

if (!$photo || !is_file($path)) {
    http_response_code(404);
    exit('Foto no encontrada.');
}

header('Content-Type: ' . $photo['mime_type']);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($path);
