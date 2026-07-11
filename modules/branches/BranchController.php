<?php
class BranchController extends Controller
{
    public function index(): void
    {
        Auth::requireRoles(['super_admin']);
        $model = new BranchModel();
        $this->view('branches/index', [
            'pageTitle' => 'Kelola Cabang',
            'branches'  => $model->list(),
            'roles'     => (new UserModel())->roles(),
        ]);
    }

    public function store(): void
    {
        Auth::requireRoles(['super_admin']);
        verify_csrf();
        try {
            $id = (new BranchModel())->store($_POST);
            $isNew = empty($_POST['id']) || (int)$_POST['id'] === 0;
            Audit::log($isNew ? 'create_branch' : 'update_branch', 'outlets', $id, null, $_POST);

            // Auto-create admin user for new outlet
            if ($isNew && !empty($_POST['create_admin'])) {
                $this->createAdminForOutlet($id, $_POST);
            }

            $_SESSION['flash_success'] = 'Cabang berhasil ' . ($isNew ? 'ditambahkan' : 'diperbarui') . '.';

            // Clear cached branch config
            if (function_exists('app_branch_config')) {
                // Force re-read on next request (static cache in function)
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/branches');
    }

    public function toggleActive(): void
    {
        Auth::requireRoles(['super_admin']);
        verify_csrf();
        try {
            $id = (int)($_POST['id'] ?? 0);
            (new BranchModel())->toggleActive($id);
            Audit::log('toggle_branch_active', 'outlets', $id);
            $_SESSION['flash_success'] = 'Status cabang berhasil diubah.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/branches');
    }

    public function delete(): void
    {
        Auth::requireRoles(['super_admin']);
        verify_csrf();
        try {
            $id = (int)($_POST['id'] ?? 0);
            (new BranchModel())->delete($id);
            Audit::log('delete_branch', 'outlets', $id);
            $_SESSION['flash_success'] = 'Outlet berhasil dihapus (dinonaktifkan).';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        $this->redirect('/branches');
    }

    /**
     * Auto-create an admin user for a newly created outlet.
     */
    private function createAdminForOutlet(int $outletId, array $data): void
    {
        $adminName     = trim($data['admin_name'] ?? '');
        $adminUsername  = trim($data['admin_username'] ?? '');
        $adminPassword  = trim($data['admin_password'] ?? '');
        $adminEmail     = trim($data['admin_email'] ?? '');
        $adminRoleId    = (int)($data['admin_role_id'] ?? 0);

        if ($adminUsername === '') {
            // Auto-generate from outlet slug or name
            $slug = trim($data['slug'] ?? '');
            $adminUsername = $slug !== '' ? 'admin-' . $slug : 'admin-outlet-' . $outletId;
        }
        if ($adminName === '') {
            $adminName = 'Admin ' . trim($data['name'] ?? 'Outlet');
        }
        if ($adminPassword === '') {
            $adminPassword = 'admin123';
        }

        // Find administrator role if not specified
        if ($adminRoleId <= 0) {
            $roles = (new UserModel())->roles();
            foreach ($roles as $r) {
                if ($r['code'] === 'administrator') {
                    $adminRoleId = (int)$r['id'];
                    break;
                }
            }
            // Fallback to first non-super_admin role
            if ($adminRoleId <= 0) {
                foreach ($roles as $r) {
                    if ($r['code'] !== 'super_admin') {
                        $adminRoleId = (int)$r['id'];
                        break;
                    }
                }
            }
        }

        $userModel = new UserModel();

        // Check if username already exists
        $existing = $userModel->findByUsername($adminUsername);
        if ($existing) {
            $_SESSION['flash_warning'] = "Outlet berhasil dibuat, tapi username \"{$adminUsername}\" sudah ada. Silakan buat user secara manual.";
            return;
        }

        $userId = $userModel->store([
            'outlet_id'    => $outletId,
            'role_id'      => $adminRoleId,
            'name'         => $adminName,
            'username'     => $adminUsername,
            'email'        => $adminEmail,
            'phone'        => '',
            'password'     => $adminPassword,
            'daily_salary' => 0,
            'is_active'    => 1,
        ]);

        Audit::log('auto_create_outlet_admin', 'users', $userId, null, [
            'outlet_id' => $outletId,
            'username'  => $adminUsername,
        ]);

        $_SESSION['flash_success'] .= " Akun admin \"{$adminUsername}\" (password: {$adminPassword}) berhasil dibuat.";
    }
}

