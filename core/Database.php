<?php
class Database
{
    private static ?PDO $pdo = null;
    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            $config = require __DIR__ . '/../config/database.php';
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']);
            self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Auto-install: Jika tabel users belum ada, import database/dcc_export.sql otomatis
            try {
                self::$pdo->query("SELECT 1 FROM users LIMIT 1");
            } catch (Exception $e) {
                $sqlFile = __DIR__ . '/../database/dcc_export.sql';
                if (file_exists($sqlFile)) {
                    $sql = file_get_contents($sqlFile);
                    self::$pdo->exec($sql);
                }
            }
            
            // Auto-Migration
            if (class_exists('Migration')) {
                Migration::runAuto(self::$pdo);
            }
        }
        return self::$pdo;
    }
}
