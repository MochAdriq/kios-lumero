<?php
class ForecastingController extends Controller
{
 public function index(): void { Auth::requireRoles(['super_admin','administrator']); $this->view('forecasting/index',['pageTitle'=>'Forecast Belanja','items'=>(new ForecastingModel())->recommendations()]); }
 public function generate(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); $n=(new ForecastingModel())->generate(); $_SESSION['flash_success']='Rekomendasi belanja dibuat: '.$n.' item.'; $this->redirect('/forecasting'); }
 public function status(): void { Auth::requireRoles(['super_admin','administrator']); verify_csrf(); (new ForecastingModel())->updateStatus((int)$_POST['id'],$_POST['status']); $this->redirect('/forecasting'); }
}
