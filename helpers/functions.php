<?php
function app_env_all(): array
{
    static $values = null;
    if ($values !== null) {
        return $values;
    }

    $values = [];
    $envPath = __DIR__ . '/../.env';
    if (!is_file($envPath)) {
        return $values;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return $values;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $eqPos));
        $raw = trim(substr($line, $eqPos + 1));
        if ($key === '') {
            continue;
        }
        if ((str_starts_with($raw, '"') && str_ends_with($raw, '"')) || (str_starts_with($raw, "'") && str_ends_with($raw, "'"))) {
            $raw = substr($raw, 1, -1);
        }
        $values[$key] = $raw;
    }

    return $values;
}

function app_env(string $key, $default = null)
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    $env = app_env_all();
    if (array_key_exists($key, $env) && $env[$key] !== '') {
        return $env[$key];
    }
    return $default;
}

function app_bool($value, bool $default = false): bool
{
    if ($value === null || $value === '') {
        return $default;
    }
    if (is_bool($value)) {
        return $value;
    }
    $normalized = strtolower((string)$value);
    if (in_array($normalized, ['1', 'true', 'yes', 'on'], true)) {
        return true;
    }
    if (in_array($normalized, ['0', 'false', 'no', 'off'], true)) {
        return false;
    }
    return $default;
}

