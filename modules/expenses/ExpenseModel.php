<?php
class ExpenseModel extends Model
{
    private function outletId(): int { return function_exists('current_outlet_id') ? current_outlet_id() : ((int)(Auth::user()['outlet_id']??1) ?: 1); }
    public function categories(): array { return $this->all("SELECT * FROM expense_categories ORDER BY type,name"); }
    public function list(string $from,string $to): array { return $this->all("SELECT e.*, c.name category_name, c.type FROM operational_expenses e JOIN expense_categories c ON c.id=e.category_id WHERE e.outlet_id=? AND e.business_date BETWEEN ? AND ? ORDER BY e.business_date DESC,e.id DESC LIMIT 300",[$this->outletId(),$from,$to]); }
    public function store(array $d): int { $this->execSql("INSERT INTO operational_expenses (outlet_id,business_date,category_id,amount,payment_method,description,created_by,created_at) VALUES (?,?,?,?,?,?,?,NOW())",[$this->outletId(),$d['business_date']?:today(),(int)$d['category_id'],(float)$d['amount'],$d['payment_method']??'cash',trim($d['description']??''),Auth::id()]); return (int)Database::connection()->lastInsertId(); }
    public function find(int $id): ?array { return $this->row("SELECT * FROM operational_expenses WHERE id=? AND outlet_id=?", [$id, $this->outletId()]); }
    public function update(int $id, array $d): void { $this->execSql("UPDATE operational_expenses SET business_date=?, category_id=?, amount=?, payment_method=?, description=? WHERE id=? AND outlet_id=?", [$d['business_date']?:today(), (int)$d['category_id'], (float)$d['amount'], $d['payment_method']??'cash', trim($d['description']??''), $id, $this->outletId()]); }
}
