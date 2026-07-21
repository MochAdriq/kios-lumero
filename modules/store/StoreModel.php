<?php
class StoreModel extends Model
{
    public function todaySession(int $outletId): ?array
    { return $this->one("SELECT * FROM daily_store_sessions WHERE outlet_id=? AND business_date=? ORDER BY id DESC LIMIT 1", [$outletId, business_date($outletId)]); }
    public function open(int $outletId, int $userId, float $openingCash, array $staffIds): int
    {
        $this->db->beginTransaction();
        try {
            $bizDate = business_date($outletId);
            $existing = $this->todaySession($outletId);
            if ($existing && $existing['status'] === 'open') throw new RuntimeException('Toko sudah dibuka hari ini.');
            if ($existing) {
                $stmt = $this->db->prepare("UPDATE daily_store_sessions SET status='open', opened_by=?, opened_at=?, opening_cash=?, updated_at=? WHERE id=?");
                $stmt->execute([$userId, now(), $openingCash, now(), $existing['id']]); $sessionId=(int)$existing['id'];
            } else {
                $stmt = $this->db->prepare("INSERT INTO daily_store_sessions (outlet_id,business_date,status,opened_by,opened_at,opening_cash,created_at,updated_at) VALUES (?,?, 'open', ?, ?, ?, ?, ?)");
                $stmt->execute([$outletId,$bizDate,$userId,now(),$openingCash,now(),now()]); $sessionId=(int)$this->db->lastInsertId();
            }
            foreach ($staffIds as $sid) {
                $u = $this->one("SELECT daily_salary FROM users WHERE id=?", [$sid]); if (!$u) continue;
                $this->execSql("INSERT INTO daily_staff_attendance (outlet_id,business_date,user_id,daily_salary,status,created_by,created_at) VALUES (?,?,?,?, 'present', ?, ?) ON DUPLICATE KEY UPDATE daily_salary=VALUES(daily_salary), status='present'", [$outletId,$bizDate,$sid,$u['daily_salary'],$userId,now()]);
                $this->execSql("INSERT INTO payroll_expenses (outlet_id,business_date,user_id,amount,source,notes,created_at) VALUES (?,?,?,?, 'auto_open_store', 'Auto payroll saat buka toko', ?) ON DUPLICATE KEY UPDATE amount=VALUES(amount)", [$outletId,$bizDate,$sid,$u['daily_salary'],now()]);
            }
            $this->db->commit(); return $sessionId;
        } catch (Throwable $e) { $this->db->rollBack(); throw $e; }
    }
    public function close(int $outletId, int $userId, float $physicalCash, string $notes): void
    {
        $session = $this->one("SELECT * FROM daily_store_sessions WHERE outlet_id=? AND status='open' ORDER BY id DESC LIMIT 1", [$outletId]);
        if (!$session) throw new RuntimeException('Tidak ada sesi toko yang sedang terbuka.');
        $bizDate = $session['business_date'];
        $cash = $this->one("SELECT COALESCE(SUM(p.amount),0) total FROM payments p JOIN orders o ON o.id=p.order_id WHERE o.outlet_id=? AND o.business_date=? AND p.payment_method='cash' AND p.status='paid'", [$outletId,$bizDate]);
        $systemCash = (float)$session['opening_cash'] + (float)($cash['total'] ?? 0);
        $diff = $physicalCash - $systemCash;
        $this->execSql("UPDATE daily_store_sessions SET status='closed', closed_by=?, closed_at=?, closing_cash_system=?, closing_cash_physical=?, cash_difference=?, notes=?, updated_at=? WHERE id=?", [$userId,now(),$systemCash,$physicalCash,$diff,$notes,now(),$session['id']]);
        Audit::log('close_store','daily_store_sessions',(int)$session['id'], $session, ['physical_cash'=>$physicalCash]);
    }
    public function activeStaff(int $outletId): array { return $this->all("SELECT id,name,daily_salary FROM users WHERE is_active=1 AND outlet_id=? ORDER BY name", [$outletId]); }
}
