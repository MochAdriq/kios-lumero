<?php
require __DIR__.'/core/Database.php';

$pdo = Database::connection();
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
        $insert = $pdo->prepare("INSERT INTO event_prizes (event_id, name, chance_percentage, stock, is_default_fallback, prize_type) VALUES (?, ?, ?, ?, 0, 'product')");
        $insert->execute([$eventId, $name, $chance, $stock]);
    }
}

echo "Hadiah fisik berhasil dikembalikan!\n";
