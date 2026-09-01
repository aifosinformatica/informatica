<?php

declare(strict_types=1);

require_once __DIR__ . '/session.php';

if (empty($_SESSION['admin_id'])) {
    redirect('/admin/login.php');
}

// Si el usuario fue desactivado o eliminado mientras tenía la sesión abierta, cortamos acá.
$stmt = db()->prepare('SELECT active FROM admins WHERE id = :id');
$stmt->execute(['id' => $_SESSION['admin_id']]);
$active = $stmt->fetchColumn();

if ($active === false || (int) $active !== 1) {
    session_destroy();
    redirect('/admin/login.php');
}

define('CURRENT_ADMIN_ID', (int) $_SESSION['admin_id']);
