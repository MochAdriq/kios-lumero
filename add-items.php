<?php
require __DIR__ . '/core/Database.php';
require __DIR__ . '/core/Auth.php';
session_start();

$pdo = Database::connection();

// Temukan member
$phone = '0895338977816';
$m = $pdo->query("SELECT * FROM members WHERE phone = '$phone'")->fetch(PDO::FETCH_ASSOC);

if (!$m) {
    die("Member dengan nomor $phone tidak ditemukan.");
}

$memberId = $m['id'];

// 1. Tambah 50.000 poin supaya bisa test tukar poin sendiri
$pdo->exec("UPDATE members SET total_points = 50000 WHERE id = $memberId");

// 2. Tambah 5 kupon undian eskrim (prize_id = 5)
$stmt = $pdo->prepare("INSERT INTO reward_claims (user_id, prize_id, qr_code, status, expired_at) VALUES (?, 5, ?, 'PENDING', DATE_ADD(NOW(), INTERVAL 7 DAY))");

for ($i = 0; $i < 5; $i++) {
    $qr = strtoupper(substr(md5(uniqid()), 0, 8));
    $stmt->execute([$memberId, $qr]);
}

echo "<h1>Sukses!</h1>";
echo "<p>Berhasil menambahkan 5 Kupon Hasil Undian Eskrim (Silakan cek di halaman My Rewards).</p>";
echo "<p>Saya juga menambahkan <b>50.000 Poin</b> ke akun Anda, sehingga Anda bisa langsung mengetes proses <b>Tukar Poin</b> menjadi 5 Eskrim dari dalam aplikasi pelanggan!</p>";
echo "<p>Member: <b>$phone</b> (Nama: {$m['name']})</p>";
