<?php
class UserModel extends Model
{
    /**
     * Get all roles.
     */
    public function roles(?int $outletId = null): array
    {
        if ($outletId !== null) {
            return $this->all("SELECT * FROM roles WHERE code IN ('administrator', 'cashier') ORDER BY id");
        }
        return $this->all("SELECT * FROM roles ORDER BY id");
    }

    /**
     * Get all outlets (for dropdown).
     */
    public function outlets(): array
    {
        return $this->all("SELECT * FROM outlets ORDER BY name");
    }

    /**
     * Get all active outlets.
     */
    public function activeOutlets(?int $outletId = null): array
    {
        if ($outletId !== null) {
            return $this->all("SELECT * FROM outlets WHERE id = ? AND is_active = 1 ORDER BY name ASC", [$outletId]);
        }
        return $this->all("SELECT * FROM outlets WHERE is_active = 1 ORDER BY is_hq DESC, name ASC");
    }

    /**
     * List all users with role and outlet info.
     */
    public function list(?int $outletId = null): array
    {
        $whereSql = '';
        $params = [];
        if ($outletId !== null) {
            $whereSql = 'WHERE u.outlet_id = ? AND r.code <> \'super_admin\'';
            $params = [$outletId];
        }
        return $this->all("
            SELECT u.*, r.name AS role_name, r.code AS role_code, o.name AS outlet_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            LEFT JOIN outlets o ON o.id = u.outlet_id
            {$whereSql}
            ORDER BY u.is_active DESC, u.name ASC
        ", $params);
    }

    /**
     * Find user by ID.
     */
    public function findById(int $id): ?array
    {
        return $this->one("
            SELECT u.*, r.name AS role_name, r.code AS role_code, o.name AS outlet_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            LEFT JOIN outlets o ON o.id = u.outlet_id
            WHERE u.id = ?
        ", [$id]);
    }

    /**
     * Find user by username.
     */
    public function findByUsername(string $username): ?array
    {
        return $this->one("SELECT * FROM users WHERE username = ? LIMIT 1", [$username]);
    }

    /**
     * Payroll summary (latest 50).
     */
    public function payrollSummary(?int $outletId = null): array
    {
        $whereSql = '';
        $params = [];
        if ($outletId !== null) {
            $whereSql = 'WHERE pe.outlet_id = ?';
            $params = [$outletId];
        }
        return $this->all("
            SELECT pe.business_date, u.name, pe.amount, pe.source
            FROM payroll_expenses pe
            JOIN users u ON u.id = pe.user_id
            {$whereSql}
            ORDER BY pe.business_date DESC, pe.id DESC
            LIMIT 50
        ", $params);
    }

    /**
     * Create or update a user.
     */
    public function store(array $d): int
    {
        $isSuperAdmin = (Auth::role() === 'super_admin');
        $id          = (int)($d['id'] ?? 0);
        $outletId    = $isSuperAdmin ? ((int)($d['outlet_id'] ?? 0) ?: null) : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id', 1));
        $roleId      = (int)($d['role_id'] ?? 0);
        $name        = trim($d['name'] ?? '');
        $username    = trim($d['username'] ?? '');
        $email       = trim($d['email'] ?? '');
        $phone       = trim($d['phone'] ?? '');
        $password    = trim($d['password'] ?? '');
        $dailySalary = (float)($d['daily_salary'] ?? 0);
        $isActive    = (int)($d['is_active'] ?? 1);

        if ($name === '') {
            throw new RuntimeException('Nama user wajib diisi.');
        }

        if (!$isSuperAdmin) {
            $roleObj = $this->one("SELECT code FROM roles WHERE id=?", [$roleId]);
            if ($roleObj && $roleObj['code'] === 'super_admin') {
                throw new RuntimeException('Admin Cabang tidak dapat membuat atau mengubah user Super Admin.');
            }
            if ($id > 0) {
                $existUser = $this->findById($id);
                if ($existUser && ($existUser['role_code'] === 'super_admin' || (int)$existUser['outlet_id'] !== (int)$outletId)) {
                    throw new RuntimeException('Anda tidak memiliki wewenang memodifikasi user ini.');
                }
            }
        }

        if ($id > 0) {
            // Update existing user
            // Check username uniqueness (exclude self)
            if ($username !== '') {
                $existing = $this->one("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1", [$username, $id]);
                if ($existing) {
                    throw new RuntimeException("Username \"{$username}\" sudah digunakan.");
                }
            }

            $params = [
                $outletId,
                $roleId,
                $name,
                $email,
                $phone,
                $dailySalary,
                $isActive,
                $id
            ];
            $this->execSql("
                UPDATE users SET outlet_id=?, role_id=?, name=?, email=?, phone=?,
                daily_salary=?, is_active=?, updated_at=NOW() WHERE id=?
            ", $params);

            // Update username if provided
            if ($username !== '') {
                $this->execSql("UPDATE users SET username=? WHERE id=?", [$username, $id]);
            }

            // Update password if provided
            if ($password !== '') {
                $this->execSql("UPDATE users SET password=? WHERE id=?", [
                    password_hash($password, PASSWORD_DEFAULT),
                    $id
                ]);
            }

            return $id;
        }

        // Create new user
        if ($username === '') {
            throw new RuntimeException('Username wajib diisi.');
        }

        // Check username uniqueness
        $existing = $this->one("SELECT id FROM users WHERE username = ? LIMIT 1", [$username]);
        if ($existing) {
            throw new RuntimeException("Username \"{$username}\" sudah digunakan.");
        }

        if ($password === '') {
            $password = 'admin123';
        }

        $this->execSql("
            INSERT INTO users (outlet_id, role_id, name, username, email, phone, password, daily_salary, is_active, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,NOW(),NOW())
        ", [
            $outletId,
            $roleId,
            $name,
            $username,
            $email,
            $phone,
            password_hash($password, PASSWORD_DEFAULT),
            $dailySalary,
            $isActive
        ]);

        return (int)Database::connection()->lastInsertId();
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(int $id): void
    {
        $user = $this->findById($id);
        if (!$user) {
            throw new RuntimeException('User tidak ditemukan.');
        }

        if (Auth::role() !== 'super_admin') {
            $myOutletId = (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id', 1));
            if ($user['role_code'] === 'super_admin' || (int)$user['outlet_id'] !== $myOutletId) {
                throw new RuntimeException('Anda tidak memiliki wewenang mengubah status user ini.');
            }
        }

        // Prevent deactivating the last super_admin
        if ($user['role_code'] === 'super_admin' && $user['is_active']) {
            $activeAdminCount = $this->one("
                SELECT COUNT(*) AS cnt FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE r.code = 'super_admin' AND u.is_active = 1
            ");
            if ($activeAdminCount && (int)$activeAdminCount['cnt'] <= 1) {
                throw new RuntimeException('Tidak bisa menonaktifkan Super Admin terakhir.');
            }
        }

        $newStatus = $user['is_active'] ? 0 : 1;
        $this->execSql("UPDATE users SET is_active = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $id]);
    }

    /**
     * Reset user password to a given value (or default).
     */
    public function resetPassword(int $id, string $newPassword = 'admin123'): void
    {
        $user = $this->findById($id);
        if (!$user) {
            throw new RuntimeException('User tidak ditemukan.');
        }

        if (Auth::role() !== 'super_admin') {
            $myOutletId = (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id', 1));
            if ($user['role_code'] === 'super_admin' || (int)$user['outlet_id'] !== $myOutletId) {
                throw new RuntimeException('Anda tidak memiliki wewenang mereset password user ini.');
            }
        }

        if ($newPassword === '') {
            $newPassword = 'admin123';
        }

        $this->execSql("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?", [
            password_hash($newPassword, PASSWORD_DEFAULT),
            $id
        ]);
    }
}
