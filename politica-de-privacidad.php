<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

page_start(
    'Política de privacidad',
    'Qué datos personales recolectamos a través de los formularios de este sitio y cómo los usamos.',
    '/politica-de-privacidad'
);
?>

<section class="section">
    <div class="container" style="max-width:720px;">
        <span class="eyebrow"><span class="dot"></span>Ley 25.326</span>
        <h1>Política de privacidad</h1>
        <p>Última actualización: <?= date('d/m/Y') ?>.</p>

        <p>
            Cuando nos escribís desde este sitio, nos dejás algunos datos personales para poder responderte.
            Acá te contamos qué hacemos con ellos. <?= e(setting('nombre_comercial', '')) ?> (en adelante,
            "nosotros") los recolecta en los términos de la Ley 25.326 de Protección de Datos Personales
            de la República Argentina.
        </p>

        <h2>Qué datos recolectamos</h2>
        <p>Según el formulario: nombre, WhatsApp, email, nombre del negocio, equipo y una descripción del problema o necesidad.</p>

        <h2>Para qué los usamos</h2>
        <p>Únicamente para responder tu consulta y, si corresponde, coordinar el servicio solicitado. No los usamos con fines comerciales de terceros ni los vendemos ni compartimos con nadie.</p>

        <h2>Cuánto tiempo los conservamos</h2>
        <p>Mientras sea razonable para atender tu consulta o mantener un registro comercial básico. Podés pedir que eliminemos tus datos en cualquier momento.</p>

        <h2>Cómo pedir la baja de tus datos</h2>
        <p>Escribinos por <a href="<?= e(wa_link()) ?>" target="_blank" rel="noopener">WhatsApp</a><?= setting('email') ? ' o a ' . e(setting('email')) : '' ?> pidiendo la baja de tus datos, y los eliminamos de nuestros registros.</p>

        <h2>Cookies</h2>
        <p>Este sitio no usa cookies de seguimiento de terceros por defecto. Si en el futuro se incorpora Google Analytics o Meta Pixel, esta política se actualizará para informarlo.</p>
    </div>
</section>

<?php page_end(); ?>
