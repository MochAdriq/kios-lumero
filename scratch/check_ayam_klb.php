<?php
require 'helpers/app.php';
require 'config/database.php';
$pdo = Database::connection();

// find klb outlet
$st = $pdo->query("SELECT id, name FROM outlets WHERE slug = 'klb' OR name LIKE '%kalibunder%'");
$outlet = $st->fetch(PDO::FETCH_ASSOC);
echo "Outlet: " . json_encode($outlet) . "\n";
$outletId = $outlet['id'] ?? 2;

$st = $pdo->prepare("SELECT id, name, category, image_url FROM products WHERE outlet_id = ? AND category LIKE '%ayam%'");
$st->execute([$outletId]);
$products = $st->fetchAll(PDO::FETCH_ASSOC);
echo "Products: " . json_encode($products, JSON_PRETTY_PRINT) . "\n";
