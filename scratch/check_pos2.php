<?php
$pdo = new PDO("mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4", "u643003184_kios_lumero", "Lawmotion1!@#");
$st = $pdo->query("SELECT * FROM products WHERE outlet_id = 5 AND name IN ('Keju', 'Lada Hitam', 'Garlic / Bawang', 'Teriyaki', 'Sadis / Pedas', 'Mentai / Mayo')");
print_r($st->fetchAll(PDO::FETCH_ASSOC));
