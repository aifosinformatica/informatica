<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function whatsapp_number(): string
{
    $raw = setting('whatsapp', '');
    return preg_replace('/[^0-9]/', '', (string) $raw) ?? '';
}

function whatsapp_display(): string
{
    return setting('whatsapp_display', setting('whatsapp', ''));
}

function wa_link(string $message = ''): string
{
    $url = 'https://wa.me/' . whatsapp_number();
    if ($message !== '') {
        $url .= '?text=' . rawurlencode($message);
    }
    return $url;
}

function wa_link_service(string $serviceName): string
{
    return wa_link("Hola, quería consultar por {$serviceName}.");
}

function wa_link_web(): string
{
    return wa_link('Hola, quería consultar por una página web para mi negocio.');
}

function maps_query(): string
{
    return setting('maps_query', setting('direccion', ''));
}

function maps_embed_url(): string
{
    return 'https://www.google.com/maps?q=' . rawurlencode(maps_query()) . '&output=embed';
}

function maps_directions_url(): string
{
    return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode(maps_query());
}
