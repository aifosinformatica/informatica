<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';

if (!empty($_SESSION['admin_id'])) {
    redirect('/admin/');
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Sesión expirada, volvé a intentar.';
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $attemptStmt = db()->prepare('SELECT * FROM login_attempts WHERE ip = :ip');
        $attemptStmt->execute(['ip' => $ip]);
        $attemptRow = $attemptStmt->fetch();

        $locked = $attemptRow && $attemptRow['locked_until'] && strtotime($attemptRow['locked_until']) > time();

        if ($locked) {
            $error = 'Demasiados intentos fallidos. Probá de nuevo en unos minutos.';
        } else {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $stmt = db()->prepare('SELECT * FROM admins WHERE username = :u LIMIT 1');
            $stmt->execute(['u' => $username]);
            $admin = $stmt->fetch();

            if ($admin && !password_verify($password, $admin['password_hash'])) {
                $admin = null; // contraseña incorrecta: se trata igual que "no existe" para el resto del flujo
            }

            if ($admin && (int) $admin['active'] !== 1) {
                $error = 'Tu usuario está deshabilitado. Consultá a otro administrador.';
                log_login_attempt((int) $admin['id'], $username, 'login_failed');
            } elseif ($admin) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $admin['id'];
                db()->prepare('DELETE FROM login_attempts WHERE ip = :ip')->execute(['ip' => $ip]);
                db()->prepare('UPDATE admins SET last_login_at = NOW() WHERE id = :id')->execute(['id' => $admin['id']]);
                log_login_attempt((int) $admin['id'], $username, 'login_success');
                redirect('/admin/');
            } else {
                $attempts = (int) ($attemptRow['attempts'] ?? 0) + 1;
                $lockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', time() + 300) : null;
                db()->prepare(
                    'INSERT INTO login_attempts (ip, attempts, locked_until) VALUES (:ip, :attempts, :locked)
                     ON DUPLICATE KEY UPDATE attempts = :attempts2, locked_until = :locked2'
                )->execute([
                    'ip' => $ip,
                    'attempts' => $attempts,
                    'locked' => $lockedUntil,
                    'attempts2' => $attempts,
                    'locked2' => $lockedUntil,
                ]);
                log_login_attempt(null, $username, 'login_failed');

                $error = 'Usuario o contraseña incorrectos.';
            }
        }
    }
}
?>
<!doctype html>
<html lang="es-AR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Ingresar · Administración</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/style.css')) ?>">
</head>
<body class="admin-auth-page">
<main class="container auth-box">
    <h1>Administración</h1>
    <?php if ($error): ?><p class="alert alert--error"><?= e($error) ?></p><?php endif; ?>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <label>Usuario <input type="text" name="username" required autocomplete="username"></label>
        <label>Contraseña <input type="password" name="password" required autocomplete="current-password"></label>
        <button type="submit" class="btn btn--primary btn--block">Ingresar</button>
    </form>
</main>
</body>
</html>
