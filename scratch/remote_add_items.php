<?php
$host = 'srv1864.hstgr.io';
$db = 'u643003184_kios_lumero';
$user = 'u643003184_kios_lumero';
$pass = 'Lawmotion1!@#';

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Koneksi gagal: " . $e->getMessage());
}

$phone = '0895338977816';
$m = $pdo->query("SELECT * FROM members WHERE phone = '$phone'")->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("Member tidak ditemukan.");
}

$memberId = $m['id'];

// 1. Tambah 50.000 poin supaya bisa test tukar poin sendiri
$pdo->exec("UPDATE members SET total_points = total_points + 50000 WHERE id = $memberId");

// 2. Tambah 5 kupon undian eskrim (prize_id = 5)
$stmt = $pdo->prepare("INSERT INTO reward_claims (user_id, prize_id, qr_code, status, expired_at) VALUES (?, 5, ?, 'PENDING', DATE_ADD(NOW(), INTERVAL 7 DAY))");

for ($i = 0; $i < 5; $i++) {
    $qr = strtoupper(substr(md5(uniqid()), 0, 8));
    $stmt->execute([$memberId, $qr]);
}

echo "Sukses!\n";
echo "Ditambahkan 5 kupon undian Eskrim.\n";
echo "Ditambahkan 50.000 Poin.\n";