function app_is_local(): bool
{
    $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
    $server = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
    $addr = strtolower((string)($_SERVER['SERVER_ADDR'] ?? ''));
    $remote = strtolower((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $isLocalHost = in_array($host, ['localhost', '127.0.0.1'], true) || in_array($server, ['localhost', '127.0.0.1'], true);
    $isLocalAddr = in_array($addr, ['127.0.0.1', '::1'], true) || in_array($remote, ['127.0.0.1', '::1'], true);
    $isCli = PHP_SAPI === 'cli';
    $isXamppPath = stripos(__DIR__, 'xampp\\htdocs') !== false || stripos(__DIR__, '/xampp/htdocs') !== false;

    $isPhysicallyLocal = $isLocalHost || $isLocalAddr || $isCli || $isXamppPath;

    // Only respect APP_ENV=local if we are actually physically on a local machine
    $forced = app_env('APP_ENV', '');
    if (strtolower((string)$forced) === 'local' && $isPhysicallyLocal) {
        return true;
    }

    return $isPhysicallyLocal;
}

function app_config(string $key = null, $default = null)
{
    static $config;
    if (!$config) {
        $config = require __DIR__ . '/../config/app.php';
    }
    return $key ? ($config[$key] ?? $default) : $config;
}

function app_base_url(): string
{
    $configured = trim((string)app_config('base_url', ''), " \t\n\r\0\x0B/");
    if ($configured !== '') {
        // Safety check: if configured URL is localhost but we are not physically local, ignore it
        if (!app_is_local() && (str_contains($configured, 'localhost') || str_contains($configured, '127.0.0.1'))) {
            // Ignore configured URL and fallback to auto-detection
        } else {
            return rtrim($configured, '/');
        }
    }

    if (PHP_SAPI === 'cli') {
        return rtrim((string)app_env('APP_URL', 'http://localhost'), '/');
    }

    $https = $_SERVER['HTTPS'] ?? '';
    $scheme = (!empty($https) && strtolower((string)$https) !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $dir = rtrim(dirname($scriptName), '/');
    if ($dir === '' || $dir === '.') {
        $dir = '';
    }

    return $scheme . '://' . $host . $dir;
}

function app_request_path(): string
{
    if (!empty($_GET['route'])) {
        $path = '/' . trim((string)$_GET['route'], '/');
    } else {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $basePath = rtrim((string)parse_url(app_base_url(), PHP_URL_PATH), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }
    }

    $path = preg_replace('#/index\.php$#i', '/', $path);
    $path = preg_replace('#^/index\.php#i', '', $path);
    $path = '/' . trim((string)$path, '/');
    return $path === '//' ? '/' : $path;
}

function app_branch_config(): array
{
    static $branchConfig;
    if (!$branchConfig) {
        $branchConfig = require __DIR__ . '/../config/branches.php';
    }
    return $branchConfig;
}

function branch_context(): array
{
    static $context = null;
    if ($context !== null) {
        return $context;
    }

    $config = app_branch_config();
    $default = $config['default'] ?? [];
    $map = $config['map'] ?? [];
    $path = trim(app_request_path(), '/');
    $segments = $path === '' ? [] : explode('/', $path);
    $first = $segments[0] ?? '';

    $isMapped = $first !== '' && isset($map[$first]);
    $slug = $isMapped ? $first : '';
    $branchData = $isMapped ? (array)$map[$slug] : (array)$default;
    if ($slug === '' && !isset($branchData['slug'])) {
        $branchData['slug'] = '';
    }
    if (!isset($branchData['name'])) {
        $branchData['name'] = 'Outlet';
    }
    if (!isset($branchData['outlet_id'])) {
        $branchData['outlet_id'] = 0;
    }

    $context = [
        'slug' => (string)($branchData['slug'] ?? $slug),
        'prefix' => $slug === '' ? '' : '/' . $slug,
        'name' => (string)$branchData['name'],
        'outlet_id' => (int)$branchData['outlet_id'],
        'outlet_code' => (string)($branchData['outlet_code'] ?? ''),
        'is_hq' => (bool)($branchData['is_hq'] ?? false),
        'closing_hour' => (string)($branchData['closing_hour'] ?? '21:00:00'),
    ];

    return $context;
}

function strip_branch_prefix(string $path): string
{
    $prefix = branch_context()['prefix'] ?? '';
    if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
        $path = substr($path, strlen($prefix));
    }

    $path = '/' . trim($path, '/');
    return $path === '//' ? '/' : $path;
}

function current_route_path(): string
{
    return strip_branch_prefix(app_request_path());
}

function branch_url(string $slug = '', string $path = '/'): string
{
    $base = rtrim(app_base_url(), '/');
    $slug = trim($slug, '/');
    $prefix = $slug === '' ? '' : '/' . $slug;
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        $path = '';
    }
    return $base . $prefix . $path;
}

function url(string $path = '', bool $withBranch = true): string
{
    $base = rtrim(app_base_url(), '/');
    $prefix = $withBranch ? (branch_context()['prefix'] ?? '') : '';
    $path = '/' . ltrim($path, '/');
    if ($path === '/') {
        $path = '';
    }
    return $base . $prefix . $path;
}

function asset(string $path): string
{
    return url('/public/assets/' . ltrim($path, '/'), false);
}

function current_outlet_id(): int
{
    if (isset($_GET['outlet_id']) && (int)$_GET['outlet_id'] > 0) {
        return (int)$_GET['outlet_id'];
    }
    if (isset($_SESSION['lumero_selected_outlet_id']) && (int)$_SESSION['lumero_selected_outlet_id'] > 0) {
        return (int)$_SESSION['lumero_selected_outlet_id'];
    }

    $branchOutletId = (int)(branch_context()['outlet_id'] ?? 0);
    if ($branchOutletId > 0) {
        return $branchOutletId;
    }

    $user = Auth::user() ?? null;
    $userOutlet = (int)($user['outlet_id'] ?? 0);
    if ($userOutlet > 0) {
        return $userOutlet;
    }

    return (int)app_config('default_outlet_id', 1);
}

function current_outlet_name(): string
{
    $branchName = trim((string)(branch_context()['name'] ?? ''));
    if ($branchName !== '') {
        return $branchName;
    }
    $user = Auth::user() ?? null;
    return (string)($user['outlet_name'] ?? 'Outlet Utama');
}

function branch_slug_for_outlet_id(int $outletId): ?string
{
    $config = app_branch_config();
    $default = $config['default'] ?? [];
    if ((int)($default['outlet_id'] ?? 0) === $outletId) {
        return '';
    }
    foreach (($config['map'] ?? []) as $slug => $branch) {
        if ((int)($branch['outlet_id'] ?? 0) === $outletId) {
            return (string)$slug;
        }
    }
    return null;
}

function branch_scope_outlet_ids(?int $outletId = null): array
{
    $current = $outletId ?? current_outlet_id();
    $default = (int)app_config('default_outlet_id', 1);
    $ids = [];

    foreach ([$current, $default] as $id) {
        $id = (int)$id;
        if ($id > 0 && !in_array($id, $ids, true)) {
            $ids[] = $id;
        }
    }

    return $ids ?: [1];
}

function outlet_scope_sql(string $column = 'outlet_id', ?int $outletId = null): array
{
    $ids = branch_scope_outlet_ids($outletId);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    return [
        'sql' => "($column IS NULL OR $column IN ($placeholders))",
        'params' => $ids,
    ];
}

function get_setting(string $key, string $default = ''): string
{
    static $cache = [];
    $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
    $cacheKey = $outletId . ':' . $key;
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }
    try {
        $pdo = Database::connection();
        // Prioritize exact outlet match, then NULL/0
        $row = $pdo->prepare(
            'SELECT setting_value FROM system_settings WHERE setting_key = ? AND outlet_id = ? LIMIT 1'
        );
        $row->execute([$key, $outletId]);
        $val = $row->fetchColumn();
        if ($val === false) {
            $row2 = $pdo->prepare(
                'SELECT setting_value FROM system_settings WHERE setting_key = ? AND (outlet_id IS NULL OR outlet_id = 0) LIMIT 1'
            );
            $row2->execute([$key]);
            $val = $row2->fetchColumn();
        }
        $cache[$cacheKey] = $val !== false ? (string)$val : $default;
    } catch (Throwable $e) {
        $cache[$cacheKey] = $default;
    }
    return $cache[$cacheKey];
}

function rupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function rupiahPlain($value): string
{
    return number_format((float)$value, 0, ',', '.');
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['_csrf'] ?? '') !== ($_SESSION['_csrf'] ?? ''))) {
        http_response_code(419);
        die('CSRF token tidak valid.');
    }
}

function old(string $key, $default = '')
{
    return htmlspecialchars($_POST[$key] ?? $default);
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function today(): string
{
    return date('Y-m-d');
}

/**
 * Business date considering closing hour cutoff.
 * Transactions after the cutoff hour are assigned to the next business day.
 * Default cutoff: 21:00 (configurable per outlet via outlets.closing_hour).
 */
function business_date(?int $outletId = null): string
{
    $closingHour = '21:00:00';

    // Try to get outlet-specific closing hour from branch context or DB
    if ($outletId) {
        try {
            $db = Database::connection();
            $row = $db->prepare("SELECT closing_hour FROM outlets WHERE id = ? LIMIT 1");
            $row->execute([$outletId]);
            $fetched = $row->fetch();
            if ($fetched && !empty($fetched['closing_hour'])) {
                $closingHour = $fetched['closing_hour'];
            }
        } catch (Throwable $e) {
            // fallback to default
        }
    } else {
        $branch = function_exists('branch_context') ? branch_context() : [];
        if (!empty($branch['closing_hour'])) {
            $closingHour = $branch['closing_hour'];
        }
    }

    $tz = new DateTimeZone(app_config('timezone', 'Asia/Jakarta'));
    $now = new DateTime('now', $tz);
    $cutoff = DateTime::createFromFormat('H:i:s', $closingHour, $tz);
    if (!$cutoff) {
        $cutoff = DateTime::createFromFormat('H:i:s', '21:00:00', $tz);
    }
    $cutoff->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

    if ($now >= $cutoff) {
        $now->modify('+1 day');
    }
    return $now->format('Y-m-d');
}

/**
 * Render an inline SVG icon from the tabler sprite.
 */
function sim_icon(string $name, string $class = '', string $style = ''): string
{
    $iconName = str_replace(['ti ti-', 'ti-'], '', $name);
    if (str_ends_with($iconName, '-filled')) {
        $iconName = 'filled-' . substr($iconName, 0, -7);
    }
    if ($iconName === 'cash-register') {
        $iconName = 'cash-banknote';
    }
    $url = asset('tabler-sprite.svg') . '#tabler-' . $iconName;
    $classAttr = trim("sim-icon " . $class);
    $defaultStyle = 'display:inline-block;vertical-align:-0.125em;flex:0 0 auto;';
    $styleAttr = ' style="'.htmlspecialchars($defaultStyle . $style).'"';
    // Use 1.25em to roughly match the size of font icons (1em is often too small compared to line-height)
    return '<svg class="' . htmlspecialchars($classAttr) . '" width="1.25em" height="1.25em" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"' . $styleAttr . '><use href="' . htmlspecialchars($url) . '" /></svg>';
}

/**
 * Ensure outlet_raw_materials table exists and backfill initial stock from raw_materials
 */
function inventory_ensure_outlet_stocks(PDO $pdo): void
{
    // The outlet_raw_materials table is now part of the permanent schema.
    // Migration is already done in scratch_migrate.php.
    // We do not run CREATE TABLE here to avoid implicit commits which break transactions.
    return;
}

/**
 * Dapatkan saldo dan harga rata-rata bahan baku untuk outlet tertentu dari outlet_raw_materials
 */
function inventory_get_material_stock(PDO $pdo, int $rmId, ?int $outletId = null): array
{
    if ($outletId === null) {
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
    }
    inventory_ensure_outlet_stocks($pdo);

    $stmt = $pdo->prepare("
        SELECT rm.id, rm.name, rm.sku, rm.unit_id,
               COALESCE(orm.stock_qty, 0) AS stock_qty,
               COALESCE(orm.min_stock_qty, rm.min_stock_qty, 0) AS min_stock_qty,
               COALESCE(orm.average_cost, rm.average_cost, 0) AS average_cost
        FROM raw_materials rm
        LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ?
        WHERE rm.id = ?
    ");
    $stmt->execute([$outletId, $rmId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return ['stock_qty' => 0.0, 'min_stock_qty' => 0.0, 'average_cost' => 0.0];
    }
    return $row;
}

/**
 * Update atau tambah saldo dan harga rata-rata bahan baku untuk outlet tertentu di outlet_raw_materials
 */
function inventory_set_material_stock(PDO $pdo, int $rmId, float $newStock, ?float $newAvgCost = null, ?int $outletId = null): void
{
    if ($outletId === null) {
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
    }
    inventory_ensure_outlet_stocks($pdo);

    if ($newAvgCost === null) {
        $curr = inventory_get_material_stock($pdo, $rmId, $outletId);
        $newAvgCost = (float)($curr['average_cost'] ?? 0);
    }

    $stmt = $pdo->prepare("
        INSERT INTO outlet_raw_materials (outlet_id, raw_material_id, stock_qty, average_cost, min_stock_qty, created_at, updated_at)
        SELECT ?, id, ?, ?, min_stock_qty, NOW(), NOW() FROM raw_materials WHERE id = ?
        ON DUPLICATE KEY UPDATE stock_qty = VALUES(stock_qty), average_cost = VALUES(average_cost), updated_at = NOW()
    ");
    $stmt->execute([$outletId, $newStock, $newAvgCost, $rmId]);

    // Jika outlet adalah HQ (1), sync juga ke raw_materials master agar kompatibel dengan legacy query
    if ($outletId == 1) {
        $pdo->prepare("UPDATE raw_materials SET stock_qty = ?, average_cost = ?, updated_at = NOW() WHERE id = ?")
            ->execute([$newStock, $newAvgCost, $rmId]);
    }
}

/**
 * Cek ketersediaan stok bahan baku berdasarkan resep untuk sebuah varian produk per outlet
 */
function check_variant_stock(PDO $pdo, int $variantId, ?int $outletId = null): bool
{
    if ($outletId === null) {
        $outletId = function_exists('current_outlet_id') ? current_outlet_id() : 1;
    }
    inventory_ensure_outlet_stocks($pdo);

    // Cari resep aktif untuk varian ini
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE product_variant_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$variantId]);
    $recipeId = $stmt->fetchColumn();
    
    // Jika tidak ada resep spesifik, asumsikan stok selalu tersedia (unlimited)
    if (!$recipeId) {
        return true;
    }

    // Ambil kebutuhan bahan baku dan stok saat ini dari outlet_raw_materials
    $stmt = $pdo->prepare("
        SELECT ri.qty as required_qty, COALESCE(orm.stock_qty, 0) as stock_qty 
        FROM recipe_items ri
        JOIN raw_materials rm ON ri.raw_material_id = rm.id
        LEFT JOIN outlet_raw_materials orm ON orm.raw_material_id = rm.id AND orm.outlet_id = ?
        WHERE ri.recipe_id = ? AND ri.item_type = 'raw_material'
    ");
    $stmt->execute([$outletId, $recipeId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Jika tidak ada bahan baku terhubung, anggap tersedia
    if (empty($items)) {
        return true;
    }

    // Cek apakah setiap bahan baku mencukupi untuk 1 porsi (required_qty)
    foreach ($items as $item) {
        if ((float)$item['stock_qty'] < (float)$item['required_qty']) {
            return false;
        }
    }

    return true;
}

/**
 * Mengembalikan mode database yang sedang aktif: 'local' atau 'production'.
 * Toggle hanya berlaku jika APP_ENV=local.
 */
function active_db_mode(): string
{
    if (app_env('APP_ENV', 'local') !== 'local') {
        return 'production';
    }
    $mode = $_SESSION['kios_db_mode'] ?? $_COOKIE['kios_db_mode'] ?? 'local';
    return $mode === 'production' ? 'production' : 'local';
}

/**
 * Menampilkan badge/tombol toggle switch database di header ketika APP_ENV=local.
 */
function render_db_switcher(): string
{
    if (app_env('APP_ENV', 'local') !== 'local') {
        return '';
    }

    $mode = active_db_mode();
    if ($mode === 'production') {
        $switchUrl = url('/switch-db?mode=local');
        return '<span class="badge bg-danger text-white d-inline-flex align-items-center gap-1 px-3 py-2 border border-light shadow-sm ms-2" style="font-size:0.82rem;border-radius:20px;">'
             . sim_icon('ti-database', 'me-1') . ' DB: HOSTINGER / PROD '
             . '<a href="javascript:void(0);" onclick="if(confirm(\'Kembali gunakan Database LOKAL (XAMPP)?\')) window.location.href=\'' . $switchUrl . '\'" class="ms-2 btn btn-xs btn-light text-danger rounded-pill px-2 py-0 fw-bold" style="font-size:0.72rem;text-decoration:none;">Ganti ke Lokal 🔀</a>'
             . '</span>';
    } else {
        $switchUrl = url('/switch-db?mode=production');
        return '<span class="badge bg-success text-white d-inline-flex align-items-center gap-1 px-3 py-2 border border-light shadow-sm ms-2" style="font-size:0.82rem;border-radius:20px;">'
             . sim_icon('ti-database', 'me-1') . ' DB: LOKAL (XAMPP) '
             . '<a href="javascript:void(0);" onclick="if(confirm(\'Ganti ke Database HOSTINGER (PRODUKSI)?\\nPastikan kredensial PROD_DB_* di .env sudah terisi dengan benar!\')) window.location.href=\'' . $switchUrl . '\'" class="ms-2 btn btn-xs btn-light text-success rounded-pill px-2 py-0 fw-bold" style="font-size:0.72rem;text-decoration:none;">Ganti ke Prod 🔀</a>'
             . '</span>';
    }
}

/**
 * Cek status operasional outlet (buka/tutup berdasarkan jam kerja dan status is_active)
 */
function check_outlet_operating_status(int $outletId, ?array $outletRow = null): array
{
    $tz = new DateTimeZone(app_config('timezone', 'Asia/Jakarta'));
    $now = new DateTime('now', $tz);
    $currentTimeStr = $now->format('H:i:s');

    if (!$outletRow) {
        try {
            $db = Database::connection();
            $stmt = $db->prepare("SELECT id, name, is_active, closing_hour, opening_hour FROM outlets WHERE id = ? LIMIT 1");
            $stmt->execute([$outletId]);
            $outletRow = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {}
    }

    if (!$outletRow || (isset($outletRow['is_active']) && empty($outletRow['is_active']))) {
        return [
            'is_open' => false,
            'reason' => 'Cabang saat ini tidak aktif atau sedang tutup sementara.',
            'opening_time' => '10:00',
            'closing_time' => '21:00'
        ];
    }

    $closingHour = trim((string)($outletRow['closing_hour'] ?? '21:00:00'));
    if ($closingHour === '' || $closingHour === '00:00:00' || $closingHour === '24:00:00') $closingHour = '23:59:59';
    if (strlen($closingHour) === 5) $closingHour .= ':00';
    
    $openingHour = trim((string)($outletRow['opening_hour'] ?? '08:00:00'));
    if ($openingHour === '') $openingHour = '08:00:00';
    if (strlen($openingHour) === 5) $openingHour .= ':00';

    $openingTimeFormatted = substr($openingHour, 0, 5);
    $closingTimeFormatted = substr($closingHour, 0, 5);

    // Cek status sesi toko di daily_store_sessions (Primary Source of Truth)
    try {
        $db = Database::connection();
        $sessStmt = $db->prepare("SELECT status, business_date FROM daily_store_sessions WHERE outlet_id = ? ORDER BY id DESC LIMIT 1");
        $sessStmt->execute([$outletId]);
        $storeSession = $sessStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($storeSession && isset($storeSession['status'])) {
            if ($storeSession['status'] === 'open') {
                return [
                    'is_open' => true,
                    'reason' => 'Buka (Sesi Toko Aktif)',
                    'opening_time' => $openingTimeFormatted,
                    'closing_time' => $closingTimeFormatted
                ];
            } elseif ($storeSession['status'] === 'closed') {
                $bizDate = function_exists('business_date') ? business_date($outletId) : $now->format('Y-m-d');
                if ($storeSession['business_date'] === $bizDate) {
                    return [
                        'is_open' => false,
                        'reason' => 'Cabang sudah ditutup hari ini.',
                        'opening_time' => $openingTimeFormatted,
                        'closing_time' => $closingTimeFormatted
                    ];
                }
            }
        }
    } catch (Throwable $e) {}

    // Compare current time with opening and closing hours (fallback jika belum ada sesi)
    if ($closingHour < $openingHour) {
        // Closes past midnight (e.g. 02:00:00)
        $isOpen = ($currentTimeStr >= $openingHour || $currentTimeStr < $closingHour);
    } else {
        $isOpen = ($currentTimeStr >= $openingHour && $currentTimeStr < $closingHour);
    }

    return [
        'is_open' => $isOpen,
        'reason' => $isOpen ? 'Buka' : 'Cabang saat ini di luar jam operasional (' . $openingTimeFormatted . ' - ' . $closingTimeFormatted . ' WIB).',
        'opening_time' => $openingTimeFormatted,
        'closing_time' => $closingTimeFormatted
    ];
}

