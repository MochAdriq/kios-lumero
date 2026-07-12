<?php
/**
 * Lumero / D'Celup Loyalty Program Helper
 * - Member berbasis nomor HP
 * - Earning: Rp 1.000 = 1 point
 * - Redeem pembelanjaan: default 1 point = Rp500 (teks rumus tidak ditampilkan ke member)
 * - Klaim point berdasarkan kode struk
 */
if (session_status() === PHP_SESSION_NONE) session_start();

if (!function_exists('loyalty_table_exists')) {
function loyalty_table_exists(PDO $pdo, string $table): bool {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
        $st->execute([$table]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}
function loyalty_col_exists(PDO $pdo, string $table, string $col): bool {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
        $st->execute([$table,$col]);
        return (int)$st->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}
function loyalty_add_col(PDO $pdo, string $table, string $col, string $definition): void {
    try { if (loyalty_table_exists($pdo,$table) && !loyalty_col_exists($pdo,$table,$col)) $pdo->exec("ALTER TABLE `$table` ADD COLUMN $definition"); } catch (Throwable $e) {}
}
function loyalty_try_add_key(PDO $pdo, string $table, string $keySql): void { try { $pdo->exec("ALTER TABLE `$table` ADD $keySql"); } catch (Throwable $e) {} }
function loyalty_normalize_phone(string $phone): string {
    $p = preg_replace('/[^0-9+]/','',trim($phone));
    if (strpos($p,'+62')===0) $p='0'.substr($p,3);
    if (strpos($p,'62')===0 && strlen($p)>10) $p='0'.substr($p,2);
    return preg_replace('/[^0-9]/','',$p);
}
function loyalty_mask_phone(string $phone): string {
    $p=loyalty_normalize_phone($phone);
    if(strlen($p)<=6) return $p;
    return substr($p,0,4).str_repeat('*',max(2,strlen($p)-6)).substr($p,-2);
}
function loyalty_money(int $n): string { return function_exists('rupiah') ? rupiah($n) : 'Rp'.number_format($n,0,',','.'); }

function loyalty_ensure_tables(PDO $pdo): void {
    static $done=false;
    if($done) return;
    $done=true;
    $pdo->exec("CREATE TABLE IF NOT EXISTS members (
        id INT(11) NOT NULL AUTO_INCREMENT,
        member_code VARCHAR(40) DEFAULT NULL,
        name VARCHAR(120) DEFAULT NULL,
        phone VARCHAR(30) NOT NULL,
        email VARCHAR(160) DEFAULT NULL,
        gender VARCHAR(20) DEFAULT NULL,
        birth_date DATE DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        pin_hash VARCHAR(255) DEFAULT NULL,
        total_points INT(11) NOT NULL DEFAULT 0,
        total_spent INT(11) NOT NULL DEFAULT 0,
        total_transactions INT(11) NOT NULL DEFAULT 0,
        status ENUM('active','inactive') NOT NULL DEFAULT 'active',
        joined_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        profile_completed_at DATETIME DEFAULT NULL,
        profile_bonus_awarded_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        UNIQUE KEY uniq_members_phone (phone),
        UNIQUE KEY uniq_members_code (member_code),
        KEY idx_members_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Perluasan profil member untuk halaman member v2 dan bonus biodata.
    loyalty_add_col($pdo,'members','email',"email VARCHAR(160) DEFAULT NULL AFTER phone");
    loyalty_add_col($pdo,'members','gender',"gender VARCHAR(20) DEFAULT NULL AFTER email");
    loyalty_add_col($pdo,'members','birth_date',"birth_date DATE DEFAULT NULL AFTER gender");
    loyalty_add_col($pdo,'members','address',"address VARCHAR(255) DEFAULT NULL AFTER birth_date");
    loyalty_add_col($pdo,'members','profile_completed_at',"profile_completed_at DATETIME DEFAULT NULL AFTER joined_at");
    loyalty_add_col($pdo,'members','profile_bonus_awarded_at',"profile_bonus_awarded_at DATETIME DEFAULT NULL AFTER profile_completed_at");

    $pdo->exec("CREATE TABLE IF NOT EXISTS member_point_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        member_id INT(11) NOT NULL,
        transaction_id INT(11) DEFAULT NULL,
        receipt_claim_id INT(11) DEFAULT NULL,
        type VARCHAR(40) NOT NULL,
        points_in INT(11) NOT NULL DEFAULT 0,
        points_out INT(11) NOT NULL DEFAULT 0,
        balance_after INT(11) NOT NULL DEFAULT 0,
        description VARCHAR(255) DEFAULT NULL,
        created_by INT(11) DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_member_point_logs_member (member_id),
        KEY idx_member_point_logs_transaction (transaction_id),
        KEY idx_member_point_logs_type (type),
        KEY idx_member_point_logs_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS loyalty_settings (
        id INT(11) NOT NULL DEFAULT 1,
        earn_amount INT(11) NOT NULL DEFAULT 1000,
        earn_point INT(11) NOT NULL DEFAULT 1,
        redeem_point_value INT(11) NOT NULL DEFAULT 500,
        minimum_redeem_points INT(11) NOT NULL DEFAULT 10,
        maximum_redeem_percent INT(11) NOT NULL DEFAULT 100,
        claim_expiry_days INT(11) NOT NULL DEFAULT 14,
        profile_bonus_points INT(11) NOT NULL DEFAULT 2,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("INSERT IGNORE INTO loyalty_settings (id,earn_amount,earn_point,redeem_point_value,minimum_redeem_points,maximum_redeem_percent,claim_expiry_days,is_active) VALUES (1,1000,1,500,10,100,14,1)");
    loyalty_add_col($pdo,'loyalty_settings','profile_bonus_points',"profile_bonus_points INT(11) NOT NULL DEFAULT 2 AFTER claim_expiry_days");

    $pdo->exec("CREATE TABLE IF NOT EXISTS receipt_claims (
        id INT(11) NOT NULL AUTO_INCREMENT,
        transaction_id INT(11) NOT NULL,
        claim_code VARCHAR(40) NOT NULL,
        claim_points INT(11) NOT NULL DEFAULT 0,
        status ENUM('unclaimed','claimed','expired','cancelled') NOT NULL DEFAULT 'unclaimed',
        claimed_by_member_id INT(11) DEFAULT NULL,
        claimed_at DATETIME DEFAULT NULL,
        expired_at DATETIME DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        UNIQUE KEY uniq_receipt_claim_code (claim_code),
        UNIQUE KEY uniq_receipt_claim_transaction (transaction_id),
        KEY idx_receipt_claims_status (status),
        KEY idx_receipt_claims_member (claimed_by_member_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS member_activity_logs (
        id INT(11) NOT NULL AUTO_INCREMENT,
        member_id INT(11) DEFAULT NULL,
        phone VARCHAR(30) DEFAULT NULL,
        activity_type VARCHAR(60) NOT NULL,
        ip_address VARCHAR(80) DEFAULT NULL,
        user_agent VARCHAR(255) DEFAULT NULL,
        description VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_member_activity_member (member_id),
        KEY idx_member_activity_phone (phone),
        KEY idx_member_activity_type (activity_type),
        KEY idx_member_activity_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS member_login_otps (
        id INT(11) NOT NULL AUTO_INCREMENT,
        phone VARCHAR(30) NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        expired_at DATETIME NOT NULL,
        used_at DATETIME DEFAULT NULL,
        attempt_count INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_member_login_otps_phone (phone),
        KEY idx_member_login_otps_expired (expired_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");



    // Produk reward yang dapat ditukar member dengan point.
    $pdo->exec("CREATE TABLE IF NOT EXISTS point_reward_products (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(160) NOT NULL,
        description VARCHAR(255) DEFAULT NULL,
        required_points INT(11) NOT NULL DEFAULT 0,
        image_url VARCHAR(255) DEFAULT NULL,
        stock_qty INT(11) DEFAULT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT(11) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        KEY idx_point_rewards_active (is_active,sort_order)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS point_reward_redemptions (
        id INT(11) NOT NULL AUTO_INCREMENT,
        member_id INT(11) NOT NULL,
        reward_product_id INT(11) NOT NULL,
        redemption_code VARCHAR(40) DEFAULT NULL,
        order_id INT(11) DEFAULT NULL,
        points_used INT(11) NOT NULL DEFAULT 0,
        status ENUM('requested','approved','completed','cancelled') NOT NULL DEFAULT 'requested',
        note VARCHAR(255) DEFAULT NULL,
        completed_at DATETIME DEFAULT NULL,
        completed_by INT(11) DEFAULT NULL,
        cancelled_at DATETIME DEFAULT NULL,
        cancelled_by INT(11) DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY(id),
        UNIQUE KEY uniq_point_redemption_code (redemption_code),
        KEY idx_point_reward_member (member_id),
        KEY idx_point_reward_status (status),
        KEY idx_point_reward_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Perluasan tabel produk reward untuk kebutuhan pengaturan admin v10.
    loyalty_add_col($pdo,'point_reward_products','reward_code',"reward_code VARCHAR(40) DEFAULT NULL AFTER id");
    loyalty_add_col($pdo,'point_reward_products','category',"category VARCHAR(80) DEFAULT NULL AFTER description");
    loyalty_add_col($pdo,'point_reward_products','terms',"terms VARCHAR(255) DEFAULT NULL AFTER category");
    loyalty_add_col($pdo,'point_reward_products','nominal_value',"nominal_value INT(11) DEFAULT NULL AFTER required_points");
    loyalty_add_col($pdo,'point_reward_products','visible_from',"visible_from DATETIME DEFAULT NULL AFTER sort_order");
    loyalty_add_col($pdo,'point_reward_products','visible_until',"visible_until DATETIME DEFAULT NULL AFTER visible_from");
    loyalty_add_col($pdo,'point_reward_products','max_redeem_per_member',"max_redeem_per_member INT(11) DEFAULT NULL AFTER visible_until");
    // v11: reward dapat ditautkan ke produk menu aktif agar HPP, harga, dan omzet point terukur.
    loyalty_add_col($pdo,'point_reward_products','source_menu_item_id',"source_menu_item_id INT(11) DEFAULT NULL AFTER reward_code");
    loyalty_add_col($pdo,'point_reward_products','source_price',"source_price INT(11) DEFAULT NULL AFTER source_menu_item_id");
    loyalty_add_col($pdo,'point_reward_products','source_hpp',"source_hpp INT(11) DEFAULT NULL AFTER source_price");
    loyalty_try_add_key($pdo,'point_reward_products','KEY idx_point_reward_source_menu (source_menu_item_id)');
    loyalty_try_add_key($pdo,'point_reward_products','KEY idx_point_rewards_category (category)');
    loyalty_try_add_key($pdo,'point_reward_products','KEY idx_point_rewards_visibility (is_active,visible_from,visible_until)');

    // Perluasan tabel penukaran reward untuk instalasi lama.
    loyalty_add_col($pdo,'point_reward_redemptions','redemption_code',"redemption_code VARCHAR(40) DEFAULT NULL AFTER reward_product_id");
    loyalty_add_col($pdo,'point_reward_redemptions','order_id',"order_id INT(11) DEFAULT NULL AFTER redemption_code");
    loyalty_add_col($pdo,'point_reward_redemptions','completed_at',"completed_at DATETIME DEFAULT NULL AFTER note");
    loyalty_add_col($pdo,'point_reward_redemptions','completed_by',"completed_by INT(11) DEFAULT NULL AFTER completed_at");
    loyalty_add_col($pdo,'point_reward_redemptions','cancelled_at',"cancelled_at DATETIME DEFAULT NULL AFTER completed_by");
    loyalty_add_col($pdo,'point_reward_redemptions','cancelled_by',"cancelled_by INT(11) DEFAULT NULL AFTER cancelled_at");
    loyalty_try_add_key($pdo,'point_reward_redemptions','UNIQUE KEY uniq_point_redemption_code (redemption_code)');
    loyalty_try_add_key($pdo,'point_reward_redemptions','KEY idx_point_reward_order (order_id)');

    try {
        $pdo->exec("INSERT IGNORE INTO point_reward_products (id,name,description,required_points,image_url,stock_qty,is_active,sort_order) VALUES
            (1,'Gratis Kentang Kriwil','Tukar point dengan 1 porsi Kentang Kriwil.',20,'assets/img/kentang-kriwil.png',NULL,1,10),
            (2,'Gratis Matcha','Tukar point dengan 1 cup Matcha pilihan.',26,'assets/img/matcha.png',NULL,1,20),
            (3,'Gratis Ayam Original','Tukar point dengan 1 pcs ayam original.',24,'assets/img/original.png',NULL,1,30)");
    } catch (Throwable $e) {}

    // Dukungan loyalty untuk free/online order.
    if (loyalty_table_exists($pdo,'free_orders')) {
        loyalty_add_col($pdo,'free_orders','member_id',"member_id INT(11) DEFAULT NULL AFTER customer_phone");
        loyalty_add_col($pdo,'free_orders','loyalty_points_redeemed',"loyalty_points_redeemed INT(11) NOT NULL DEFAULT 0 AFTER total_hpp");
        loyalty_add_col($pdo,'free_orders','loyalty_point_value',"loyalty_point_value INT(11) NOT NULL DEFAULT 0 AFTER loyalty_points_redeemed");
        loyalty_add_col($pdo,'free_orders','loyalty_redeem_amount',"loyalty_redeem_amount INT(11) NOT NULL DEFAULT 0 AFTER loyalty_point_value");
        loyalty_add_col($pdo,'free_orders','nominal_point',"nominal_point INT(11) NOT NULL DEFAULT 0 AFTER loyalty_redeem_amount");
        try { $pdo->exec("ALTER TABLE free_orders MODIFY payment_method ENUM('qris','transfer','cash','point') NOT NULL DEFAULT 'qris'"); } catch (Throwable $e) {}
    }

    // Perluasan tabel orders untuk integrasi loyalty.
    loyalty_add_col($pdo,'orders','customer_phone',"customer_phone VARCHAR(30) DEFAULT NULL");
    loyalty_add_col($pdo,'orders','member_id',"member_id INT(11) DEFAULT NULL");
    loyalty_add_col($pdo,'orders','loyalty_points_earned',"loyalty_points_earned INT(11) NOT NULL DEFAULT 0");
    loyalty_add_col($pdo,'orders','loyalty_points_redeemed',"loyalty_points_redeemed INT(11) NOT NULL DEFAULT 0");
    loyalty_add_col($pdo,'orders','loyalty_point_value',"loyalty_point_value INT(11) NOT NULL DEFAULT 0");
    loyalty_add_col($pdo,'orders','loyalty_redeem_amount',"loyalty_redeem_amount INT(11) NOT NULL DEFAULT 0");
    loyalty_add_col($pdo,'orders','nominal_point',"nominal_point INT(11) NOT NULL DEFAULT 0");
    loyalty_add_col($pdo,'orders','loyalty_claim_code',"loyalty_claim_code VARCHAR(40) DEFAULT NULL");
    loyalty_add_col($pdo,'orders','loyalty_claim_points',"loyalty_claim_points INT(11) NOT NULL DEFAULT 0");
    loyalty_add_col($pdo,'orders','loyalty_claim_status',"loyalty_claim_status ENUM('none','unclaimed','claimed','expired','cancelled') NOT NULL DEFAULT 'none'");
    loyalty_try_add_key($pdo,'orders','KEY idx_orders_member_id (member_id)');
    loyalty_try_add_key($pdo,'orders','KEY idx_orders_customer_phone (customer_phone)');
    loyalty_try_add_key($pdo,'orders','UNIQUE KEY uniq_orders_loyalty_claim_code (loyalty_claim_code)');

    // Tambahkan jenis metode bayar point/mixed jika kolom masih enum lama.
    try {
        $pdo->exec("ALTER TABLE orders MODIFY payment_method ENUM('cash','qris','transfer','pending','point','point_cash','point_qris','point_transfer') NOT NULL DEFAULT 'pending'");
    } catch (Throwable $e) {}

    // Item reward diperlukan agar penukaran point tercatat sebagai transaksi kasir berbasis point.
    try {
        if (loyalty_table_exists($pdo,'order_items') && loyalty_col_exists($pdo,'order_items','item_type')) {
            $pdo->exec("ALTER TABLE order_items MODIFY item_type ENUM('chicken','matcha','kentang','drink','promo','reward') NOT NULL DEFAULT 'chicken'");
        }
    } catch (Throwable $e) {}

    // Tabel pengeluaran otomatis untuk mencatat beban penukaran point.
    // Setiap transaksi/penukaran yang berhasil memakai point akan dibuatkan expense otomatis.
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS expenses (
            id INT(11) NOT NULL AUTO_INCREMENT,
            expense_date DATE NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            amount INT(11) NOT NULL DEFAULT 0,
            created_by INT(11) DEFAULT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY idx_expense_date (expense_date),
            KEY idx_expense_category (category),
            KEY idx_expense_created_by (created_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        loyalty_add_col($pdo,'expenses','is_auto',"is_auto TINYINT(1) NOT NULL DEFAULT 0 AFTER amount");
        loyalty_add_col($pdo,'expenses','auto_key',"auto_key VARCHAR(120) DEFAULT NULL AFTER is_auto");
        loyalty_add_col($pdo,'expenses','source_ref',"source_ref VARCHAR(120) DEFAULT NULL AFTER auto_key");
        loyalty_try_add_key($pdo,'expenses','KEY idx_expense_auto_key (auto_key)');
        loyalty_try_add_key($pdo,'expenses','KEY idx_expense_source_ref (source_ref)');
    } catch (Throwable $e) {}
}

function loyalty_settings(PDO $pdo): array {
    loyalty_ensure_tables($pdo);
    $row=$pdo->query("SELECT * FROM loyalty_settings WHERE id=1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if(!$row) $row=['earn_amount'=>1000,'earn_point'=>1,'redeem_point_value'=>500,'minimum_redeem_points'=>10,'maximum_redeem_percent'=>100,'claim_expiry_days'=>14,'profile_bonus_points'=>2,'is_active'=>1];
    foreach(['earn_amount','earn_point','redeem_point_value','minimum_redeem_points','maximum_redeem_percent','claim_expiry_days','profile_bonus_points','is_active'] as $k) $row[$k]=(int)($row[$k] ?? 0);
    if($row['earn_amount']<=0) $row['earn_amount']=1000;
    if($row['earn_point']<=0) $row['earn_point']=1;
    if($row['redeem_point_value']<=0) $row['redeem_point_value']=500;
    if(($row['profile_bonus_points'] ?? 0)<=0) $row['profile_bonus_points']=2;
    return $row;
}
function loyalty_calc_earn_points(PDO $pdo, int $amount): int {
    $s=loyalty_settings($pdo);
    if((int)$s['is_active']!==1 || $amount<=0) return 0;
    return (int)floor($amount / max(1,$s['earn_amount'])) * max(1,$s['earn_point']);
}
function loyalty_member_code(int $id): string { return 'MBR'.str_pad((string)$id,6,'0',STR_PAD_LEFT); }
function loyalty_find_member_by_phone(PDO $pdo, string $phone) {
    loyalty_ensure_tables($pdo);
    $p = loyalty_normalize_phone($phone);
    if ($p === '') return false;
    $st = $pdo->prepare("SELECT * FROM members WHERE phone=? OR phone=? OR REPLACE(REPLACE(phone,'-',''),' ','')=? LIMIT 1");
    $st->execute([$p, $phone, $p]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) return $row;

    if (strlen($p) >= 9) {
        $tail = substr($p, -9);
        $st = $pdo->prepare("SELECT * FROM members WHERE REPLACE(REPLACE(phone,'-',''),' ','') LIKE ? LIMIT 1");
        $st->execute(['%' . $tail]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
    }
    return false;
}
function loyalty_member_by_id(PDO $pdo, int $id) {
    loyalty_ensure_tables($pdo);
    $st=$pdo->prepare("SELECT * FROM members WHERE id=? LIMIT 1"); $st->execute([$id]); return $st->fetch(PDO::FETCH_ASSOC);
}
function loyalty_create_member(PDO $pdo, string $phone, string $name='', string $pin='', $createdBy=null): array {
    loyalty_ensure_tables($pdo);
    $p=loyalty_normalize_phone($phone);
    if(strlen($p)<9) throw new Exception('Nomor HP member tidak valid.');
    $existing=loyalty_find_member_by_phone($pdo,$p);
    if($existing) return $existing;
    $hash=$pin!=='' ? password_hash($pin,PASSWORD_DEFAULT) : null;
    $st=$pdo->prepare("INSERT INTO members (name,phone,pin_hash,status) VALUES (?,?,?,'active')");
    $st->execute([$name ?: null,$p,$hash]);
    $id=(int)$pdo->lastInsertId();
    $code=loyalty_member_code($id);
    $pdo->prepare("UPDATE members SET member_code=? WHERE id=?")->execute([$code,$id]);
    loyalty_activity($pdo,$id,$p,'member_created','Member dibuat'.($createdBy?' oleh user '.$createdBy:''));
    return loyalty_member_by_id($pdo,$id);
}
function loyalty_activity(PDO $pdo, $memberId, ?string $phone, string $type, string $description=''): void {
    try {
        $ip=(string)($_SERVER['REMOTE_ADDR'] ?? 'cli');
        $ua=substr((string)($_SERVER['HTTP_USER_AGENT'] ?? 'cli'),0,250);
        $st=$pdo->prepare("INSERT INTO member_activity_logs (member_id,phone,activity_type,ip_address,user_agent,description) VALUES (?,?,?,?,?,?)");
        $st->execute([$memberId ?: null, loyalty_normalize_phone((string)$phone), $type, $ip, $ua, $description]);
    } catch (Throwable $e) {}
}
function loyalty_rate_limited(PDO $pdo, string $phone, string $activity='member_login_failed', int $limit=5, int $minutes=10): bool {
    loyalty_ensure_tables($pdo);
    $p=loyalty_normalize_phone($phone);
    $ip=(string)($_SERVER['REMOTE_ADDR'] ?? 'cli');
    $st=$pdo->prepare("SELECT COUNT(*) FROM member_activity_logs WHERE activity_type=? AND (phone=? OR ip_address=?) AND created_at >= (NOW() - INTERVAL ? MINUTE)");
    $st->execute([$activity,$p,$ip,$minutes]);
    return (int)$st->fetchColumn() >= $limit;
}
function loyalty_add_points(PDO $pdo, int $memberId, int $pointsIn, string $type, string $description='', $transactionId=null, $receiptClaimId=null, $createdBy=null): int {
    $pointsIn=max(0,$pointsIn);
    if($pointsIn<=0) return (int)(loyalty_member_by_id($pdo,$memberId)['total_points'] ?? 0);
    $st=$pdo->prepare("SELECT * FROM members WHERE id=? FOR UPDATE"); $st->execute([$memberId]); $m=$st->fetch(PDO::FETCH_ASSOC);
    if(!$m) throw new Exception('Member tidak ditemukan.');
    $balance=(int)$m['total_points'] + $pointsIn;
    $pdo->prepare("UPDATE members SET total_points=?, updated_at=NOW() WHERE id=?")->execute([$balance,$memberId]);
    $ins=$pdo->prepare("INSERT INTO member_point_logs (member_id,transaction_id,receipt_claim_id,type,points_in,points_out,balance_after,description,created_by) VALUES (?,?,?,?,?,?,?,?,?)");
    $ins->execute([$memberId,$transactionId,$receiptClaimId,$type,$pointsIn,0,$balance,$description,$createdBy]);
    return $balance;
}
function loyalty_deduct_points(PDO $pdo, int $memberId, int $pointsOut, string $type, string $description='', $transactionId=null, $createdBy=null): int {
    $pointsOut=max(0,$pointsOut);
    if($pointsOut<=0) return (int)(loyalty_member_by_id($pdo,$memberId)['total_points'] ?? 0);
    $st=$pdo->prepare("SELECT * FROM members WHERE id=? FOR UPDATE"); $st->execute([$memberId]); $m=$st->fetch(PDO::FETCH_ASSOC);
    if(!$m) throw new Exception('Member tidak ditemukan.');
    if((int)$m['status']!==1 && ($m['status'] ?? 'active')!=='active') throw new Exception('Member tidak aktif.');
    if((int)$m['total_points'] < $pointsOut) throw new Exception('Point member tidak mencukupi.');
    $balance=(int)$m['total_points'] - $pointsOut;
    $pdo->prepare("UPDATE members SET total_points=?, updated_at=NOW() WHERE id=?")->execute([$balance,$memberId]);
    $ins=$pdo->prepare("INSERT INTO member_point_logs (member_id,transaction_id,type,points_in,points_out,balance_after,description,created_by) VALUES (?,?,?,?,?,?,?,?)");
    $ins->execute([$memberId,$transactionId,$type,0,$pointsOut,$balance,$description,$createdBy]);
    return $balance;
}
function loyalty_profile_is_complete(array $member): bool {
    $name = trim((string)($member['name'] ?? ''));
    $email = trim((string)($member['email'] ?? ''));
    $gender = trim((string)($member['gender'] ?? ''));
    $birth = trim((string)($member['birth_date'] ?? ''));
    $address = trim((string)($member['address'] ?? ''));
    // Bonus diberikan saat nama diisi dan minimal satu data tambahan dilengkapi.
    return $name !== '' && ($email !== '' || $gender !== '' || $birth !== '' || $address !== '');
}
function loyalty_validate_member_profile(array $data): array {
    $name = trim((string)($data['name'] ?? ''));
    $email = trim((string)($data['email'] ?? ''));
    $gender = trim((string)($data['gender'] ?? ''));
    $birth = trim((string)($data['birth_date'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));
    if($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Format email tidak valid.');
    if($gender !== '' && !in_array($gender, ['pria','wanita','lainnya'], true)) $gender='';
    if($birth !== ''){
        $dt = DateTime::createFromFormat('Y-m-d', $birth);
        if(!$dt || $dt->format('Y-m-d') !== $birth) throw new Exception('Format tanggal lahir tidak valid.');
    } else $birth = null;
    if(strlen($address) > 255) $address = substr($address, 0, 255);
    return ['name'=>$name,'email'=>$email ?: null,'gender'=>$gender ?: null,'birth_date'=>$birth,'address'=>$address ?: null];
}
function loyalty_update_member_profile(PDO $pdo, int $memberId, array $data, $createdBy=null): array {
    loyalty_ensure_tables($pdo);
    $clean = loyalty_validate_member_profile($data);
    $pdo->prepare("UPDATE members SET name=?, email=?, gender=?, birth_date=?, address=?, updated_at=NOW() WHERE id=?")
        ->execute([$clean['name'] ?: null, $clean['email'], $clean['gender'], $clean['birth_date'], $clean['address'], $memberId]);
    loyalty_activity($pdo, $memberId, null, 'member_profile_update', 'Update profil member');
    return loyalty_award_profile_bonus_if_needed($pdo, $memberId, $createdBy);
}
function loyalty_award_profile_bonus_if_needed(PDO $pdo, int $memberId, $createdBy=null): array {
    loyalty_ensure_tables($pdo);
    $member = loyalty_member_by_id($pdo, $memberId);
    if(!$member) throw new Exception('Member tidak ditemukan.');
    $complete = loyalty_profile_is_complete($member);
    $awarded = !empty($member['profile_bonus_awarded_at']);
    $bonus = (int)(loyalty_settings($pdo)['profile_bonus_points'] ?? 2);
    if($complete && empty($member['profile_completed_at'])){
        $pdo->prepare("UPDATE members SET profile_completed_at=NOW() WHERE id=?")->execute([$memberId]);
        $member['profile_completed_at'] = date('Y-m-d H:i:s');
    }
    if($complete && !$awarded && $bonus > 0){
        $pdo->beginTransaction();
        try{
            $balance = loyalty_add_points($pdo, $memberId, $bonus, 'profile_bonus', 'Bonus melengkapi data member', null, null, $createdBy);
            $pdo->prepare("UPDATE members SET profile_bonus_awarded_at=NOW(), profile_completed_at=COALESCE(profile_completed_at,NOW()) WHERE id=?")->execute([$memberId]);
            $pdo->commit();
            return ['complete'=>true,'awarded'=>true,'bonus'=>$bonus,'balance'=>$balance,'message'=>'Profil berhasil dilengkapi. Bonus '.$bonus.' point telah ditambahkan.'];
        }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
    }
    return ['complete'=>$complete,'awarded'=>$awarded,'bonus'=>0,'balance'=>(int)($member['total_points'] ?? 0),'message'=>$complete ? 'Profil berhasil disimpan.' : 'Profil disimpan. Lengkapi nama dan minimal satu data tambahan untuk mendapat bonus point.'];
}

function loyalty_generate_claim_code(PDO $pdo, string $prefix='LMR'): string {
    loyalty_ensure_tables($pdo);
    for($i=0;$i<20;$i++){
        $raw=strtoupper(bin2hex(random_bytes(4)));
        $code=$prefix.'-'.substr($raw,0,4).substr($raw,4,4);
        $st=$pdo->prepare("SELECT COUNT(*) FROM receipt_claims WHERE claim_code=?"); $st->execute([$code]);
        if((int)$st->fetchColumn()===0) return $code;
    }
    return $prefix.'-'.strtoupper(substr(md5(uniqid('',true)),0,10));
}
function loyalty_create_receipt_claim(PDO $pdo, int $orderId, int $points): ?array {
    loyalty_ensure_tables($pdo);
    $points=max(0,$points);
    if($points<=0) return null;
    $settings=loyalty_settings($pdo);
    $days=max(1,(int)$settings['claim_expiry_days']);
    $code=loyalty_generate_claim_code($pdo);
    $st=$pdo->prepare("INSERT INTO receipt_claims (transaction_id,claim_code,claim_points,status,expired_at) VALUES (?,?,?,'unclaimed',DATE_ADD(NOW(), INTERVAL ? DAY))");
    $st->execute([$orderId,$code,$points,$days]);
    try{ $pdo->prepare("UPDATE orders SET loyalty_claim_code=?, loyalty_claim_points=?, loyalty_claim_status='unclaimed' WHERE id=?")->execute([$code,$points,$orderId]); }catch(Throwable $e){}
    return ['id'=>(int)$pdo->lastInsertId(),'claim_code'=>$code,'claim_points'=>$points,'expired_days'=>$days];
}
function loyalty_claim_receipt(PDO $pdo, int $memberId, string $code): array {
    loyalty_ensure_tables($pdo);
    $code=strtoupper(trim($code));
    if($code==='') throw new Exception('Kode struk wajib diisi.');
    $pdo->beginTransaction();
    try{
        $st=$pdo->prepare("SELECT rc.*, o.order_number AS order_no, o.payment_status, o.order_status, o.member_id AS order_member_id FROM receipt_claims rc JOIN orders o ON o.id=rc.transaction_id WHERE rc.claim_code=? FOR UPDATE");
        $st->execute([$code]);
        $rc=$st->fetch(PDO::FETCH_ASSOC);
        if(!$rc) throw new Exception('Kode struk tidak ditemukan.');
        if($rc['status']!=='unclaimed') throw new Exception('Kode struk sudah pernah diklaim atau tidak aktif.');
        if(!empty($rc['expired_at']) && strtotime($rc['expired_at']) < time()){
            $pdo->prepare("UPDATE receipt_claims SET status='expired' WHERE id=?")->execute([$rc['id']]);
            try{ $pdo->prepare("UPDATE orders SET loyalty_claim_status='expired' WHERE id=?")->execute([$rc['transaction_id']]); }catch(Throwable $e){}
            throw new Exception('Kode struk sudah kedaluwarsa.');
        }
        if($rc['payment_status']!=='paid' || $rc['order_status']==='cancelled') throw new Exception('Transaksi belum lunas atau sudah dibatalkan.');
        if((int)($rc['order_member_id'] ?? 0)>0) throw new Exception('Point transaksi ini sudah terhubung ke member saat pembayaran.');
        $points=(int)$rc['claim_points'];
        $balance=loyalty_add_points($pdo,$memberId,$points,'claim_receipt','Klaim struk '.$rc['order_no'],(int)$rc['transaction_id'],(int)$rc['id'],null);
        $pdo->prepare("UPDATE receipt_claims SET status='claimed', claimed_by_member_id=?, claimed_at=NOW() WHERE id=?")->execute([$memberId,$rc['id']]);
        try{ $pdo->prepare("UPDATE orders SET member_id=?, loyalty_points_earned=?, loyalty_claim_status='claimed' WHERE id=?")->execute([$memberId,$points,$rc['transaction_id']]); }catch(Throwable $e){}
        try{ loyalty_sync_member_purchase_stats($pdo,$memberId); }catch(Throwable $e){}
        $pdo->commit();
        return ['points'=>$points,'balance'=>$balance,'order_no'=>$rc['order_no']];
    }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function loyalty_sync_member_purchase_stats(PDO $pdo, int $memberId): void {
    try{
        $st=$pdo->prepare("SELECT COUNT(*) trx, COALESCE(SUM(grand_total),0) spent FROM orders WHERE member_id=? AND payment_status='paid' AND order_status<>'cancelled'");
        $st->execute([$memberId]); $r=$st->fetch(PDO::FETCH_ASSOC) ?: ['trx'=>0,'spent'=>0];
        $pdo->prepare("UPDATE members SET total_transactions=?, total_spent=? WHERE id=?")->execute([(int)$r['trx'],(int)$r['spent'],$memberId]);
    } catch(Throwable $e) {}
}
function loyalty_apply_order_after_insert(PDO $pdo, int $orderId, ?array $member, int $cashPaidAmount, int $redeemPoints, int $redeemAmount, $createdBy=null): array {
    loyalty_ensure_tables($pdo);
    $earned=loyalty_calc_earn_points($pdo,$cashPaidAmount);
    $claim=null;
    if($member && (int)$member['id']>0){
        if($redeemPoints>0) loyalty_deduct_points($pdo,(int)$member['id'],$redeemPoints,'redeem_payment','Bayar order dengan point',$orderId,$createdBy);
        if($earned>0) loyalty_add_points($pdo,(int)$member['id'],$earned,'earn_transaction','Point pembelian order',$orderId,null,$createdBy);
        loyalty_sync_member_purchase_stats($pdo,(int)$member['id']);
        try{ $pdo->prepare("UPDATE orders SET loyalty_points_earned=?, loyalty_points_redeemed=?, loyalty_redeem_amount=?, loyalty_point_value=?, nominal_point=?, loyalty_claim_status='none' WHERE id=?")->execute([$earned,$redeemPoints,$redeemAmount,(int)(loyalty_settings($pdo)['redeem_point_value'] ?? 0),$redeemAmount,$orderId]); }catch(Throwable $e){ try{ loyalty_set_order_nominal_point($pdo,$orderId,$redeemAmount); }catch(Throwable $e2){} }
    }else{
        if($earned>0) $claim=loyalty_create_receipt_claim($pdo,$orderId,$earned);
    }
    return ['earned'=>$earned,'claim'=>$claim];
}



function loyalty_member_base_url(): string {
    if (defined('LOYALTY_MEMBER_BASE_URL')) return rtrim((string)LOYALTY_MEMBER_BASE_URL,'/').'/';
    return 'https://lokapedia.id/lumero/member/';
}
function loyalty_member_claim_url(string $claimCode=''): string {
    $base = loyalty_member_base_url();
    $code = strtoupper(trim($claimCode));
    return $code !== '' ? $base.'?claim='.rawurlencode($code) : $base;
}
function loyalty_member_qr_url(string $claimCode='', int $size=180): string {
    $url = loyalty_member_claim_url($claimCode);
    return 'https://quickchart.io/qr?size='.(int)$size.'&margin=2&text='.rawurlencode($url);
}
function loyalty_required_points_for_amount(PDO $pdo, int $amount): int {
    $value = max(1, (int)(loyalty_settings($pdo)['redeem_point_value'] ?? 500));
    return (int)ceil(max(0,$amount) / $value);
}
function loyalty_nominal_point_value(PDO $pdo, int $points, int $fallbackAmount=0): int {
    // Nominal point dicatat sebagai nilai omzet non-tunai. Default mengikuti nilai redeem internal.
    $points = max(0, $points);
    $fallbackAmount = max(0, $fallbackAmount);
    if($fallbackAmount > 0) return $fallbackAmount;
    $settings = loyalty_settings($pdo);
    $value = max(1, (int)($settings['redeem_point_value'] ?? 500));
    return $points * $value;
}
function loyalty_set_order_nominal_point(PDO $pdo, int $orderId, int $nominalPoint): void {
    $nominalPoint = max(0, $nominalPoint);
    try{
        if($orderId>0 && loyalty_table_exists($pdo,'orders') && loyalty_col_exists($pdo,'orders','nominal_point')){
            $pdo->prepare("UPDATE orders SET nominal_point=? WHERE id=?")->execute([$nominalPoint,$orderId]);
        }
    }catch(Throwable $e){}
}
function loyalty_get_active_menu_products(PDO $pdo): array {
    try{
        if(!loyalty_table_exists($pdo,'menu_items')) return [];
        $join = loyalty_table_exists($pdo,'menu_categories') ? " LEFT JOIN menu_categories mc ON mc.id=mi.category_id" : "";
        $catSel = loyalty_table_exists($pdo,'menu_categories') ? "COALESCE(mc.name,'')" : "''";
        $catOrder = loyalty_table_exists($pdo,'menu_categories') ? "COALESCE(mc.sort_order,99)," : "";
        $sql = "SELECT mi.id, mi.name, mi.description, mi.price, mi.hpp, mi.image_url, mi.category_id, $catSel AS category_name FROM menu_items mi $join WHERE mi.is_active=1 ORDER BY $catOrder mi.sort_order, mi.name, mi.id";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }catch(Throwable $e){ return []; }
}
function loyalty_menu_product_by_id(PDO $pdo, int $id) {
    try{
        if($id<=0 || !loyalty_table_exists($pdo,'menu_items')) return false;
        $join = loyalty_table_exists($pdo,'menu_categories') ? " LEFT JOIN menu_categories mc ON mc.id=mi.category_id" : "";
        $catSel = loyalty_table_exists($pdo,'menu_categories') ? "COALESCE(mc.name,'')" : "''";
        $st=$pdo->prepare("SELECT mi.id, mi.name, mi.description, mi.price, mi.hpp, mi.image_url, mi.category_id, mi.is_active, $catSel AS category_name FROM menu_items mi $join WHERE mi.id=? LIMIT 1");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }catch(Throwable $e){ return false; }
}
function loyalty_reward_hpp_value(PDO $pdo, array $reward): int {
    $sourceId = (int)($reward['source_menu_item_id'] ?? 0);
    if($sourceId>0){
        $src = loyalty_menu_product_by_id($pdo,$sourceId);
        if($src && isset($src['hpp'])) return max(0,(int)$src['hpp']);
    }
    if(isset($reward['source_hpp']) && $reward['source_hpp'] !== null && $reward['source_hpp'] !== '') return max(0,(int)$reward['source_hpp']);
    return 0;
}
function loyalty_reward_nominal_value(PDO $pdo, array $reward, int $pointsUsed=0): int {
    $custom = isset($reward['nominal_value']) && $reward['nominal_value'] !== null && $reward['nominal_value'] !== '' ? (int)$reward['nominal_value'] : 0;
    if($custom > 0) return $custom;
    $sourceId = (int)($reward['source_menu_item_id'] ?? 0);
    if($sourceId>0){
        $src = loyalty_menu_product_by_id($pdo,$sourceId);
        if($src && isset($src['price']) && (int)$src['price']>0) return (int)$src['price'];
    }
    if(isset($reward['source_price']) && $reward['source_price'] !== null && $reward['source_price'] !== '' && (int)$reward['source_price']>0) return (int)$reward['source_price'];
    $points = $pointsUsed > 0 ? $pointsUsed : (int)($reward['required_points'] ?? 0);
    return loyalty_nominal_point_value($pdo, max(0,$points));
}
function loyalty_reward_select_sql(PDO $pdo): string {
    if(loyalty_table_exists($pdo,'menu_items')){
        $joinCat = loyalty_table_exists($pdo,'menu_categories') ? " LEFT JOIN menu_categories mc ON mc.id=mi.category_id" : "";
        $catSel = loyalty_table_exists($pdo,'menu_categories') ? "COALESCE(mc.name,'')" : "''";
        return "SELECT prp.*, mi.name AS source_menu_name, mi.price AS source_menu_price, mi.hpp AS source_menu_hpp, mi.image_url AS source_menu_image_url, mi.is_active AS source_menu_active, $catSel AS source_menu_category FROM point_reward_products prp LEFT JOIN menu_items mi ON mi.id=prp.source_menu_item_id $joinCat";
    }
    return "SELECT prp.*, NULL AS source_menu_name, NULL AS source_menu_price, NULL AS source_menu_hpp, NULL AS source_menu_image_url, NULL AS source_menu_active, NULL AS source_menu_category FROM point_reward_products prp";
}
function loyalty_get_reward_products(PDO $pdo, bool $onlyActive=true): array {
    loyalty_ensure_tables($pdo);
    try {
        $where = $onlyActive ? "WHERE prp.is_active=1 AND (prp.visible_from IS NULL OR prp.visible_from<=NOW()) AND (prp.visible_until IS NULL OR prp.visible_until>=NOW())" : "";
        if($onlyActive && loyalty_table_exists($pdo,'menu_items')) $where .= " AND (prp.source_menu_item_id IS NULL OR mi.is_active=1)";
        return $pdo->query(loyalty_reward_select_sql($pdo)." $where ORDER BY prp.sort_order ASC, prp.id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { return []; }
}
function loyalty_reward_product_by_id(PDO $pdo, int $id) {
    loyalty_ensure_tables($pdo);
    if($id<=0) return false;
    try{ $st=$pdo->prepare(loyalty_reward_select_sql($pdo)." WHERE prp.id=? LIMIT 1"); $st->execute([$id]); return $st->fetch(PDO::FETCH_ASSOC); }catch(Throwable $e){ return false; }
}
function loyalty_reward_product_has_redemptions(PDO $pdo, int $id): bool {
    loyalty_ensure_tables($pdo);
    try{ $st=$pdo->prepare("SELECT COUNT(*) FROM point_reward_redemptions WHERE reward_product_id=?"); $st->execute([$id]); return (int)$st->fetchColumn()>0; }catch(Throwable $e){ return false; }
}
function loyalty_clean_reward_product_data(array $data): array {
    $sourceId = max(0,(int)($data['source_menu_item_id'] ?? 0));
    $name = trim((string)($data['name'] ?? ''));
    if($name==='' && $sourceId<=0) throw new Exception('Nama produk penukaran wajib diisi, atau pilih produk aktif dari daftar menu.');
    $desc = trim((string)($data['description'] ?? ''));
    $category = trim((string)($data['category'] ?? ''));
    $terms = trim((string)($data['terms'] ?? ''));
    $required = max(1,(int)($data['required_points'] ?? 0));
    $nominalRaw = trim((string)($data['nominal_value'] ?? ''));
    $nominal = $nominalRaw==='' ? null : max(0,(int)$nominalRaw);
    $image = trim((string)($data['image_url'] ?? ''));
    $stockRaw = trim((string)($data['stock_qty'] ?? ''));
    $stock = $stockRaw==='' ? null : max(0,(int)$stockRaw);
    $sort = (int)($data['sort_order'] ?? 0);
    $from = trim((string)($data['visible_from'] ?? ''));
    $until = trim((string)($data['visible_until'] ?? ''));
    $limitRaw = trim((string)($data['max_redeem_per_member'] ?? ''));
    $limit = $limitRaw==='' ? null : max(0,(int)$limitRaw);
    $active = !empty($data['is_active']) ? 1 : 0;
    $fmt = function($v){
        $v=trim((string)$v); if($v==='') return null;
        $v=str_replace('T',' ',$v); if(strlen($v)===16) $v.=':00';
        $ts=strtotime($v); if(!$ts) return null;
        return date('Y-m-d H:i:s',$ts);
    };
    return [
        'source_menu_item_id'=>$sourceId>0?$sourceId:null,
        'source_price'=>isset($data['source_price']) && $data['source_price']!=='' ? max(0,(int)$data['source_price']) : null,
        'source_hpp'=>isset($data['source_hpp']) && $data['source_hpp']!=='' ? max(0,(int)$data['source_hpp']) : null,
        'name'=>substr($name,0,160),
        'description'=>$desc!==''?substr($desc,0,255):null,
        'category'=>$category!==''?substr($category,0,80):null,
        'terms'=>$terms!==''?substr($terms,0,255):null,
        'required_points'=>$required,
        'nominal_value'=>$nominal,
        'image_url'=>$image!==''?substr($image,0,255):null,
        'stock_qty'=>$stock,
        'sort_order'=>$sort,
        'visible_from'=>$fmt($from),
        'visible_until'=>$fmt($until),
        'max_redeem_per_member'=>$limit,
        'is_active'=>$active
    ];
}
function loyalty_save_reward_product(PDO $pdo, array $data, int $id=0): array {
    loyalty_ensure_tables($pdo);
    $d=loyalty_clean_reward_product_data($data);
    if((int)($d['source_menu_item_id'] ?? 0)>0){
        $src=loyalty_menu_product_by_id($pdo,(int)$d['source_menu_item_id']);
        if(!$src || (int)($src['is_active'] ?? 0)!==1) throw new Exception('Produk sumber tidak ditemukan atau tidak aktif. Pilih produk aktif dari menu.');
        $d['source_price']=max(0,(int)($src['price'] ?? 0));
        $d['source_hpp']=max(0,(int)($src['hpp'] ?? 0));
        $d['name']=substr((string)$src['name'],0,160);
        if(empty($d['description']) && !empty($src['description'])) $d['description']=substr((string)$src['description'],0,255);
        if(empty($d['category']) && !empty($src['category_name'])) $d['category']=substr((string)$src['category_name'],0,80);
        if(empty($d['image_url']) && !empty($src['image_url'])) $d['image_url']=substr((string)$src['image_url'],0,255);
        if($d['nominal_value']===null || (int)$d['nominal_value']<=0) $d['nominal_value']=$d['source_price'];
    }else{
        $d['source_menu_item_id']=null; $d['source_price']=null; $d['source_hpp']=null;
    }
    if($id>0){
        $sql="UPDATE point_reward_products SET source_menu_item_id=?, source_price=?, source_hpp=?, name=?, description=?, category=?, terms=?, required_points=?, nominal_value=?, image_url=?, stock_qty=?, is_active=?, sort_order=?, visible_from=?, visible_until=?, max_redeem_per_member=?, updated_at=NOW() WHERE id=?";
        $pdo->prepare($sql)->execute([$d['source_menu_item_id'],$d['source_price'],$d['source_hpp'],$d['name'],$d['description'],$d['category'],$d['terms'],$d['required_points'],$d['nominal_value'],$d['image_url'],$d['stock_qty'],$d['is_active'],$d['sort_order'],$d['visible_from'],$d['visible_until'],$d['max_redeem_per_member'],$id]);
    }else{
        $sql="INSERT INTO point_reward_products (source_menu_item_id,source_price,source_hpp,name,description,category,terms,required_points,nominal_value,image_url,stock_qty,is_active,sort_order,visible_from,visible_until,max_redeem_per_member) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $pdo->prepare($sql)->execute([$d['source_menu_item_id'],$d['source_price'],$d['source_hpp'],$d['name'],$d['description'],$d['category'],$d['terms'],$d['required_points'],$d['nominal_value'],$d['image_url'],$d['stock_qty'],$d['is_active'],$d['sort_order'],$d['visible_from'],$d['visible_until'],$d['max_redeem_per_member']]);
        $id=(int)$pdo->lastInsertId();
    }
    return loyalty_reward_product_by_id($pdo,$id) ?: $d;
}
function loyalty_delete_or_deactivate_reward_product(PDO $pdo, int $id): string {
    loyalty_ensure_tables($pdo);
    if($id<=0) throw new Exception('Produk penukaran tidak valid.');
    if(loyalty_reward_product_has_redemptions($pdo,$id)){
        $pdo->prepare("UPDATE point_reward_products SET is_active=0, updated_at=NOW() WHERE id=?")->execute([$id]);
        return 'Produk sudah memiliki riwayat penukaran, sehingga dinonaktifkan dan tidak dihapus.';
    }
    $pdo->prepare("DELETE FROM point_reward_products WHERE id=?")->execute([$id]);
    return 'Produk penukaran berhasil dihapus.';
}
function loyalty_generate_redemption_code(PDO $pdo, string $prefix='RDM'): string {
    loyalty_ensure_tables($pdo);
    for($i=0;$i<20;$i++){
        $raw=strtoupper(bin2hex(random_bytes(4)));
        $code=$prefix.'-'.substr($raw,0,4).'-'.substr($raw,4,4);
        try { $st=$pdo->prepare("SELECT COUNT(*) FROM point_reward_redemptions WHERE redemption_code=?"); $st->execute([$code]); if((int)$st->fetchColumn()===0) return $code; } catch(Throwable $e) { return $code; }
    }
    return $prefix.'-'.strtoupper(substr(md5(uniqid('',true)),0,10));
}
function loyalty_next_point_order_no(PDO $pdo): string {
    try{
        $max=(int)$pdo->query("SELECT COALESCE(MAX(CAST(SUBSTRING(order_no,4) AS UNSIGNED)),1999) FROM orders WHERE order_no REGEXP '^DCK[0-9]+$'")->fetchColumn();
        for($i=1;$i<50;$i++){
            $no='DCK'.($max+$i);
            $st=$pdo->prepare("SELECT COUNT(*) FROM orders WHERE order_no=?"); $st->execute([$no]);
            if((int)$st->fetchColumn()===0) return $no;
        }
    }catch(Throwable $e){}
    if(function_exists('order_no')) return order_no('DCK');
    return 'DCK'.date('ymdHis').random_int(10,99);
}
function loyalty_insert_reward_order(PDO $pdo, array $member, array $reward, int $pointsUsed, string $redemptionCode, $createdBy=null): int {
    loyalty_ensure_tables($pdo);
    if(!loyalty_table_exists($pdo,'orders')) return 0;
    $orderNo=loyalty_next_point_order_no($pdo);
    $memberId=(int)($member['id'] ?? 0);
    $customerName=trim((string)($member['name'] ?? 'Member Loyalty')) ?: 'Member Loyalty';
    $phone=loyalty_normalize_phone((string)($member['phone'] ?? ''));
    $note='Penukaran reward member '.$redemptionCode.' - '.$reward['name'].' (menunggu diserahkan; akan masuk transaksi hari ini saat status completed)';
    $pointNominal = loyalty_reward_nominal_value($pdo, $reward, $pointsUsed);
    $rewardHpp = loyalty_reward_hpp_value($pdo, $reward);
    $cols=[]; $vals=[];
    $add=function($col,$val) use (&$cols,&$vals,$pdo){ if(loyalty_col_exists($pdo,'orders',$col)){ $cols[]=$col; $vals[]=$val; } };
    $add('brand_id',1); $add('outlet_id',1);
    $add('order_no',$orderNo); $add('channel','kasir'); $add('order_source','wic');
    $add('customer_name',$customerName); $add('customer_phone',$phone); $add('member_id',$memberId ?: null);
    // Order reward dibuat sebagai catatan pending dulu. Baru dihitung sebagai transaksi harian setelah status redemption = completed.
    $add('payment_method','point'); $add('payment_status','unpaid');
    $add('subtotal',$pointNominal); $add('tax',0); $add('discount',0); $add('discount_note',$note); $add('total',0);
    $add('total_hpp',$rewardHpp); $add('gross_profit',max(0,$pointNominal-$rewardHpp));
    $add('loyalty_points_earned',0); $add('loyalty_points_redeemed',$pointsUsed); $add('loyalty_point_value',(int)(loyalty_settings($pdo)['redeem_point_value'] ?? 0)); $add('loyalty_redeem_amount',$pointNominal); $add('nominal_point',$pointNominal); $add('loyalty_claim_status','none');
    $add('paid_amount',0); $add('change_amount',0); $add('status','waiting_payment');
    $add('print_status','printed'); $add('print_error','Auto record penukaran reward; tidak dicetak otomatis.');
    $add('created_by',$createdBy); $add('paid_at',null);
    if(!$cols) return 0;
    $sql="INSERT INTO orders (`".implode('`,`',$cols)."`) VALUES (".implode(',',array_fill(0,count($cols),'?')).")";
    $st=$pdo->prepare($sql); $st->execute($vals);
    $orderId=(int)$pdo->lastInsertId();
    if($orderId>0 && loyalty_table_exists($pdo,'order_items')){
        $icols=[]; $ivals=[];
        $iadd=function($col,$val) use (&$icols,&$ivals,$pdo){ if(loyalty_col_exists($pdo,'order_items',$col)){ $icols[]=$col; $ivals[]=$val; } };
        $iadd('brand_id',1); $iadd('outlet_id',1); $iadd('order_id',$orderId); $iadd('item_type','reward');
        $iadd('item_name','[REWARD] '.$reward['name']); $iadd('qty',1); $iadd('price',$pointNominal); $iadd('hpp',$rewardHpp); $iadd('line_total',$pointNominal); $iadd('line_hpp',$rewardHpp); $iadd('line_profit',$pointNominal-$rewardHpp);
        if($icols){
            try{ $pdo->prepare("INSERT INTO order_items (`".implode('`,`',$icols)."`) VALUES (".implode(',',array_fill(0,count($icols),'?')).")")->execute($ivals); }catch(Throwable $e){}
        }
    }
    return $orderId;
}

function loyalty_reward_order_item_exists(PDO $pdo, int $orderId): bool {
    try{
        if($orderId<=0 || !loyalty_table_exists($pdo,'order_items')) return false;
        $st=$pdo->prepare("SELECT COUNT(*) FROM order_items WHERE order_id=?");
        $st->execute([$orderId]);
        return (int)$st->fetchColumn()>0;
    }catch(Throwable $e){ return false; }
}
function loyalty_ensure_reward_order_item(PDO $pdo, int $orderId, array $reward, int $pointNominal=0): void {
    if($orderId<=0 || !loyalty_table_exists($pdo,'order_items')) return;
    $pointNominal=max(0,$pointNominal);
    $rewardHpp=loyalty_reward_hpp_value($pdo,$reward);
    if(loyalty_reward_order_item_exists($pdo,$orderId)) {
        try{
            $sets=[]; $vals=[];
            if(loyalty_col_exists($pdo,'order_items','price')){ $sets[]='price=?'; $vals[]=$pointNominal; }
            if(loyalty_col_exists($pdo,'order_items','hpp')){ $sets[]='hpp=?'; $vals[]=$rewardHpp; }
            if(loyalty_col_exists($pdo,'order_items','line_total')){ $sets[]='line_total=?'; $vals[]=$pointNominal; }
            if(loyalty_col_exists($pdo,'order_items','line_hpp')){ $sets[]='line_hpp=?'; $vals[]=$rewardHpp; }
            if(loyalty_col_exists($pdo,'order_items','line_profit')){ $sets[]='line_profit=?'; $vals[]=$pointNominal-$rewardHpp; }
            if($sets){ $vals[]=$orderId; $pdo->prepare("UPDATE order_items SET ".implode(',',$sets)." WHERE order_id=? AND item_name LIKE '[REWARD]%'")->execute($vals); }
        }catch(Throwable $e){}
        return;
    }
    $icols=[]; $ivals=[];
    $iadd=function($col,$val) use (&$icols,&$ivals,$pdo){ if(loyalty_col_exists($pdo,'order_items',$col)){ $icols[]=$col; $ivals[]=$val; } };
    $iadd('brand_id',1); $iadd('outlet_id',1); $iadd('order_id',$orderId); $iadd('item_type','reward');
    $iadd('item_name','[REWARD] '.((string)($reward['name'] ?? 'Reward Member'))); $iadd('qty',1);
    $iadd('price',$pointNominal); $iadd('hpp',$rewardHpp); $iadd('line_total',$pointNominal); $iadd('line_hpp',$rewardHpp); $iadd('line_profit',$pointNominal-$rewardHpp);
    if($icols){ try{ $pdo->prepare("INSERT INTO order_items (`".implode('`,`',$icols)."`) VALUES (".implode(',',array_fill(0,count($icols),'?')).")")->execute($ivals); }catch(Throwable $e){} }
}
function loyalty_sync_single_completed_reward_redemption(PDO $pdo, array $r, $adminId=null): bool {
    loyalty_ensure_tables($pdo);
    if(($r['status'] ?? '') !== 'completed') return false;
    $redemptionId=(int)($r['id'] ?? 0);
    if($redemptionId<=0) return false;
    $doneAt = !empty($r['completed_at']) ? (string)$r['completed_at'] : date('Y-m-d H:i:s');
    if(empty($r['completed_at'])){
        try{ $pdo->prepare("UPDATE point_reward_redemptions SET completed_at=? WHERE id=?")->execute([$doneAt,$redemptionId]); }catch(Throwable $e){}
    }
    $member=[]; $reward=[];
    try{ $st=$pdo->prepare("SELECT * FROM members WHERE id=? LIMIT 1"); $st->execute([(int)$r['member_id']]); $member=$st->fetch(PDO::FETCH_ASSOC) ?: []; }catch(Throwable $e){}
    try{ $st=$pdo->prepare("SELECT * FROM point_reward_products WHERE id=? LIMIT 1"); $st->execute([(int)$r['reward_product_id']]); $reward=$st->fetch(PDO::FETCH_ASSOC) ?: []; }catch(Throwable $e){}
    if(!$member){ $member=['id'=>(int)$r['member_id'],'name'=>'Member Loyalty','phone'=>'']; }
    if(!$reward){ $reward=['id'=>(int)$r['reward_product_id'],'name'=>'Reward Member']; }
    $pointsUsed=(int)($r['points_used'] ?? 0);
    $pointNominal=loyalty_reward_nominal_value($pdo,$reward,$pointsUsed);
    $rewardHpp=loyalty_reward_hpp_value($pdo,$reward);
    $code=(string)($r['redemption_code'] ?: ('RDM-'.$redemptionId));
    $orderId=(int)($r['order_id'] ?? 0);
    $orderExists=false;
    if($orderId>0){
        try{ $st=$pdo->prepare("SELECT COUNT(*) FROM orders WHERE id=?"); $st->execute([$orderId]); $orderExists=((int)$st->fetchColumn()>0); }catch(Throwable $e){ $orderExists=false; }
    }
    if(!$orderExists){
        $orderId=loyalty_insert_reward_order($pdo,$member,$reward,$pointsUsed,$code,$adminId);
        if($orderId>0){
            try{ $pdo->prepare("UPDATE point_reward_redemptions SET order_id=? WHERE id=?")->execute([$orderId,$redemptionId]); }catch(Throwable $e){}
        }
    }
    if($orderId<=0) return false;
    $customerName=trim((string)($member['name'] ?? 'Member Loyalty')) ?: 'Member Loyalty';
    $phone=loyalty_normalize_phone((string)($member['phone'] ?? ''));
    $note='Penukaran reward member '.$code.' - '.((string)($reward['name'] ?? 'Reward Member')).' | Completed: '.$doneAt;
    $sets=[]; $vals=[];
    $set=function($col,$val) use (&$sets,&$vals,$pdo){ if(loyalty_col_exists($pdo,'orders',$col)){ $sets[]="`$col`=?"; $vals[]=$val; } };
    $set('channel','kasir'); $set('order_source','wic');
    $set('customer_name',$customerName); $set('customer_phone',$phone); $set('member_id',(int)($member['id'] ?? 0) ?: null);
    $set('payment_method','point'); $set('payment_status','paid');
    // total tetap nominal uang yang diterima kasir; nominal_point adalah omzet yang dibayar dengan point.
    $set('subtotal',$pointNominal); $set('tax',0); $set('discount',0); $set('discount_note',$note); $set('total',0);
    $set('total_hpp',$rewardHpp); $set('gross_profit',$pointNominal-$rewardHpp);
    $set('loyalty_points_earned',0); $set('loyalty_points_redeemed',$pointsUsed); $set('loyalty_point_value',(int)(loyalty_settings($pdo)['redeem_point_value'] ?? 0)); $set('loyalty_redeem_amount',$pointNominal); $set('nominal_point',$pointNominal); $set('loyalty_claim_status','none');
    $set('paid_amount',0); $set('change_amount',0); $set('status','done');
    $set('print_status','printed'); $set('print_error','Auto record penukaran reward; tidak dicetak otomatis.');
    $set('created_at',$doneAt); $set('paid_at',$doneAt);
    if($sets){
        $vals[]=$orderId;
        try{ $pdo->prepare("UPDATE orders SET ".implode(',',$sets)." WHERE id=?")->execute($vals); }catch(Throwable $e){ return false; }
    }
    loyalty_ensure_reward_order_item($pdo,$orderId,$reward,$pointNominal);
    return true;
}
function loyalty_sync_completed_reward_redemptions(PDO $pdo, ?int $redemptionId=null, $adminId=null): array {
    loyalty_ensure_tables($pdo);
    $out=['checked'=>0,'synced'=>0,'missing_orders'=>0,'errors'=>0];
    if(!loyalty_table_exists($pdo,'point_reward_redemptions') || !loyalty_table_exists($pdo,'orders')) return $out;
    try{
        $sql="SELECT r.* FROM point_reward_redemptions r WHERE r.status='completed'";
        $params=[];
        if($redemptionId!==null && $redemptionId>0){ $sql.=" AND r.id=?"; $params[]=$redemptionId; }
        // Cek semua completed yang berpotensi belum masuk histori transaksi: order kosong, order hilang, belum paid, cancelled, atau tanggal order tidak sama dengan completed_at.
        $sql.=" ORDER BY r.id DESC LIMIT 500";
        $st=$pdo->prepare($sql); $st->execute($params);
        $rows=$st->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r){
            $out['checked']++;
            $needs=true;
            $orderId=(int)($r['order_id'] ?? 0);
            if($orderId<=0) $out['missing_orders']++;
            if($orderId>0){
                try{
                    $q=$pdo->prepare("SELECT id,payment_status,status,created_at,paid_at FROM orders WHERE id=? LIMIT 1");
                    $q->execute([$orderId]); $o=$q->fetch(PDO::FETCH_ASSOC);
                    if(!$o){ $out['missing_orders']++; }
                    else{
                        $targetDate=!empty($r['completed_at']) ? date('Y-m-d',strtotime($r['completed_at'])) : date('Y-m-d');
                        $orderDate=!empty($o['created_at']) ? date('Y-m-d',strtotime($o['created_at'])) : '';
                        $needs=(($o['payment_status'] ?? '')!=='paid' || ($o['status'] ?? '')==='cancelled' || $orderDate!==$targetDate);
                    }
                }catch(Throwable $e){ $needs=true; }
            }
            if($needs){
                try{ if(loyalty_sync_single_completed_reward_redemption($pdo,$r,$adminId)) $out['synced']++; }
                catch(Throwable $e){ $out['errors']++; }
            }
        }
    }catch(Throwable $e){ $out['errors']++; }
    return $out;
}
function loyalty_request_reward_redemption(PDO $pdo, int $memberId, int $rewardId, $createdBy=null): array {
    loyalty_ensure_tables($pdo);
    if(loyalty_table_exists($pdo,'menu_items')){
        $st=$pdo->prepare("SELECT prp.* FROM point_reward_products prp LEFT JOIN menu_items mi ON mi.id=prp.source_menu_item_id WHERE prp.id=? AND prp.is_active=1 AND (prp.visible_from IS NULL OR prp.visible_from<=NOW()) AND (prp.visible_until IS NULL OR prp.visible_until>=NOW()) AND (prp.source_menu_item_id IS NULL OR mi.is_active=1) LIMIT 1");
    }else{
        $st=$pdo->prepare("SELECT * FROM point_reward_products WHERE id=? AND is_active=1 AND (visible_from IS NULL OR visible_from<=NOW()) AND (visible_until IS NULL OR visible_until>=NOW()) LIMIT 1");
    }
    $st->execute([$rewardId]);
    $reward=$st->fetch(PDO::FETCH_ASSOC);
    if(!$reward) throw new Exception('Produk penukaran tidak ditemukan, belum aktif, atau masa penukaran sudah berakhir.');
    $need=max(1,(int)$reward['required_points']);
    $pdo->beginTransaction();
    try{
        $member=loyalty_member_by_id($pdo,$memberId);
        if(!$member) throw new Exception('Member tidak ditemukan.');
        if(isset($reward['stock_qty']) && $reward['stock_qty'] !== null && $reward['stock_qty'] !== '' && (int)$reward['stock_qty'] <= 0) throw new Exception('Stok reward sedang habis.');
        $maxPerMember=(int)($reward['max_redeem_per_member'] ?? 0);
        if($maxPerMember>0){
            $lim=$pdo->prepare("SELECT COUNT(*) FROM point_reward_redemptions WHERE member_id=? AND reward_product_id=? AND status<>'cancelled'");
            $lim->execute([$memberId,(int)$reward['id']]);
            if((int)$lim->fetchColumn() >= $maxPerMember) throw new Exception('Batas penukaran produk ini untuk member sudah tercapai.');
        }
        $code=loyalty_generate_redemption_code($pdo);
        $balance=loyalty_deduct_points($pdo,$memberId,$need,'reward_redemption','Tukar reward '.$code.': '.$reward['name'],null,$createdBy);
        if(isset($reward['stock_qty']) && $reward['stock_qty'] !== null && $reward['stock_qty'] !== ''){
            $pdo->prepare("UPDATE point_reward_products SET stock_qty=GREATEST(stock_qty-1,0) WHERE id=?")->execute([(int)$reward['id']]);
        }
        $orderId=loyalty_insert_reward_order($pdo,$member,$reward,$need,$code,$createdBy);
        $ins=$pdo->prepare("INSERT INTO point_reward_redemptions (member_id,reward_product_id,redemption_code,order_id,points_used,status,note) VALUES (?,?,?,?,?,'requested',?)");
        $ins->execute([$memberId,(int)$reward['id'],$code,$orderId ?: null,$need,'Menunggu penyerahan di kasir/outlet. Transaksi kasir otomatis tercatat sebagai pembayaran dengan point.']);
        $redemptionId=(int)$pdo->lastInsertId();
        loyalty_activity($pdo,$memberId,$member['phone'] ?? null,'reward_redemption_requested','Penukaran reward '.$code.' - '.$reward['name']);
        $pdo->commit();
        return ['id'=>$redemptionId,'code'=>$code,'order_id'=>$orderId,'reward'=>$reward,'points_used'=>$need,'balance'=>$balance];
    }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function loyalty_member_reward_redemptions(PDO $pdo, int $memberId, int $limit=80): array {
    loyalty_ensure_tables($pdo);
    try{
        $limit=max(1,min(200,$limit));
        $st=$pdo->prepare("SELECT r.*, p.name product_name, p.description product_description, p.image_url, o.order_no, COALESCE(o.nominal_point,0) AS nominal_point FROM point_reward_redemptions r LEFT JOIN point_reward_products p ON p.id=r.reward_product_id LEFT JOIN orders o ON o.id=r.order_id WHERE r.member_id=? ORDER BY r.id DESC LIMIT ".$limit);
        $st->execute([$memberId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }catch(Throwable $e){ return []; }
}
function loyalty_update_reward_redemption_status(PDO $pdo, int $redemptionId, string $status, $adminId=null, string $note=''): array {
    loyalty_ensure_tables($pdo);
    if(!in_array($status,['requested','approved','completed','cancelled'],true)) throw new Exception('Status penukaran tidak valid.');
    $pdo->beginTransaction();
    try{
        $st=$pdo->prepare("SELECT r.*, p.name product_name, p.stock_qty FROM point_reward_redemptions r LEFT JOIN point_reward_products p ON p.id=r.reward_product_id WHERE r.id=? FOR UPDATE");
        $st->execute([$redemptionId]);
        $r=$st->fetch(PDO::FETCH_ASSOC);
        if(!$r) throw new Exception('Data penukaran tidak ditemukan.');
        $old=(string)$r['status'];
        if($old==='cancelled') throw new Exception('Penukaran yang sudah dibatalkan tidak bisa diubah.');
        $extra=$note!=='' ? ' | '.$note : '';
        if($status==='cancelled' && $old!=='cancelled'){
            loyalty_add_points($pdo,(int)$r['member_id'],(int)$r['points_used'],'reward_cancelled','Pembatalan penukaran reward '.$r['redemption_code'].$extra,(int)($r['order_id'] ?: 0),null,$adminId);
            $pdo->prepare("UPDATE point_reward_redemptions SET status='cancelled', cancelled_at=NOW(), cancelled_by=?, note=? WHERE id=?")->execute([$adminId,trim(($r['note'] ?? '').$extra),$redemptionId]);
            if(!empty($r['order_id'])){ try{ $pdo->prepare("UPDATE orders SET payment_status='cancelled', status='cancelled', discount_note=CONCAT(COALESCE(discount_note,''),' | Dibatalkan') WHERE id=?")->execute([(int)$r['order_id']]); }catch(Throwable $e){} }
            try{ if($r['stock_qty'] !== null) $pdo->prepare("UPDATE point_reward_products SET stock_qty=stock_qty+1 WHERE id=?")->execute([(int)$r['reward_product_id']]); }catch(Throwable $e){}
            loyalty_activity($pdo,(int)$r['member_id'],null,'reward_redemption_cancelled','Pembatalan penukaran '.$r['redemption_code']);
        }elseif($status==='completed'){
            // Saat reward benar-benar diserahkan kasir, barulah transaksi POINT dihitung sebagai transaksi hari ini.
            $doneAt = ($old==='completed' && !empty($r['completed_at'])) ? (string)$r['completed_at'] : date('Y-m-d H:i:s');
            $pdo->prepare("UPDATE point_reward_redemptions SET status='completed', completed_at=IF(completed_at IS NULL, ?, completed_at), completed_by=COALESCE(completed_by,?), note=? WHERE id=?")->execute([$doneAt,$adminId,trim(($r['note'] ?? '').$extra),$redemptionId]);
            // Sinkronkan order kasir: buat ulang jika order_id kosong/hilang, lalu pastikan status paid/done dan tanggal transaksi = completed_at.
            $fresh=$pdo->prepare("SELECT * FROM point_reward_redemptions WHERE id=? LIMIT 1");
            $fresh->execute([$redemptionId]);
            $freshRow=$fresh->fetch(PDO::FETCH_ASSOC);
            if($freshRow){ loyalty_sync_single_completed_reward_redemption($pdo,$freshRow,$adminId); }
            loyalty_activity($pdo,(int)$r['member_id'],null,'reward_redemption_completed','Penukaran diserahkan '.$r['redemption_code'].' dan masuk transaksi '.$doneAt);
        }else{
            $pdo->prepare("UPDATE point_reward_redemptions SET status=?, note=? WHERE id=?")->execute([$status,trim(($r['note'] ?? '').$extra),$redemptionId]);
            loyalty_activity($pdo,(int)$r['member_id'],null,'reward_redemption_status','Status penukaran '.$r['redemption_code'].' menjadi '.$status);
        }
        $pdo->commit();
        return ['id'=>$redemptionId,'status'=>$status,'code'=>$r['redemption_code'],'member_id'=>(int)$r['member_id']];
    }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}

function loyalty_void_order(PDO $pdo, int $orderId, $createdBy=null): void {
    loyalty_ensure_tables($pdo);
    $st=$pdo->prepare("SELECT * FROM orders WHERE id=? FOR UPDATE"); $st->execute([$orderId]); $o=$st->fetch(PDO::FETCH_ASSOC);
    if(!$o) return;
    $memberId=(int)($o['member_id'] ?? 0);
    $earned=(int)($o['loyalty_points_earned'] ?? 0);
    $redeemed=(int)($o['loyalty_points_redeemed'] ?? 0);
    if($memberId>0){
        if($earned>0){ try{ loyalty_deduct_points($pdo,$memberId,$earned,'refund_reversal','Pembatalan point dari order '.$o['order_no'],$orderId,$createdBy); }catch(Throwable $e){} }
        if($redeemed>0){ try{ loyalty_add_points($pdo,$memberId,$redeemed,'refund_reversal','Pengembalian point karena order dibatalkan '.$o['order_no'],$orderId,null,$createdBy); }catch(Throwable $e){} }
        loyalty_sync_member_purchase_stats($pdo,$memberId);
    }
    try{ $pdo->prepare("UPDATE receipt_claims SET status='cancelled' WHERE transaction_id=? AND status='unclaimed'")->execute([$orderId]); }catch(Throwable $e){}
    try{ $pdo->prepare("UPDATE orders SET loyalty_claim_status='cancelled' WHERE id=? AND loyalty_claim_status='unclaimed'")->execute([$orderId]); }catch(Throwable $e){}
}

}
?>
