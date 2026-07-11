<?php
abstract class Model
{
    protected PDO $db;
    public function __construct() { $this->db = Database::connection(); }
    protected function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); $row = $stmt->fetch(); return $row ?: null;
    }
    protected function all(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    protected function execSql(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->rowCount();
    }
}
