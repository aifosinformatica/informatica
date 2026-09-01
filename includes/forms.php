<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

/**
 * Guarda una consulta de contacto. Devuelve null si se guardó bien,
 * o un mensaje de error para mostrar en el formulario.
 *
 * @param array{origin:string,name:string,whatsapp?:string,email?:string,business_name?:string,
 *              device?:string,message?:string,service_slug?:string,consent:string} $data
 */
function save_contact_request(array $data): ?string
{
    if (is_spam_submission()) {
        return null; // Se descarta en silencio, no delatamos el honeypot.
    }

    $name = trim($data['name'] ?? '');
    if ($name === '') {
        return 'Contanos tu nombre para poder responderte.';
    }
    if (empty($data['consent'])) {
        return 'Necesitamos que aceptes la política de privacidad para poder contactarte.';
    }

    $stmt = db()->prepare(
        'INSERT INTO contact_requests
            (origin, name, whatsapp, email, business_name, device, message, service_slug, consent)
         VALUES
            (:origin, :name, :whatsapp, :email, :business_name, :device, :message, :service_slug, 1)'
    );
    $stmt->execute([
        'origin' => $data['origin'],
        'name' => $name,
        'whatsapp' => $data['whatsapp'] ?? null,
        'email' => $data['email'] ?? null,
        'business_name' => $data['business_name'] ?? null,
        'device' => $data['device'] ?? null,
        'message' => $data['message'] ?? null,
        'service_slug' => $data['service_slug'] ?? null,
    ]);

    return null;
}
