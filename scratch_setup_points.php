<?php
require __DIR__.'/core/Database.php';
require __DIR__.'/helpers/functions.php';

$pdo = Database::connection();

// This will trigger the schema migration (adding prize_type and points_amount)
require __DIR__.'/config/loyalty.php';

$eventId = 'kalibunder_go';

// 1. Wipe all active prizes
$pdo->prepare("DELETE FROM event_prizes WHERE event_id = ? AND is_default_fallback = 0")->execute([$eventId]);

// 2. We need 10 prizes (5, 10, 15, ..., 50) + 1 fallback prize
$points = [5, 10, 15, 20, 25, 30, 35, 40, 45, 50];

$chancePerPoint = 10.00; // 10% each * 10 = 100%

foreach ($points as $pt) {
    $name = $pt . ' Poin';
    $prizeType = 'points';
    $pointsAmount = $pt;
    $chance = $chancePerPoint;
    $stock = 999999; // Unlimited
    $isActive = 1;
    $isFallback = 0;
    
    $stmt = $pdo->prepare("INSERT INTO event_prizes (event_id, name, prize_type, points_amount, chance_percentage, stock, is_active, is_default_fallback) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$eventId, $name, $prizeType, $pointsAmount, $chance, $stock, $isActive, $isFallback]);
}

$pdo->prepare("UPDATE event_prizes SET chance_percentage = 0 WHERE event_id = ? AND is_default_fallback = 1")->execute([$eventId]);

echo "Prizes setup successfully!\n";
