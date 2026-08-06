<?php

class RaffleModel extends Model
{
    public function ensureTables(): void
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS raffle_batches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status ENUM('draft','active','completed') NOT NULL DEFAULT 'draft',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->exec("CREATE TABLE IF NOT EXISTS raffle_prizes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            batch_id INT NOT NULL,
            name VARCHAR(150) NOT NULL,
            image_url VARCHAR(255) DEFAULT NULL,
            winner_ticket_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (batch_id) REFERENCES raffle_batches(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->exec("CREATE TABLE IF NOT EXISTS raffle_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_code VARCHAR(40) NOT NULL,
            batch_id INT NOT NULL,
            member_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_raffle_ticket_code (ticket_code),
            FOREIGN KEY (batch_id) REFERENCES raffle_batches(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function getBatches(): array
    {
        try {
            $st = $this->db->query("SELECT * FROM raffle_batches ORDER BY created_at DESC");
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { return []; }
    }

    public function getBatchById(int $id): ?array
    {
        try {
            $st = $this->db->prepare("SELECT * FROM raffle_batches WHERE id = ?");
            $st->execute([$id]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Throwable $e) { return null; }
    }

    public function saveBatch(array $data): bool
    {
        try {
            if (!empty($data['id'])) {
                $st = $this->db->prepare("UPDATE raffle_batches SET name=?, start_date=?, end_date=?, status=? WHERE id=?");
                return $st->execute([$data['name'], $data['start_date'], $data['end_date'], $data['status'], $data['id']]);
            } else {
                $st = $this->db->prepare("INSERT INTO raffle_batches (name, start_date, end_date, status) VALUES (?, ?, ?, ?)");
                return $st->execute([$data['name'], $data['start_date'], $data['end_date'], $data['status'] ?? 'draft']);
            }
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getPrizesByBatch(int $batchId): array
    {
        try {
            $st = $this->db->prepare("SELECT p.*, t.ticket_code, m.name as winner_name, m.phone as winner_phone 
                                      FROM raffle_prizes p 
                                      LEFT JOIN raffle_tickets t ON p.winner_ticket_id = t.id 
                                      LEFT JOIN loyalty_members m ON t.member_id = m.id 
                                      WHERE p.batch_id = ? ORDER BY p.id ASC");
            $st->execute([$batchId]);
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) { return []; }
    }

    public function savePrize(array $data): bool
    {
        try {
            if (!empty($data['id'])) {
                if (!empty($data['image_url'])) {
                    $st = $this->db->prepare("UPDATE raffle_prizes SET name=?, image_url=? WHERE id=?");
                    return $st->execute([$data['name'], $data['image_url'], $data['id']]);
                } else {
                    $st = $this->db->prepare("UPDATE raffle_prizes SET name=? WHERE id=?");
                    return $st->execute([$data['name'], $data['id']]);
                }
            } else {
                $st = $this->db->prepare("INSERT INTO raffle_prizes (batch_id, name, image_url) VALUES (?, ?, ?)");
                return $st->execute([$data['batch_id'], $data['name'], $data['image_url'] ?? null]);
            }
        } catch (Throwable $e) { return false; }
    }

    public function deletePrize(int $id): bool
    {
        try {
            $st = $this->db->prepare("DELETE FROM raffle_prizes WHERE id=?");
            return $st->execute([$id]);
        } catch (Throwable $e) { return false; }
    }

    public function getTicketStatsByBatch(int $batchId): array
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) as total_tickets, COUNT(DISTINCT member_id) as total_participants FROM raffle_tickets WHERE batch_id = ?");
            $st->execute([$batchId]);
            return $st->fetch(PDO::FETCH_ASSOC) ?: ['total_tickets' => 0, 'total_participants' => 0];
        } catch (Throwable $e) { return ['total_tickets' => 0, 'total_participants' => 0]; }
    }

    public function drawWinner(int $prizeId, int $batchId): array
    {
        try {
            // Check if prize already has a winner
            $stCheck = $this->db->prepare("SELECT winner_ticket_id FROM raffle_prizes WHERE id = ?");
            $stCheck->execute([$prizeId]);
            $currentWinner = $stCheck->fetchColumn();
            if ($currentWinner) {
                return ['success' => false, 'message' => 'Hadiah ini sudah memiliki pemenang!'];
            }

            // Get a random ticket that hasn't won anything in this batch yet (assuming 1 prize per ticket, but a member can win multiple times if they have multiple tickets)
            // Wait, to spread it out, let's make it 1 prize per MEMBER per batch.
            $sql = "SELECT id FROM raffle_tickets 
                    WHERE batch_id = ? 
                    AND member_id NOT IN (
                        SELECT t2.member_id FROM raffle_prizes p2 
                        JOIN raffle_tickets t2 ON p2.winner_ticket_id = t2.id 
                        WHERE p2.batch_id = ? AND p2.winner_ticket_id IS NOT NULL
                    )
                    ORDER BY RAND() LIMIT 1";
            
            $stPick = $this->db->prepare($sql);
            $stPick->execute([$batchId, $batchId]);
            $winningTicketId = $stPick->fetchColumn();

            if (!$winningTicketId) {
                return ['success' => false, 'message' => 'Tidak ada tiket yang valid atau semua peserta sudah menang!'];
            }

            // Assign winner
            $stUpdate = $this->db->prepare("UPDATE raffle_prizes SET winner_ticket_id = ? WHERE id = ?");
            $stUpdate->execute([$winningTicketId, $prizeId]);

            return ['success' => true, 'message' => 'Pemenang berhasil diacak!'];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}
