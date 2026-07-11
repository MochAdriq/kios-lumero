<?php
/**
 * Branch configuration — loaded dynamically from `outlets` table.
 * Falls back to .env-based config if database is unavailable.
 */

$branches = [];
$default  = null;
$dbLoaded = false;

try {
    $db   = Database::connection();
    $rows = $db->query(
        "SELECT id, slug, name, outlet_code AS code, is_hq, closing_hour, address, phone
         FROM outlets
         WHERE is_active = 1
         ORDER BY is_hq DESC, name ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    if ($rows && count($rows) > 0) {
        $dbLoaded = true;
        foreach ($rows as $row) {
            $slug  = trim((string)($row['slug'] ?? ''));
            $entry = [
                'slug'         => $slug,
                'name'         => (string)$row['name'],
                'outlet_id'    => (int)$row['id'],
                'outlet_code'  => (string)($row['code'] ?? ''),
                'is_hq'        => (bool)($row['is_hq'] ?? false),
                'closing_hour' => (string)($row['closing_hour'] ?? '21:00:00'),
                'address'      => (string)($row['address'] ?? ''),
                'phone'        => (string)($row['phone'] ?? ''),
            ];

            if ($row['is_hq'] || $slug === '') {
                $default = $entry;
            }
            if ($slug !== '') {
                $branches[$slug] = $entry;
            }
        }
    }
} catch (Throwable $e) {
    // Database not ready — fall back to .env config below
}

// --- Fallback: .env-based config (original logic) ---
if (!$dbLoaded || !$default) {
    $defaultOutletId = (int)app_env('DEFAULT_OUTLET_ID', 1);

    $default = [
        'slug'         => '',
        'name'         => app_env('BRANCH_MAIN_NAME', 'Pasekon'),
        'outlet_id'    => $defaultOutletId,
        'outlet_code'  => app_env('BRANCH_MAIN_OUTLET_CODE', 'DCP'),
        'is_hq'        => true,
        'closing_hour' => '21:00:00',
    ];

    // KB branch from .env
    $kbOutletId = (int)app_env('BRANCH_KB_OUTLET_ID', 2);
    if ($kbOutletId > 0) {
        $branches['kb'] = [
            'slug'         => 'kb',
            'name'         => app_env('BRANCH_KB_NAME', 'Kalibunder'),
            'outlet_id'    => $kbOutletId,
            'outlet_code'  => app_env('BRANCH_KB_OUTLET_CODE', 'KB'),
            'is_hq'        => false,
            'closing_hour' => '21:00:00',
        ];
    }
}

// Support APP_BRANCHES_JSON override (existing feature)
$jsonBranches = app_env('APP_BRANCHES_JSON', '');
if (is_string($jsonBranches) && trim($jsonBranches) !== '') {
    $decoded = json_decode($jsonBranches, true);
    if (is_array($decoded)) {
        foreach ($decoded as $slug => $data) {
            if (!is_string($slug) || !is_array($data)) {
                continue;
            }
            $slug = trim($slug);
            if ($slug === '') {
                continue;
            }
            $branches[$slug] = array_merge(['slug' => $slug, 'is_hq' => false, 'closing_hour' => '21:00:00'], $data);
        }
    }
}

return [
    'default' => $default,
    'map'     => $branches,
];
