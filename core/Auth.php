<?php
class Auth
{
    public static function user(): ?array
    {
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            return null;
        }

        return $user;
    }

    public static function id(): ?int { return self::user()['id'] ?? null; }
    public static function role(): ?string { return self::user()['role_code'] ?? null; }
    public static function check(): bool { return !empty($_SESSION['user']); }
    public static function requireLogin(): void { if (!self::check()) { header('Location: ' . url('/login')); exit; } }
    public static function can(array $roles): bool { return in_array(self::role(), $roles, true); }
    public static function requireRoles(array $roles): void { self::requireLogin(); if (!self::can($roles)) { http_response_code(403); include __DIR__ . '/../views/errors/403.php'; exit; } }

    /** Get current user's outlet_id */
    public static function outletId(): int
    {
        $user = self::user();
        return (int)($user['outlet_id'] ?? app_config('default_outlet_id', 1));
    }

    /** Check if current user is HQ / pusat (super_admin accessing root or HQ outlet) */
    public static function isHQ(): bool
    {
        if (self::role() !== 'super_admin') {
            return false;
        }
        if (function_exists('branch_context')) {
            $branch = branch_context();
            // HQ = root slug or outlet marked as is_hq
            return ($branch['slug'] ?? '') === '' || !empty($branch['is_hq']);
        }
        return true;
    }

    /** Check if user can access a specific branch/outlet */
    public static function canAccessBranch(int $outletId): bool
    {
        // Super admin can access any branch
        if (self::role() === 'super_admin') {
            return true;
        }
        return self::outletId() === $outletId;
    }

    /** Require that user can access the current branch context */
    public static function requireBranchAccess(): void
    {
        self::requireLogin();
        if (self::role() === 'super_admin') {
            return;
        }
        if (function_exists('branch_context')) {
            $branch = branch_context();
            $branchOutletId = (int)($branch['outlet_id'] ?? 0);
            if ($branchOutletId > 0 && !self::canAccessBranch($branchOutletId)) {
                $_SESSION['flash_error'] = 'Anda tidak memiliki akses ke cabang ini.';
                $slug = function_exists('branch_slug_for_outlet_id') ? branch_slug_for_outlet_id(self::outletId()) : null;
                $target = $slug !== null ? branch_url($slug, '/dashboard') : url('/dashboard', false);
                header('Location: ' . $target);
                exit;
            }
        }
    }
}
