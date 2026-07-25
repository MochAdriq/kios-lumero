<?php
require_once __DIR__ . '/../helpers/functions.php';
require_once __DIR__ . '/../core/Database.php';

$host = app_env('PROD_DB_HOST');
$db   = app_env('PROD_DB_DATABASE');
$user = app_env('PROD_DB_USERNAME');
$pass = app_env('PROD_DB_PASSWORD');

$pdo = new PDO("mysql:host={$host};dbname={$db};charset=utf8mb4", $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// We need to simulate Auth for the Model
class Auth {
    public static function user() {
        return ['id' => 1, 'outlet_id' => 8];
    }
    public static function id() {
        return 1;
}

require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../modules/reports/ReportModel.php';

class CLIReportModel extends ReportModel {
    public function __construct($pdo) {
        parent::__construct();
        $this->db = $pdo;
    }
    protected function outletId(): int {
        return 8;
    }
}

$OUTLET_ID = 8;
$dates = $pdo->query("SELECT DISTINCT DATE(created_at) as d FROM orders WHERE outlet_id = $OUTLET_ID ORDER BY d")->fetchAll(PDO::FETCH_COLUMN);

$reportModel = new CLIReportModel($pdo);

$success = 0;
foreach ($dates as $date) {
    // Check if daily_store_sessions exists, if not, create a dummy one
    $session = $pdo->query("SELECT id FROM daily_store_sessions WHERE outlet_id = $OUTLET_ID AND business_date = '$date' ORDER BY id DESC LIMIT 1")->fetchColumn();
    if (!$session) {
        $pdo->exec("INSERT INTO daily_store_sessions (outlet_id, business_date, opened_by, status, created_at, updated_at) VALUES ($OUTLET_ID, '$date', 1, 'closed', '$date 00:00:00', '$date 23:59:59')");
    }
    
    try {
        $reportModel->saveDaily($date, 0);
        $success++;
    } catch (Exception $e) {
        echo "Error on $date: " . $e->getMessage() . "\n";
    }
}

echo "Berhasil regenerate $success laporan harian dengan ReportModel!\n";
