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
                <p style="font-size:.85rem;color:var(--text-muted);">Atendemos <strong>únicamente con turno previo</strong> — coordiná día y horario por WhatsApp antes de acercarte, no es un local a la calle.</p>
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

        <div class="faq reveal" style="margin-top:18px;">
            <details>
                <summary>📍 Cómo llegar a Av. Mitre 5761, Caseros</summary>
                <p><strong>🚌 En colectivo:</strong></p>
                <ul>
                    <li><strong>Líneas 343 y 123:</strong> bajar en la parada de Av. Mitre y Lisandro de la Torre (o Av. Mitre y Marconi/Pringles). Queda a solo 1 o 2 cuadras de la oficina.</li>
                    <li><strong>Línea 181:</strong> bajar en la zona de Av. Mitre y La Merced (o Av. Mitre y Lisandro de la Torre).</li>
                    <li><strong>Líneas 53, 105 y 237:</strong> bajar sobre Av. San Martín y Lisandro de la Torre, y caminar unas 3 o 4 cuadras por Lisandro de la Torre hasta Av. Mitre.</li>
                </ul>
                <p><strong>🚆 En tren:</strong></p>
                <ul>
                    <li><strong>Estación Lourdes</strong> (línea Urquiza, 4,3★): bajar ahí y caminar unas 10 cuadras, o tomar el colectivo 343/123 sobre Av. Mitre.</li>
                    <li><strong>Estación Caseros</strong> (línea San Martín, 4,1★): bajar ahí y tomar el colectivo 343/181/123 hasta la oficina.</li>
                </ul>
            </details>
        </div>
    </div>
</section>

<?php page_end(); ?>
