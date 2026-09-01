<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$admin = null;
if ($id) {
    $stmt = db()->prepare('SELECT * FROM admins WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $admin = $stmt->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        flash_set('error', 'Sesión expirada, volvé a intentar.');
        redirect('/admin/user-form.php' . ($id ? "?id={$id}" : ''));
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? '')) ?: null;
    $phone = trim((string) ($_POST['phone'] ?? '')) ?: null;
    $active = isset($_POST['active']) ? 1 : 0;
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($username === '' || mb_strlen($username) < 3) {
        flash_set('error', 'El usuario debe tener al menos 3 caracteres.');
        redirect('/admin/user-form.php' . ($id ? "?id={$id}" : ''));
    }
    if (!$admin && mb_strlen($password) < 8) {
        flash_set('error', 'La contraseña debe tener al menos 8 caracteres.');
        redirect('/admin/user-form.php');
    }
    if ($password !== '' && $password !== $passwordConfirm) {
        flash_set('error', 'Las contraseñas no coinciden.');
        redirect('/admin/user-form.php' . ($id ? "?id={$id}" : ''));
    }
    if ($admin && $id === CURRENT_ADMIN_ID && $active === 0) {
        flash_set('error', 'No podés deshabilitarte a vos mismo desde acá. Usá otro usuario para eso.');
        redirect("/admin/user-form.php?id={$id}");
    }

    try {
        if ($admin) {
            if ($password !== '') {
                db()->prepare(
                    'UPDATE admins SET username=:username, email=:email, phone=:phone, active=:active, password_hash=:hash WHERE id=:id'
                )->execute([
                    'username' => $username, 'email' => $email, 'phone' => $phone, 'active' => $active,
                    'hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => $admin['id'],
                ]);
            } else {
                db()->prepare(
                    'UPDATE admins SET username=:username, email=:email, phone=:phone, active=:active WHERE id=:id'
                )->execute([
                    'username' => $username, 'email' => $email, 'phone' => $phone, 'active' => $active, 'id' => $admin['id'],
                ]);
            }
            flash_set('ok', 'Usuario actualizado.');
        } else {
            db()->prepare(
                'INSERT INTO admins (username, email, phone, active, password_hash) VALUES (:username, :email, :phone, :active, :hash)'
            )->execute([
                'username' => $username, 'email' => $email, 'phone' => $phone, 'active' => $active,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            flash_set('ok', 'Usuario creado.');
        }
        redirect('/admin/users.php');
    } catch (PDOException $e) {
        flash_set('error', str_contains($e->getMessage(), 'Duplicate') ? 'Ese usuario ya existe.' : 'No se pudo guardar.');
        redirect('/admin/user-form.php' . ($id ? "?id={$id}" : ''));
    }
}

admin_page_start($admin ? 'Editar usuario' : 'Nuevo usuario', 'users');
?>

<form method="post" class="form form--wide">
    <?= csrf_field() ?>
    <?php if ($admin): ?><input type="hidden" name="id" value="<?= (int) $admin['id'] ?>"><?php endif; ?>

    <label>Usuario <input type="text" name="username" required value="<?= e($admin['username'] ?? '') ?>"></label>

    <div class="form-row form-row--2">
        <label>Email <input type="email" name="email" value="<?= e($admin['email'] ?? '') ?>"></label>
        <label>Teléfono <input type="text" name="phone" value="<?= e($admin['phone'] ?? '') ?>"></label>
    </div>

    <div class="form-row form-row--2">
        <label><?= $admin ? 'Nueva contraseña (dejar en blanco para no cambiarla)' : 'Contraseña' ?>
            <input type="password" name="password" autocomplete="new-password" <?= $admin ? '' : 'required minlength="8"' ?>>
        </label>
        <label>Repetir contraseña
            <input type="password" name="password_confirm" autocomplete="new-password" <?= $admin ? '' : 'required minlength="8"' ?>>
        </label>
    </div>

    <?php $isSelf = $admin && (int) $admin['id'] === CURRENT_ADMIN_ID; ?>
    <label class="checkbox">
        <input type="checkbox" name="active" <?= ($admin['active'] ?? 1) ? 'checked' : '' ?> <?= $isSelf ? 'disabled' : '' ?>>
        <span>Usuario activo (puede iniciar sesión)</span>
    </label>
    <?php if ($isSelf): ?>
        <input type="hidden" name="active" value="1">
        <p style="font-size:.85rem;">No podés deshabilitarte a vos mismo — pedile a otro administrador que lo haga si hace falta.</p>
    <?php endif; ?>

    <button type="submit" class="btn btn--primary"><?= $admin ? 'Guardar cambios' : 'Crear usuario' ?></button>
</form>

<?php admin_page_end(); ?>
