<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Cliente SMTP mínimo (sin librerías — el sitio no usa Composer/vendor).
 * Pensado para Gmail con "contraseña de aplicación" (SMTP_HOST/PORT/USER/PASS
 * en config/.env), pero sirve con cualquier SMTP que hable STARTTLS + AUTH LOGIN.
 *
 * smtp_send() nunca lanza excepción: cualquier falla queda en error_log() y
 * devuelve false, para que un mail caído no le rompa una reserva de turno al
 * cliente (ver includes/booking.php).
 */

/** @return array{code:int, message:string} */
function smtp_read_response($socket): array
{
    $data = '';
    while (!feof($socket)) {
        $line = fgets($socket, 515);
        if ($line === false) {
            break;
        }
        $data .= $line;
        // Una respuesta multilínea usa "-" en la 4ta posición salvo la última línea (" ").
        if (!isset($line[3]) || $line[3] === ' ') {
            break;
        }
    }

    return ['code' => (int) substr($data, 0, 3), 'message' => trim($data)];
}

/** @return array{code:int, message:string} */
function smtp_command($socket, string $command): array
{
    fwrite($socket, $command . "\r\n");
    return smtp_read_response($socket);
}

/**
 * @param int[] $okCodes
 * @return array{code:int, message:string}
 */
function smtp_expect($socket, string $command, array $okCodes): array
{
    $response = smtp_command($socket, $command);
    if (!in_array($response['code'], $okCodes, true)) {
        throw new RuntimeException('el servidor SMTP rechazó el comando: ' . $response['message']);
    }
    return $response;
}

function smtp_client_domain(): string
{
    return (string) (parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost');
}

/** Evita inyección de headers si algún dato de usuario trae saltos de línea. */
function smtp_header_safe(string $value): string
{
    return trim((string) preg_replace('/[\r\n]+/', ' ', $value));
}

/** "Dot-stuffing" del protocolo SMTP: una línea que empieza con "." termina el mensaje si no se escapa. */
function smtp_dot_stuff(string $body): string
{
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    $body = str_replace("\n", "\r\n", $body);
    $body = str_replace("\r\n.", "\r\n..", $body);
    if (str_starts_with($body, '.')) {
        $body = '.' . $body;
    }
    return $body;
}

function smtp_build_message(string $to, string $toName, string $subject, string $htmlBody): string
{
    $to = smtp_header_safe($to);
    $toName = smtp_header_safe($toName);
    $subject = smtp_header_safe($subject);

    $headers = [
        'From: ' . mb_encode_mimeheader(SMTP_FROM_NAME) . ' <' . SMTP_USER . '>',
        'To: ' . mb_encode_mimeheader($toName) . ' <' . $to . '>',
        'Subject: ' . mb_encode_mimeheader($subject),
        'Date: ' . date('r'),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ];

    return implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody;
}

/**
 * Envía un mail por SMTP. Nunca lanza: devuelve false y loguea el motivo si algo falla
 * (SMTP sin configurar, no conecta, credenciales inválidas, destinatario rechazado, etc.).
 */
function smtp_send(string $to, string $toName, string $subject, string $htmlBody): bool
{
    if (SMTP_USER === '' || SMTP_PASS === '') {
        error_log('mailer: SMTP no configurado (faltan SMTP_USER/SMTP_PASS en .env), no se envía "' . $subject . '".');
        return false;
    }

    $socket = @stream_socket_client(
        'tcp://' . SMTP_HOST . ':' . SMTP_PORT,
        $errno,
        $errstr,
        10
    );
    if ($socket === false) {
        error_log('mailer: no se pudo conectar a ' . SMTP_HOST . ':' . SMTP_PORT . ' — ' . $errstr);
        return false;
    }

    try {
        $greeting = smtp_read_response($socket);
        if ($greeting['code'] !== 220) {
            throw new RuntimeException('saludo inesperado del servidor: ' . $greeting['message']);
        }

        $domain = smtp_client_domain();
        smtp_expect($socket, 'EHLO ' . $domain, [250]);
        smtp_expect($socket, 'STARTTLS', [220]);

        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('no se pudo negociar TLS con el servidor SMTP');
        }

        smtp_expect($socket, 'EHLO ' . $domain, [250]);
        smtp_expect($socket, 'AUTH LOGIN', [334]);
        smtp_expect($socket, base64_encode(SMTP_USER), [334]);
        smtp_expect($socket, base64_encode(SMTP_PASS), [235]);

        smtp_expect($socket, 'MAIL FROM:<' . SMTP_USER . '>', [250]);
        smtp_expect($socket, 'RCPT TO:<' . smtp_header_safe($to) . '>', [250, 251]);
        smtp_expect($socket, 'DATA', [354]);

        $message = smtp_build_message($to, $toName, $subject, $htmlBody);
        smtp_expect($socket, smtp_dot_stuff($message) . "\r\n.", [250]);

        smtp_command($socket, 'QUIT');
        fclose($socket);
        return true;
    } catch (Throwable $e) {
        error_log('mailer: error enviando "' . $subject . '" a ' . $to . ' — ' . $e->getMessage());
        fclose($socket);
        return false;
    }
}

