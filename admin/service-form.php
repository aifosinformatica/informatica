<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

$priceTypes = [
    'fijo' => 'Precio fijo',
    'desde' => 'Desde',
    'adicional' => 'Adicional (+)',
    'consultar' => 'Consultar',
    'mas_insumos' => 'Precio + insumos',
    'incluido_combo' => 'Incluido en combo',
];

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$service = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM services WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $service = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesión expirada, volvé a intentar.');
        redirect('/admin/service-form.php' . ($id ? "?id={$id}" : ''));
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $priceUsdRaw = trim((string) ($_POST['price_usd'] ?? ''));
    $priceUsd = $priceUsdRaw === '' ? null : (float) str_replace(',', '.', $priceUsdRaw);
    $priceType = (string) ($_POST['price_type'] ?? 'fijo');
    $extraText = trim((string) ($_POST['extra_text'] ?? '')) ?: null;
    $shortDescription = trim((string) ($_POST['short_description'] ?? '')) ?: null;
    $fullDescription = trim((string) ($_POST['full_description'] ?? '')) ?: null;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $visible = isset($_POST['visible']) ? 1 : 0;
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

    if ($name === '' || $categoryId === 0 || !array_key_exists($priceType, $priceTypes)) {
        flash_set('error', 'Completá al menos el nombre, la categoría y un tipo de precio válido.');
        redirect('/admin/service-form.php' . ($id ? "?id={$id}" : ''));
    }

    $pageStmt = db()->prepare('SELECT page FROM service_categories WHERE id = :id');
    $pageStmt->execute(['id' => $categoryId]);
    $page = (string) $pageStmt->fetchColumn();
    $slug = $page . '-' . slugify($name);

    if ($service) {
        db()->prepare(
            'UPDATE services SET category_id=:category_id, name=:name, slug=:slug, short_description=:short_description,
             full_description=:full_description, price_usd=:price_usd, price_type=:price_type, extra_text=:extra_text,
             featured=:featured, visible=:visible, sort_order=:sort_order WHERE id=:id'
        )->execute([
            'category_id' => $categoryId, 'name' => $name, 'slug' => $slug,
            'short_description' => $shortDescription, 'full_description' => $fullDescription,
            'price_usd' => $priceUsd, 'price_type' => $priceType, 'extra_text' => $extraText,
            'featured' => $featured, 'visible' => $visible, 'sort_order' => $sortOrder, 'id' => $service['id'],
        ]);
        flash_set('ok', 'Servicio actualizado.');
    } else {
        db()->prepare(
            'INSERT INTO services (category_id, name, slug, short_description, full_description, price_usd,
             price_type, extra_text, featured, visible, sort_order)
             VALUES (:category_id, :name, :slug, :short_description, :full_description, :price_usd,
             :price_type, :extra_text, :featured, :visible, :sort_order)'
        )->execute([
            'category_id' => $categoryId, 'name' => $name, 'slug' => $slug,
            'short_description' => $shortDescription, 'full_description' => $fullDescription,
            'price_usd' => $priceUsd, 'price_type' => $priceType, 'extra_text' => $extraText,
            'featured' => $featured, 'visible' => $visible, 'sort_order' => $sortOrder,
        ]);
        flash_set('ok', 'Servicio creado.');
    }
    redirect('/admin/services.php');
}

$categorias = db()->query('SELECT * FROM service_categories ORDER BY page, sort_order, name')->fetchAll();

admin_page_start($service ? 'Editar servicio' : 'Nuevo servicio', 'services');
?>

<form method="post" class="form form--wide">
    <?= csrf_field() ?>
    <?php if ($service): ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><?php endif; ?>

    <label>Nombre <input type="text" name="name" required value="<?= e($service['name'] ?? '') ?>"></label>

    <label>Categoría
        <select name="category_id" required>
            <option value="">— elegir —</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>" <?= ($service['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['page']) ?> — <?= e($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <div class="form-row form-row--2">
        <label>Precio en USD (vacío = sin precio numérico)
            <input type="text" name="price_usd" value="<?= e($service['price_usd'] !== null ? (string) $service['price_usd'] : '') ?>" placeholder="Ej: 29.27">
        </label>
        <label>Tipo de precio
            <select name="price_type">
                <?php foreach ($priceTypes as $value => $label): ?>
                    <option value="<?= e($value) ?>" <?= ($service['price_type'] ?? 'fijo') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <label>Texto adicional (ej: "+ insumos", "/ año")
        <input type="text" name="extra_text" value="<?= e($service['extra_text'] ?? '') ?>">
    </label>

    <label>Descripción breve (una línea, se muestra en el listado)
        <input type="text" name="short_description" value="<?= e($service['short_description'] ?? '') ?>">
    </label>

    <label>Descripción completa (opcional, no se usa en el listado corto)
        <textarea name="full_description" rows="3"><?= e($service['full_description'] ?? '') ?></textarea>
    </label>

    <div class="form-row form-row--2">
        <label class="checkbox"><input type="checkbox" name="visible" <?= ($service['visible'] ?? 1) ? 'checked' : '' ?>> <span>Visible en el sitio</span></label>
        <label class="checkbox"><input type="checkbox" name="featured" <?= ($service['featured'] ?? 0) ? 'checked' : '' ?>> <span>Destacado en home</span></label>
    </div>

    <label>Orden <input type="number" name="sort_order" value="<?= e((string) ($service['sort_order'] ?? 0)) ?>"></label>

    <button type="submit" class="btn btn--primary"><?= $service ? 'Guardar cambios' : 'Crear servicio' ?></button>
</form>

<?php admin_page_end(); ?>
