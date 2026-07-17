<?php
class UserController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin', 'administrator']);
        $isSuperAdmin = (Auth::role() === 'super_admin');
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
}
