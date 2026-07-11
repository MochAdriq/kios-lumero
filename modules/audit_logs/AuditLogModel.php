<?php
class AuditLogModel extends Model
{
 public function list(string $q=''): array { $where='';$p=[]; if($q!==''){ $where="WHERE a.action LIKE ? OR a.table_name LIKE ? OR u.name LIKE ?"; $s='%'.$q.'%'; $p=[$s,$s,$s]; } return $this->all("SELECT a.*, u.name user_name, o.name outlet_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN outlets o ON o.id=a.outlet_id $where ORDER BY a.created_at DESC LIMIT 300",$p); }
}
