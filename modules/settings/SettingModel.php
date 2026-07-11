<?php
class SettingModel extends Model
{
 private function outletId(): int { if(function_exists('current_outlet_id')) return current_outlet_id(); return (int)(Auth::user()['outlet_id']??1) ?: 1; }
 public function outlet(): ?array { return $this->one("SELECT o.*, c.name company_name, c.legal_name FROM outlets o JOIN companies c ON c.id=o.company_id WHERE o.id=?",[$this->outletId()]); }
 public function settings(): array { $scope=outlet_scope_sql('outlet_id',$this->outletId()); return $this->all("SELECT * FROM system_settings WHERE {$scope['sql']} ORDER BY setting_key",$scope['params']); }
 public function gateway(): array { $scope=outlet_scope_sql('outlet_id',$this->outletId()); return $this->all("SELECT * FROM payment_gateway_configs WHERE {$scope['sql']} ORDER BY provider",$scope['params']); }
 public function save(array $d): void { $out=$this->outletId(); foreach(($d['settings']??[]) as $k=>$v){ $this->execSql("INSERT INTO system_settings (outlet_id,setting_key,setting_value,created_at,updated_at) VALUES (?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),updated_at=NOW()",[$out,$k,$v]); } if(!empty($d['outlet_name'])){ $this->execSql("UPDATE outlets SET name=?, address=?, phone=?, updated_at=NOW() WHERE id=?",[trim($d['outlet_name']),trim($d['outlet_address']??''),trim($d['outlet_phone']??''),$out]); } }
}
