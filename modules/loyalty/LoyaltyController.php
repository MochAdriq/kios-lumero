<?php
require_once __DIR__ . '/../../config/loyalty.php';

class LoyaltyController extends Controller
{
    public function members()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        // Ambil ID batch undian aktif untuk menghitung jumlah kupon/tiket
        $batchId = 0;
        try {
            $r = $pdo->query("SELECT id FROM raffle_batches WHERE status = 'active' ORDER BY end_date ASC LIMIT 1");
            if ($activeBatch = $r->fetch(PDO::FETCH_ASSOC)) {
                $batchId = (int)$activeBatch['id'];
            }
        } catch (Throwable $e) {}

        $stmt = $pdo->query("
            SELECT m.*, 
                   (SELECT COUNT(*) FROM raffle_tickets t WHERE t.member_id = m.id AND t.batch_id = $batchId) as total_kupon
            FROM members m 
            ORDER BY m.total_points DESC, m.id DESC
        ");
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

        // Fetch WA Templates
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        $stmtTemplates = $pdo->prepare("SELECT * FROM wa_templates WHERE outlet_id = ? OR outlet_id = 0 ORDER BY id DESC");
        $stmtTemplates->execute([$outletId]);
        $waTemplates = $stmtTemplates->fetchAll(PDO::FETCH_ASSOC);

        $this->view('loyalty/members', [
            'pageTitle' => 'Data Member & Poin',
            'members' => $members,
            'settings' => $settings,
            'waTemplates' => $waTemplates
        ]);
    }

