<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

/**
 * Genera un dump SQL completo (estructura + datos) sin depender del binario mysqldump,
 * para que funcione igual en cualquier hosting compartido.
 */

$pdo = db();
$filename = 'backup-' . DB_NAME . '-' . date('Y-m-d_His') . '.sql';

header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

echo "-- Backup de {$filename}\n";
echo "-- Generado desde /admin/export.php\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $createRow = $pdo->query('SHOW CREATE TABLE `' . $table . '`')->fetch();
    $createSql = $createRow['Create Table'] ?? null;

    echo "-- --------------------------------------------------------\n";
    echo "-- Tabla `{$table}`\n";
    echo "-- --------------------------------------------------------\n\n";
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    echo $createSql . ";\n\n";

    $rowCount = 0;
    $stmt = $pdo->query('SELECT * FROM `' . $table . '`');
    while ($row = $stmt->fetch()) {
        $columns = array_map(fn (string $c) => '`' . $c . '`', array_keys($row));
        $values = array_map(function ($v) use ($pdo) {
            if ($v === null) {
                return 'NULL';
            }
            return $pdo->quote((string) $v);
        }, array_values($row));

        echo 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', $values) . ");\n";
        $rowCount++;
    }
    if ($rowCount > 0) {
        echo "\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
