<?php
class Audit
{
    public static function log(string $action, string $entity, ?string $entityId = null, array $meta = []): void
    {
        try {
            Database::execute('INSERT INTO audit_logs(user_id, action, entity, entity_id, meta, ip_address, user_agent) VALUES (?,?,?,?,?,?,?)', [
                Auth::id(), $action, $entity, $entityId, $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? null, substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)
            ]);
        } catch (Throwable $e) {
            error_log('Audit failed: ' . $e->getMessage());
        }
    }
}