    public function memberDetail()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';
        loyalty_ensure_tables($pdo);

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: ' . url('/loyalty/members'));
            exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            header('Location: ' . url('/loyalty/members'));
            exit;
        }

        // Ambil riwayat poin
        $logStmt = $pdo->prepare("SELECT * FROM member_point_logs WHERE member_id = ? ORDER BY created_at DESC");
        $logStmt->execute([$id]);
        $pointLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ambil kupon undian aktif (dan join nama batch)
        $tiketStmt = $pdo->prepare("SELECT t.*, b.name as batch_name FROM raffle_tickets t LEFT JOIN raffle_batches b ON t.batch_id = b.id WHERE t.member_id = ? ORDER BY t.created_at DESC");
        $tiketStmt->execute([$id]);
        $tickets = $tiketStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->view('loyalty/member_detail', [
            'pageTitle' => 'Detail Member & Riwayat Poin',
            'member' => $member,
            'pointLogs' => $pointLogs,
            'tickets' => $tickets
        ]);
    }

    public function loginAsMember()
    {
        Auth::requireRoles(['super_admin']);
        $pdo = Database::connection();
        
        $id = (int)($_POST['member_id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Member tidak valid.';
            header('Location: ' . url('/loyalty/members'));
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ?");
        $stmt->execute([$id]);
        if (!$stmt->fetch()) {
            $_SESSION['flash_error'] = 'Member tidak ditemukan.';
            header('Location: ' . url('/loyalty/members'));
            exit;
        }

        // Set member session
        $_SESSION['member_id'] = $id;
        
        // Redirect to member dashboard
        header('Location: ../user/dashboard.php');
        exit;
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
        
        $limit = 10;
        
        // --- 1. Reward Redemptions (Poin) Pagination ---
        $page_reward = max(1, (int)($_GET['page_reward'] ?? 1));
        $offset_reward = ($page_reward - 1) * $limit;
        
        $sql_reward_count = "SELECT COUNT(*) FROM point_reward_redemptions r
                             LEFT JOIN members m ON m.id = r.member_id
                             LEFT JOIN point_reward_products p ON p.id = r.reward_product_id";
        $params_reward = [];
        if ($q !== '') {
            $sql_reward_count .= " WHERE r.redemption_code LIKE ? OR m.name LIKE ?";
            $params_reward[] = "%$q%";
            $params_reward[] = "%$q%";
        }
        $stmt_reward_count = $pdo->prepare($sql_reward_count);
        $stmt_reward_count->execute($params_reward);
        $total_rewards = $stmt_reward_count->fetchColumn();
        $total_pages_reward = ceil($total_rewards / $limit);

        $sql_reward = "SELECT r.*, m.name AS member_name, m.phone AS member_phone, p.name AS reward_name 
                       FROM point_reward_redemptions r
                       LEFT JOIN members m ON m.id = r.member_id
                       LEFT JOIN point_reward_products p ON p.id = r.reward_product_id";
        if ($q !== '') {
            $sql_reward .= " WHERE r.redemption_code LIKE ? OR m.name LIKE ?";
        }
        $sql_reward .= " ORDER BY r.id DESC LIMIT $limit OFFSET $offset_reward";
        $stmt_reward = $pdo->prepare($sql_reward);
        $stmt_reward->execute($params_reward);
        $redemptions = $stmt_reward->fetchAll();

        // --- 2. Event Claims (Undian) Pagination ---
        $page_event = max(1, (int)($_GET['page_event'] ?? 1));
        $offset_event = ($page_event - 1) * $limit;
        
        $sql_event_count = "SELECT COUNT(*) FROM reward_claims rc
                            JOIN members m ON m.id = rc.user_id
                            JOIN event_prizes ep ON ep.id = rc.prize_id";
        $params_event = [];
        if ($q !== '') {
            $sql_event_count .= " WHERE rc.qr_code LIKE ? OR m.name LIKE ?";
            $params_event[] = "%$q%";
            $params_event[] = "%$q%";
        }
        $stmt_event_count = $pdo->prepare($sql_event_count);
        $stmt_event_count->execute($params_event);
        $total_events = $stmt_event_count->fetchColumn();
        $total_pages_event = ceil($total_events / $limit);

        $sql_event = "SELECT rc.*, m.name as member_name, m.phone as member_phone, ep.name as prize_name
                      FROM reward_claims rc
                      JOIN members m ON m.id = rc.user_id
                      JOIN event_prizes ep ON ep.id = rc.prize_id";
        if ($q !== '') {
            $sql_event .= " WHERE rc.qr_code LIKE ? OR m.name LIKE ?";
        }
        $sql_event .= " ORDER BY rc.id DESC LIMIT $limit OFFSET $offset_event";
        $stmt_event = $pdo->prepare($sql_event);
        $stmt_event->execute($params_event);
        $recentClaims = $stmt_event->fetchAll();

        $this->view('loyalty/redemptions', [
            'pageTitle' => 'Validasi Hadiah (Poin & Undian)',
            'redemptions' => $redemptions,
            'recentClaims' => $recentClaims,
            'page_reward' => $page_reward,
            'total_pages_reward' => $total_pages_reward,
            'page_event' => $page_event,
            'total_pages_event' => $total_pages_event,
            'q' => $q
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

    public function processRewardClaim()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/redemptions'));
            exit;
        }

        $code = trim($_POST['redemption_code'] ?? '');
        $adminId = $_SESSION['user_id'] ?? null;
        $pdo = Database::connection();
        require_once __DIR__ . '/../../config/loyalty.php';

        try {
            if ($code === '') throw new Exception("Kode Penukaran tidak boleh kosong.");

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT r.*, p.name AS reward_name 
                                   FROM point_reward_redemptions r
                                   LEFT JOIN point_reward_products p ON p.id = r.reward_product_id
                                   WHERE r.redemption_code = ? FOR UPDATE");
            $stmt->execute([$code]);
            $redemption = $stmt->fetch();

            if (!$redemption) {
                throw new Exception("Kode Penukaran tidak ditemukan atau salah.");
            }

            if ($redemption['status'] === 'completed') {
                throw new Exception("Hadiah ini sudah divalidasi dan diambil sebelumnya.");
            }
            if ($redemption['status'] === 'rejected') {
                throw new Exception("Klaim ini sudah ditolak.");
            }

            // Validasi sukses
            loyalty_update_reward_redemption_status($pdo, $redemption['id'], 'completed', $adminId, 'Divalidasi otomatis via Scan.');
            
            $pdo->commit();
            $_SESSION['flash_success'] = 'Hadiah berhasil di claim, buatkan hadiah nya sekarang juga (' . ($redemption['reward_name'] ?? 'Produk') . ')';
        } catch (Throwable $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $rx) {}
            $_SESSION['flash_error'] = $e->getMessage();
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
    public function eventClaims()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        
        $stmt = $pdo->query("SELECT rc.*, m.name as member_name, m.phone as member_phone, ep.name as prize_name
                             FROM reward_claims rc
                             JOIN members m ON m.id = rc.user_id
                             JOIN event_prizes ep ON ep.id = rc.prize_id
                             ORDER BY rc.id DESC LIMIT 50");
        $recentClaims = $stmt->fetchAll();

        $this->view('loyalty/event_claims', [
            'pageTitle' => 'Validasi Hadiah Undian',
            'recentClaims' => $recentClaims
        ]);
    }

    public function processEventClaim()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/redemptions'));
            exit;
        }

        $code = trim($_POST['qr_code'] ?? '');
        $pdo = Database::connection();

        try {
            if ($code === '') throw new Exception("Kode Klaim tidak boleh kosong.");

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT rc.*, ep.id as ep_id, ep.stock, ep.name as prize_name 
                                   FROM reward_claims rc 
                                   JOIN event_prizes ep ON ep.id = rc.prize_id 
                                   WHERE rc.qr_code = ? FOR UPDATE");
            $stmt->execute([$code]);
            $claim = $stmt->fetch();

            if (!$claim) {
                throw new Exception("Kode Klaim tidak ditemukan atau salah.");
            }

            if ($claim['status'] === 'CLAIMED') {
                throw new Exception("Tiket ini sudah ditukarkan sebelumnya.");
            }

            if ($claim['status'] === 'EXPIRED' || (!empty($claim['expired_at']) && strtotime($claim['expired_at']) < time())) {
                if ($claim['status'] !== 'EXPIRED') {
                    $pdo->prepare("UPDATE reward_claims SET status = 'EXPIRED' WHERE id = ?")->execute([$claim['id']]);
                }
                throw new Exception("Maaf, kupon ini sudah kedaluwarsa/hangus dan tidak berlaku lagi.");
            }

            if ($claim['stock'] <= 0) {
                throw new Exception("Maaf, stok hadiah (" . $claim['prize_name'] . ") sudah habis!");
            }

            // Update status and stock
            $pdo->prepare("UPDATE reward_claims SET status = 'CLAIMED' WHERE id = ?")->execute([$claim['id']]);
            $pdo->prepare("UPDATE event_prizes SET stock = stock - 1 WHERE id = ?")->execute([$claim['ep_id']]);

            // Buat pesanan Rp 0 agar tercetak di dapur & memotong HPP/stok bahan
            try {
                require_once __DIR__ . '/../../config/loyalty.php';
                $orderNo = loyalty_next_point_order_no($pdo);
                $memberId = (int)($claim['user_id'] ?? 0);
                $member = [];
                if ($memberId > 0) {
                    $stMem = $pdo->prepare("SELECT * FROM members WHERE id=? LIMIT 1");
                    $stMem->execute([$memberId]);
                    $member = $stMem->fetch(PDO::FETCH_ASSOC) ?: [];
                }
                
                $customerName = trim((string)($member['name'] ?? 'Pemenang Undian')) ?: 'Pemenang Undian';
                $phone = loyalty_normalize_phone((string)($member['phone'] ?? ''));
                $note = 'Klaim Hadiah Undian: ' . $code . ' - ' . $claim['prize_name'];
                
                // Gunakan 0 untuk HPP sementara jika event_prizes tidak memiliki pengaturan HPP khusus
                $rewardHpp = 0; 
                
                $cols = []; $vals = [];
                $add = function($col,$val) use (&$cols,&$vals,$pdo){ if(loyalty_col_exists($pdo,'orders',$col)){ $cols[]=$col; $vals[]=$val; } };
                $add('brand_id',1); $add('outlet_id',1);
                $add('order_number',$orderNo); $add('channel','kasir'); $add('order_source','wic');
                $add('customer_name',$customerName); $add('customer_phone',$phone); $add('member_id',$memberId ?: null);
                
                $sessionId = (int)$pdo->query("SELECT id FROM daily_store_sessions WHERE outlet_id=1 ORDER BY status='open' DESC, id DESC LIMIT 1")->fetchColumn();
                $add('daily_store_session_id', $sessionId ?: 0);
                $add('business_date', date('Y-m-d'));
                $add('payment_method','point'); $add('payment_status','paid');
                $add('subtotal',0); $add('tax',0); $add('discount',0); $add('discount_note',$note); $add('total',0);
                $add('total_hpp',$rewardHpp); $add('gross_profit',0 - $rewardHpp);
                $add('paid_amount',0); $add('change_amount',0); $add('status','done');
                $add('print_status','waiting'); $add('print_error',null);
                $add('created_by',$_SESSION['user_id'] ?? null); $add('paid_at',date('Y-m-d H:i:s'));
                
                if ($cols) {
                    $sql = "INSERT INTO orders (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")";
                    $st = $pdo->prepare($sql); $st->execute($vals);
                    $orderId = (int)$pdo->lastInsertId();
                    
                    if ($orderId > 0 && loyalty_table_exists($pdo,'order_items')) {
                        $icols=[]; $ivals=[];
                        $iadd=function($col,$val) use (&$icols,&$ivals,$pdo){ if(loyalty_col_exists($pdo,'order_items',$col)){ $icols[]=$col; $ivals[]=$val; } };
                        $iadd('brand_id',1); $iadd('outlet_id',1); $iadd('order_id',$orderId); $iadd('item_type','reward');
                        $iadd('item_name','[UNDIAN] '.$claim['prize_name']); $iadd('qty',1); 
                        $iadd('price',0); $iadd('hpp',$rewardHpp); $iadd('line_total',0); $iadd('line_hpp',$rewardHpp); $iadd('line_profit',0 - $rewardHpp);
                        if ($icols) {
                            $pdo->prepare("INSERT INTO order_items (`".implode('`,`',$icols)."`) VALUES (".implode(',',array_fill(0,count($icols),'?')).")")->execute($ivals);
                        }
                    }
                }
            } catch (Throwable $ex) {}

            $pdo->commit();
            $_SESSION['flash_success'] = 'Hadiah berhasil di claim, buatkan hadiah nya sekarang juga (' . $claim['prize_name'] . ')';
        } catch (Throwable $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $rx) {}
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: ' . url('/loyalty/redemptions'));
        exit;
    }

    public function eventSettings()
    {
        Auth::requireLogin();
        $pdo = Database::connection();
        
        $stmt = $pdo->query("SELECT * FROM event_prizes WHERE event_id = 'kalibunder_go' ORDER BY id ASC");
        $prizes = $stmt->fetchAll();

        $this->view('loyalty/event_settings', [
            'pageTitle' => 'Manajemen Hadiah Undian',
            'prizes' => $prizes
        ]);
    }

    public function saveEventPrize()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/eventSettings'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $stock = (int)($_POST['stock'] ?? 0);
        $prizeType = ($_POST['prize_type'] ?? '') === 'points' ? 'points' : 'product';
        $pointsAmount = (int)($_POST['points_amount'] ?? 0);
        $isFallback = isset($_POST['is_default_fallback']) && $_POST['is_default_fallback'] ? 1 : 0;
        $isActive = isset($_POST['is_active']) && $_POST['is_active'] ? 1 : 0;
        
        $pdo = Database::connection();
        
        try {
            if ($name === '') throw new Exception('Nama hadiah tidak boleh kosong.');
            
            $imageUrl = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    throw new Exception('Format gambar tidak didukung. Gunakan JPG/PNG/WEBP.');
                }
                
                $uploadDir = __DIR__ . '/../../public/assets/images/event-prizes/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                
                $filename = 'prize_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
                $targetFile = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
                    $imageUrl = 'images/event-prizes/' . $filename;
                } else {
                    throw new Exception('Gagal mengupload gambar.');
                }
            }

            if ($id > 0) {
                if ($imageUrl !== null) {
                    $stmt = $pdo->prepare("UPDATE event_prizes SET name=?, stock=?, is_default_fallback=?, is_active=?, image_url=?, prize_type=?, points_amount=? WHERE id=?");
                    $stmt->execute([$name, $stock, $isFallback, $isActive, $imageUrl, $prizeType, $pointsAmount, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE event_prizes SET name=?, stock=?, is_default_fallback=?, is_active=?, prize_type=?, points_amount=? WHERE id=?");
                    $stmt->execute([$name, $stock, $isFallback, $isActive, $prizeType, $pointsAmount, $id]);
                }
                
                // If inactive, reset chance_percentage to 0
                if (!$isActive) {
                    $pdo->prepare("UPDATE event_prizes SET chance_percentage = 0 WHERE id = ?")->execute([$id]);
                }
                
                $_SESSION['flash_success'] = 'Hadiah berhasil diperbarui.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO event_prizes (event_id, name, chance_percentage, stock, is_default_fallback, is_active, image_url, prize_type, points_amount) VALUES ('kalibunder_go', ?, 0, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $stock, $isFallback, $isActive, $imageUrl, $prizeType, $pointsAmount]);
                $_SESSION['flash_success'] = 'Hadiah baru berhasil ditambahkan.';
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: ' . url('/loyalty/eventSettings'));
        exit;
    }

    public function saveEventPercentages()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/eventSettings'));
            exit;
        }

        $pdo = Database::connection();
        $chances = $_POST['chances'] ?? [];

        try {
            // Get active map
            $stmtPrizes = $pdo->query("SELECT id, is_active FROM event_prizes WHERE event_id = 'kalibunder_go'");
            $activeMap = [];
            while ($row = $stmtPrizes->fetch()) {
                $activeMap[$row['id']] = (bool)$row['is_active'];
            }

            $totalChance = 0;
            $finalChances = [];
            foreach ($chances as $prizeId => $val) {
                // Ignore inactive prizes
                if (empty($activeMap[$prizeId])) {
                    $val = 0;
                }
                $finalChances[$prizeId] = (float)$val;
                $totalChance += $finalChances[$prizeId];
            }

            if (round($totalChance, 2) != 100.00) {
                throw new Exception('Total persentase harus tepat 100%. Saat ini: ' . round($totalChance, 2) . '%');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE event_prizes SET chance_percentage = ? WHERE id = ?");
            foreach ($finalChances as $prizeId => $chanceVal) {
                $stmt->execute([(float)$chanceVal, (int)$prizeId]);
            }
            $pdo->commit();

            $_SESSION['flash_success'] = 'Persentase undian berhasil diperbarui dan divalidasi 100%.';
        } catch (Throwable $e) {
            try { if ($pdo->inTransaction()) $pdo->rollBack(); } catch (Throwable $rx) {}
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: ' . url('/loyalty/eventSettings'));
        exit;
    }

    public function deleteEventPrize()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/eventSettings'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $pdo = Database::connection();

        try {
            $pdo->prepare("DELETE FROM event_prizes WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = 'Hadiah berhasil dihapus.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus hadiah: ' . $e->getMessage();
        }

        header('Location: ' . url('/loyalty/eventSettings'));
        exit;
    }

    public function saveWaTemplate()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/members'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        
        $pdo = Database::connection();
        
        try {
            if ($title === '') throw new Exception('Judul template tidak boleh kosong.');
            if ($message === '') throw new Exception('Isi pesan tidak boleh kosong.');
            
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE wa_templates SET title=?, message=? WHERE id=? AND outlet_id=?");
                $stmt->execute([$title, $message, $id, $outletId]);
                $_SESSION['flash_success'] = 'Template WA berhasil diperbarui.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO wa_templates (outlet_id, title, message) VALUES (?, ?, ?)");
                $stmt->execute([$outletId, $title, $message]);
                $_SESSION['flash_success'] = 'Template WA baru berhasil ditambahkan.';
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: ' . url('/loyalty/members'));
        exit;
    }

    public function deleteWaTemplate()
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/loyalty/members'));
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : (int)(Auth::user()['outlet_id'] ?? app_config('default_outlet_id'));
        $pdo = Database::connection();

        try {
            $pdo->prepare("DELETE FROM wa_templates WHERE id = ? AND outlet_id = ?")->execute([$id, $outletId]);
            $_SESSION['flash_success'] = 'Template WA berhasil dihapus.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Gagal menghapus template: ' . $e->getMessage();
        }

        header('Location: ' . url('/loyalty/members'));
        exit;
    }
}
