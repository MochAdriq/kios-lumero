<?php
class AuditLogController extends Controller
{ public function index(): void { Auth::requireRoles(['super_admin']); $q=trim($_GET['q']??''); $this->view('audit-logs/index',['pageTitle'=>'Audit Trail','q'=>$q,'items'=>(new AuditLogModel())->list($q)]); } }
