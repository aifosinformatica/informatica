<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/forms.php';
require_once __DIR__ . '/includes/layout.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form']) && $_POST['form'] === 'web') {
    if (!csrf_verify()) {
        $error = 'Sesión expirada, volvé a intentar.';
    } else {
        $error = save_contact_request([
            'origin' => 'desarrollo-web',
            'name' => (string) ($_POST['name'] ?? ''),
            'business_name' => (string) ($_POST['business_name'] ?? ''),
            'whatsapp' => (string) ($_POST['whatsapp'] ?? ''),
            'email' => (string) ($_POST['email'] ?? ''),
            'message' => (string) ($_POST['need'] ?? ''),
            'consent' => (string) ($_POST['consent'] ?? ''),
        ]);
        if ($error === null) {
            flash_set('ok', 'Listo, recibimos tu consulta. Te contactamos por WhatsApp a la brevedad.');
            redirect('/desarrollo-web#contacto');
        }
    }
}

$categorias = get_categories_with_services('desarrollo-web');
$paquetes = [];
$adicionales = [];
$email = [];
foreach ($categorias as $categoria) {
    if ($categoria['name'] === 'Paquetes') { $paquetes = $categoria['services']; }
    elseif ($categoria['name'] === 'Email corporativo') { $email = $categoria['services']; }
    else { $adicionales = array_merge($adicionales, $categoria['services']); }
}

page_start(
    'Desarrollo web y correo corporativo en Caseros',
    'Tu negocio necesita una página web que haga que te escriban, no un sitio que junte polvo. Paquetes con precio claro y arranque rápido.',
    '/desarrollo-web'
);
?>

<section class="section">
    <div class="container">
        <div class="page-intro">
            <span class="eyebrow"><span class="dot"></span>Presencia digital</span>
            <h1>Páginas web para tu negocio</h1>
            <p>Elegís el paquete que más se ajusta a lo que necesitás, nos escribís por WhatsApp y arrancamos.</p>
        </div>

        <div class="grid grid--3 reveal">
            <?php foreach ($paquetes as $i => $paquete): ?>
                <div class="card package <?= $i === 1 ? 'package--featured' : '' ?>">
                    <h3><?= e($paquete['name']) ?></h3>
                    <p class="price"><?= e(service_price_label($paquete)) ?></p>
                    <?php if ($paquete['short_description']): ?><p><?= e($paquete['short_description']) ?></p><?php endif; ?>
                    <?php if ($paquete['full_description']): ?>
                        <ul>
                            <?php foreach (explode('|', $paquete['full_description']) as $item): ?>
                                <li><?= e(trim($item)) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <a href="<?= e(wa_link_service($paquete['name'])) ?>" class="btn btn--primary btn--block" target="_blank" rel="noopener">Consultar</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if ($adicionales): ?>
<section class="section">
    <div class="container">
        <h2>Adicionales</h2>
        <div class="receipt reveal">
            <?php foreach ($adicionales as $servicio): ?>
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
</section>
<?php endif; ?>

<?php if ($email): ?>
<section class="section">
    <div class="container">
        <h2>Correo corporativo</h2>
        <p style="font-size:1.1rem;">¿Todavía respondés consultas desde <em>tunegocio@gmail.com</em>? Pasate a <strong>vos@tunegocio.com.ar</strong> y sumá una cuota de profesionalismo.</p>
        <div class="card reveal">
            <p><?= e($email[0]['full_description']) ?></p>
            <p class="price"><?= e(service_price_label($email[0])) ?></p>
            <a href="<?= e(wa_link_service('correo corporativo para mi negocio')) ?>" class="btn btn--primary" target="_blank" rel="noopener">Consultar</a>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section" id="contacto">
    <div class="container">
        <h2>Contanos cómo es tu negocio</h2>
        <?php if ($error): ?><p class="alert alert--error"><?= e($error) ?></p><?php endif; ?>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="form" value="web">
            <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">
            <label>Nombre <input type="text" name="name" required></label>
            <label>Negocio <input type="text" name="business_name"></label>
            <label>WhatsApp <input type="tel" name="whatsapp" required></label>
            <label>Email <input type="email" name="email"></label>
            <label>¿Qué necesitás? <textarea name="need" rows="3"></textarea></label>
            <label class="checkbox">
                <input type="checkbox" name="consent" value="1" required>
                <span>Acepto la <a href="<?= e(url('/politica-de-privacidad')) ?>" target="_blank">política de privacidad</a>.</span>
            </label>
            <button type="submit" class="btn btn--primary btn--block">Enviar consulta</button>
        </form>
    </div>
</section>

<?php page_end(); ?>
