<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/forms.php';
require_once __DIR__ . '/includes/layout.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'reparacion') {
    if (!csrf_verify()) {
        $error = 'Sesión expirada, volvé a intentar.';
    } else {
        $error = save_contact_request([
            'origin' => 'reparacion-pc',
            'name' => (string) ($_POST['name'] ?? ''),
            'whatsapp' => (string) ($_POST['whatsapp'] ?? ''),
            'device' => (string) ($_POST['device'] ?? ''),
            'message' => (string) ($_POST['problem'] ?? ''),
            'service_slug' => (string) ($_POST['service'] ?? ''),
            'consent' => (string) ($_POST['consent'] ?? ''),
        ]);
        if ($error === null) {
            flash_set('ok', 'Listo, recibimos tu consulta. Te contactamos por WhatsApp a la brevedad.');
            redirect('/reparacion-pc#contacto');
        }
    }
}

$categorias = get_categories_with_services('reparacion-pc');

$diagnostico = null;
foreach ($categorias as $categoria) {
    foreach ($categoria['services'] as $servicio) {
        if ($servicio['slug'] === 'reparacion-pc-diagnostico-tecnico') {
            $diagnostico = $servicio;
            break 2;
        }
    }
}

page_start(
    'Reparación de PC y notebooks en Caseros — precios',
    'Estos son los precios reales de reparar, actualizar o hacerle mantenimiento a tu PC o notebook en Caseros. Sin sorpresas, y con WhatsApp a un click.',
    '/reparacion-pc'
);
?>

<section class="section">
    <div class="container">
        <div class="page-intro">
            <span class="eyebrow"><span class="dot"></span>Precios reales, actualizados hoy</span>
            <h1>Reparación y actualización de PC y notebooks</h1>
            <p>Estos son los precios que manejamos hoy. Si tu equipo o tu problema no está en la lista, escribinos igual — seguro lo resolvemos.</p>
            <div class="hero__actions">
                <a href="<?= e(wa_link()) ?>" class="btn btn--primary" target="_blank" rel="noopener">Consultar por WhatsApp</a>
            </div>
        </div>

        <?php foreach ($categorias as $categoria): ?>
            <?php if (!$categoria['services']) continue; ?>
            <div class="category-block reveal">
                <div class="receipt">
                    <div class="receipt__cat"><h3><?= e($categoria['name']) ?></h3></div>
                    <?php foreach ($categoria['services'] as $servicio): ?>
                        <div class="receipt__row">
                            <span class="receipt__name">
                                <?= e($servicio['name']) ?>
                                <?php if ($servicio['short_description']): ?><span class="receipt__detail"><?= e($servicio['short_description']) ?></span><?php endif; ?>
                            </span>
                            <span class="receipt__leader"></span>
                            <span class="receipt__price"><?= e(service_price_label($servicio)) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <p style="font-size:.85rem;">Los precios se actualizan solos con la cotización del dólar, así que siempre están al día. Los que dicen "Consultar" dependen del equipo o del repuesto — te los confirmamos por WhatsApp en un rato.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <h2>Preguntas frecuentes</h2>
        <div class="faq reveal">
            <details>
                <summary>¿El diagnóstico tiene costo?</summary>
                <p>Sí<?= $diagnostico ? ', ' . e(service_price_label($diagnostico)) : '' ?>. Pero si después arreglás el equipo con nosotros, ese monto se descuenta de la reparación. Y si tu equipo no tiene arreglo, no te cobramos nada.</p>
            </details>
            <details>
                <summary>¿Puedo pagar con tarjeta?</summary>
                <p>Sí, hay un recargo del <?= e((string) card_surcharge_pct()) ?>% (cosas de las tarjetas). En efectivo, transferencia o Mercado Pago con saldo en cuenta no pagás nada de más.</p>
            </details>
            <details>
                <summary>¿Cuánto tardan los trabajos?</summary>
                <p>Casi todo lo resolvemos en <?= e(setting('tiempo_estimado', '24/48 hs')) ?>. Si tu caso lleva más (pasa con algunos repuestos), te avisamos apenas lo sabemos.</p>
            </details>
        </div>
    </div>
</section>

<section class="section" id="contacto">
    <div class="container">
        <h2>¿Le pasa esto a tu equipo? Contanos</h2>
        <?php if ($error): ?><p class="alert alert--error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="reparacion">
            <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">
            <label>Nombre <input type="text" name="name" required></label>
            <label>WhatsApp <input type="tel" name="whatsapp" required></label>
            <label>Equipo <input type="text" name="device" placeholder="Ej: notebook Lenovo"></label>
            <label>¿Qué te pasa? <textarea name="problem" rows="3"></textarea></label>
            <label class="checkbox">
                <input type="checkbox" name="consent" value="1" required>
                <span>Acepto la <a href="<?= e(url('/politica-de-privacidad')) ?>" target="_blank">política de privacidad</a>.</span>
            </label>
            <button type="submit" class="btn btn--primary btn--block">Enviar consulta</button>
        </form>
    </div>
</section>

<?php page_end(); ?>
