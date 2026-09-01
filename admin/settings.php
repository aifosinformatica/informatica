<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/price.php';
require_once __DIR__ . '/includes/layout.php';

$fields = [
    'nombre_comercial', 'direccion', 'maps_query', 'whatsapp', 'whatsapp_display', 'telefono',
    'email', 'instagram', 'horario', 'tiempo_estimado',
    'dolar_mode', 'dolar_ajuste_pct', 'dolar_manual', 'redondeo_multiplo', 'recargo_tarjeta_pct',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $stmt = db()->prepare(
        'INSERT INTO settings (`key`, `value`) VALUES (:key, :value)
         ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
    );
    foreach ($fields as $field) {
        $stmt->execute(['key' => $field, 'value' => trim((string) ($_POST[$field] ?? ''))]);
    }
    flash_set('ok', 'Configuración guardada.');
    redirect('/admin/settings.php');
}

$values = [];
foreach ($fields as $field) {
    $values[$field] = setting($field, '');
}
$rate = get_exchange_rate();

admin_page_start('Configuración', 'settings');
?>

<?php if ($rate): ?>
    <p class="alert alert--ok">
        Cotización API: ARS <?= e(number_format((float) ($rate['rate_api'] ?? 0), 2, ',', '.')) ?> ·
        Ajuste: <?= e((string) $rate['adjustment_pct']) ?>% ·
        Efectiva: ARS <?= e(number_format((float) $rate['rate_effective'], 2, ',', '.')) ?> ·
        Actualizada: <?= e(date('d/m/Y H:i', strtotime((string) $rate['fetched_at']))) ?> (<?= e($rate['source']) ?>)
    </p>
<?php endif; ?>

<form method="post" class="form form--wide">
    <?= csrf_field() ?>

    <fieldset>
        <legend>Datos del negocio</legend>
        <label>Nombre comercial <input type="text" name="nombre_comercial" value="<?= e($values['nombre_comercial']) ?>"></label>
        <label>Dirección <input type="text" name="direccion" value="<?= e($values['direccion']) ?>"></label>
        <label>Dirección para el mapa (Google Maps) <input type="text" name="maps_query" value="<?= e($values['maps_query']) ?>"></label>
        <div class="form-row form-row--2">
            <label>WhatsApp (solo dígitos, con código de país) <input type="text" name="whatsapp" value="<?= e($values['whatsapp']) ?>" placeholder="5491156970599"></label>
            <label>WhatsApp (texto que se muestra) <input type="text" name="whatsapp_display" value="<?= e($values['whatsapp_display']) ?>" placeholder="+54 9 11 5697-0599"></label>
        </div>
        <label>Teléfono (para "Llamar") <input type="text" name="telefono" value="<?= e($values['telefono']) ?>"></label>
        <div class="form-row form-row--2">
            <label>Email <input type="email" name="email" value="<?= e($values['email']) ?>"></label>
            <label>Instagram (URL completa) <input type="url" name="instagram" value="<?= e($values['instagram']) ?>"></label>
        </div>
        <label>Horario <input type="text" name="horario" value="<?= e($values['horario']) ?>"></label>
        <label>Tiempo estimado de trabajos <input type="text" name="tiempo_estimado" value="<?= e($values['tiempo_estimado']) ?>"></label>
    </fieldset>

    <fieldset>
        <legend>Cotización del dólar</legend>
        <label>Modo
            <select name="dolar_mode">
                <option value="automatico" <?= $values['dolar_mode'] === 'automatico' ? 'selected' : '' ?>>Automático (DolarApi, dólar MEP/Bolsa)</option>
                <option value="manual" <?= $values['dolar_mode'] === 'manual' ? 'selected' : '' ?>>Manual</option>
            </select>
        </label>
        <div class="form-row form-row--2">
            <label>Ajuste porcentual sobre la cotización (ej: 3 = +3%)
                <input type="number" step="0.01" name="dolar_ajuste_pct" value="<?= e($values['dolar_ajuste_pct']) ?>">
            </label>
            <label>Cotización manual (ARS por USD, solo si el modo es manual)
                <input type="number" step="0.01" name="dolar_manual" value="<?= e($values['dolar_manual']) ?>">
            </label>
        </div>
    </fieldset>

    <fieldset>
        <legend>Precios</legend>
        <div class="form-row form-row--2">
            <label>Redondear al múltiplo de (siempre hacia arriba, a favor del negocio)
                <input type="number" name="redondeo_multiplo" value="<?= e($values['redondeo_multiplo']) ?>">
            </label>
            <label>Recargo por tarjeta / Mercado Pago con tarjeta (%)
                <input type="number" step="0.01" name="recargo_tarjeta_pct" value="<?= e($values['recargo_tarjeta_pct']) ?>">
            </label>
        </div>
    </fieldset>

    <button type="submit" class="btn btn--primary">Guardar configuración</button>
</form>

<h2>Backup</h2>
<p>Descargá una copia completa de la base de datos en cualquier momento.</p>
<a href="<?= e(url('/admin/export.php')) ?>" class="btn btn--ghost">Exportar todo (SQL)</a>

<?php admin_page_end(); ?>
