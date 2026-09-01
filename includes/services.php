<?php

declare(strict_types=1);

require_once __DIR__ . '/price.php';

/**
 * Categorías visibles de una página ("reparacion-pc" | "desarrollo-web"), cada una con sus
 * servicios visibles ya cargados.
 */
function get_categories_with_services(string $page): array
{
    $stmt = db()->prepare(
        'SELECT * FROM service_categories WHERE page = :page AND visible = 1 ORDER BY sort_order, name'
    );
    $stmt->execute(['page' => $page]);
    $categories = $stmt->fetchAll();

    $servicesStmt = db()->prepare(
        'SELECT * FROM services WHERE category_id = :cid AND visible = 1 ORDER BY sort_order, name'
    );

    foreach ($categories as &$category) {
        $servicesStmt->execute(['cid' => $category['id']]);
        $category['services'] = $servicesStmt->fetchAll();
    }
    unset($category);

    return $categories;
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
