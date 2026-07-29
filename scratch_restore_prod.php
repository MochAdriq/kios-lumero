<?php
$host = 'srv1864.hstgr.io';
$db = 'u643003184_kios_lumero';
$user = 'u643003184_kios_lumero';
$pass = 'Lawmotion1!@#';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $eventId = 'kalibunder_go';
    
    $prizes = [
        ['Voucher Diskon 10%', 20.00, 100],
        ['Tumbler Eksklusif', 5.00, 10],
        ['Ayam Crispy Lumero', 10.00, 50]
    ];
    
    foreach ($prizes as $p) {
        $name = $p[0];
        $chance = $p[1];
        $stock = $p[2];
        
        $stmt = $pdo->prepare("SELECT id FROM event_prizes WHERE event_id = ? AND name = ?");
        $stmt->execute([$eventId, $name]);
        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO event_prizes (event_id, name, chance_percentage, stock, is_default_fallback, prize_type, is_active) VALUES (?, ?, ?, ?, 0, 'product', 1)");
            $insert->execute([$eventId, $name, $chance, $stock]);
        }
    }
    
    echo "Prizes restored successfully to PROD database!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
