<?php
class Audit
{
    public static function log(string $action, ?string $table = null, ?int $recordId = null, $old = null, $new = null): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("INSERT INTO audit_logs (outlet_id,user_id,action,table_name,record_id,old_values,new_values,ip_address,user_agent,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $user = Auth::user();
            $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)($user['outlet_id'] ?? app_config('default_outlet_id'));
            $stmt->execute([
                $outletId, Auth::id(), $action, $table, $recordId,
                $old ? json_encode($old, JSON_UNESCAPED_UNICODE) : null,
                $new ? json_encode($new, JSON_UNESCAPED_UNICODE) : null,
                $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null, now()
            ]);
        } catch (Throwable $e) { error_log('[AUDIT] '.$e->getMessage()); }
    }
}
