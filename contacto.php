<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/forms.php';
require_once __DIR__ . '/includes/layout.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesión expirada, volvé a intentar.';
    } else {
        $error = save_contact_request([
            'origin' => 'contacto',
            'name' => (string) ($_POST['name'] ?? ''),
            'whatsapp' => (string) ($_POST['whatsapp'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'message' => (string) ($_POST['message'] ?? ''),
            'consent' => (string) ($_POST['consent'] ?? ''),
        ]);
        if ($error === null) {
            flash_set('ok', 'Listo, recibimos tu mensaje. Te contactamos por WhatsApp a la brevedad.');
            redirect('/contacto');
        }
    }
}

page_start(
    'Contacto — servicio técnico y desarrollo web en Caseros',
    'Contanos qué necesitás y te respondemos por WhatsApp. Estamos en Mitre 5761, Caseros, Buenos Aires.',
    '/contacto'
);
?>

<section class="section">
    <div class="container">
        <div class="page-intro">
            <span class="eyebrow"><span class="dot"></span>Contacto directo</span>
            <h1>Escribinos</h1>
            <p>Contanos qué te pasa o qué necesitás y te respondemos por WhatsApp, rápido.</p>
        </div>

        <div class="grid grid--3" style="align-items:start;">
            <div class="card reveal">
                <h3>WhatsApp</h3>
                <p><a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener"><?= e(whatsapp_display()) ?></a></p>
                <h3>Teléfono</h3>
                <p><a href="tel:<?= e(whatsapp_number()) ?>"><?= e(whatsapp_display()) ?></a></p>
                <h3>Dirección</h3>
                <p><?= e(setting('direccion', '')) ?></p>
                <h3>Horario</h3>
                <p><?= e(setting('horario', '')) ?></p>
                <?php if (setting('instagram')): ?>
                    <h3>Instagram</h3>
                    <p><a href="<?= e(setting('instagram')) ?>" target="_blank" rel="noopener">Seguinos</a></p>
                <?php endif; ?>
            </div>

            <div class="card reveal" style="grid-column: span 2;">
                <?php if ($error): ?><p class="alert alert--error"><?= e($error) ?></p><?php endif; ?>
                <form method="post" class="form">
                    <?= csrf_field() ?>
                    <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">
                    <label>Nombre <input type="text" name="name" required></label>
                    <label>WhatsApp <input type="tel" name="whatsapp" required></label>
                    <label>Email <input type="email" name="email"></label>
                    <label>Mensaje <textarea name="message" rows="4" required></textarea></label>
                    <label class="checkbox">
                        <input type="checkbox" name="consent" value="1" required>
                        <span>Acepto la <a href="<?= e(url('/politica-de-privacidad')) ?>" target="_blank">política de privacidad</a>.</span>
                    </label>
                    <button type="submit" class="btn btn--primary btn--block">Enviar</button>
                </form>
            </div>
        </div>

        <h2 style="margin-top:40px;">Cómo llegar</h2>
        <iframe class="map-frame reveal" src="<?= e(maps_embed_url()) ?>" loading="lazy" title="Ubicación en el mapa"></iframe>
        <p style="margin-top:14px;"><a href="<?= e(maps_directions_url()) ?>" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">Cómo llegar</a></p>
    </div>
</section>

<?php page_end(); ?>
