<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $id = (int) ($_POST['id'] ?? 0);
    $status = (string) ($_POST['status'] ?? 'nuevo');
    if (in_array($status, ['nuevo', 'contactado', 'cerrado'], true)) {
        db()->prepare('UPDATE contact_requests SET status = :status WHERE id = :id')
            ->execute(['status' => $status, 'id' => $id]);
    }
    redirect('/admin/contacts.php');
}

$requests = db()->query('SELECT * FROM contact_requests ORDER BY created_at DESC LIMIT 200')->fetchAll();

admin_page_start('Consultas recibidas', 'contacts');
?>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Fecha</th><th>Origen</th><th>Nombre</th><th>WhatsApp</th><th>Mensaje</th><th>Estado</th></tr></thead>
        <tbody>
        <?php foreach ($requests as $r): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i', strtotime((string) $r['created_at']))) ?></td>
                <td><?= e($r['origin']) ?></td>
                <td><?= e($r['name']) ?><?php if ($r['business_name']): ?><br><small><?= e($r['business_name']) ?></small><?php endif; ?></td>
                <td>
                    <?php if ($r['whatsapp']): ?>
                        <a href="https://wa.me/<?= e(preg_replace('/[^0-9]/', '', (string) $r['whatsapp'])) ?>" target="_blank" rel="noopener"><?= e($r['whatsapp']) ?></a>
                    <?php endif; ?>
                    <?php if ($r['email']): ?><br><small><?= e($r['email']) ?></small><?php endif; ?>
                </td>
                <td><?= e(mb_strimwidth((string) ($r['message'] ?? ''), 0, 100, '…')) ?><?php if ($r['device']): ?><br><small>Equipo: <?= e($r['device']) ?></small><?php endif; ?></td>
                <td>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <select name="status" onchange="this.form.submit()">
                            <?php foreach (['nuevo' => 'Nuevo', 'contactado' => 'Contactado', 'cerrado' => 'Cerrado'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $r['status'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$requests): ?>
            <tr><td colspan="6">Todavía no hay consultas.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php admin_page_end(); ?>
