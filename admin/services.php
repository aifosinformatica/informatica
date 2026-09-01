<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/services.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle') {
        db()->prepare('UPDATE services SET visible = 1 - visible WHERE id = :id')->execute(['id' => $id]);
    } elseif ($action === 'feature') {
        db()->prepare('UPDATE services SET featured = 1 - featured WHERE id = :id')->execute(['id' => $id]);
    } elseif ($action === 'delete') {
        db()->prepare('DELETE FROM services WHERE id = :id')->execute(['id' => $id]);
        flash_set('ok', 'Servicio eliminado.');
    }
    redirect('/admin/services.php');
}

$categorias = db()->query('SELECT * FROM service_categories ORDER BY page, sort_order, name')->fetchAll();
$serviciosStmt = db()->prepare('SELECT * FROM services WHERE category_id = :cid ORDER BY sort_order, name');

admin_page_start('Servicios', 'services');
?>

<div class="admin-toolbar">
    <p>Todos los precios se guardan en USD y se muestran en pesos, redondeados a favor del negocio según la cotización vigente.</p>
    <a href="<?= e(url('/admin/service-form.php')) ?>" class="btn btn--primary btn--sm">+ Nuevo servicio</a>
</div>

<?php foreach ($categorias as $categoria): ?>
    <?php
    $serviciosStmt->execute(['cid' => $categoria['id']]);
    $servicios = $serviciosStmt->fetchAll();
    if (!$servicios) continue;
    ?>
    <h2><?= e($categoria['name']) ?> <small style="color:var(--text-muted);font-weight:400;">(<?= e($categoria['page']) ?>)</small></h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Nombre</th><th>Tipo</th><th>USD</th><th>Precio hoy</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($servicios as $servicio): ?>
                <tr>
                    <td><?= e($servicio['name']) ?></td>
                    <td><?= e($servicio['price_type']) ?></td>
                    <td><?= $servicio['price_usd'] !== null ? 'USD ' . e(number_format((float) $servicio['price_usd'], 2, ',', '.')) : '—' ?></td>
                    <td><?= e(service_price_label($servicio)) ?></td>
                    <td>
                        <span class="badge <?= $servicio['visible'] ? 'badge--on' : 'badge--off' ?>"><?= $servicio['visible'] ? 'Visible' : 'Oculto' ?></span>
                        <?php if ($servicio['featured']): ?><span class="badge badge--on">Destacado</span><?php endif; ?>
                    </td>
                    <td class="actions">
                        <a href="<?= e(url('/admin/service-form.php')) ?>?id=<?= (int) $servicio['id'] ?>">Editar</a>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $servicio['id'] ?>"><button type="submit">Ocultar/mostrar</button></form>
                        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="feature"><input type="hidden" name="id" value="<?= (int) $servicio['id'] ?>"><button type="submit">Destacar</button></form>
                        <form method="post" onsubmit="return confirm('¿Eliminar este servicio? No se puede deshacer.');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $servicio['id'] ?>"><button type="submit">Eliminar</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endforeach; ?>

<?php admin_page_end(); ?>
