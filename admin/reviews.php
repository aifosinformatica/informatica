<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/includes/layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $rating = max(1, min(5, (int) ($_POST['rating'] ?? 5)));
        $date = (string) ($_POST['review_date'] ?? '') ?: null;
        $url = trim((string) ($_POST['url'] ?? '')) ?: null;

        if ($name === '' || $body === '') {
            flash_set('error', 'Nombre y texto de la reseña son obligatorios. No se cargan testimonios falsos.');
        } else {
            db()->prepare(
                'INSERT INTO reviews (name, rating, body, review_date, url) VALUES (:name, :rating, :body, :date, :url)'
            )->execute(['name' => $name, 'rating' => $rating, 'body' => $body, 'date' => $date, 'url' => $url]);
            flash_set('ok', 'Reseña cargada.');
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('UPDATE reviews SET visible = 1 - visible WHERE id = :id')->execute(['id' => $id]);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM reviews WHERE id = :id')->execute(['id' => $id]);
        flash_set('ok', 'Reseña eliminada.');
    }
    redirect('/admin/reviews.php');
}

$reviews = db()->query('SELECT * FROM reviews ORDER BY sort_order, review_date DESC')->fetchAll();

admin_page_start('Reseñas', 'reviews');
?>

<p>Solo cargá reseñas reales de clientes. No se crean testimonios falsos.</p>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead><tr><th>Nombre</th><th>Puntaje</th><th>Texto</th><th>Fecha</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $review): ?>
            <tr>
                <td><?= e($review['name']) ?></td>
                <td><?= str_repeat('★', (int) $review['rating']) ?></td>
                <td><?= e(mb_strimwidth($review['body'], 0, 80, '…')) ?></td>
                <td><?= e((string) $review['review_date']) ?></td>
                <td><span class="badge <?= $review['visible'] ? 'badge--on' : 'badge--off' ?>"><?= $review['visible'] ? 'Visible' : 'Oculta' ?></span></td>
                <td class="actions">
                    <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $review['id'] ?>"><button type="submit">Ocultar/mostrar</button></form>
                    <form method="post" onsubmit="return confirm('¿Eliminar esta reseña?');"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $review['id'] ?>"><button type="submit">Eliminar</button></form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2>Nueva reseña</h2>
<form method="post" class="form form--wide">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-row form-row--2">
        <label>Nombre <input type="text" name="name" required></label>
        <label>Puntaje
            <select name="rating">
                <?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
            </select>
        </label>
    </div>
    <label>Texto <textarea name="body" rows="3" required></textarea></label>
    <div class="form-row form-row--2">
        <label>Fecha <input type="date" name="review_date"></label>
        <label>Enlace (opcional, a la reseña original) <input type="url" name="url"></label>
    </div>
    <button type="submit" class="btn btn--primary">Cargar reseña</button>
</form>

<?php admin_page_end(); ?>
