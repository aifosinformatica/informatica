<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

function count_active_admins(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM admins WHERE active = 1")->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'toggle') {
        if ($id === CURRENT_ADMIN_ID) {
            flash_set('error', 'No podés deshabilitar tu propio usuario. Pedile a otro administrador que lo haga.');
        } else {
            $stmt = db()->prepare('SELECT active FROM admins WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $isActive = (int) $stmt->fetchColumn();

            if ($isActive === 1 && count_active_admins() <= 1) {
                flash_set('error', 'No podés deshabilitar el único administrador activo que queda.');
            } else {
                db()->prepare('UPDATE admins SET active = 1 - active WHERE id = :id')->execute(['id' => $id]);
                flash_set('ok', 'Estado actualizado.');
            }
        }
    } elseif ($action === 'delete') {
        if ($id === CURRENT_ADMIN_ID) {
            flash_set('error', 'No podés eliminar tu propio usuario mientras estás logueado con él.');
        } elseif (count_active_admins() <= 1) {
            flash_set('error', 'No podés eliminar el único administrador activo que queda.');
        } else {
            db()->prepare('DELETE FROM admins WHERE id = :id')->execute(['id' => $id]);
            flash_set('ok', 'Usuario eliminado.');
        }
    }
    redirect('/admin/users.php');
}

$admins = db()->query('SELECT * FROM admins ORDER BY username')->fetchAll();

admin_page_start('Usuarios', 'users');
?>

<div class="admin-toolbar">
    <p>Todos los administradores tienen el mismo nivel de acceso: no hay roles ni permisos diferenciados.</p>
    <a href="<?= e(url('/admin/user-form.php')) ?>" class="btn btn--primary btn--sm">+ Nuevo usuario</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Usuario</th><th>Email</th><th>Teléfono</th><th>Último ingreso</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($admins as $admin): ?>
            <tr>
                <td><?= e($admin['username']) ?><?= (int) $admin['id'] === CURRENT_ADMIN_ID ? ' <small>(vos)</small>' : '' ?></td>
                <td><?= e($admin['email'] ?? '') ?></td>
                <td><?= e($admin['phone'] ?? '') ?></td>
                <td><?= $admin['last_login_at'] ? e(date('d/m/Y H:i', strtotime((string) $admin['last_login_at']))) : '—' ?></td>
                <td><span class="badge <?= $admin['active'] ? 'badge--on' : 'badge--off' ?>"><?= $admin['active'] ? 'Activo' : 'Deshabilitado' ?></span></td>
                <td class="actions">
                    <a href="<?= e(url('/admin/user-form.php')) ?>?id=<?= (int) $admin['id'] ?>">Editar</a>
                    <form method="post" onsubmit="return confirm('¿Cambiar el estado de este usuario?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                        <button type="submit">Habilitar/deshabilitar</button>
                    </form>
                    <form method="post" onsubmit="return confirm('¿Eliminar este usuario? No se puede deshacer.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php admin_page_end(); ?>
