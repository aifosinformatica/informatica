<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/layout.php';

$reviews = get_visible_reviews();

page_start(
    'Cómo trabajamos — servicio técnico en Caseros',
    'Antes de escribirnos, esto es todo lo que necesitás saber: dónde estamos, cómo cobramos y qué pasa con el diagnóstico.',
    '/servicio'
);
json_ld_local_business();
?>

<section class="section">
    <div class="container">
        <div class="page-intro">
            <span class="eyebrow"><span class="dot"></span>Cómo trabajamos</span>
            <h1>Así trabajamos, sin letra chica</h1>
        </div>

        <div class="grid grid--4 reveal">
            <div class="card trust-item">
                <h3><?= e(setting('direccion', '')) ?></h3>
                <p>Acá estamos.</p>
            </div>
            <div class="card trust-item">
                <h3><?= e(setting('horario', '')) ?></h3>
                <p>Cuando podés encontrarnos.</p>
            </div>
            <div class="card trust-item">
                <h3><?= e(setting('tiempo_estimado', '24/48 hs')) ?></h3>
                <p>Es lo que suele tardar un trabajo.</p>
            </div>
            <div class="card trust-item">
                <h3>Presupuesto claro</h3>
                <p>Te lo confirmamos antes de tocar nada.</p>
            </div>
        </div>

        <h2 style="margin-top:40px;">¿Cómo es el diagnóstico?</h2>
        <p>Revisamos el equipo y te contamos, en criollo, qué tiene y cuánto sale arreglarlo. Si seguís adelante con la reparación, ese diagnóstico te lo descontamos del total. Y si el equipo no tiene un arreglo que valga la pena, no te cobramos nada.</p>

        <h2>¿Cómo se paga?</h2>
        <p><strong>Sin recargo:</strong> efectivo, transferencia, o Mercado Pago con saldo en cuenta.</p>
        <p><strong>Con tarjeta</strong> (propia o Mercado Pago) hay un recargo del <?= e((string) card_surcharge_pct()) ?>%. Si necesitás pagarlo en cuotas, contanos y vemos cómo armarlo.</p>

        <h2>Antes de escribirnos</h2>
        <div class="faq">
            <details>
                <summary>¿Dónde tengo que llevar el equipo?</summary>
                <p><?= e(setting('direccion', '')) ?>. Si te queda lejos, escribinos igual: muchas veces se puede resolver a distancia o coordinando un retiro.</p>
            </details>
            <details>
                <summary>¿Necesito sacar turno?</summary>
                <p>No hace falta. Escribinos por WhatsApp y vemos juntos el mejor momento para traerlo.</p>
            </details>
            <details>
                <summary>¿Trabajan con cualquier marca?</summary>
                <p>Sí, con casi todas. Si tu caso es raro, mandanos un mensaje antes de acercarte y te confirmamos.</p>
            </details>
        </div>

        <?php if ($reviews): ?>
        <h2 style="margin-top:40px;">Reseñas</h2>
        <div class="grid grid--3">
            <?php foreach ($reviews as $review): ?>
                <div class="card review reveal">
                    <p class="review__rating" aria-label="<?= e((string) $review['rating']) ?> de 5">
                        <?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?>
                    </p>
                    <p><?= e($review['body']) ?></p>
                    <p style="color:var(--text);font-weight:600;">— <?= e($review['name']) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <h2 style="margin-top:40px;">Dónde estamos</h2>
        <iframe class="map-frame reveal" src="<?= e(maps_embed_url()) ?>" loading="lazy" title="Ubicación en el mapa"></iframe>
        <p style="margin-top:14px;"><a href="<?= e(maps_directions_url()) ?>" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">Cómo llegar</a></p>
    </div>
</section>

<?php page_end(); ?>
