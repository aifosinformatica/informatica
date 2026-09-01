<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$labels = [
    'login_success' => 'Ingreso exitoso',
    'login_failed' => 'Ingreso fallido',
    'logout' => 'Cierre de sesión',
];

$rows = db()->query(
    'SELECT l.*, a.username AS admin_username FROM login_audit l
     LEFT JOIN admins a ON a.id = l.admin_id
     ORDER BY l.created_at DESC LIMIT 300'
)->fetchAll();

admin_page_start('Auditoría de accesos', 'audit');
?>

<p>Últimos 300 eventos de ingreso al panel.</p>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Evento</th><th>Usuario</th><th>IP</th><th>Navegador</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i:s', strtotime((string) $row['created_at']))) ?></td>
                <td>
                    <span class="badge <?= $row['action'] === 'login_failed' ? 'badge--off' : 'badge--on' ?>">
                        <?= e($labels[$row['action']] ?? $row['action']) ?>
                    </span>
                </td>
                <td><?= e($row['admin_username'] ?? $row['username_attempted'] ?? '—') ?></td>
                <td><?= e($row['ip'] ?? '') ?></td>
                <td><?= e(mb_strimwidth((string) ($row['user_agent'] ?? ''), 0, 60, '…')) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="5">Todavía no hay eventos registrados.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_page_end(); ?>
