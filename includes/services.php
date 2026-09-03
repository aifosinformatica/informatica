<?php

declare(strict_types=1);

require_once __DIR__ . '/price.php';

/**
 * Categorías visibles de una página ("reparacion-pc" | "desarrollo-web"), cada una con sus
 * servicios visibles ya cargados. Un servicio con variantes (ej. "Cambio de componentes")
 * es una fila propia sin precio (price_type = 'grupo') con sus variantes en $service['variants'],
 * cada una con su propio precio independiente (ver includes/db > services.parent_service_id).
 */
function get_categories_with_services(string $page): array
{
    $stmt = db()->prepare(
        'SELECT * FROM service_categories WHERE page = :page AND visible = 1 ORDER BY sort_order, name'
    );
    $stmt->execute(['page' => $page]);
    $categories = $stmt->fetchAll();

    $servicesStmt = db()->prepare(
        'SELECT * FROM services WHERE category_id = :cid AND parent_service_id IS NULL AND visible = 1 ORDER BY sort_order, name'
    );
    $variantsStmt = db()->prepare(
        'SELECT * FROM services WHERE parent_service_id = :pid AND visible = 1 ORDER BY sort_order, name'
    );

    foreach ($categories as &$category) {
        $servicesStmt->execute(['cid' => $category['id']]);
        $services = $servicesStmt->fetchAll();
        foreach ($services as &$service) {
            $variantsStmt->execute(['pid' => $service['id']]);
            $service['variants'] = $variantsStmt->fetchAll();
        }
        unset($service);
        $category['services'] = $services;
    }
    unset($category);

    return $categories;
}

/**
 * Servicios visibles "elegibles" (sin las cabeceras de grupo, que no son un
 * ítem concreto), agrupados por página y con un label listo para un <option>
 * ("Servicio – Variante" cuando es una variante). Se usa en el <select> de
 * /turnos para que el cliente indique qué necesita al pedir el turno.
 *
 * @return array<string, array<int, array{id:int, label:string}>> label de página => opciones
 */
function get_services_for_booking_select(): array
{
    $pages = ['reparacion-pc' => 'Reparación de PC', 'desarrollo-web' => 'Desarrollo web'];
    $groups = [];

    foreach ($pages as $page => $pageLabel) {
        $items = [];
        foreach (get_categories_with_services($page) as $category) {
            foreach ($category['services'] as $service) {
                if ($service['variants']) {
                    foreach ($service['variants'] as $variant) {
                        $items[] = ['id' => (int) $variant['id'], 'label' => $service['name'] . ' – ' . $variant['name']];
                    }
                } else {
                    $items[] = ['id' => (int) $service['id'], 'label' => $service['name']];
                }
            }
        }
        if ($items) {
            $groups[$pageLabel] = $items;
        }
    }

    return $groups;
}

/**
 * Busca un servicio visible y "elegible" (no cabecera de grupo) por su slug.
 * Pensado para preseleccionar el <select> de /turnos vía ?service=<slug>
 * cuando el turno se pide desde la ficha de un servicio puntual.
 */
function get_bookable_service_id_by_slug(string $slug): ?int
{
    if ($slug === '') {
        return null;
    }
    $stmt = db()->prepare(
        "SELECT id FROM services WHERE slug = :slug AND visible = 1 AND price_type != 'grupo' LIMIT 1"
    );
    $stmt->execute(['slug' => $slug]);
    $id = $stmt->fetchColumn();
    return $id !== false ? (int) $id : null;
}

function get_featured_services(int $limit = 4): array
{
    $stmt = db()->prepare(
        'SELECT * FROM services WHERE visible = 1 AND featured = 1 ORDER BY sort_order LIMIT :limit'
    );
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Texto de precio a mostrar según price_type. Nunca deja de mostrar algo:
 * si falla la cotización, cae a "Consultar".
 */
function service_price_label(array $service): string
{
    $usd = $service['price_usd'] !== null ? (float) $service['price_usd'] : null;
    $ars = $usd !== null ? price_ars_from_usd($usd) : null;

    switch ($service['price_type']) {
        case 'consultar':
            return 'Consultar';

        case 'desde':
            return $ars !== null ? 'Desde ' . format_ars($ars) : 'Consultar';

        case 'adicional':
            return $ars !== null ? '+ ' . format_ars($ars) : 'Consultar';

        case 'incluido_combo':
            return 'Incluido';

        case 'grupo':
            // Fila "cabecera" de un grupo de variantes (sin precio propio): se resume con el
            // menor precio entre sus variantes visibles, como "Desde $X" (o el precio directo
            // si todas las variantes valen lo mismo). Se usa en el home (destacados) y en el
            // admin; en la página de reparacion-pc las variantes se listan aparte, ver reparacion-pc.php.
            $variantsStmt = db()->prepare(
                'SELECT price_usd FROM services WHERE parent_service_id = :id AND visible = 1 AND price_usd IS NOT NULL'
            );
            $variantsStmt->execute(['id' => $service['id']]);
            $variantUsd = array_map('floatval', $variantsStmt->fetchAll(PDO::FETCH_COLUMN));
            if (!$variantUsd) {
                return 'Consultar';
            }
            $minArs = price_ars_from_usd(min($variantUsd));
            $maxArs = price_ars_from_usd(max($variantUsd));
            if ($minArs === null) {
                return 'Consultar';
            }
            return $minArs === $maxArs ? format_ars($minArs) : 'Desde ' . format_ars($minArs);

        case 'mas_insumos':
        case 'fijo':
        default:
            if ($ars === null) {
                return 'Consultar';
            }
            $label = format_ars($ars);
            if (!empty($service['extra_text'])) {
                $label .= ' ' . $service['extra_text'];
            }
            return $label;
    }
}

function get_visible_reviews(): array
{
    return db()->query(
        'SELECT * FROM reviews WHERE visible = 1 ORDER BY sort_order, review_date DESC'
    )->fetchAll();
}
