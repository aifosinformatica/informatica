<?php

declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

const DOLAR_API_URL = 'https://dolarapi.com/v1/dolares/bolsa';
const DOLAR_CACHE_MINUTES = 45;

/**
 * Devuelve la cotización efectiva (ARS por USD), usando caché en `exchange_rates`.
 * Si la API falla, usa la última cotización válida guardada: nunca deja de haber un precio
 * mientras exista al menos una fila histórica.
 */
function get_exchange_rate(): ?array
{
    $mode = setting('dolar_mode', 'automatico'); // automatico | manual
    $adjustmentPct = (float) setting('dolar_ajuste_pct', '0');

    $last = db()->query('SELECT * FROM exchange_rates ORDER BY id DESC LIMIT 1')->fetch();

    if ($mode === 'manual') {
        $manualRate = (float) setting('dolar_manual', '0');
        if ($manualRate > 0) {
            return [
                'rate_api' => $last['rate_api'] ?? null,
                'adjustment_pct' => $adjustmentPct,
                'rate_effective' => round($manualRate * (1 + $adjustmentPct / 100), 2),
                'fetched_at' => $last['fetched_at'] ?? date('Y-m-d H:i:s'),
                'source' => 'manual',
            ];
        }
    }

    $needsRefresh = true;
    if ($last && $last['fetched_at']) {
        $ageMinutes = (time() - strtotime((string) $last['fetched_at'])) / 60;
        $needsRefresh = $ageMinutes > DOLAR_CACHE_MINUTES;
    }

    if ($needsRefresh) {
        $fresh = fetch_dolar_api_rate();
        if ($fresh !== null) {
            $effective = round($fresh * (1 + $adjustmentPct / 100), 2);
            db()->prepare(
                'INSERT INTO exchange_rates (source, rate_api, adjustment_pct, rate_effective, fetched_at)
                 VALUES (:source, :rate_api, :adjustment_pct, :rate_effective, NOW())'
            )->execute([
                'source' => 'api',
                'rate_api' => $fresh,
                'adjustment_pct' => $adjustmentPct,
                'rate_effective' => $effective,
            ]);

            return [
                'rate_api' => $fresh,
                'adjustment_pct' => $adjustmentPct,
                'rate_effective' => $effective,
                'fetched_at' => date('Y-m-d H:i:s'),
                'source' => 'api',
            ];
        }
    }

    // La API falló o todavía no hacía falta refrescar: se usa la última guardada.
    if ($last) {
        return [
            'rate_api' => $last['rate_api'],
            'adjustment_pct' => $last['adjustment_pct'],
            'rate_effective' => (float) $last['rate_effective'],
            'fetched_at' => $last['fetched_at'],
            'source' => $last['source'],
        ];
    }

    return null; // Sin ninguna cotización guardada todavía (solo antes del primer fetch exitoso).
}

function fetch_dolar_api_rate(): ?float
{
    $json = function_exists('curl_init')
        ? fetch_url_curl(DOLAR_API_URL)
        : fetch_url_streams(DOLAR_API_URL);

    if ($json === null) {
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data) || !isset($data['venta'])) {
        return null;
    }

    return (float) $data['venta'];
}

function fetch_url_curl(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 4,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $body = curl_exec($ch);
    $ok = $body !== false && curl_errno($ch) === 0;
    curl_close($ch);
    return $ok ? (string) $body : null;
}

function fetch_url_streams(string $url): ?string
{
    if (!ini_get('allow_url_fopen')) {
        return null;
    }
    $ctx = stream_context_create([
        'http' => ['timeout' => 4, 'ignore_errors' => true],
        'https' => ['timeout' => 4],
    ]);
    $body = @file_get_contents($url, false, $ctx);
    return $body === false ? null : $body;
}

/**
 * Redondea SIEMPRE hacia arriba, a favor del negocio, al múltiplo configurado (default $500).
 */
function round_price(float $ars): int
{
    $step = (int) setting('redondeo_multiplo', '500');
    if ($step < 1) {
        $step = 500;
    }
    return (int) (ceil($ars / $step) * $step);
}

function price_ars_from_usd(float $usd): ?int
{
    $rate = get_exchange_rate();
    if ($rate === null) {
        return null;
    }
    return round_price($usd * $rate['rate_effective']);
}

function format_ars(int $ars): string
{
    return '$' . number_format($ars, 0, ',', '.');
}

function card_surcharge_pct(): float
{
    return (float) setting('recargo_tarjeta_pct', '18');
}

function price_with_card_surcharge(int $arsContado): int
{
    $pct = card_surcharge_pct();
    return (int) round($arsContado * (1 + $pct / 100));
}
