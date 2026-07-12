<?php
session_start();
require __DIR__ . '/../helpers/functions.php';
date_default_timezone_set(app_config('timezone', 'Asia/Jakarta'));
spl_autoload_register(function($class){
    $paths = [__DIR__.'/../core/'.$class.'.php', __DIR__.'/../helpers/'.$class.'.php'];
    foreach (glob(__DIR__.'/../modules/*/'.$class.'.php') as $p) $paths[]=$p;
    foreach ($paths as $p) if (file_exists($p)) { require $p; return; }
});
set_exception_handler(function(Throwable $e){
    error_log($e);
    http_response_code(500);
    $debug = app_config('debug', false);
    include __DIR__.'/../views/errors/500.php';
});
$router = new Router();

$reqPath = app_request_path();
if ($reqPath === '/public' || str_starts_with($reqPath, '/public/')) {
    $newPath = substr($reqPath, 7);
    if ($newPath === '') $newPath = '/';
    header('Location: ' . url($newPath), true, 301);
    exit;
}

if (Auth::check() && !in_array($reqPath, ['/login', '/logout'], true)) {
    Auth::requireBranchAccess();
}

$router->get('/', function(){ header('Location: '.url(Auth::check()?'/dashboard':'/login')); });
$router->get('/login', [AuthController::class,'loginForm']);
$router->post('/login', [AuthController::class,'login']);
$router->get('/logout', [AuthController::class,'logout']);
$router->get('/dashboard', [DashboardController::class,'index']);
$router->get('/api/dashboard/summary', [DashboardController::class,'apiSummary']);
$router->get('/store', [StoreController::class,'index']);
$router->post('/store/open', [StoreController::class,'open']);
$router->post('/store/close', [StoreController::class,'close']);
$router->get('/products', [ProductController::class,'index']);
$router->post('/products', [ProductController::class,'store']);
$router->post('/products/update', [ProductController::class,'update']);
$router->post('/products/delete', [ProductController::class,'delete']);
$router->get('/products/overrides', [ProductController::class,'overrides']);
$router->post('/products/overrides', [ProductController::class,'saveOverride']);
$router->get('/products/{id}', [ProductController::class,'show']);
$router->get('/inventory', [InventoryController::class,'index']);
$router->post('/inventory', [InventoryController::class,'store']);
$router->post('/inventory/raw/store', [InventoryController::class,'storeRaw']);
$router->post('/inventory/raw/update', [InventoryController::class,'updateRaw']);
$router->get('/inventory/raw/template', [InventoryController::class,'downloadTemplate']);
$router->post('/inventory/raw/import', [InventoryController::class,'importCsv']);
$router->get('/inventory/low-stock', [InventoryController::class,'lowStock']);
$router->get('/recipes', [RecipeController::class,'index']);
$router->post('/recipes/sub', [RecipeController::class,'storeSub']);
$router->post('/recipes/sub/delete', [RecipeController::class,'deleteSub']);
$router->get('/recipes/sub/template', [RecipeController::class,'downloadSubTemplate']);
$router->post('/recipes/sub/import', [RecipeController::class,'importSubCsv']);
$router->post('/recipes/recalculate-all', [RecipeController::class,'recalculateAll']);
$router->get('/recipes/composition/template', [RecipeController::class,'downloadCompTemplate']);
$router->post('/recipes/composition/import', [RecipeController::class,'importCompCsv']);
$router->get('/recipes/variant/{id}', [RecipeController::class,'showByVariant']);
$router->get('/recipes/{recipeId}', [RecipeController::class,'show']);
$router->post('/recipes/{recipeId}/item', [RecipeController::class,'addItem']);
$router->post('/recipes/item/{itemId}/update', [RecipeController::class,'updateItem']);
$router->post('/recipes/item/{itemId}/delete', [RecipeController::class,'removeItem']);
$router->post('/recipes/{recipeId}/recalculate', [RecipeController::class,'recalculate']);

$router->get('/central-settings', [CentralSettingsController::class,'index']);
$router->get('/central-settings/wizard', [CentralSettingsController::class,'wizard']);
$router->post('/central-settings/wizard', [CentralSettingsController::class,'processWizard']);
$router->get('/central-settings/api/items', [CentralSettingsController::class,'apiItems']);

if (class_exists('LoyaltyController')) {
    $router->get('/loyalty/members', [LoyaltyController::class, 'members']);
    $router->get('/loyalty/rewards', [LoyaltyController::class, 'rewards']);
    $router->get('/loyalty/redemptions', [LoyaltyController::class, 'redemptions']);
    $router->post('/loyalty/settings/update', [LoyaltyController::class, 'updateSettings']);
}
$router->get('/member', function() {
    $claim = trim((string)($_GET['claim'] ?? ''));
    $query = $claim !== '' ? '?claim=' . rawurlencode($claim) : '';
    header('Location: ' . url('/member/hook.php', false) . $query);
    exit;
});

