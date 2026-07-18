<?php
require 'helpers/functions.php';
require 'core/Database.php';
$db = Database::connection();
echo $db->query('SHOW CREATE TABLE products')->fetch(PDO::FETCH_ASSOC)['Create Table'];
echo "\n\n";
echo $db->query('SHOW CREATE TABLE product_variants')->fetch(PDO::FETCH_ASSOC)['Create Table'];
echo "\n\n";
echo $db->query('SHOW CREATE TABLE product_categories')->fetch(PDO::FETCH_ASSOC)['Create Table'];
