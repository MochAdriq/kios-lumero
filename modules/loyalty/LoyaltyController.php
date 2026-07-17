<?php
class LoyaltyController extends Controller
{
    public function members()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        $stmt = $pdo->query("SELECT * FROM members ORDER BY total_points DESC, id DESC");
        $members = $stmt->fetchAll();

        $settingsStmt = $pdo->query("SELECT * FROM loyalty_settings WHERE id = 1 LIMIT 1");
        $settings = $settingsStmt->fetch() ?: [
            'earn_amount' => 1000,
            'earn_point' => 1,
            'redeem_point_value' => 100,
            'minimum_redeem_points' => 10,
            'claim_expiry_days' => 14,
            'profile_bonus_points' => 2
        ];

        $this->view('loyalty/members', [
            'pageTitle' => 'Data Member & Poin',
            'members' => $members,
            'settings' => $settings
        ]);
    }

    public function rewards()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        $rewards = loyalty_get_reward_products($pdo, false);
        $menuProducts = loyalty_get_active_menu_products($pdo);

        $this->view('loyalty/rewards', [
            'pageTitle' => 'Katalog Hadiah Poin',
            'rewards' => $rewards,
            'menuProducts' => $menuProducts
        ]);
    }

    public function saveReward()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        $id = (int)($_POST['id'] ?? 0);
        try {
            loyalty_save_reward_product($pdo, $_POST, $id);
            $_SESSION['flash_success'] = $id > 0 ? 'Hadiah poin berhasil diperbarui.' : 'Hadiah poin baru dari katalog berhasil ditambahkan.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        header('Location: ' . url('/loyalty/rewards'));
        exit;
    }

    public function deleteReward()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($id > 0) {
                $msg = loyalty_delete_or_deactivate_reward_product($pdo, $id);
                $_SESSION['flash_success'] = $msg;
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }
        header('Location: ' . url('/loyalty/rewards'));
        exit;
    }

    public function toggleStatusReward()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare("UPDATE point_reward_products SET is_active = IF(is_active=1, 0, 1), updated_at=NOW() WHERE id=?")->execute([$id]);
                $_SESSION['flash_success'] = 'Status hadiah poin berhasil diubah.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = $e->getMessage();
            }
        }
        header('Location: ' . url('/loyalty/rewards'));
        exit;
    }


    public function redemptions()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        $q = trim($_GET['q'] ?? '');
        $sql = "SELECT r.*, m.name AS member_name, m.phone AS member_phone, p.name AS reward_name 
                FROM point_reward_redemptions r
                LEFT JOIN members m ON m.id = r.member_id
                LEFT JOIN point_reward_products p ON p.id = r.reward_product_id";
        
        $params = [];
        if ($q !== '') {
            $sql .= " WHERE r.redemption_code LIKE ? OR m.name LIKE ?";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        
        $sql .= " ORDER BY r.id DESC LIMIT 100";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $redemptions = $stmt->fetchAll();

        $this->view('loyalty/redemptions', [
            'pageTitle' => 'Validasi Penukaran Poin',
            'redemptions' => $redemptions
        ]);
    }

    public function updateRedemptionStatus()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/redemptions'));
            exit;
        }

        $redemptionId = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $adminId = $_SESSION['user_id'] ?? null;
        
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';

        try {
            loyalty_update_reward_redemption_status($pdo, $redemptionId, $status, $adminId, 'Divalidasi oleh Admin via layar Validasi.');
            $_SESSION['flash_success'] = 'Status penukaran poin berhasil diperbarui.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal memperbarui: ' . $e->getMessage();
        }

        header('Location: ' . url('/loyalty/redemptions'));
        exit;
    }

    public function updateSettings()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        $earnAmount = max(100, (int)($_POST['earn_amount'] ?? 1000));
        $earnPoint = max(1, (int)($_POST['earn_point'] ?? 1));
        $redeemValue = max(10, (int)($_POST['redeem_point_value'] ?? 100));
        $minRedeem = max(1, (int)($_POST['minimum_redeem_points'] ?? 10));

        $stmt = $pdo->prepare("INSERT INTO loyalty_settings (id, earn_amount, earn_point, redeem_point_value, minimum_redeem_points)
                               VALUES (1, ?, ?, ?, ?)
                               ON DUPLICATE KEY UPDATE earn_amount=?, earn_point=?, redeem_point_value=?, minimum_redeem_points=?");
        $stmt->execute([$earnAmount, $earnPoint, $redeemValue, $minRedeem, $earnAmount, $earnPoint, $redeemValue, $minRedeem]);

        $_SESSION['flash_success'] = 'Pengaturan Loyalty Poin berhasil diperbarui.';
        header('Location: ' . url('/loyalty/members'));
        exit;
    }
}
