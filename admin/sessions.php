<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $sessionId = (string) ($_POST['session_id'] ?? '');
    if ($sessionId === session_id()) {
        flash_set('error', 'No podés cerrar tu propia sesión desde acá — usá "Salir".');
    } else {
        db()->prepare('DELETE FROM admin_sessions WHERE id = :id')->execute(['id' => $sessionId]);
        flash_set('ok', 'Sesión cerrada. Esa persona va a tener que volver a iniciar sesión.');
    }
    redirect('/admin/sessions.php');
}

$sessions = db()->query(
    'SELECT s.*, a.username FROM admin_sessions s
     LEFT JOIN admins a ON a.id = s.admin_id
     ORDER BY s.last_activity_at DESC'
)->fetchAll();

$currentSessionId = session_id();

admin_page_start('Sesiones activas', 'sessions');
?>

<p>Cada fila es un navegador con sesión abierta en el panel. Cerrarla obliga a volver a iniciar sesión ahí.</p>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Usuario</th><th>IP</th><th>Navegador</th><th>Última actividad</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($sessions as $s): ?>
            <tr>
                <td><?= e($s['username'] ?? '—') ?><?= $s['id'] === $currentSessionId ? ' <span class="badge badge--on">esta sesión</span>' : '' ?></td>
                <td><?= e($s['ip'] ?? '') ?></td>
                <td><?= e(mb_strimwidth((string) ($s['user_agent'] ?? ''), 0, 60, '…')) ?></td>
                <td><?= e(date('d/m/Y H:i', strtotime((string) $s['last_activity_at']))) ?></td>
                <td>
                    <?php if ($s['id'] !== $currentSessionId): ?>
                        <form method="post" onsubmit="return confirm('¿Cerrar esta sesión de forma remota?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="session_id" value="<?= e($s['id']) ?>">
                            <button type="submit">Cerrar sesión</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$sessions): ?>
            <tr><td colspan="5">No hay sesiones activas registradas.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_page_end(); ?>
