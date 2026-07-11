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
        $route = '/' . trim((string)$_GET['route'], '/');
        return $route === '/' ? '/' : $route;
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = rtrim((string)parse_url(app_base_url(), PHP_URL_PATH), '/');
    if ($basePath !== '' && str_starts_with($path, $basePath)) {
        $path = substr($path, strlen($basePath));
    }

    $path = preg_replace('#/index\.php$#', '/', $path);
    $path = preg_replace('#^/index\.php#', '', $path);
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

function rupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
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
