<?php
class AuthModel extends Model
{
    public function findByUsername(string $username): ?array
    {
        return $this->one("SELECT u.*, r.code AS role_code, r.name AS role_name, o.name AS outlet_name FROM users u JOIN roles r ON r.id=u.role_id LEFT JOIN outlets o ON o.id=u.outlet_id WHERE u.username=? AND u.is_active=1 LIMIT 1", [$username]);
    }
    public function rehashPassword(int $id, string $password): void
    {
        $this->execSql("UPDATE users SET password=?, updated_at=? WHERE id=?", [password_hash($password, PASSWORD_BCRYPT), now(), $id]);
    }
}
