<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create' || $action === 'update') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $page = (string) ($_POST['page'] ?? 'reparacion-pc');
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);

        if ($name === '') {
            flash_set('error', 'El nombre es obligatorio.');
        } else {
            $slug = $page . '-' . slugify($name);
            if ($action === 'create') {
                db()->prepare(
                    'INSERT INTO service_categories (page, name, slug, sort_order) VALUES (:page, :name, :slug, :sort)'
                )->execute(['page' => $page, 'name' => $name, 'slug' => $slug, 'sort' => $sortOrder]);
                flash_set('ok', 'Categoría creada.');
            } else {
                $id = (int) ($_POST['id'] ?? 0);
                db()->prepare(
                    'UPDATE service_categories SET name = :name, page = :page, sort_order = :sort WHERE id = :id'
                )->execute(['name' => $name, 'page' => $page, 'sort' => $sortOrder, 'id' => $id]);
                flash_set('ok', 'Categoría actualizada.');
            }
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE service_categories SET visible = 1 - visible WHERE id = :id')->execute(['id' => $id]);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM service_categories WHERE id = :id')->execute(['id' => $id]);
        flash_set('ok', 'Categoría eliminada (y sus servicios).');
    }
    redirect('/admin/categories.php');
}

$categorias = db()->query('SELECT * FROM service_categories ORDER BY page, sort_order, name')->fetchAll();
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$editing = null;
if ($editId) {
    $stmt = db()->prepare('SELECT * FROM service_categories WHERE id = :id');
    $stmt->execute(['id' => $editId]);
    $editing = $stmt->fetch() ?: null;
}

admin_page_start('Categorías', 'categories');
?>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Nombre</th><th>Página</th><th>Orden</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($categorias as $cat): ?>
            <tr>
                <td><?= e($cat['name']) ?></td>
                <td><?= e($cat['page']) ?></td>
                <td><?= (int) $cat['sort_order'] ?></td>
                <td><span class="badge <?= $cat['visible'] ? 'badge--on' : 'badge--off' ?>"><?= $cat['visible'] ? 'Visible' : 'Oculta' ?></span></td>
                <td class="actions">
                    <a href="?edit=<?= (int) $cat['id'] ?>">Editar</a>
                    <form method="post" onsubmit="return confirm('¿Ocultar/mostrar esta categoría?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                        <button type="submit">Ocultar/mostrar</button>
                    </form>
                    <form method="post" onsubmit="return confirm('¿Eliminar esta categoría y todos sus servicios? No se puede deshacer.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2><?= $editing ? 'Editar categoría' : 'Nueva categoría' ?></h2>
<form method="post" class="form form--wide">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int) $editing['id'] ?>"><?php endif; ?>
    <div class="form-row form-row--2">
        <label>Nombre <input type="text" name="name" required value="<?= e($editing['name'] ?? '') ?>"></label>
        <label>Página
            <select name="page">
                <option value="reparacion-pc" <?= ($editing['page'] ?? '') === 'reparacion-pc' ? 'selected' : '' ?>>Reparación PC</option>
                <option value="desarrollo-web" <?= ($editing['page'] ?? '') === 'desarrollo-web' ? 'selected' : '' ?>>Desarrollo web</option>
            </select>
        </label>
    </div>
    <label>Orden <input type="number" name="sort_order" value="<?= e((string) ($editing['sort_order'] ?? 0)) ?>"></label>
    <button type="submit" class="btn btn--primary">Guardar</button>
</form>

<?php admin_page_end(); ?>
