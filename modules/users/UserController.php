<?php
class UserController extends Controller
{
    public function index(): void
    {
        if (empty($_SESSION['impersonator'])) {
            Auth::requireRoles(['super_admin', 'administrator']);
        }
        $isSuperAdmin = (Auth::role() === 'super_admin' || !empty($_SESSION['impersonator']));
        $outletId = $isSuperAdmin ? null : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id', 1));
        $m = new UserModel();
        $this->view('users/index', [
            'pageTitle' => 'User & HR',
            'items'     => $m->list($outletId),
            'roles'     => $m->roles($outletId),
            'outlets'   => $m->activeOutlets($outletId),
            'payroll'   => $m->payrollSummary($outletId),
        ]);
    }

    public function store(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();

        // Validate: non-super_admin roles MUST have an outlet_id
        $roleId = (int)($_POST['role_id'] ?? 0);
        $outletId = (int)($_POST['outlet_id'] ?? 0);

        // Look up role code
        $roles = (new UserModel())->roles();
        $roleCode = '';
        foreach ($roles as $r) {
            if ((int)$r['id'] === $roleId) {
                $roleCode = $r['code'];
                break;
            }
        }

        if ($roleCode !== 'super_admin' && $outletId <= 0 && Auth::role() === 'super_admin') {
            $_SESSION['flash_error'] = 'User dengan role selain Super Admin wajib memilih outlet/cabang.';
            $this->redirect('/users');
            return;
        }

        try {
            $id = (new UserModel())->store($_POST);
            $isNew = empty($_POST['id']) || (int)$_POST['id'] === 0;
            Audit::log($isNew ? 'create_user' : 'update_user', 'users', $id, null, array_diff_key($_POST, ['password' => '', '_csrf' => '']));
            $_SESSION['flash_success'] = 'User berhasil ' . ($isNew ? 'ditambahkan' : 'diperbarui') . '.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/users');
    }

    public function toggleActive(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();
        try {
            $id = (int)($_POST['id'] ?? 0);
            (new UserModel())->toggleActive($id);
            Audit::log('toggle_user_active', 'users', $id);
            $_SESSION['flash_success'] = 'Status user berhasil diubah.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/users');
    }

    public function resetPassword(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        verify_csrf();
        try {
            $id = (int)($_POST['id'] ?? 0);
            $newPassword = trim($_POST['new_password'] ?? 'admin123');
            (new UserModel())->resetPassword($id, $newPassword);
            Audit::log('reset_user_password', 'users', $id);
            $_SESSION['flash_success'] = 'Password berhasil direset.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/users');
    }

    public function apiDetail(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $id = (int)($_GET['id'] ?? 0);
        $user = (new UserModel())->findById($id);
        if (!$user) {
            $this->json(['error' => 'User tidak ditemukan'], 404);
            return;
        }
        if (Auth::role() !== 'super_admin') {
            $myOutletId = (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id', 1));
            if ((int)$user['outlet_id'] !== $myOutletId && $user['role_code'] === 'super_admin') {
                $this->json(['error' => 'Akses ditolak'], 403);
                return;
            }
        }
        // Remove sensitive data
        unset($user['password']);
        $this->json($user);
    }

    public function impersonate(): void
    {
        verify_csrf();
        $originalUser = $_SESSION['impersonator'] ?? null;
        if (Auth::role() !== 'super_admin' && empty($originalUser)) {
            $_SESSION['flash_error'] = 'Akses ditolak. Hanya Super Admin yang dapat melakukan Login As.';
            $this->redirect('/dashboard');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $user = (new UserModel())->findById($id);
        if (!$user || (isset($user['is_active']) && (int)$user['is_active'] === 0)) {
            $_SESSION['flash_error'] = 'User tidak ditemukan atau tidak aktif.';
            $this->redirect('/users');
            return;
        }

        if (empty($_SESSION['impersonator'])) {
            $_SESSION['impersonator'] = $_SESSION['user'];
        }

        $_SESSION['user'] = [
            'id'          => (int)$user['id'],
            'name'        => $user['name'],
            'username'    => $user['username'],
            'role_code'   => $user['role_code'],
            'role_name'   => $user['role_name'],
            'outlet_id'   => $user['outlet_id'] ?: app_config('default_outlet_id'),
            'outlet_name' => $user['outlet_name'] ?: 'Outlet Utama',
        ];

        Audit::log('impersonate_user', 'users', (int)$user['id']);
        $_SESSION['flash_success'] = 'Berhasil Login As: ' . $user['name'] . ' (' . $user['role_name'] . ')';

        $outletId = (int)$user['outlet_id'];
        $slug = function_exists('branch_slug_for_outlet_id') ? branch_slug_for_outlet_id($outletId) : null;
        $target = $slug !== null ? branch_url($slug, '/dashboard') : url('/dashboard', false);
        header('Location: ' . $target);
        exit;
    }

    public function stopImpersonation(): void
    {
        verify_csrf();
        if (empty($_SESSION['impersonator'])) {
            $this->redirect('/dashboard');
            return;
        }

        $_SESSION['user'] = $_SESSION['impersonator'];
        unset($_SESSION['impersonator']);

        Audit::log('stop_impersonating');
        $_SESSION['flash_success'] = 'Berhasil kembali ke Akun Owner / Pusat.';

        $outletId = (int)($_SESSION['user']['outlet_id'] ?? 1);
        $slug = function_exists('branch_slug_for_outlet_id') ? branch_slug_for_outlet_id($outletId) : null;
        $target = ($slug !== null && $slug !== '') ? branch_url($slug, '/users') : url('/users', false);
        header('Location: ' . $target);
        exit;
    }
}
