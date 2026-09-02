<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/services.php';
require_once __DIR__ . '/includes/layout.php';

$destacados = get_featured_services(4);
$reviews = array_slice(get_visible_reviews(), 0, 3);

page_start(
    'Reparación de PC y notebooks, y desarrollo web en Caseros',
    'Se te rompió la PC o necesitás una página web para tu negocio en Caseros? Escribinos: presupuesto claro y la mayoría de los trabajos listos en 24/48 hs.',
    '/'
);
json_ld_local_business();
?>

<section class="hero">
    <div class="container hero__inner">
        <div>
            <span class="eyebrow"><span class="dot"></span><?= e(setting('direccion', '')) ?></span>
            <h1>Diagnóstico y reparación <em>profesional</em> de PC y notebooks</h1>
            <p style="font-size:1.1rem;max-width:520px;">
                Service técnico en Caseros y El Palomar, con presupuesto claro antes de tocar el equipo.
                También armamos páginas web que hacen que te escriban. Mayoría de los trabajos listos en
                <?= e(setting('tiempo_estimado', '24/48 hs')) ?>.
            </p>
            <div class="hero__actions">
                <a href="<?= e(wa_link()) ?>" class="btn btn--primary" target="_blank" rel="noopener">Consultar por WhatsApp</a>
                <a href="<?= e(url('/reparacion-pc')) ?>" class="btn btn--ghost">Ver servicios y precios</a>
            </div>
            <div class="hero__actions" style="margin-top:0;">
                <span class="chip">Respuesta en <strong>&lt; 1&nbsp;hora</strong></span>
                <span class="chip">Diagnóstico <strong>a la vista</strong></span>
                <span class="chip">Trabajos en <strong><?= e(setting('tiempo_estimado', '24/48 hs')) ?></strong></span>
            </div>
        </div>

        <div class="diag reveal" aria-hidden="true">
            <div class="diag__scan"></div>
            <div class="diag__head">
                <span class="eyebrow"><span class="dot"></span>Diagnóstico en curso</span>
                <div class="diag__dots"><span></span><span></span><span></span></div>
            </div>
            <div class="diag__row"><span class="label">CPU</span><span class="diag__bar"><i style="width:38%"></i></span><span class="val">38%</span></div>
            <div class="diag__row"><span class="label">RAM</span><span class="diag__bar"><i style="width:71%"></i></span><span class="val">71%</span></div>
            <div class="diag__row"><span class="label">DISCO</span><span class="diag__bar"><i style="width:19%"></i></span><span class="val">19%</span></div>
            <div class="diag__row"><span class="label">BATERÍA</span><span class="diag__bar"><i style="width:84%"></i></span><span class="val">84%</span></div>
            <div class="diag__foot">
                <span class="tag"><span class="dot"></span>Sin fallas críticas</span>
                <span class="ref">#DX-0417</span>
            </div>
        </div>
    </div>
</section>

<section class="section section--tight">
    <div class="container">
        <div class="bento">
            <div class="bento__item bento__item--wide reveal">
                <strong><?= e(setting('tiempo_estimado', '24/48 hs')) ?></strong>
                <span>Es lo que suele tardar la mayoría de los trabajos, de punta a punta.</span>
            </div>
            <div class="bento__item reveal">
                <strong>100%</strong>
                <span>Presupuesto antes de reparar. Sin sorpresas.</span>
            </div>
            <div class="bento__item reveal">
                <strong>0</strong>
                <span>Intermediarios: hablás con quien arregla el equipo.</span>
            </div>
        </div>
    </div>
</section>

<?php if ($destacados): ?>
<section class="section">
    <div class="container">
        <span class="eyebrow"><span class="dot"></span>Lo más pedido</span>
        <h2>Precios claros, sin vueltas</h2>
        <div class="price-cards">
            <?php foreach ($destacados as $servicio): ?>
                <div class="price-card reveal">
                    <h3 class="price-card__name"><?= e($servicio['name']) ?></h3>
                    <?php if ($servicio['short_description']): ?>
                        <p class="price-card__desc"><?= e($servicio['short_description']) ?></p>
                    <?php endif; ?>
                    <?php if ($servicio['full_description']): ?>
                        <ul class="price-card__features">
                            <?php foreach (explode('|', $servicio['full_description']) as $item): ?>
                                <?php if (trim($item) !== ''): ?><li><?= e(trim($item)) ?></li><?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    <span class="price-card__price"><?= e(service_price_label($servicio)) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top:16px;"><a href="<?= e(url('/reparacion-pc')) ?>" class="btn btn--ghost btn--sm">Ver todos los precios de reparación</a></p>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <span class="eyebrow"><span class="dot"></span>Presencia digital</span>
        <h2>¿Necesitás una página web?</h2>
        <p>Armamos sitios simples que hacen que te escriban, sin vueltas técnicas.</p>
        <div class="grid grid--3">
            <div class="card package reveal">
                <h3>Presencia</h3>
                <p>Una sola página que cuenta quién sos y hace que te escriban.</p>
            </div>
            <div class="card package package--featured reveal">
                <h3>Negocio</h3>
                <p>El sitio completo de tu negocio: quién sos, qué hacés y cómo te encuentran.</p>
            </div>
            <div class="card package reveal">
                <h3>Completo</h3>
                <p>Para negocios con más para mostrar y más formas de que te contacten.</p>
            </div>
        </div>
        <p style="margin-top:16px;"><a href="<?= e(url('/desarrollo-web')) ?>" class="btn btn--ghost btn--sm">Ver paquetes y precios</a></p>
    </div>
</section>

<?php if ($reviews): ?>
<section class="section">
    <div class="container">
        <span class="eyebrow"><span class="dot"></span>Reseñas reales</span>
        <h2>Lo que dicen nuestros clientes</h2>
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
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <span class="eyebrow"><span class="dot"></span><?= e(setting('direccion', '')) ?></span>
        <h2>Dónde estamos</h2>
        <iframe class="map-frame reveal" src="<?= e(maps_embed_url()) ?>" loading="lazy" title="Ubicación en el mapa"></iframe>
        <p style="margin-top:14px;"><a href="<?= e(maps_directions_url()) ?>" class="btn btn--ghost btn--sm" target="_blank" rel="noopener">Cómo llegar</a></p>
    </div>
</section>

<?php page_end(); ?>
