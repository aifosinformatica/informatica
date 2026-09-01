<?php

declare(strict_types=1);

/**
 * Guarda la sesión del panel en la tabla `admin_sessions` en vez de en archivos.
 * Esto permite listar sesiones activas y cerrarlas de forma remota: borrar la fila
 * es suficiente, la próxima vez que ese navegador pida una página, read() no
 * encuentra datos y PHP la trata como una sesión nueva (sin admin_id).
 */
final class DbSessionHandler implements SessionHandlerInterface
{
    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = db()->prepare('SELECT payload FROM admin_sessions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? (string) $row['payload'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $adminId = $_SESSION['admin_id'] ?? null;
        $stmt = db()->prepare(
            'INSERT INTO admin_sessions (id, admin_id, ip, user_agent, payload)
             VALUES (:id, :admin_id, :ip, :ua, :payload)
             ON DUPLICATE KEY UPDATE
                payload = VALUES(payload),
                admin_id = COALESCE(VALUES(admin_id), admin_id),
                ip = VALUES(ip),
                user_agent = VALUES(user_agent)'
        );
        return $stmt->execute([
            'id' => $id,
            'admin_id' => $adminId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'ua' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            'payload' => $data,
        ]);
    }

    public function destroy(string $id): bool
    {
        db()->prepare('DELETE FROM admin_sessions WHERE id = :id')->execute(['id' => $id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = db()->prepare(
            'DELETE FROM admin_sessions WHERE last_activity_at < DATE_SUB(NOW(), INTERVAL :sec SECOND)'
        );
        $stmt->execute(['sec' => $max_lifetime]);
        return $stmt->rowCount();
    }
}
