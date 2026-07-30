<?php
define('APP_ENV', 'development');
require __DIR__.'/../helpers/functions.php';
require __DIR__.'/../core/Database.php';
require __DIR__.'/../core/Model.php';
require __DIR__.'/../core/Auth.php';
require __DIR__.'/../modules/pos/POSModel.php';
require __DIR__.'/../core/Controller.php';
require __DIR__.'/../modules/pos/POSController.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set up DB connection using production credentials
$db = new PDO('mysql:host=srv1864.hstgr.io;dbname=u643003184_kios_lumero', 'u643003184_kios_lumero', 'Lawmotion1!@#');
Database::$pdo = $db;

$_SESSION['user'] = ['id' => 1, 'role_code' => 'super_admin', 'outlet_id' => 1];
$_GET['id'] = 2356;

$c = new POSController();
try {
    $c->orderDetails();
} catch (Throwable $e) {
    echo "EXCEPTION: " . $e->getMessage();
}