if (class_exists('POSController')) {
    $router->get('/pos', [POSController::class,'index']);
    $router->get('/pos/check-member', [POSController::class,'checkMember']);
    $router->post('/pos/checkout', [POSController::class,'checkout']);
    $router->get('/pos/receipt/{id}', [POSController::class,'receipt']);
    $router->get('/orders', [POSController::class,'orders']);
    $router->get('/payments', [POSController::class,'payments']);
    $router->post('/payments/verify', [POSController::class,'verifyPayment']);
    if (class_exists('MidtransController')) {
        $router->post('/api/midtrans/notification', [MidtransController::class,'notification']);
        $router->post('/api/midtrans/token', [MidtransController::class,'createToken']);
    }
}
$router->get('/daily-stock', [DailyStockController::class,'index']);
$router->post('/daily-stock/save', [DailyStockController::class,'save']);
$router->get('/purchases', [PurchaseController::class,'index']);
$router->post('/purchases', [PurchaseController::class,'store']);
$router->get('/expenses', [ExpenseController::class,'index']);
$router->post('/expenses', [ExpenseController::class,'store']);
$router->get('/categories', [CategoryController::class,'index']);
$router->post('/categories/product', [CategoryController::class,'storeProductCategory']);
$router->post('/categories/product/update', [CategoryController::class,'updateProductCategory']);
$router->post('/categories/product/delete', [CategoryController::class,'deleteProductCategory']);
$router->post('/categories/raw', [CategoryController::class,'storeRawCategory']);
$router->post('/categories/raw/update', [CategoryController::class,'updateRawCategory']);
$router->post('/categories/raw/delete', [CategoryController::class,'deleteRawCategory']);
$router->get('/vendors', [VendorController::class,'index']);
$router->post('/vendors', [VendorController::class,'store']);
$router->get('/reports/daily', [ReportController::class,'daily']);
$router->post('/reports/daily/generate', [ReportController::class,'generateDaily']);
$router->get('/reports/financial', [ReportController::class,'financial']);
$router->get('/executive', [ExecutiveController::class,'index']);
$router->get('/executive/print', [ExecutiveController::class,'printReport']);
$router->post('/executive/capital', [ExecutiveController::class,'storeCapital']);
$router->post('/executive/capital/delete', [ExecutiveController::class,'deleteCapital']);
$router->post('/executive/target', [ExecutiveController::class,'storeTarget']);
$router->post('/executive/settings', [ExecutiveController::class,'saveSettings']);
$router->post('/executive/experiment', [ExecutiveController::class,'saveExperiment']);
$router->post('/executive/experiment/update', [ExecutiveController::class,'updateExperiment']);
$router->post('/executive/trend', [ExecutiveController::class,'saveTrend']);
$router->get('/forecasting', [ForecastingController::class,'index']);
$router->post('/forecasting/generate', [ForecastingController::class,'generate']);
$router->post('/forecasting/status', [ForecastingController::class,'status']);
$router->get('/users', [UserController::class,'index']);
$router->post('/users', [UserController::class,'store']);
$router->post('/users/toggle', [UserController::class,'toggleActive']);
$router->post('/users/reset-password', [UserController::class,'resetPassword']);
$router->get('/api/user', [UserController::class,'apiDetail']);
$router->get('/audit-logs', [AuditLogController::class,'index']);
$router->get('/settings', [SettingController::class,'index']);
$router->post('/settings', [SettingController::class,'save']);

// Costing Diagnostics
$router->get('/costing-diagnostics', [CostingDiagnosticsController::class,'index']);

// Stock Corrections & Void
$router->get('/corrections', [CorrectionController::class,'index']);
$router->post('/corrections/void-order', [CorrectionController::class,'voidOrder']);
$router->post('/corrections/stock', [CorrectionController::class,'stockCorrection']);
$router->get('/corrections/history', [CorrectionController::class,'history']);

// HQ / Pusat routes
$router->get('/hq', [HQController::class,'index']);
$router->get('/hq/report', [HQController::class,'report']);

// Branch management routes
$router->get('/branches', [BranchController::class,'index']);
$router->post('/branches', [BranchController::class,'store']);
$router->post('/branches/toggle', [BranchController::class,'toggleActive']);
$router->post('/branches/delete', [BranchController::class,'delete']);

$router->dispatch();