/**
 * Fecha/hora de un turno, formateada para mostrar en un mail.
 * @param array{date:string,start_time:string,end_time:string} $booking
 */
function booking_datetime_label(array $booking): string
{
    $fecha = date('d/m/Y', strtotime($booking['date']));
    $desde = substr($booking['start_time'], 0, 5);
    $hasta = substr($booking['end_time'], 0, 5);
    return "{$fecha}, de {$desde} a {$hasta} hs";
}

/**
 * Aviso al admin (setting('email')) de que hay un turno nuevo.
 * @param array{date:string,start_time:string,end_time:string,name:string,email:string,whatsapp:?string,motivo:?string,service_name?:?string} $booking
 */
function send_booking_admin_notification(array $booking): bool
{
    $to = setting('email', 'leandrosferrara@gmail.com');
    if (!$to) {
        error_log('mailer: no hay email de admin configurado (setting "email"), no se avisa el turno nuevo.');
        return false;
    }

    $body = '<p>Nuevo turno reservado:</p><ul>'
        . '<li><strong>Cuándo:</strong> ' . e(booking_datetime_label($booking)) . '</li>'
        . '<li><strong>Nombre:</strong> ' . e($booking['name']) . '</li>'
        . '<li><strong>Email:</strong> ' . e($booking['email']) . '</li>'
        . '<li><strong>WhatsApp:</strong> ' . e($booking['whatsapp'] ?: '—') . '</li>'
        . '<li><strong>Servicio:</strong> ' . e($booking['service_name'] ?? '—') . '</li>'
        . '<li><strong>Motivo:</strong> ' . e($booking['motivo'] ?: '—') . '</li>'
        . '</ul>';

    return smtp_send((string) $to, 'Admin', 'Nuevo turno: ' . booking_datetime_label($booking), $body);
}

/**
 * Confirmación al cliente que reservó el turno.
 * @param array{date:string,start_time:string,end_time:string,name:string,email:string,service_name?:?string} $booking
 */
function send_booking_client_confirmation(array $booking): bool
{
    $servicioLinea = !empty($booking['service_name'])
        ? '<p>Servicio: ' . e($booking['service_name']) . '</p>'
        : '';

    $body = '<p>Hola ' . e($booking['name']) . ', tu turno quedó reservado:</p>'
        . '<p><strong>' . e(booking_datetime_label($booking)) . '</strong></p>'
        . $servicioLinea
        . '<p>' . e(setting('direccion', '')) . '. Atendemos únicamente con turno previo, no es un local a la calle.</p>'
        . '<p>Si necesitás cancelarlo, podés hacerlo desde <a href="' . e(absolute_url('/turnos')) . '">' . e(absolute_url('/turnos')) . '</a> (sección "Mis turnos"), o escribinos por WhatsApp.</p>';

    return smtp_send($booking['email'], $booking['name'], 'Confirmación de tu turno — ' . booking_datetime_label($booking), $body);
}
