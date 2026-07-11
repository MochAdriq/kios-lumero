<?php
class VendorModel extends Model
{
 public function list(): array { return $this->all("SELECT v.*, COALESCE(SUM(vp.remaining_amount),0) payable_total FROM vendors v LEFT JOIN vendor_payables vp ON vp.vendor_id=v.id AND vp.status<>'paid' GROUP BY v.id ORDER BY v.is_active DESC,v.name"); }
 public function store(array $d): int { $id=(int)($d['id']??0); if($id>0){$this->execSql("UPDATE vendors SET name=?,phone=?,contact_person=?,address=?,payment_terms=?,is_active=?,updated_at=NOW() WHERE id=?",[trim($d['name']),trim($d['phone']??''),trim($d['contact_person']??''),trim($d['address']??''),trim($d['payment_terms']??''),(int)($d['is_active']??1),$id]); return $id;} $this->execSql("INSERT INTO vendors (name,phone,contact_person,address,payment_terms,is_active,created_at,updated_at) VALUES (?,?,?,?,?,?,NOW(),NOW())",[trim($d['name']),trim($d['phone']??''),trim($d['contact_person']??''),trim($d['address']??''),trim($d['payment_terms']??''),(int)($d['is_active']??1)]); return (int)Database::connection()->lastInsertId(); }
}
