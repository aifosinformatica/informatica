<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$stmt = db()->prepare('SELECT * FROM admins WHERE id = :id');
$stmt->execute(['id' => CURRENT_ADMIN_ID]);
$me = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $currentPassword = (string) ($_POST['current_password'] ?? '');

    if (!password_verify($currentPassword, $me['password_hash'])) {
        flash_set('error', 'Tu contraseña actual no es correcta.');
        redirect('/admin/profile.php');
    }

    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? '')) ?: null;
    $phone = trim((string) ($_POST['phone'] ?? '')) ?: null;
    $newPassword = (string) ($_POST['new_password'] ?? '');
    $newPasswordConfirm = (string) ($_POST['new_password_confirm'] ?? '');

    if ($username === '' || mb_strlen($username) < 3) {
        flash_set('error', 'El usuario debe tener al menos 3 caracteres.');
        redirect('/admin/profile.php');
    }
    if ($newPassword !== '' && mb_strlen($newPassword) < 8) {
        flash_set('error', 'La nueva contraseña debe tener al menos 8 caracteres.');
        redirect('/admin/profile.php');
    }
    if ($newPassword !== '' && $newPassword !== $newPasswordConfirm) {
        flash_set('error', 'Las contraseñas nuevas no coinciden.');
        redirect('/admin/profile.php');
    }

    try {
        if ($newPassword !== '') {
            db()->prepare('UPDATE admins SET username=:u, email=:e, phone=:p, password_hash=:h WHERE id=:id')
                ->execute([
                    'u' => $username, 'e' => $email, 'p' => $phone,
                    'h' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => CURRENT_ADMIN_ID,
                ]);
        } else {
            db()->prepare('UPDATE admins SET username=:u, email=:e, phone=:p WHERE id=:id')
                ->execute(['u' => $username, 'e' => $email, 'p' => $phone, 'id' => CURRENT_ADMIN_ID]);
        }
        flash_set('ok', 'Tus datos se actualizaron.');
    } catch (PDOException $e) {
        flash_set('error', str_contains($e->getMessage(), 'Duplicate') ? 'Ese usuario ya existe.' : 'No se pudo guardar.');
    }
    redirect('/admin/profile.php');
}

admin_page_start('Mi perfil', 'profile');
?>

<form method="post" class="form form--wide">
    <?= csrf_field() ?>

    <label>Usuario <input type="text" name="username" required value="<?= e($me['username']) ?>"></label>

    <div class="form-row form-row--2">
        <label>Email <input type="email" name="email" value="<?= e($me['email'] ?? '') ?>"></label>
        <label>Teléfono <input type="text" name="phone" value="<?= e($me['phone'] ?? '') ?>"></label>
    </div>

    <fieldset>
        <legend>Cambiar contraseña (opcional)</legend>
        <div class="form-row form-row--2">
            <label>Nueva contraseña <input type="password" name="new_password" autocomplete="new-password"></label>
            <label>Repetir nueva contraseña <input type="password" name="new_password_confirm" autocomplete="new-password"></label>
        </div>
    </fieldset>

    <label>Confirmá tu contraseña actual para guardar
        <input type="password" name="current_password" required autocomplete="current-password">
    </label>

    <button type="submit" class="btn btn--primary">Guardar cambios</button>
</form>

<?php admin_page_end(); ?>
