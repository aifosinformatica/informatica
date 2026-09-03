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
    'grupo' => 'Grupo de variantes (sin precio propio)',
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
    $parentServiceId = (int) ($_POST['parent_service_id'] ?? 0) ?: null;
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

    if ($parentServiceId !== null && $service && $parentServiceId === (int) $service['id']) {
        flash_set('error', 'Un servicio no puede ser variante de sí mismo.');
        redirect('/admin/service-form.php' . ($id ? "?id={$id}" : ''));
    }

    $pageStmt = db()->prepare('SELECT page FROM service_categories WHERE id = :id');
    $pageStmt->execute(['id' => $categoryId]);
    $page = (string) $pageStmt->fetchColumn();

    // Si es variante de otro servicio, se antepone el nombre del "padre" al slug para
    // evitar choques entre variantes homónimas de grupos distintos (ej. "Memoria RAM"
    // existe tanto en "Cambio de componentes" de PC como en el de Notebook).
    $parentName = null;
    if ($parentServiceId) {
        $parentNameStmt = db()->prepare('SELECT name FROM services WHERE id = :id');
        $parentNameStmt->execute(['id' => $parentServiceId]);
        $parentName = $parentNameStmt->fetchColumn() ?: null;
    }
    $slug = $page . '-' . ($parentName ? slugify($parentName) . '-' : '') . slugify($name);

    if ($service) {
        db()->prepare(
            'UPDATE services SET category_id=:category_id, parent_service_id=:parent_service_id, name=:name, slug=:slug,
             short_description=:short_description, full_description=:full_description, price_usd=:price_usd,
             price_type=:price_type, extra_text=:extra_text, featured=:featured, visible=:visible,
             sort_order=:sort_order WHERE id=:id'
        )->execute([
            'category_id' => $categoryId, 'parent_service_id' => $parentServiceId, 'name' => $name, 'slug' => $slug,
            'short_description' => $shortDescription, 'full_description' => $fullDescription,
            'price_usd' => $priceUsd, 'price_type' => $priceType, 'extra_text' => $extraText,
            'featured' => $featured, 'visible' => $visible, 'sort_order' => $sortOrder, 'id' => $service['id'],
        ]);
        flash_set('ok', 'Servicio actualizado.');
    } else {
        db()->prepare(
            'INSERT INTO services (category_id, parent_service_id, name, slug, short_description, full_description, price_usd,
             price_type, extra_text, featured, visible, sort_order)
             VALUES (:category_id, :parent_service_id, :name, :slug, :short_description, :full_description, :price_usd,
             :price_type, :extra_text, :featured, :visible, :sort_order)'
        )->execute([
            'category_id' => $categoryId, 'parent_service_id' => $parentServiceId, 'name' => $name, 'slug' => $slug,
            'short_description' => $shortDescription, 'full_description' => $fullDescription,
            'price_usd' => $priceUsd, 'price_type' => $priceType, 'extra_text' => $extraText,
            'featured' => $featured, 'visible' => $visible, 'sort_order' => $sortOrder,
        ]);
        flash_set('ok', 'Servicio creado.');
    }
    redirect('/admin/services.php');
}

$categorias = db()->query('SELECT * FROM service_categories ORDER BY page, sort_order, name')->fetchAll();

// Candidatos a "padre" de una variante: solo servicios que a su vez no sean ya variantes
// de otro (se admite un solo nivel de anidamiento, a propósito: una variante no es una
// categoría nueva).
$parentCandidatesStmt = db()->query(
    "SELECT s.id, s.name, c.page, c.name AS category_name FROM services s
     JOIN service_categories c ON c.id = s.category_id
     WHERE s.parent_service_id IS NULL
     ORDER BY c.page, c.sort_order, s.sort_order, s.name"
);
$parentCandidates = $parentCandidatesStmt->fetchAll();

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

    <label>¿Es variante de otro servicio? (opcional — ej: "Memoria RAM" puede ser variante de "Cambio de componentes")
        <select name="parent_service_id">
            <option value="">— No, es un servicio independiente —</option>
            <?php foreach ($parentCandidates as $p): ?>
                <?php if ($service && (int) $p['id'] === (int) $service['id']) continue; ?>
                <option value="<?= (int) $p['id'] ?>" <?= (int) ($service['parent_service_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>>
                    <?= e($p['page']) ?> — <?= e($p['category_name']) ?> — <?= e($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <p style="font-size:.85rem;color:var(--text-muted);margin-top:-8px;">
        Si elegís un "padre", este servicio se muestra como una de sus variantes (con su propio precio) en vez de
        como fila independiente. El servicio "padre" debería tener tipo de precio "Grupo de variantes" y usar la
        misma categoría que sus variantes.
    </p>

    <label>Texto adicional (ej: "+ insumos", "/ año")
        <input type="text" name="extra_text" value="<?= e($service['extra_text'] ?? '') ?>">
    </label>

    <label>Descripción breve (una línea, se muestra en el listado)
        <input type="text" name="short_description" value="<?= e($service['short_description'] ?? '') ?>">
    </label>

    <label>Descripción completa (opcional). Para varios ítems separá con "|" — ej: "Limpieza física|Cambio de pasta térmica|Test de estrés". Se muestra como lista en los paquetes de desarrollo web y, si el servicio está destacado, también en la home.
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
