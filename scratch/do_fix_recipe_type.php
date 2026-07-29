<?php
$pdo = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero;charset=utf8mb4', 'u643003184_kios_lumero', 'Lawmotion1!@#'); 
$stmt = $pdo->query("UPDATE recipes SET recipe_type = 'final' WHERE recipe_type = '' OR recipe_type IS NULL"); 
echo "Rows updated: " . $stmt->rowCount();
