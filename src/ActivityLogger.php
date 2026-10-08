<?php
namespace App;

class ActivityLogger
{
    private const BLOCKED_KEYS = [
        'password', 'password_confirmation', 'senha',
        'token', 'cpf', 'crm', 'cartao', 'cvv',
    ];

    public static function log(
    string  $action,
    ?string $entity = null,
    ?int    $entityId = null,
    ?string $description = null,
    array   $metadata = [],
    array   $tags = []   // 👈 NOVO
): void {
        try {
            $pdo  = Database::conn();
            $user = Auth::user();

            $stmt = $pdo->prepare("
    INSERT INTO activity_logs
        (user_id, user_name, user_role, action, entity, entity_id,
         tags, description, ip_address, user_agent, metadata)
    VALUES
        (:user_id, :user_name, :user_role, :action, :entity, :entity_id,
         :tags, :description, :ip, :ua, :metadata)
");

            $stmt->execute([
                ':user_id'     => $user['id']    ?? null,
                ':user_name'   => $user['name']  ?? null,
                ':user_role'   => $user['role']  ?? null,
                ':action'      => $action,
                ':entity'      => $entity,
                ':entity_id'   => $entityId,
                ':tags'        => $tags ? implode(',', $tags) : null,   // 👈
                ':description' => $description,
                ':ip'          => self::clientIp(),
                ':ua'          => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ':metadata'    => $metadata
                                    ? json_encode(self::sanitize($metadata), JSON_UNESCAPED_UNICODE)
                                    : null,
            ]);
        } catch (\Throwable $e) {
            error_log('[ActivityLogger] ' . $e->getMessage());
        }
    }

    private static function sanitize(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (in_array(strtolower((string)$key), self::BLOCKED_KEYS, true)) {
                $out[$key] = '***';
            } elseif (is_array($value)) {
                $out[$key] = self::sanitize($value);
            } else {
                $out[$key] = $value;
            }
        }
        return $out;
    }

    private static function clientIp(): ?string
    {
        foreach (['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return null;
    }
}