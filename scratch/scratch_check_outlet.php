<?php
require 'helpers/functions.php';
require 'core/Database.php';
$db = Database::connection();
print_r($db->query('SELECT id, name FROM outlets')->fetchAll(PDO::FETCH_ASSOC));
