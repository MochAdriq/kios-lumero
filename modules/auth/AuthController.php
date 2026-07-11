<?php
class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (Auth::check()) $this->redirect('/dashboard');
        $this->view('auth/login', ['error' => null], 'guest');
    }

    public function login(): void
    {
        verify_csrf();
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        $model = new AuthModel();
        $user = $model->findByUsername($username);

        $ok = false;
        if ($user) {
            $hash = $user['password'];
            $ok = password_verify($password, $hash) || hash_equals($hash, $password);
            if ($ok && !password_get_info($hash)['algo']) {
                $model->rehashPassword((int)$user['id'], $password);
            }
        }

        if (!$ok) {
            $this->view('auth/login', ['error' => 'Username atau password tidak sesuai.'], 'guest');
            return;
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

        Audit::log('login', 'users', (int)$user['id']);

        // Redirect logic based on role & branch
        $role = $user['role_code'];
        $branch = branch_context();
        $branchOutletId = (int)($branch['outlet_id'] ?? 0);
        $userOutletId = (int)($user['outlet_id'] ?: app_config('default_outlet_id'));

        if ($role === 'super_admin') {
            // Super admin: if on HQ branch, go to HQ dashboard; otherwise normal dashboard
            if (Auth::isHQ()) {
                $this->redirect('/hq');
            } else {
                $this->redirect('/dashboard');
            }
        } else {
            // Non-super_admin: must match branch outlet
            if ($branchOutletId > 0 && $branchOutletId !== $userOutletId) {
                // User is on wrong branch URL — redirect to correct branch
                $correctSlug = $this->findUserBranchSlug($userOutletId);
                if ($correctSlug !== null) {
                    header('Location: ' . branch_url($correctSlug, '/dashboard'));
                    exit;
                }
            }
            $this->redirect('/dashboard');
        }
    }

    public function logout(): void
    {
        Audit::log('logout');
        session_destroy();
        $this->redirect('/login');
    }

    /**
     * Find the branch slug for a given outlet_id.
     */
    private function findUserBranchSlug(int $outletId): ?string
    {
        $config = app_branch_config();
        $default = $config['default'] ?? [];
        if ((int)($default['outlet_id'] ?? 0) === $outletId) {
            return '';
        }
        foreach (($config['map'] ?? []) as $slug => $branch) {
            if ((int)($branch['outlet_id'] ?? 0) === $outletId) {
                return $slug;
            }
        }
        return null;
    }
}
