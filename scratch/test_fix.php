<?php
require __DIR__ . '/../helpers/functions.php';
require __DIR__ . '/../core/Database.php';
$pdo = Database::connection();
$stOutlets = $pdo->query("SELECT id, slug, name, outlet_code AS code, is_hq, is_active, closing_hour, address, phone, latitude, longitude FROM outlets WHERE is_active = 1 ORDER BY is_hq DESC, name ASC");
$outlets = $stOutlets ? $stOutlets->fetchAll(PDO::FETCH_ASSOC) : [];
foreach ($outlets as $o) {
    $st = check_outlet_operating_status((int)$o['id'], $o);
    echo "Outlet [ID " . $o['id'] . "] " . $o['name'] . ": is_open = " . ($st['is_open'] ? 'YES' : 'NO') . " (" . $st['reason'] . ")" . PHP_EOL;
}
