<?php
class Migration
{
    public static function runAuto(PDO $pdo): void
    {
        // Only run once per session to save performance
        if (isset($_SESSION['migrations_checked'])) {
            return;
        }

        try {
            self::ensureMigrationsTable($pdo);
            self::runPendingMigrations($pdo);
            $_SESSION['migrations_checked'] = true;
        } catch (Throwable $e) {
            error_log('[Migration] Error running auto-migrations: ' . $e->getMessage());
        }
    }

    private static function ensureMigrationsTable(PDO $pdo): void
    {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    private static function runPendingMigrations(PDO $pdo): void
    {
        $stmt = $pdo->query("SELECT migration FROM migrations");
        $executed = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $dbDir = __DIR__ . '/../database';
        if (!is_dir($dbDir)) {
            return;
        }

        $files = glob($dbDir . '/*.sql');
        if (!$files) {
            return;
        }

        sort($files); // Ensure alphabetical order (e.g. 001, 002)

        foreach ($files as $file) {
            $basename = basename($file);

            // Skip base export and anything that doesn't look like a numbered migration
            if ($basename === 'dcc_export.sql' || !preg_match('/^\d+_[a-zA-Z0-9_]+\.sql$/', $basename)) {
                continue;
            }

            if (in_array($basename, $executed, true)) {
                continue;
            }

            self::executeMigrationFile($pdo, $file, $basename);
        }
    }

    private static function executeMigrationFile(PDO $pdo, string $filePath, string $basename): void
    {
        $sql = file_get_contents($filePath);
        if (!$sql) {
            return;
        }

        $pdo->beginTransaction();
        try {
            $pdo->exec($sql);
            
            $stmt = $pdo->prepare("INSERT INTO migrations (migration, executed_at) VALUES (?, NOW())");
            $stmt->execute([$basename]);
            
            $pdo->commit();
            error_log("[Migration] Successfully executed: $basename");
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log("[Migration] Failed to execute $basename: " . $e->getMessage());
            throw $e;
        }
    }
}
