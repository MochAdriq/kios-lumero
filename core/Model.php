<?php
abstract class Model
{
    protected PDO $db;
    public function __construct() { $this->db = Database::connection(); }
    public function one(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); $row = $stmt->fetch(); return $row ?: null;
    }
    public function all(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public function execSql(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql); $stmt->execute($params); return $stmt->rowCount();
    }
}
