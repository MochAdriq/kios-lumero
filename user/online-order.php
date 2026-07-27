<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__.'/../helpers/functions.php';
require_once __DIR__.'/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__.'/../helpers/free_order_helper.php';
require_once __DIR__.'/../config/loyalty.php';
require_once __DIR__.'/../helpers/delivery_helper.php';
date_default_timezone_set('Asia/Jakarta');
try{ if(isset($pdo) && $pdo instanceof PDO) $pdo->exec("SET time_zone = '+07:00'"); }catch(Throwable $e){}
if(function_exists('ensure_today_stock')) ensure_today_stock();
fo_ensure_tables($pdo);
loyalty_ensure_tables($pdo);
$memberOnline = null;
if(!empty($_SESSION['member_id'])) $memberOnline = loyalty_member_by_id($pdo,(int)$_SESSION['member_id']);
if(!$memberOnline){ header('Location: login.php'); exit; }

if(empty($_SESSION['welcome_passed']) || (!isset($_GET['outlet_id']) && !isset($_SESSION['lumero_selected_outlet_id']) && !isset($_GET['lookup_phone']))) {
    header('Location: welcome.php'); exit;
}

$memberPointBalance=(int)($memberOnline['total_points'] ?? 0);
$memberPointValue=max(1,(int)(loyalty_settings($pdo)['redeem_point_value'] ?? 500));

$deliveryEnabled = delivery_is_enabled($pdo);
$deliverySettings = delivery_settings($pdo);
$deliveryOutletCoords = delivery_outlet_coords($pdo) ?? ['lat' => -6.9175, 'lng' => 106.9275];

$stOutlets = $pdo->query("SELECT id, slug, name, outlet_code AS code, is_hq, is_active, closing_hour, address, phone, latitude, longitude FROM outlets WHERE is_active = 1 ORDER BY is_hq DESC, name ASC");
$activeOutletsList = $stOutlets ? $stOutlets->fetchAll(PDO::FETCH_ASSOC) : [];
if (empty($activeOutletsList)) {
    $bc = app_branch_config();
    if (!empty($bc['default'])) $activeOutletsList[] = $bc['default'];
    foreach (($bc['map'] ?? []) as $b) {
        $activeOutletsList[] = $b;
    }
}


if(isset($_GET['lookup_phone'])){
  header('Content-Type: application/json; charset=utf-8');
  $phone=fo_normalize_phone((string)$_GET['lookup_phone']);
  $cust=fo_get_customer_by_phone($pdo,$phone);
  $created=false;
  if(!$cust && isset($_GET['remember_phone']) && $phone!==''){
    fo_register_phone_only($pdo,$phone);
    $cust=fo_get_customer_by_phone($pdo,$phone);
    $created=true;
  }
  $name=$cust['name'] ?? '';
  $isPlaceholder=(mb_strtolower(trim((string)$name))==='pelanggan');
  echo json_encode([
    'ok'=>true,
    'found'=>(bool)$cust && !$isPlaceholder,
    'registered'=>(bool)$cust,
    'created'=>$created,
    'phone'=>$phone,
    'name'=>$isPlaceholder ? '' : $name,
    'last_order_no'=>$cust['last_order_no'] ?? '',
    'order_count'=>(int)($cust['order_count'] ?? 0)
  ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}

$msg=$err='';
$orderPopup=null;
$today=date('Y-m-d');
$tomorrow=(new DateTime($today))->modify('+1 day')->format('Y-m-d');
$nowTime=date('H:i');

$qrisInfo=function_exists('get_setting')?get_setting('payment_qris_info','Scan QRIS outlet D\'Celup'):'Scan QRIS outlet D\'Celup';
$paymentQrisImage=trim((string)(function_exists('get_setting')?get_setting('payment_qris_image',''):''));
if($paymentQrisImage==='assets/img/payment/qris-dana.jpeg' || $paymentQrisImage==='public/assets/images/pos-products/payment/qris-dana.jpeg') $paymentQrisImage='';

require_once __DIR__.'/../helpers/MidtransService.php';
$isMidtransQris = (get_setting('qris_payment_method', 'manual') === 'midtrans') && class_exists('MidtransService') && MidtransService::getServerKey() !== '';

$bankName='BCA';
$bankAccountName='Sri Kusma Dewi';
$bankAccountNo='0382731393';

$freeOrderVideo='public/assets/video/lumero-promo.mp4';
$freeOrderPoster='public/assets/images/pos-products/lumero-pasekon.png';
$freeOrderVoiceBase='../public/assets/audio/';

if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $customerName=trim((string)($_POST['customer_name'] ?? ''));
    $customerPhone=fo_normalize_phone(trim((string)($_POST['customer_phone'] ?? '')));
    $pickupDate=trim((string)($_POST['pickup_date'] ?? $today));
    $pickupTime=trim((string)($_POST['pickup_time'] ?? '09:00'));
    $paymentMethod=trim((string)($_POST['payment_method'] ?? ''));
    $pickupType=trim((string)($_POST['pickup_type'] ?? 'outlet'));
    if(!in_array($pickupType, ['outlet', 'delivery'], true)) $pickupType='outlet';
    if($pickupType==='delivery' && !$deliveryEnabled) throw new Exception('Fitur Delivery Order saat ini tidak tersedia.');
    $note=trim((string)($_POST['customer_note'] ?? ''));
    $cart=json_decode($_POST['cart'] ?? '[]', true);
    if($customerName==='') throw new Exception('Nama pemesan wajib diisi.');
    if($customerPhone==='') throw new Exception('Nomor WhatsApp wajib diisi.');
    if(!is_array($cart) || count($cart)<=0) throw new Exception('Keranjang masih kosong (dari PHP Server, data POST yang diterima: ' . substr(trim($_POST['cart'] ?? 'KOSONG'), 0, 150) . ')');
    if($pickupType === 'delivery') {
      $pickupDate = $today;
      $pickupTime = $nowTime;
    } else {
      if(!fo_valid_date($pickupDate) || strtotime($pickupDate)<strtotime($today)) throw new Exception('Tanggal pengambilan tidak valid.');
      if(!fo_valid_time($pickupTime)) throw new Exception('Jam pengambilan tidak valid.');
    }
    if(!in_array($paymentMethod, ['qris', 'transfer', 'point'], true)) throw new Exception('Pilihan metode pembayaran tidak valid.');

    $calc=fo_normalize_cart($pdo,$cart);
    if(!$calc['items']) throw new Exception('Keranjang tidak valid (dari PHP Server fo_normalize_cart).');

    $deliveryAddress = null;
    $deliveryLat = null;
    $deliveryLng = null;
    $deliveryFee = 0;
    $deliveryDistanceKm = null;
    $deliveryStatus = null;
    $deliveryCourierName = null;

    $selectedOutletId = (int)($_POST['outlet_id'] ?? current_outlet_id());
    if ($selectedOutletId <= 0) $selectedOutletId = 1;

    if($pickupType === 'delivery'){
      if ($selectedOutletId > 0) {
          $stO = $pdo->prepare("SELECT latitude, longitude FROM outlets WHERE id = ? AND latitude IS NOT NULL AND longitude IS NOT NULL");
          $stO->execute([$selectedOutletId]);
          $rowO = $stO->fetch(PDO::FETCH_ASSOC);
          if ($rowO && $rowO['latitude'] && $rowO['longitude']) {
              $deliveryOutletCoords = ['lat' => (float)$rowO['latitude'], 'lng' => (float)$rowO['longitude']];
          }
      }
      $deliveryAddress = trim((string)($_POST['delivery_address'] ?? ''));
      $deliveryLat = (float)($_POST['delivery_lat'] ?? 0);
      $deliveryLng = (float)($_POST['delivery_lng'] ?? 0);
      if($deliveryAddress === '' || $deliveryLat == 0 || $deliveryLng == 0){
        throw new Exception('Silakan lengkapi titik lokasi dan alamat lengkap pengantaran pada peta.');
      }
      $deliveryDistanceKm = delivery_haversine($deliveryOutletCoords['lat'], $deliveryOutletCoords['lng'], $deliveryLat, $deliveryLng);
      if(!delivery_validate_radius($pdo, $deliveryDistanceKm)){
        throw new Exception('Alamat pengantaran (' . $deliveryDistanceKm . ' km) melebihi batas radius maksimal (' . $deliverySettings['delivery_max_radius_km'] . ' km).');
      }
      $deliveryFee = delivery_calculate_fee($pdo, $deliveryDistanceKm, (int)$calc['subtotal']);
      $deliveryStatus = 'preparing';
      $deliveryCourierName = 'Kurir Internal';
    }

    $pdo->beginTransaction();
    $no=fo_next_no($pdo);
    $memberId=(int)($memberOnline['id'] ?? 0);
    $subtotalOnline=(int)$calc['subtotal'];
    $redeemPoints=0; $redeemAmount=0; $pointValue=$memberPointValue; $paymentStatus='unpaid'; 
    $totalDue=$subtotalOnline + $deliveryFee;
    if($paymentMethod==='point'){
      if($memberId<=0) throw new Exception('Sesi member tidak ditemukan. Silakan masuk ulang.');
      $redeemPoints=loyalty_required_points_for_amount($pdo,$subtotalOnline);
      if($redeemPoints<=0) throw new Exception('Total belanja tidak valid untuk pembayaran point.');
      $fresh=loyalty_member_by_id($pdo,$memberId);
      if((int)($fresh['total_points'] ?? 0) < $redeemPoints) throw new Exception('Point belum mencukupi. Butuh '.$redeemPoints.' point untuk membayar total belanja ini.');
      $redeemAmount=$subtotalOnline;
      $paymentStatus='paid';
      $totalDue=0;
      loyalty_deduct_points($pdo,$memberId,$redeemPoints,'redeem_payment','Bayar online order dengan point '.$no,null,null);
    }
    $st=$pdo->prepare("INSERT INTO free_orders (pre_order_no,customer_name,customer_phone,member_id,pickup_type,pickup_date,pickup_time,payment_method,payment_status,order_status,subtotal,discount,total,total_hpp,loyalty_points_redeemed,loyalty_point_value,loyalty_redeem_amount,customer_note,cart_json,stock_reserved,delivery_address,delivery_lat,delivery_lng,delivery_fee,delivery_distance_km,delivery_status,delivery_courier_name,outlet_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$no,$customerName,$customerPhone,$memberId ?: null,$pickupType,$pickupDate,$pickupTime.':00',$paymentMethod,$paymentStatus,'new',$subtotalOnline,$redeemAmount,$totalDue,$calc['total_hpp'],$redeemPoints,$pointValue,$redeemAmount,$note,json_encode($cart,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),0,$deliveryAddress,$deliveryLat,$deliveryLng,$deliveryFee,$deliveryDistanceKm,$deliveryStatus,$deliveryCourierName,$selectedOutletId]);
    $freeOrderId=(int)$pdo->lastInsertId();

    $ins=$pdo->prepare("INSERT INTO free_order_items (free_order_id,item_type,chicken_part_id,chicken_style,sauce_id,with_rice,matcha_variant_id,kentang_variant_id,menu_item_id,item_name,qty,price,hpp,line_total,line_hpp,payload_json) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach($calc['items'] as $ni){
      $ins->execute([
        $freeOrderId,
        $ni['type'] ?? 'menu',
        $ni['chicken_part_id'] ?? null,
        $ni['chicken_style'] ?? null,
        $ni['sauce_id'] ?? null,
        $ni['with_rice'] ?? 0,
        $ni['matcha_variant_id'] ?? null,
        $ni['kentang_variant_id'] ?? null,
        $ni['menu_item_id'] ?? ($ni['variant_id'] ?? ($ni['id'] ?? null)),
        $ni['item_name'] ?? ($ni['name'] ?? 'Menu'),
        $ni['qty'] ?? 1,
        $ni['price'] ?? 0,
        $ni['hpp'] ?? 0,
        ($ni['price'] ?? 0) * ($ni['qty'] ?? 1),
        ($ni['hpp'] ?? 0) * ($ni['qty'] ?? 1),
        isset($ni['payload']) ? json_encode($ni['payload'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null
      ]);
    }
    fo_upsert_customer($pdo,$customerPhone,$customerName,$no);
    $pdo->commit();

    if ($paymentMethod === 'point') {
        try {
            require_once __DIR__.'/../modules/pos/POSModel.php';
            if (class_exists('POSModel')) {
                $posModel = new POSModel($pdo);
                $posModel->verifyFreeOrderPayment($freeOrderId, null);
            }
        } catch (Throwable $e) {}
    }

    // === MIDTRANS QRIS: Intercept untuk payment QRIS via AJAX ===
    if ($paymentMethod === 'qris' && $isMidtransQris && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        try {
            $items = [];
            foreach ($calc['items'] as $ci) {
                $items[] = [
                    'id'       => (string)($ci['menu_item_id'] ?? ($ci['id'] ?? 'ITEM')),
                    'price'    => (int)round((float)($ci['price'] ?? 0)),
                    'quantity' => (int)($ci['qty'] ?? 1),
                    'name'     => mb_substr((string)($ci['item_name'] ?? ($ci['name'] ?? 'Menu')), 0, 50),
                ];
            }
            $qrisResult = MidtransService::createQrisCharge([
                'order_number'  => $no,
                'grand_total'   => $totalDue > 0 ? $totalDue : $subtotalOnline,
                'customer_name' => $customerName,
                'items'         => $items,
            ]);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'             => true,
                'mode'           => 'qris_midtrans',
                'order_no'       => $no,
                'qr_url'         => $qrisResult['qr_url'],
                'qr_string'      => $qrisResult['qr_string'],
                'midtrans_order' => $qrisResult['order_id'],
                'gross_amount'   => $qrisResult['gross_amount'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        } catch (Throwable $midEx) {
            // Gagal buat QRIS Midtrans — fallback: redirect normal
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'       => false,
                'mode'     => 'qris_fallback',
                'order_no' => $no,
                'error'    => $midEx->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    header('Location: ../order-online/lacak.php?no='.urlencode($no).'&success=1'); exit;
  }catch(Throwable $e){
    if(isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    // Jika AJAX, kembalikan JSON error
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $err=$e->getMessage();
  }
}
// order berhasil diarahkan ke halaman lacak pesanan.

require_once __DIR__.'/../core/Model.php';
require_once __DIR__.'/../modules/pos/POSModel.php';
require_once __DIR__.'/../helpers/pos_helper.php';
try {
    $orderNo = '#ORD-' . date('Y') . '-' . str_pad(rand(1,999), 3, '0', STR_PAD_LEFT);
    $activeOutletId = (int)($_GET['outlet_id'] ?? $_SESSION['lumero_selected_outlet_id'] ?? current_outlet_id());
    if ($activeOutletId <= 0) $activeOutletId = 1;
    if (isset($_GET['outlet_id'])) {
        $_SESSION['lumero_selected_outlet_id'] = (int)$_GET['outlet_id'];
    }
    $activeOutletRow = null;
    foreach ($activeOutletsList as $oL) {
        if ((int)$oL['id'] === $activeOutletId) {
            $activeOutletRow = $oL;
            break;
        }
    }
    $outletOpStatus = check_outlet_operating_status($activeOutletId, $activeOutletRow);
    $posModel = new POSModel();
    $categories = $posModel->categoriesWithProducts($activeOutletId);
    $preparedData = sim_pos_prepare_data($categories);
    $preparedCategories = $preparedData['categories'];
    $posAssets = $preparedData['assets'];
    $totalVariants = $preparedData['total_variants'];
} catch(Exception $e) {
    $preparedCategories = [];
    $posAssets = [];
    $totalVariants = 0;
}
?>
<script>
window.SIM_POS_DATA = <?= json_encode(['categories'=>$preparedCategories,'assets'=>$posAssets], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
</script>
<?php

$activeOutletId = (int)($_GET['outlet_id'] ?? $_SESSION['lumero_selected_outlet_id'] ?? current_outlet_id());
if ($activeOutletId <= 0) $activeOutletId = 1;
$data = function_exists('fo_load_pos_menu_data') ? fo_load_pos_menu_data($pdo, $activeOutletId) : (function_exists('load_menu_data') ? load_menu_data() : ['parts'=>[],'sauces'=>[],'kentang'=>[],'matcha'=>[]]);
$data['parts']=array_values(array_filter($data['parts'] ?? [], function($p){
  $name=mb_strtolower(trim((string)($p['name'] ?? '')));
  return strpos($name,'chicken crips')===false
    && strpos($name,'chicken crisp')===false
    && strpos($name,'ayam crips')===false
    && strpos($name,'ayam crisp')===false
    && strpos($name,'crisps')===false;
}));

$wholeChickenMenus=fo_all($pdo,"SELECT id,name,description,price,hpp,image_url FROM menu_items WHERE is_active=1 AND (LOWER(name) LIKE '%1 ekor ayam%' OR LOWER(slug) LIKE '%1-ekor-ayam%') ORDER BY price ASC, id ASC");
if(!$wholeChickenMenus){
  $wholeChickenMenus=[
    ['id'=>0,'name'=>'1 ekor ayam original','description'=>'Paket 1 ekor ayam original','price'=>66000,'hpp'=>40000,'image_url'=>''],
    ['id'=>0,'name'=>'1 ekor ayam + saus','description'=>'Paket 1 ekor ayam plus saus','price'=>76000,'hpp'=>45000,'image_url'=>'']
  ];
}
function fo_menu_item_img($row){
  $name=mb_strtolower((string)($row['name'] ?? ''));
  if(strpos($name,'1 ekor ayam')!==false || strpos($name,'satu ekor ayam')!==false){
    if(strpos($name,'saus')!==false || strpos($name,'sauce')!==false || strpos($name,'celup')!==false){
      return '../public/assets/images/pos-products/celup-saus.png';
    }
    return '../public/assets/images/pos-products/original.png';
  }
  $img=trim((string)($row['image_url'] ?? ''));
  if($img!==''){
    if(preg_match('~^https?://~i',$img)) return $img;
    return '../'.ltrim($img,'/');
  }
  return '../public/assets/images/pos-products/original.png';
}


function fo_img_part($name){
  if(function_exists('part_image')) return part_image($name);
  $n=strtolower($name);
  if(strpos($n,'dada')!==false) return 'dada.png';
  if(strpos($n,'paha bawah')!==false) return 'paha-bawah.png';
  if(strpos($n,'paha atas')!==false) return 'paha-atas.png';
  if(strpos($n,'sayap')!==false) return 'sayap.png';
  return 'original.png';
}
function fo_img_sauce($name){ return function_exists('sauce_image') ? sauce_image($name) : 'saus.png'; }
function fo_img_matcha($name){ return function_exists('matcha_image') ? matcha_image($name) : 'matcha.png'; }

$priceMap=[];
foreach($data['parts'] as $p){
  $partId=(int)$p['id'];
  $partName=(string)$p['name'];
  foreach([0,1] as $rice){
    $calc=fo_calc_item($pdo,['type'=>'chicken','part_id'=>$partId,'part_name'=>$partName,'style'=>'original','with_rice'=>$rice,'qty'=>1]);
    $priceMap['chicken'][$partId]['original'][0][$rice]=['price'=>(int)$calc['price'],'hpp'=>(int)$calc['hpp'],'name'=>($calc['name'] ?? $calc['item_name'] ?? '')];
    foreach($data['sauces'] as $s){
      $sauceId=(int)$s['id'];
      $calc=fo_calc_item($pdo,['type'=>'chicken','part_id'=>$partId,'part_name'=>$partName,'style'=>'sauce','sauce_id'=>$sauceId,'with_rice'=>$rice,'qty'=>1]);
      $priceMap['chicken'][$partId]['sauce'][$sauceId][$rice]=['price'=>(int)$calc['price'],'hpp'=>(int)$calc['hpp'],'name'=>($calc['name'] ?? $calc['item_name'] ?? '')];
    }
  }
}

$priceMap['menu_item']=[];
foreach($wholeChickenMenus as $mi){
  $priceMap['menu_item'][(int)$mi['id']]=['price'=>(int)$mi['price'],'hpp'=>(int)$mi['hpp'],'name'=>(string)$mi['name']];
}

$extraRicePrice=(int)(function_exists('get_setting') ? get_setting('online_order_extra_rice_price','5000') : 5000);
$extraRiceHpp=(int)(function_exists('get_setting') ? get_setting('online_order_extra_rice_hpp','2500') : 2500);
$extraSaucePrice=(int)(function_exists('get_setting') ? get_setting('online_order_extra_sauce_price','5000') : 5000);
$extraSauceHpp=(int)(function_exists('get_setting') ? get_setting('online_order_extra_sauce_hpp','2000') : 2000);

$aiReco=fo_all($pdo,"SELECT * FROM online_order_ai_recommendations WHERE is_active=1 ORDER BY priority ASC, id DESC LIMIT 1");
$aiReco=$aiReco[0] ?? null;

$comboMenus=fo_all($pdo,"SELECT * FROM online_order_combo_menus WHERE is_active=1 ORDER BY priority ASC, id DESC LIMIT 20");
if(!$comboMenus){
  $comboMenus=[
    ['id'=>0,'title'=>'Combo Hemat 2 Ayam + Nasi','description'=>'2 ayam original + nasi untuk makan siang atau malam lebih hemat.','price'=>20000,'hpp'=>12000,'image_url'=>'','promo_text'=>'Cocok untuk Kakak yang ingin makan kenyang dengan harga hemat.'],
    ['id'=>0,'title'=>'Combo Celup 2 Ayam + Nasi','description'=>'2 ayam celup saus favorit + nasi.','price'=>22000,'hpp'=>13500,'image_url'=>'','promo_text'=>'Pilihan tepat kalau Kakak suka rasa saus yang lebih lumer dan gurih.']
  ];
}
$nowHM=(int)date('Hi');
$comboWindowActive=(($nowHM>=1100 && $nowHM<=1130) || ($nowHM>=1500 && $nowHM<=1530) || ($nowHM>=1900 && $nowHM<=2100));

$aiNarratives=[];
try{
  $narrRows=fo_all($pdo,"SELECT * FROM online_order_ai_narratives WHERE is_active=1 ORDER BY priority ASC, id DESC");
  foreach($narrRows as $nr){
    $aiNarratives[$nr['scenario']][]=[
      'title'=>(string)$nr['title'],
      'message'=>(string)$nr['message'],
      'suggested_menu'=>(string)$nr['suggested_menu'],
      'cta_text'=>(string)$nr['cta_text'],
      'priority'=>(int)$nr['priority']
    ];
  }
}catch(Throwable $e){}
if(!$aiNarratives){
  $aiNarratives=[
    'empty_cart'=>[['title'=>'Bingung Pilih Menu?','suggested_menu'=>'Ayam Crispy Varian Saus','message'=>'Tenang kak, jangan bingung! Kalau kaka bingung pilih menu, aku bantu ya. Di Lumero, Kakak wajib coba menu ayam crispy dengan varian saus favorit. Kriuknya mantap, sausnya lumer, aromanya menggoda, dan rasanya bikin pengen nambah. Biar makin sedap, kaka juga bisa tambahkan kentang kriwil dan Matcha, cobain deh, gak akan nyesel!!!','cta_text'=>'Coba ayam saus favorit']],
    'only_chicken_original'=>[['title'=>'Lengkapi Ayam Original','suggested_menu'=>'Kentang Kriuk dan Matcha','message'=>'Biar makin lengkap, tambahkan kentang kriuk dan minuman matcha segar sebagai penyempurna hidangan. Dijamin bikin ketagihan deh, hihihi...','cta_text'=>'Tambah kentang dan matcha']],
    'chicken_original_rice'=>[['title'=>'Ayam dan Nasi Sudah Pas','suggested_menu'=>'Saus Favorit + Minuman','message'=>'Ayam original plus nasi sudah mantap, Kak. Biar rasanya makin hidup, tambahkan varian saus favorit dan minuman segar. Sekali celup, kriuknya makin lumer di hati.','cta_text'=>'Tambah saus dan minuman']],
    'chicken_sauce'=>[['title'=>'Ayam Saus Sudah Mantap','suggested_menu'=>'Nasi + Kentang + Matcha','message'=>'Pilihan ayam saus Kakak sudah juara. Biar makin puas, tambahkan nasi hangat, kentang kriwil, atau Matcha segar. Lengkapnya dapet, nikmatnya makin nempel.','cta_text'=>'Lengkapi dengan nasi atau minuman']],
    'only_potato'=>[['title'=>'Kentang Kriwil Mantap','suggested_menu'=>'Ayam Crispy dan Minuman','message'=>'Kentangnya sudah cocok jadi teman ngemil. Biar lebih puas, pasangkan dengan ayam crispy dan minuman segar favorit Kakak.','cta_text'=>'Tambah ayam dan minuman']],
    'only_drink'=>[['title'=>'Minuman Segar Siap','suggested_menu'=>'Ayam Crispy Saus','message'=>'Minumannya sudah segar, Kak. Sekarang waktunya tambahkan ayam crispy varian saus favorit. Kriuknya mantap, sausnya lumer, cocok banget jadi pasangan minuman Kakak.','cta_text'=>'Tambah ayam crispy']],
    'drink_potato'=>[['title'=>'Minuman dan Kentang Sudah Oke','suggested_menu'=>'Ayam Crispy Varian Saus','message'=>'Minuman dan kentang sudah jadi duet yang asik. Tapi biar makin lengkap, tambahkan ayam crispy saus Lumero. Dijamin makin kenyang dan makin puas.','cta_text'=>'Tambah ayam saus']],
    'drink_chicken'=>[['title'=>'Ayam dan Minuman Sudah Mantap','suggested_menu'=>'Kentang Kriwil','message'=>'Ayam dan minuman Kakak sudah pas banget. Biar teksturnya makin rame, tambahkan kentang kriwil yang renyah. Jadi lengkap, gurih, segar, dan nagih.','cta_text'=>'Tambah kentang kriwil']],
    'all_menu'=>[['title'=>'Pesanan Sudah Lengkap','suggested_menu'=>'Tambahan Saus Favorit','message'=>'Wah, pilihan Kakak sudah lengkap banget! Ayam ada, kentang ada, minuman juga ada. Kalau mau makin lumer, tambahkan saus favorit ekstra biar setiap gigitan makin seru.','cta_text'=>'Tambah saus ekstra']],
    'only_sauce'=>[['title'=>'Sausnya Sudah Siap','suggested_menu'=>'Ayam Crispy Original','message'=>'Saus favoritnya sudah dipilih, Kak. Sekarang tinggal pasangkan dengan ayam crispy original yang kriuknya mantap. Biar sausnya punya pasangan terbaik.','cta_text'=>'Tambah ayam crispy']],
    'only_rice'=>[['title'=>'Nasinya Sudah Siap','suggested_menu'=>'Ayam Crispy Saus','message'=>'Nasi hangatnya sudah siap, Kak. Biar jadi hidangan lengkap, tambahkan ayam crispy varian saus favorit. Kriuk, lumer, dan bikin makan makin semangat.','cta_text'=>'Tambah ayam saus']],
    'whole_chicken'=>[['title'=>'Ayam 1 Ekor Mantap','suggested_menu'=>'Saus Ekstra dan Minuman','message'=>'Wah, 1 ekor ayam sudah pilihan mantap untuk rame-rame. Biar makin seru, tambahkan saus ekstra dan minuman segar supaya semua kebagian rasa favorit.','cta_text'=>'Tambah saus dan minuman']],
    'whole_chicken_sauce'=>[['title'=>'Ayam 1 Ekor Saus Juara','suggested_menu'=>'Nasi dan Matcha','message'=>'Ayam 1 ekor plus saus sudah paket yang menggoda banget. Biar makin lengkap untuk disantap bareng, tambahkan nasi hangat dan Matcha segar.','cta_text'=>'Tambah nasi dan matcha']],
    'promo_window'=>[['title'=>'Jam Promo Spesial','suggested_menu'=>'Combo Hemat Jam Spesial','message'=>'Kakak lagi masuk jam promo nih! Ini waktu paling pas ambil paket combo hemat. Ayamnya nikmat, nasinya ada, harganya lebih bersahabat, dan rasanya tetap juara.','cta_text'=>'Ambil combo promo sekarang']],
    'general'=>[['title'=>'Saran Menu Lumero','suggested_menu'=>'Ayam Crispy Varian Saus','message'=>'Kakak bisa pilih ayam crispy varian saus favorit Lumero. Kriuknya mantap, sausnya lumer, dan cocok dilengkapi kentang atau minuman segar.','cta_text'=>'Pilih menu favorit']]
  ];
}

function fo_combo_img($row){
  $img=trim((string)($row['image_url'] ?? ''));
  if($img!==''){
    if(preg_match('~^https?://~i',$img)) return $img;
    return '../'.ltrim($img,'/');
  }
  return '../public/assets/images/pos-products/original.png';
}
?>
<!doctype html>
<html lang="id" data-theme="light">
<head>
<link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<style>
/* ── Delivery Map Custom Styles ── */
.fo-search-results {
  position: absolute; left:0; right:0; top:100%; z-index:500;
  background: var(--dp-surface-2); border: 1px solid var(--dp-glass-border);
  border-radius: 8px; max-height: 160px; overflow-y: auto;
  box-shadow: 0 8px 24px rgba(0,0,0,0.5);
}
.fo-search-item {
  padding: 8px 12px; font-size: 12px; color: var(--dp-text); cursor: pointer;
  border-bottom: 1px solid rgba(255,255,255,0.05); transition: background 0.15s;
}
.fo-search-item:hover { background: var(--dp-surface-hover); color: var(--dp-gold); }
.fo-btn-search {
  background: var(--dp-surface-2); border: 1px solid var(--dp-glass-border);
  color: var(--dp-text); border-radius: 8px; padding: 0 14px; font-size: 12px;
  font-weight: 600; cursor: pointer; transition: all 0.2s;
}
.fo-btn-search:hover { background: var(--dp-red); color: #fff; border-color: var(--dp-red); }
/* ============================================
   Lumero SELF-ORDER – DARK PREMIUM CINEMATIC
   Adapted from POS kasir2-theme
   ============================================ */

/* ── Design Tokens (copas dari POS) ── */
/* ── Design Tokens (copas dari POS) ── */
:root{
  --dp-bg:#f4f6f9;--dp-bg-2:#ffffff;--dp-surface:#ffffff;--dp-surface-2:#f8f9fc;
  --dp-surface-hover:#e9ecef;--dp-glass:rgba(255,255,255,.85);--dp-glass-border:rgba(0,0,0,.08);
  --dp-text:#111827;--dp-text-2:#4b5563;--dp-muted:#6b7280;
  --dp-line:rgba(0,0,0,.08);--dp-shadow:0 4px 20px rgba(0,0,0,.06);
  --dp-red:#ff2d55;--dp-red-glow:rgba(255,45,85,.25);--dp-red-soft:rgba(255,45,85,.12);
  --dp-orange:#ff6b35;--dp-gradient:linear-gradient(135deg,#ff2d55,#ff6b35);
  --dp-gradient-subtle:linear-gradient(135deg,rgba(255,45,85,.08),rgba(255,107,53,.08));
  --dp-green:#34d399;--dp-green-soft:rgba(52,211,153,.12);
  --dp-shadow-glow:0 0 30px rgba(255,45,85,.15);--dp-radius:16px;--dp-radius-sm:10px;
  --dp-font:'Plus Jakarta Sans','Inter',system-ui,sans-serif;
  --dp-transition:.25s cubic-bezier(.4,0,.2,1);
}
[data-theme="dark"]{
  --dp-bg:#0f0f13;--dp-bg-2:#16161d;--dp-surface:#1a1a2e;--dp-surface-2:#22223a;
  --dp-surface-hover:#2a2a45;--dp-glass:rgba(26,26,46,.72);--dp-glass-border:rgba(255,255,255,.06);
  --dp-text:#f1f1f5;--dp-text-2:#c4c4d4;--dp-muted:#6b6b85;
  --dp-line:rgba(255,255,255,.06);--dp-shadow:0 4px 24px rgba(0,0,0,.4);
}

/* ── Animations ── */
@keyframes fadeInUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideInRight{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}
@keyframes pulseGlow{0%,100%{box-shadow:0 0 20px rgba(255,45,85,.3),0 4px 20px rgba(0,0,0,.3)}50%{box-shadow:0 0 40px rgba(255,45,85,.5),0 4px 20px rgba(0,0,0,.3)}}
@keyframes cardAppear{from{opacity:0;transform:translateY(12px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes popIn{0%{transform:scale(.92);opacity:0}60%{transform:scale(1.03)}100%{transform:scale(1);opacity:1}}
@keyframes foToast{from{opacity:0;transform:translate(-50%,8px)}to{opacity:1;transform:translate(-50%,0)}}
@keyframes foAddedPop{from{opacity:0;transform:translateY(8px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes foPayIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
@keyframes aiNudgeIn{from{opacity:0;transform:translateY(16px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes shimmer{0%{background-position:-200% center}100%{background-position:200% center}}

/* ── Base ── */
*{box-sizing:border-box}html,body{margin:0;padding:0}
body{font-family:var(--dp-font);background:var(--dp-bg);color:var(--dp-text);-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale}
button,input,select,textarea{font:inherit}
::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:var(--dp-muted);border-radius:10px}

/* ═══════════════════════════════════
   SPLIT LAYOUT
   ═══════════════════════════════════ */
.fo-pos-wrapper{display:flex;flex-direction:column;height:100vh;overflow:hidden;width:100%}
.fo-pos-left{flex:1;min-width:0;display:flex;flex-direction:column;height:100vh;overflow:hidden}
.fo-pos-right{flex:0 0 380px;max-width:380px;display:flex;flex-direction:column;height:100vh;background:var(--dp-bg-2);border-left:1px solid var(--dp-line)}
.fo-products-scroll{flex:1;overflow-y:auto;padding:24px 28px 120px}

/* ═══════════════════════════════════
   HEADER BAR
   ═══════════════════════════════════ */
.fo-header-bar{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 20px;background:var(--dp-glass);backdrop-filter:blur(20px) saturate(1.5);border-bottom:1px solid var(--dp-line);flex-shrink:0;z-index:40}
.fo-brand{display:flex;align-items:center;gap:12px}
.fo-brand img{width:40px;height:40px;padding:4px;background:var(--dp-surface);border-radius:12px;border:1px solid var(--dp-glass-border)}
.fo-brand h1{margin:0;font-size:18px;font-weight:800;letter-spacing:-.02em;color:var(--dp-text)}
.fo-brand small{display:block;color:var(--dp-muted);font-size:11px;font-weight:600;margin-top:2px}
.fo-header-actions{display:flex;align-items:center;gap:8px}
.fo-audio-toggles{display:flex;gap:6px;align-items:center}
.fo-audio-toggle{border:1px solid var(--dp-glass-border);background:var(--dp-surface);color:var(--dp-text-2);border-radius:999px;padding:6px 10px;font-size:11px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:4px;transition:all var(--dp-transition)}
.fo-audio-toggle.active{background:var(--dp-green-soft);border-color:rgba(52,211,153,.3);color:var(--dp-green)}
.fo-audio-toggle.off{background:var(--dp-surface);border-color:var(--dp-glass-border);color:var(--dp-muted)}
.fo-audio-toggle span{font-size:13px}
.fo-track-link{display:inline-flex;align-items:center;border:1px solid var(--dp-glass-border);background:var(--dp-surface);color:var(--dp-text-2);border-radius:999px;padding:6px 12px;font-size:11px;font-weight:700;text-decoration:none;transition:all var(--dp-transition)}
.fo-track-link:hover{background:var(--dp-surface-hover);color:var(--dp-text);border-color:var(--dp-muted)}
.fo-cart-pill{display:inline-flex;align-items:center;gap:6px;background:var(--dp-red-soft);border:1px solid rgba(255,45,85,.2);border-radius:999px;padding:6px 12px;color:var(--dp-red);font-weight:800;font-size:13px}
.fo-cart-pill span{display:inline-grid;place-items:center;min-width:22px;height:22px;padding:0 5px;border-radius:999px;background:var(--dp-gradient);color:#fff;font-size:12px;font-weight:800}
.fo-cart-icon{font-size:16px;line-height:1}

/* ═══════════════════════════════════
   TOPBAR (Category Title + Info)
   ═══════════════════════════════════ */
.fo-topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px;flex-wrap:wrap}
.fo-topbar h2{font-size:24px;font-weight:800;color:var(--dp-text);margin:0;line-height:1.2;letter-spacing:-.02em}
.fo-topbar .item-count{font-size:13px;color:var(--dp-muted);font-weight:500}

/* ═══════════════════════════════════
   HORIZONTAL CATEGORY TABS
   ═══════════════════════════════════ */
.fo-toolbar{display:flex;gap:10px;overflow-x:auto;overflow-y:hidden;padding:0 0 16px;scrollbar-width:none;-ms-overflow-style:none;flex-shrink:0}
.fo-toolbar::-webkit-scrollbar{display:none}
.fo-tab{list-style:none;min-height:40px;max-height:44px;padding:8px 18px;border:1px solid var(--dp-glass-border);border-radius:999px;background:var(--dp-surface);display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:all var(--dp-transition);white-space:nowrap;flex:0 0 auto;font-size:13px;font-weight:600;color:var(--dp-text-2);animation:fadeIn .3s ease both}
.fo-tab:hover{background:var(--dp-surface-hover);border-color:var(--dp-muted)}
.fo-tab.active{background:var(--dp-red-soft);border-color:var(--dp-red);box-shadow:0 0 16px var(--dp-red-glow);color:var(--dp-red);font-weight:700}

/* ═══════════════════════════════════
   SECTIONS
   ═══════════════════════════════════ */
.fo-section{margin:0 0 28px;animation:fadeInUp .4s ease both}
.fo-section-head{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin-bottom:16px;padding-bottom:12px;border-bottom:1px solid var(--dp-line)}
.fo-section h3{margin:0;font-size:20px;font-weight:800;letter-spacing:-.02em;color:var(--dp-text)}
.fo-section p{margin:4px 0 0;color:var(--dp-muted);font-weight:500;font-size:13px}

/* ═══════════════════════════════════
   PRODUCT CARDS (sim-kasir2 style)
   ═══════════════════════════════════ */
.fo-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;align-items:stretch}
.fo-card{min-height:auto;border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);background:var(--dp-surface);box-shadow:0 2px 16px rgba(0,0,0,.25);padding:0;overflow:hidden;cursor:pointer;transition:all .3s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;animation:cardAppear .4s ease both;position:relative}
.fo-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--dp-gradient);opacity:0;transition:opacity .3s ease}
.fo-card:hover{transform:translateY(-4px) scale(1.01);box-shadow:0 12px 40px rgba(0,0,0,.4),var(--dp-shadow-glow);border-color:rgba(255,45,85,.2)}
.fo-card:hover::before{opacity:1}
.fo-card img.hero{width:100%;height:130px;object-fit:contain;background:var(--dp-bg-2);border-bottom:1px solid var(--dp-line);padding:12px;position:relative;transition:transform .4s cubic-bezier(.4,0,.2,1);border-radius:0}
.fo-card:hover img.hero{transform:scale(1.08) translateY(-2px)}
.fo-card h4{margin:0;padding:0 14px;font-size:14px;font-weight:700;color:var(--dp-text);line-height:1.3;min-height:34px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
.fo-card .meta{padding:10px 14px 0;display:flex;justify-content:space-between;gap:8px;align-items:center;flex-wrap:wrap}
.fo-badge-inline{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;background:var(--dp-green-soft);border-radius:999px;border:1px solid rgba(52,211,153,.2);color:var(--dp-green);font-size:10px;font-weight:700}
.fo-badge-ready{background:var(--dp-green-soft);border-color:rgba(52,211,153,.2);color:var(--dp-green)}

/* Card option groups */
.fo-option-groups{display:grid;gap:8px;padding:8px 14px 0}
.fo-toggle-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:6px}
.fo-toggle-btn{border:1px solid var(--dp-glass-border);background:var(--dp-bg);border-radius:var(--dp-radius-sm);padding:8px;font-weight:700;color:var(--dp-text-2);cursor:pointer;font-size:12px;transition:all var(--dp-transition)}
.fo-toggle-btn.active{background:var(--dp-red-soft);border-color:var(--dp-red);color:var(--dp-red);box-shadow:0 0 12px var(--dp-red-glow)}
.fo-sauce-wrap{display:none}.fo-sauce-wrap.active{display:block}
.fo-sauce-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;max-height:200px;overflow:auto;padding-right:2px}
.fo-sauce-card{display:grid;grid-template-columns:48px 1fr;align-items:center;gap:8px;border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);background:var(--dp-bg);padding:6px;cursor:pointer;text-align:left;transition:all var(--dp-transition)}
.fo-sauce-card .thumb{width:48px;height:48px;border-radius:10px;background:var(--dp-bg-2);padding:6px;display:grid;place-items:center;overflow:hidden}
.fo-sauce-card .thumb img{width:100%;height:100%;object-fit:contain}
.fo-sauce-card b{display:block;font-size:12px;line-height:1.15;color:var(--dp-text)}
.fo-sauce-card small{display:block;color:var(--dp-muted);font-weight:600;margin-top:2px;font-size:10px}
.fo-sauce-card.active{border-color:var(--dp-red);box-shadow:0 0 12px var(--dp-red-glow);background:var(--dp-red-soft)}

/* Price bar & Add button */
.fo-pricebar{display:flex;justify-content:space-between;gap:8px;align-items:center;flex-wrap:wrap;margin-top:auto;padding:10px 14px 14px;border-top:1px solid var(--dp-line)}
.fo-price{display:inline-flex;align-items:center;font-size:15px;font-weight:800;color:var(--dp-text)}
.fo-subprice{color:var(--dp-muted);font-size:11px;font-weight:600}
.fo-add-btn{border:0;border-radius:8px;background:var(--dp-red-soft);color:var(--dp-red);border:1px solid rgba(255,45,85,.2);padding:8px 14px;font-weight:700;font-size:12px;cursor:pointer;transition:all .25s ease;white-space:nowrap}
.fo-add-btn:hover{background:var(--dp-gradient);background-image:var(--dp-gradient);color:#fff;border-color:transparent;box-shadow:0 4px 12px var(--dp-red-glow)}

/* Simple cards (kentang, matcha) */
.fo-simple-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.fo-simple-card{border:1px solid var(--dp-glass-border);background:var(--dp-surface);border-radius:var(--dp-radius);padding:0;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 2px 16px rgba(0,0,0,.25);animation:cardAppear .4s ease both;transition:all .3s ease;position:relative}
.fo-simple-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--dp-gradient);opacity:0;transition:opacity .3s ease}
.fo-simple-card:hover{transform:translateY(-4px);box-shadow:0 12px 40px rgba(0,0,0,.4),var(--dp-shadow-glow);border-color:rgba(255,45,85,.2)}
.fo-simple-card:hover::before{opacity:1}
.fo-simple-card img{width:100%;height:130px;object-fit:contain;background:var(--dp-bg-2);padding:12px;border-bottom:1px solid var(--dp-line)}
.fo-simple-card h4{margin:0;padding:10px 14px 4px;font-size:14px;line-height:1.2;color:var(--dp-text);font-weight:700}
.fo-simple-card .price{display:inline-flex;width:max-content;margin:0 14px;font-size:14px;font-weight:800;color:var(--dp-text)}
.fo-simple-card .fo-add-btn{margin:auto 14px 14px;width:calc(100% - 28px)}

/* Extra cards (1 ekor) */
.fo-extra-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.fo-extra-card{position:relative;border:1px solid var(--dp-glass-border);background:var(--dp-surface);border-radius:var(--dp-radius);padding:14px;box-shadow:0 2px 16px rgba(0,0,0,.25);display:grid;grid-template-columns:140px 1fr;gap:14px;align-items:center;overflow:hidden;transition:all .3s ease}
.fo-extra-card:hover{border-color:rgba(255,45,85,.2);box-shadow:0 12px 40px rgba(0,0,0,.4),var(--dp-shadow-glow)}
.fo-extra-card img{width:140px;height:120px;object-fit:contain;background:var(--dp-bg-2);border-radius:12px;padding:10px;position:relative;z-index:1}
.fo-extra-card .content{position:relative;z-index:1;display:grid;gap:6px}
.fo-extra-card h4{margin:0;font-size:18px;letter-spacing:-.03em;color:var(--dp-text);font-weight:800}
.fo-extra-card p{margin:0;color:var(--dp-muted);font-weight:600;line-height:1.4;font-size:12px}
.fo-extra-card .price{display:inline-flex;width:max-content;font-size:16px;font-weight:800;color:var(--dp-text)}
.fo-extra-badge{display:inline-flex;width:max-content;background:var(--dp-red-soft);border:1px solid rgba(255,45,85,.2);border-radius:999px;padding:4px 10px;color:var(--dp-red);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em}

/* Addon cards */
.fo-addon-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.fo-addon-card{border:1px solid var(--dp-glass-border);background:var(--dp-surface);border-radius:var(--dp-radius);padding:14px;display:grid;gap:8px;box-shadow:0 2px 16px rgba(0,0,0,.25);transition:all .3s ease}
.fo-addon-card:hover{border-color:rgba(255,45,85,.2);box-shadow:0 12px 40px rgba(0,0,0,.4),var(--dp-shadow-glow)}
.fo-addon-card .addon-icon{height:70px;border-radius:12px;background:var(--dp-bg-2);display:grid;place-items:center;font-size:32px}
.fo-addon-card h4{margin:0;font-size:16px;letter-spacing:-.02em;color:var(--dp-text);font-weight:700}
.fo-addon-card p{margin:0;color:var(--dp-muted);font-size:12px;font-weight:600;line-height:1.4}
.fo-addon-card .price{display:inline-flex;width:max-content;font-size:14px;font-weight:800;color:var(--dp-text)}

/* Combo */
.fo-combo-window{border:1px solid var(--dp-glass-border);background:var(--dp-surface);border-radius:var(--dp-radius);padding:16px;margin:0;box-shadow:0 2px 16px rgba(0,0,0,.25)}
.fo-combo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.fo-combo-card{display:grid;grid-template-columns:100px 1fr;gap:12px;align-items:center;border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);background:var(--dp-bg);padding:10px;transition:all .3s ease}
.fo-combo-card:hover{border-color:rgba(255,45,85,.2)}
.fo-combo-card img{width:100px;height:90px;object-fit:contain;background:var(--dp-bg-2);border-radius:10px;padding:8px}
.fo-combo-card h4{margin:0;font-size:16px;letter-spacing:-.02em;color:var(--dp-text);font-weight:700}
.fo-combo-card p{margin:3px 0 6px;color:var(--dp-muted);font-size:12px;font-weight:600;line-height:1.35}
.fo-combo-card .price{display:inline-flex;font-size:14px;font-weight:800;color:var(--dp-red)}

/* ═══════════════════════════════════
   ORDER PANEL (RIGHT SIDEBAR)
   ═══════════════════════════════════ */
.fo-order-header{padding:20px 20px 0}
.fo-order-header-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.fo-order-title{font-size:18px;font-weight:800;color:var(--dp-text);letter-spacing:-.02em}
.fo-order-badge{display:inline-flex;align-items:center;justify-content:center;min-width:24px;height:24px;padding:0 7px;border-radius:999px;background:var(--dp-gradient);color:#fff;font-size:12px;font-weight:800;margin-left:8px}
.fo-order-clear{color:var(--dp-muted);font-size:12px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:4px;cursor:pointer;border:0;background:0;transition:color var(--dp-transition)}
.fo-order-clear:hover{color:var(--dp-red)}
.fo-order-meta{display:flex;justify-content:space-between;align-items:center;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);padding:10px 14px;margin:0 20px 12px}
.fo-order-meta small{font-size:10px;color:var(--dp-muted);text-transform:uppercase;letter-spacing:.05em;font-weight:700}
.fo-order-meta strong{display:block;color:var(--dp-text);font-size:13px;font-weight:700}

/* Cart items in order panel */
.fo-order-items{flex:1;overflow-y:auto;padding:0 20px}
.fo-order-empty{text-align:center;padding:40px 20px;color:var(--dp-muted)}
.fo-order-empty .icon{font-size:40px;margin-bottom:12px;opacity:.4}
.fo-order-empty p{color:var(--dp-text-2);font-size:14px;font-weight:700;margin:0 0 4px}
.fo-order-empty small{color:var(--dp-muted);font-size:12px}
.fo-cart-item{display:flex;justify-content:space-between;align-items:center;padding:10px 12px;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);margin-bottom:8px;transition:all var(--dp-transition);animation:slideInRight .3s ease both}
.fo-cart-item:hover{border-color:var(--dp-muted)}
.fo-cart-item-left{display:flex;align-items:center;gap:10px;min-width:0;flex:1}
.fo-cart-item-text{min-width:0}
.fo-cart-item b{display:block;font-size:13px;font-weight:600;color:var(--dp-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:160px}
.fo-cart-item small{display:block;font-size:12px;color:var(--dp-muted);font-weight:500}
.fo-qty{display:flex;align-items:center;gap:4px;flex-shrink:0}
.fo-qty button{width:28px;height:28px;border:1px solid var(--dp-glass-border);background:var(--dp-bg);color:var(--dp-text);border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all .15s ease;line-height:1}
.fo-qty button:hover{background:var(--dp-red);border-color:var(--dp-red);color:#fff}
.fo-qty span{min-width:28px;text-align:center;font-weight:800;font-size:14px;color:var(--dp-text)}

/* Summary */
.fo-summary-section{border-top:1px solid var(--dp-line);padding:14px 20px 0;margin-top:0}
.fo-summary-row{display:flex;justify-content:space-between;align-items:center;padding:5px 0;font-size:13px;color:var(--dp-text-2);font-weight:500}
.fo-summary-total{border-top:1px solid var(--dp-line);margin-top:6px;padding-top:10px;font-size:16px}
.fo-summary-total span{color:var(--dp-text);font-weight:600}
.fo-summary-total strong{font-size:20px;font-weight:800;background:var(--dp-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}

/* Checkout button */
.fo-checkout-btn-wrap{padding:16px 20px;background:var(--dp-bg-2);border-top:1px solid var(--dp-line);flex-shrink:0}
.fo-checkout-btn{display:flex;align-items:center;justify-content:space-between;width:100%;padding:14px 20px;background:var(--dp-gradient);background-image:var(--dp-gradient);color:#fff;border:0;border-radius:14px;font-family:var(--dp-font);box-shadow:0 4px 24px var(--dp-red-glow);transition:all .3s ease;position:relative;overflow:hidden;cursor:pointer;animation:pulseGlow 3s ease-in-out infinite}
.fo-checkout-btn::before{content:'';position:absolute;top:0;left:-100%;width:100%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.15),transparent);transition:left .5s ease}
.fo-checkout-btn:hover::before{left:100%}
.fo-checkout-btn:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(255,45,85,.4)}
.fo-checkout-btn strong{font-size:15px;font-weight:800}
.fo-checkout-btn small{font-size:12px;opacity:.85;font-weight:500}

/* ═══════════════════════════════════
   FLOATING PAYBOX (Mobile only)
   ═══════════════════════════════════ */
.fo-paybox{position:fixed;left:0;right:0;bottom:0;z-index:45;background:var(--dp-glass);backdrop-filter:blur(20px) saturate(1.5);border-top:1px solid var(--dp-line);box-shadow:0 -18px 40px rgba(0,0,0,.4);display:none}
.fo-pay-inner{max-width:1260px;margin:auto;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
.fo-total small{display:block;color:var(--dp-muted);font-size:12px;font-weight:600}
.fo-total b{font-size:22px;letter-spacing:-.03em;color:var(--dp-text)}
.fo-footer-detail{margin-top:3px;color:var(--dp-muted);font-size:11px;font-weight:600;line-height:1.3}
.fo-checkout{border:0;border-radius:999px;background:var(--dp-gradient);color:#fff;padding:12px 20px;font-weight:800;cursor:pointer;min-width:160px;font-size:14px}

/* ═══════════════════════════════════
   MODALS & DRAWERS (Dark Theme)
   ═══════════════════════════════════ */
.fo-alert{padding:12px 15px;border-radius:var(--dp-radius-sm);margin:14px 0;font-weight:700}.fo-alert.err{background:var(--dp-red-soft);color:var(--dp-red)}

/* Checkout Drawer */
.fo-drawer{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:60;display:none;padding:18px;overflow:auto}.fo-drawer.show{display:block}
.fo-panel{max-width:760px;margin:0 auto;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:20px;box-shadow:var(--dp-shadow)}
.fo-panel h2{margin:0 0 12px;font-size:28px;letter-spacing:-.04em;color:var(--dp-text)}
.fo-cart-list{display:grid;gap:8px;margin-bottom:12px}
.fo-checkout-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fo-field{display:grid;gap:5px}
.fo-field label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:var(--dp-muted)}
.fo-field input,.fo-field select,.fo-field textarea{border:1px solid var(--dp-glass-border);background:var(--dp-bg);border-radius:var(--dp-radius-sm);padding:10px 12px;font-weight:700;min-height:44px;color:var(--dp-text);transition:border-color var(--dp-transition)}
.fo-field input:focus,.fo-field select:focus,.fo-field textarea:focus{border-color:var(--dp-red);box-shadow:0 0 0 2px var(--dp-red-soft);outline:none}
.fo-field input::placeholder,.fo-field textarea::placeholder{color:var(--dp-muted)}
.fo-time-hint{font-size:11px;color:var(--dp-muted);font-weight:600}

/* Payment */
.fo-payment{display:grid;grid-template-columns:1fr;gap:10px;margin:12px 0}
.payBtn{border:1px solid var(--dp-glass-border);background:var(--dp-surface);border-radius:var(--dp-radius-sm);padding:12px 10px;font-weight:800;color:var(--dp-text-2);cursor:pointer;transition:all var(--dp-transition)}
.payBtn.active{background:var(--dp-red-soft);border-color:var(--dp-red);color:var(--dp-red);box-shadow:0 0 16px var(--dp-red-glow)}
.fo-pay-preview{display:none;border:1px solid var(--dp-glass-border);background:var(--dp-bg);border-radius:var(--dp-radius);padding:14px;animation:foPayIn .2s ease both;color:var(--dp-text)}
.fo-pay-preview.active{display:block}
.fo-pay-preview b{color:var(--dp-text)}
.fo-pay-preview img{max-width:100%;width:260px;border-radius:var(--dp-radius-sm);border:1px solid var(--dp-glass-border);background:var(--dp-bg-2);padding:8px}
.fo-download-btn,.fo-copy-btn{display:inline-flex;align-items:center;justify-content:center;border:0;background:var(--dp-gradient);color:#fff;padding:10px 16px;border-radius:999px;font-weight:800;text-decoration:none;cursor:pointer;font-size:13px}
.fo-copy-row{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center}
.fo-info{font-size:13px;color:var(--dp-muted);line-height:1.55;font-weight:600}
.fo-submit{width:100%;border:0;border-radius:14px;padding:14px;background:var(--dp-gradient);color:#fff;font-weight:800;font-size:17px;margin-top:14px;cursor:pointer;box-shadow:0 4px 24px var(--dp-red-glow);transition:all .3s ease}
.fo-submit:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(255,45,85,.4)}
.fo-close{margin-top:12px;display:inline-flex;justify-content:center;align-items:center;background:var(--dp-surface);color:var(--dp-text-2);border-radius:var(--dp-radius-sm);padding:12px 16px;font-weight:700;border:1px solid var(--dp-glass-border);cursor:pointer;text-decoration:none;width:100%;transition:all var(--dp-transition)}
.fo-close:hover{background:var(--dp-surface-hover);color:var(--dp-text)}

/* Payment total box */
.fo-payment-total-box{margin:12px 0;border:1px solid var(--dp-glass-border);background:var(--dp-gradient-subtle);border-radius:var(--dp-radius);padding:14px;display:grid;gap:8px}
.fo-payment-total-box small{display:block;color:var(--dp-muted);font-weight:700;text-transform:uppercase;letter-spacing:.06em;font-size:10px}
.fo-payment-total-box b{font-size:30px;letter-spacing:-.04em;background:var(--dp-gradient);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.fo-payment-total-box .items{color:var(--dp-text-2);font-size:13px;font-weight:600;line-height:1.4}

/* Success Modal */
.fo-modal{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:70;display:none;place-items:center;padding:18px}.fo-modal.show{display:grid}
.fo-modal-card{width:min(94vw,560px);background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:24px;box-shadow:var(--dp-shadow);text-align:center;color:var(--dp-text)}
.fo-modal-card h2{margin:0;font-size:30px;letter-spacing:-.04em;color:var(--dp-text)}
.fo-order-no{margin:10px auto 14px;display:inline-flex;background:var(--dp-gradient);color:#fff;border-radius:999px;padding:10px 16px;font-weight:800}
.fo-qris-img{width:min(82vw,280px);border-radius:var(--dp-radius-sm);border:1px solid var(--dp-glass-border);padding:8px;background:var(--dp-bg)}
.fo-bank{margin:14px 0;padding:14px;background:var(--dp-bg);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);font-weight:700;color:var(--dp-text)}
.fo-success-actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:center;margin:14px 0}
.fo-success-actions .dark,.fo-success-actions .gold{display:inline-flex;align-items:center;justify-content:center;border:0;border-radius:999px;padding:12px 16px;font-weight:800;text-decoration:none;cursor:pointer}
.fo-success-actions .dark{background:var(--dp-surface);color:var(--dp-text);border:1px solid var(--dp-glass-border)}
.fo-success-actions .gold{background:var(--dp-gradient);color:#fff}

/* Added modal */
.fo-added-modal{position:fixed;inset:0;z-index:68;display:none;place-items:center;background:rgba(0,0,0,.6);padding:18px}.fo-added-modal.show{display:grid}
.fo-added-card{width:min(92vw,430px);background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:24px;text-align:center;box-shadow:var(--dp-shadow);animation:foAddedPop .2s ease both;color:var(--dp-text)}
.fo-added-icon{width:62px;height:62px;border-radius:50%;background:var(--dp-green-soft);color:var(--dp-green);display:grid;place-items:center;margin:0 auto 10px;font-size:30px;font-weight:950}
.fo-added-card h2{margin:0 0 8px;font-size:26px;letter-spacing:-.04em;color:var(--dp-text)}
.fo-added-card p{margin:0 0 16px;color:var(--dp-muted);font-weight:600;line-height:1.4}
.fo-added-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fo-added-secondary,.fo-added-primary{border:0;border-radius:var(--dp-radius-sm);padding:13px 12px;font-weight:800;cursor:pointer}
.fo-added-secondary{background:var(--dp-surface-hover);color:var(--dp-text)}
.fo-added-primary{background:var(--dp-gradient);color:#fff}

/* Pickup confirm */
.fo-pickup-confirm{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:59;display:none;padding:18px;overflow:auto}
.fo-pickup-confirm.show{display:grid;place-items:center}
.fo-pickup-confirm-card{width:min(94vw,560px);background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:20px;box-shadow:var(--dp-shadow);color:var(--dp-text)}
.fo-pickup-confirm-card h2{margin:0 0 8px;font-size:26px;letter-spacing:-.04em;color:var(--dp-text)}
.fo-pickup-confirm-card p{color:var(--dp-muted);font-weight:600;line-height:1.5;font-size:13px}
.fo-pickup-mode{display:grid;grid-template-columns:1fr 1fr;gap:10px}
.fo-pickup-option{border:1px solid var(--dp-glass-border);background:var(--dp-bg);border-radius:var(--dp-radius-sm);padding:12px;text-align:left;cursor:pointer;min-height:70px;transition:all var(--dp-transition)}
.fo-pickup-option b{display:block;font-weight:800;color:var(--dp-text)}
.fo-pickup-option span{display:block;margin-top:4px;color:var(--dp-muted);font-size:11px;font-weight:600;line-height:1.3}
.fo-pickup-option.active{background:var(--dp-red-soft);border-color:var(--dp-red);box-shadow:0 0 16px var(--dp-red-glow)}
.fo-pickup-option.disabled{opacity:.4;cursor:not-allowed;background:var(--dp-bg)}
.fo-pickup-summary{display:grid;gap:10px;background:var(--dp-bg);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);padding:14px;margin:12px 0}
.fo-pickup-summary div{display:flex;justify-content:space-between;gap:12px;font-weight:700;color:var(--dp-text-2);font-size:13px}
.fo-pickup-summary b{color:var(--dp-red)}
.fo-pickup-actions{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:12px}
.fo-pickup-actions button{border:0;border-radius:var(--dp-radius-sm);padding:13px 14px;font-weight:800;cursor:pointer}
.fo-pickup-actions .gold{background:var(--dp-gradient);color:#fff}
.fo-pickup-actions .light{background:var(--dp-surface-hover);color:var(--dp-text)}

/* Customer card in pickup */
.fo-customer-card{display:grid;gap:10px;background:var(--dp-bg);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);padding:14px}
.fo-customer-card h3{margin:0;font-size:18px;letter-spacing:-.03em;color:var(--dp-text)}
.fo-customer-found{display:none;background:var(--dp-green-soft);border:1px solid rgba(52,211,153,.2);color:var(--dp-green);border-radius:var(--dp-radius-sm);padding:9px 11px;font-size:12px;font-weight:700;line-height:1.4}
.fo-customer-found.show{display:block}

/* Checkout Drawer */
.fo-drawer{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:60;display:none;padding:18px;overflow:auto}.fo-drawer.show{display:block}
.fo-panel{max-width:760px;margin:0 auto;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:20px;box-shadow:var(--dp-shadow)}
.fo-panel h2{margin:0 0 12px;font-size:28px;letter-spacing:-.04em;color:var(--dp-text)}
.fo-cart-list{display:grid;gap:8px;margin-bottom:12px}
.fo-checkout-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.fo-field{display:grid;gap:5px}
.fo-field label{font-size:10px;text-transform:uppercase;letter-spacing:.06em;font-weight:700;color:var(--dp-muted)}
.fo-field input,.fo-field select,.fo-field textarea{border:1px solid var(--dp-glass-border);background:var(--dp-bg);border-radius:var(--dp-radius-sm);padding:10px 12px;font-weight:700;min-height:44px;color:var(--dp-text);transition:border-color var(--dp-transition)}
.fo-field input:focus,.fo-field select:focus,.fo-field textarea:focus{border-color:var(--dp-red);box-shadow:0 0 0 2px var(--dp-red-soft);outline:none}
.fo-field input::placeholder,.fo-field textarea::placeholder{color:var(--dp-muted)}
.fo-time-hint{font-size:11px;color:var(--dp-muted);font-weight:600}

/* Payment */
.fo-payment{display:grid;grid-template-columns:1fr;gap:10px;margin:12px 0}
.payBtn{border:1px solid var(--dp-glass-border);background:var(--dp-surface);border-radius:var(--dp-radius-sm);padding:12px 10px;font-weight:800;color:var(--dp-text-2);cursor:pointer;transition:all var(--dp-transition)}
.payBtn.active{background:var(--dp-red-soft);border-color:var(--dp-red);color:var(--dp-red);box-shadow:0 0 16px var(--dp-red-glow)}
.fo-pay-preview{display:none;border:1px solid var(--dp-glass-border);background:var(--dp-bg);border-radius:var(--dp-radius);padding:14px;animation:foPayIn .2s ease both;color:var(--dp-text)}
.fo-pay-preview.active{display:block}
.fo-pay-preview b{color:var(--dp-text)}
.fo-pay-preview img{max-width:100%;width:260px;border-radius:var(--dp-radius-sm);border:1px solid var(--dp-glass-border);background:var(--dp-bg-2);padding:8px}
.fo-download-btn,.fo-copy-btn{display:inline-flex;align-items:center;justify-content:center;border:0;background:var(--dp-gradient);color:#fff;padding:10px 16px;border-radius:999px;font-weight:800;text-decoration:none;cursor:pointer;font-size:13px}
.fo-copy-row{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center}
.fo-info{font-size:13px;color:var(--dp-muted);line-height:1.55;font-weight:600}
.fo-submit{width:100%;border:0;border-radius:14px;padding:14px;background:var(--dp-gradient);color:#fff;font-weight:800;font-size:17px;margin-top:14px;cursor:pointer;box-shadow:0 4px 24px var(--dp-red-glow);transition:all .3s ease}
.fo-submit:hover{transform:translateY(-2px);box-shadow:0 8px 32px rgba(255,45,85,.4)}
.fo-close{margin-top:12px;display:inline-flex;justify-content:center;align-items:center;background:var(--dp-surface);color:var(--dp-text-2);border-radius:var(--dp-radius-sm);padding:12px 16px;font-weight:700;border:1px solid var(--dp-glass-border);cursor:pointer;text-decoration:none;width:100%;transition:all var(--dp-transition)}
.fo-close:hover{background:var(--dp-surface-hover);color:var(--dp-text)}

/* ═══════════════════════════════════
   VIDEO OVERLAY (kept, dark compatible)
   ═══════════════════════════════════ */
.fo-video-overlay{position:fixed;inset:0;background:#050505;z-index:9999;display:none;align-items:center;justify-content:center;overflow:hidden}.fo-video-overlay.show{display:flex}
.fo-video-overlay video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;background:#000}
.fo-video-overlay:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,.28),rgba(0,0,0,.16) 44%,rgba(0,0,0,.78)),radial-gradient(circle at center,rgba(255,199,44,.10),rgba(0,0,0,.32));pointer-events:none}
.fo-video-content{position:relative;z-index:2;width:min(92vw,680px);min-height:74vh;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;text-align:center;color:#fff;padding:22px 18px 58px}
.fo-video-badge{width:76px;height:76px;border-radius:24px;background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.24);display:grid;place-items:center;backdrop-filter:blur(10px);margin-bottom:12px}
.fo-video-badge img{width:64px;height:64px;object-fit:contain}
.fo-video-title{margin:0 0 10px;font-size:clamp(32px,8vw,64px);line-height:.95;font-weight:950;text-shadow:0 10px 30px rgba(0,0,0,.55);letter-spacing:-.05em}
.fo-video-subtitle{margin:0 auto 18px;max-width:560px;color:rgba(255,255,255,.9);font-weight:750;line-height:1.45}
.fo-start-btn{border:0;border-radius:999px;background:var(--dp-gradient);color:#fff;font-size:20px;font-weight:800;min-width:240px;padding:16px 26px;box-shadow:0 20px 44px rgba(255,45,85,.35);cursor:pointer}
.fo-video-phone-box{width:min(92vw,430px);display:grid;gap:9px;margin:2px auto 14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(12px);border-radius:var(--dp-radius);padding:12px}
.fo-video-phone-box label{font-size:11px;text-transform:uppercase;letter-spacing:.07em;font-weight:800;color:rgba(255,255,255,.76);text-align:left}
.fo-video-phone-row{display:grid;grid-template-columns:1fr auto;gap:8px}.fo-video-phone-row.single{grid-template-columns:1fr}
.fo-video-phone-row input{height:48px;border:0;border-radius:var(--dp-radius-sm);padding:0 14px;font-weight:800;color:var(--dp-text);background:var(--dp-surface)}
.fo-video-phone-row button{border:0;border-radius:var(--dp-radius-sm);background:var(--dp-gradient);color:#fff;font-weight:800;padding:0 14px;cursor:pointer}
.fo-video-phone-info{min-height:18px;color:rgba(255,255,255,.88);font-size:12px;font-weight:700;text-align:left;line-height:1.4}
.fo-video-phone-info.ok{color:#bbf7d0}.fo-video-phone-info.warn{color:#fde68a}.fo-video-phone-info.err{color:#fecaca}
.fo-video-phone-row button.checked{background:var(--dp-green);color:#fff}

/* ═══════════════════════════════════
   FLOATING ACTIONS & AI
   ═══════════════════════════════════ */
.fo-floating-actions{position:fixed;left:14px;bottom:90px;z-index:54;display:grid;gap:10px;justify-items:start}
.fo-float-btn{border:0;border-radius:999px;padding:10px 14px;box-shadow:0 8px 24px rgba(0,0,0,.4);font-weight:800;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:12px}
.fo-float-btn.ai{background:var(--dp-gradient);color:#fff}
.fo-float-btn.wa{background:var(--dp-green);color:#fff}
.fo-ai-panel{position:fixed;left:14px;bottom:180px;width:min(92vw,390px);z-index:56;display:none;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:16px;box-shadow:var(--dp-shadow);color:var(--dp-text)}
.fo-ai-panel.show{display:block;animation:foPayIn .2s ease both}
.fo-ai-panel h3{margin:0 0 8px;font-size:20px;letter-spacing:-.03em;color:var(--dp-text)}
.fo-ai-panel p{margin:0 0 10px;color:var(--dp-muted);font-weight:600;line-height:1.4;font-size:13px}
.fo-ai-reco-menu{background:var(--dp-bg);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius-sm);padding:12px;margin:10px 0}
.fo-ai-reco-menu b{display:block;color:var(--dp-red);font-size:16px}
.fo-ai-reco-menu span{color:var(--dp-text-2);font-size:12px}
.fo-ai-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:10px}
.fo-ai-actions button{border:0;border-radius:var(--dp-radius-sm);padding:10px;font-weight:800;cursor:pointer;font-size:13px}
.fo-ai-actions .dark{background:var(--dp-surface-hover);color:var(--dp-text)}
.fo-ai-actions .gold{background:var(--dp-gradient);color:#fff}

.fo-ai-nudge{position:fixed;left:16px;bottom:148px;z-index:55;width:min(88vw,330px);display:none;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:14px;box-shadow:var(--dp-shadow);animation:aiNudgeIn .35s ease both;color:var(--dp-text)}
.fo-ai-nudge.show{display:block}
.fo-ai-nudge:after{content:"";position:absolute;left:32px;bottom:-10px;width:20px;height:20px;background:var(--dp-surface);border-right:1px solid var(--dp-glass-border);border-bottom:1px solid var(--dp-glass-border);transform:rotate(45deg)}
.fo-ai-nudge b{display:block;color:var(--dp-red);font-size:15px;margin-bottom:4px}
.fo-ai-nudge p{margin:0;color:var(--dp-text-2);font-size:12px;font-weight:600;line-height:1.4}
.fo-ai-nudge-actions{display:flex;gap:8px;margin-top:10px}
.fo-ai-nudge-actions button{border:0;border-radius:999px;padding:8px 12px;font-size:11px;font-weight:800;cursor:pointer}
.fo-ai-nudge-actions .listen{background:var(--dp-gradient);color:#fff}
.fo-ai-nudge-actions .close{background:var(--dp-surface-hover);color:var(--dp-text-2)}

/* Toast */
.fo-toast-copy{position:fixed;left:50%;bottom:92px;transform:translateX(-50%);z-index:99999;background:var(--dp-surface);color:var(--dp-text);border:1px solid var(--dp-glass-border);border-radius:999px;padding:10px 14px;font-weight:800;box-shadow:var(--dp-shadow);display:none}
.fo-toast-copy.show{display:block;animation:foToast .18s ease}

/* Spacer */
.fo-matcha-spacer{height:38px}
.fo-audio-note{position:absolute;left:14px;right:14px;bottom:14px;color:rgba(255,255,255,.64);font-size:11px}

/* ═══════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════ */
@media(max-width:1399px){.fo-grid{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:1199px){.fo-pos-right{flex:0 0 340px;max-width:340px}}

@media(max-width:991px){
  .fo-pos-wrapper{flex-direction:column;height:auto;overflow:auto}
  .fo-pos-left{height:auto;overflow:visible}
  .fo-pos-right{display:none;position:fixed;inset:0;z-index:58;max-width:100%;flex:0 0 100%;background:rgba(0,0,0,.6);height:100vh}
  .fo-pos-right.show{display:flex;flex-direction:column}
  .fo-pos-right .fo-right-inner{margin-top:auto;max-height:85vh;background:var(--dp-bg-2);border-radius:var(--dp-radius) var(--dp-radius) 0 0;overflow-y:auto;display:flex;flex-direction:column;animation:foPayIn .25s ease both}
  .fo-paybox{display:flex}
  .fo-products-scroll{padding-bottom:160px}
  .fo-floating-actions{bottom:100px}
  .fo-ai-nudge{bottom:168px}
}

@media(max-width:720px){
  .fo-header-bar{padding:10px 12px;flex-wrap:wrap;gap:8px}
  .fo-brand h1{font-size:15px}
  .fo-audio-toggles{width:100%;justify-content:flex-start}
  .fo-audio-toggle{padding:5px 8px;font-size:10px}
  .fo-products-scroll{padding:16px 12px 180px}
  .fo-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  .fo-simple-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}
  .fo-addon-grid{grid-template-columns:1fr}
  .fo-combo-grid,.fo-combo-card{grid-template-columns:1fr}
  .fo-combo-card img{width:100%;height:100px}
  .fo-extra-grid,.fo-extra-card{grid-template-columns:1fr}
  .fo-extra-card img{width:100%;height:120px}
  .fo-card img.hero,.fo-simple-card img{height:100px}
  .fo-card h4{font-size:13px;min-height:28px;padding:6px 10px}
  .fo-pricebar{padding:8px 10px 10px}
  .fo-toggle-grid,.fo-sauce-grid{grid-template-columns:1fr}
  .fo-option-groups{padding:6px 10px 0}
  .fo-checkout-grid{grid-template-columns:1fr}
  .fo-payment{grid-template-columns:1fr}
  .fo-pickup-mode,.fo-pickup-actions,.fo-added-actions{grid-template-columns:1fr}
  .fo-panel{padding:16px}.fo-panel h2{font-size:24px}
  .fo-pay-inner{flex-direction:column;align-items:stretch}
  .fo-checkout{width:100%}
  .fo-copy-row{grid-template-columns:1fr}
  .fo-video-content{padding-bottom:72px}
  .fo-start-btn{width:min(88vw,340px);min-width:0}
  .fo-header-actions{width:100%;justify-content:space-between}
  .fo-ai-panel{left:10px;right:10px;bottom:168px;width:auto}
  .fo-ai-nudge{right:10px;left:10px;bottom:168px;width:auto}
  .fo-float-btn{font-size:11px;padding:9px 11px}
}

@media(max-width:720px){.fo-video-phone-row{grid-template-columns:1fr}.fo-video-phone-row button{height:44px}}
/* Force high-contrast options and form labels/inputs specifically for Online Order */
#customerType option, select.form-select option {
  background-color: var(--dp-surface-2) !important;
  color: var(--dp-text) !important;
}
.sim-summary-section .form-label, .fo-pos-right .form-label, .sim-pos-sidebar .form-label, .form-group .form-label {
  color: var(--dp-text-2) !important;
  font-weight: 600 !important;
}
.sim-summary-section .form-control, .fo-pos-right .form-control, .sim-pos-sidebar .form-control, .sim-notes-input, .form-group .form-control {
  background: var(--dp-surface) !important;
  border: 1px solid var(--dp-glass-border) !important;
  color: var(--dp-text) !important;
  border-radius: 8px !important;
}
.sim-summary-section .form-control:focus, .fo-pos-right .form-control:focus, .sim-pos-sidebar .form-control:focus, .sim-notes-input:focus, .form-group .form-control:focus {
  border-color: var(--dp-red) !important;
  box-shadow: 0 0 0 2px var(--dp-red-soft) !important;
  color: var(--dp-text) !important;
}
.sim-summary-section .form-control::placeholder, .fo-pos-right .form-control::placeholder, .sim-pos-sidebar .form-control::placeholder, .sim-notes-input::placeholder, .form-group .form-control::placeholder {
  color: var(--dp-muted) !important;
}
</style>
  <link rel="stylesheet" href="../public/assets/pos-template/bootstrap.min.css">
  <link rel="stylesheet" href="../public/assets/pos-template/style.css">
  <link rel="stylesheet" href="../public/assets/css/pos-preadmin-overrides.css?v=<?= time() ?>">
  <link rel="stylesheet" href="../public/assets/css/pos-kasir2-theme.css?v=<?= time() ?>">
  <style>
  /* Extra specificity override after external stylesheets */
  .sim-order-type-toggle .form-select option, #customerType option {
    background-color: #22223a !important;
    color: #f1f1f5 !important;
  }
  .sim-summary-section .form-label, .form-group .form-label {
    color: #c4c4d4 !important;
    font-weight: 600 !important;
  }
  .sim-summary-section .form-control, .form-group .form-control {
    background-color: #1a1a2e !important;
    color: #f1f1f5 !important;
    border: 1px solid rgba(255,255,255,0.15) !important;
  }
  </style>
<script>
function simInitTheme(){
    let tm = localStorage.getItem('sim_theme') || 'light';
    document.documentElement.setAttribute('data-theme', tm);
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.theme-icon-light').forEach(el=>el.style.display=(tm==='dark'?'none':'inline-block'));
        document.querySelectorAll('.theme-icon-dark').forEach(el=>el.style.display=(tm==='dark'?'inline-block':'none'));
    });
}
function simToggleTheme(){
    let tm = document.documentElement.getAttribute('data-theme')==='dark'?'light':'dark';
    localStorage.setItem('sim_theme', tm);
    document.documentElement.setAttribute('data-theme', tm);
    document.querySelectorAll('.theme-icon-light').forEach(el=>el.style.display=(tm==='dark'?'none':'inline-block'));
    document.querySelectorAll('.theme-icon-dark').forEach(el=>el.style.display=(tm==='dark'?'inline-block':'none'));
}
simInitTheme();
</script>
</head>
<body class="pos-page sim-pos-template sim-pos-lumero k2-body">
<?php if (!empty($outletOpStatus) && !$outletOpStatus['is_open']): ?>
<div id="storeClosedOverlay" style="position:fixed; inset:0; z-index:99999; background:rgba(9,9,11,0.92); backdrop-filter:blur(18px); display:flex; align-items:center; justify-content:center; padding:20px;">
  <div style="background:rgba(24,24,34,0.98); border:2px solid rgba(239,68,68,0.45); border-radius:28px; padding:36px 28px; max-width:480px; width:100%; text-align:center; box-shadow:0 24px 80px rgba(0,0,0,0.85), 0 0 60px rgba(239,68,68,0.22); animation:popIn .35s cubic-bezier(.4,0,.2,1) both;">
    <div style="font-size:54px; margin-bottom:14px;">🚫</div>
    <h2 style="font-size:24px; font-weight:950; color:#FFFFFF; margin:0 0 10px; letter-spacing:-.02em;">Cabang Ini Sedang Tutup</h2>
    <p style="font-size:15px; color:#F3F4F6; line-height:1.6; font-weight:600; margin:0 0 14px;">
      <?= fo_e($activeOutletRow['name'] ?? 'Cabang Lumero') ?> saat ini sedang tidak beroperasi. <br>
      <span style="color:#FCA5A5; font-size:14px; font-weight:700;">Jam Operasional: <?= fo_e($outletOpStatus['opening_time'] ?? '10:00') ?> - <?= fo_e($outletOpStatus['closing_time'] ?? '21:00') ?> WIB</span>
    </p>
    <div style="background:rgba(239,68,68,0.14); border:1px dashed rgba(239,68,68,0.4); border-radius:14px; padding:12px 16px; margin-bottom:24px; font-size:13.5px; color:#FECACA; font-weight:650;">
      <?= fo_e($outletOpStatus['reason'] ?? 'Di luar jam operasional.') ?> <br>Pemesanan online untuk cabang ini ditutup sementara demi keamanan pesanan Anda.
    </div>
    <div style="display:flex; flex-direction:column; gap:12px;">
      <a href="select-branch.php" style="background:linear-gradient(135deg, #FF2D55 0%, #FF6B00 100%); color:#FFFFFF; font-size:15px; font-weight:900; padding:14px 24px; border-radius:999px; text-decoration:none; box-shadow:0 10px 30px rgba(255,45,85,0.4); display:block; transition:transform .2s ease;">📍 Pilih Cabang Lain yang Buka</a>
      <a href="welcome.php" style="color:#E5E7EB; font-size:14px; font-weight:700; text-decoration:underline; padding:8px 0; display:block;">&larr; Kembali ke Layar Sambutan</a>
    </div>
  </div>
</div>
<?php endif; ?>

<audio id="foBgm" src="<?=fo_e($freeOrderVoiceBase)?>slow-cafe.mp3?v=2" preload="auto" loop></audio>
<audio class="fo-voice" id="foVoiceWelcome" src="<?=fo_e($freeOrderVoiceBase)?>welcome.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceTotal" src="<?=fo_e($freeOrderVoiceBase)?>total.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceQris" src="<?=fo_e($freeOrderVoiceBase)?>qris.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceTransfer" src="<?=fo_e($freeOrderVoiceBase)?>norek.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceCash" src="<?=fo_e($freeOrderVoiceBase)?>bayarcashier.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceMaaf" src="<?=fo_e($freeOrderVoiceBase)?>maaf.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceBerhasil" src="<?=fo_e($freeOrderVoiceBase)?>berhasil.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceOpsiBayar" src="<?=fo_e($freeOrderVoiceBase)?>opsibayar.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoicePayout" src="<?=fo_e($freeOrderVoiceBase)?>payout.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceEmptyCart" src="<?=fo_e($freeOrderVoiceBase)?>keranjangkosong.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceWaktuAmbil" src="<?=fo_e($freeOrderVoiceBase)?>waktuambil.mp3?v=2" preload="auto"></audio>
<audio class="fo-voice" id="foVoiceSuccess" src="<?=fo_e($freeOrderVoiceBase)?>selamtqris.mp3?v=2" preload="auto"></audio>
<div class="fo-toast-copy" id="copyToast">Nomor berhasil disalin</div>

<div class="fo-pos-wrapper">
  <header class="fo-header-bar w-100">
    <div class="fo-brand">
      <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero">
      <div>
        <h1>Lumero SELF-ORDER</h1>
        <small>Pesan Cepat • Tanpa Antre • Ambil di Outlet</small>
      </div>
    </div>
    <div class="fo-header-actions" style="flex-wrap: wrap;">
      <div class="fo-audio-toggles">
        <a href="dashboard.php" class="fo-audio-toggle" style="text-decoration:none; font-weight:800; border-color:var(--dp-glass-border);">&larr; Dashboard</a>
        <button class="fo-audio-toggle" id="toggleTheme" type="button" aria-pressed="false" onclick="simToggleTheme()">
          <span class="theme-icon-light" style="display:none;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg></span>
          <span class="theme-icon-dark"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg></span>
        </button>
        <button class="fo-audio-toggle on" id="toggleBgm" type="button" aria-pressed="true">
          <span>♪</span> Musik ON
        </button>
        <button class="fo-audio-toggle on" id="toggleVoice" type="button" aria-pressed="true">
          <span><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon><path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path></svg></span> Suara ON
        </button>
      </div>
      <a href="../order-online/lacak.php" class="fo-track-link">
        <span>📍</span> Lacak Pesanan
      </a>
      <button type="button" class="fo-cart-pill" onclick="document.querySelector('.fo-pos-right').classList.add('show')">
        <i class="ti ti-shopping-cart fo-cart-icon"></i> Keranjang Pesanan <span id="itemCount3">0</span>
      </button>
    </div>
  </header>

  <!-- LEFT PANEL: Catalog -->
  <div class="row pos-wrapper g-0 w-100 flex-fill ">
            <!-- ===== LEFT: PRODUCTS AREA ===== -->
            <div class="col-md-12 col-lg-7 col-xl-8 d-flex sim-pos-main-col">
                <div class="pos-categories tabs_wrapper p-0 flex-fill sim-pos-workspace">
                    <div class="content-wrap sim-pos-content-wrap">
                        <!-- ===== MAIN CONTENT (no sidebar) ===== -->
                        <main class="tab-content-wrap sim-pos-products-panel">
                            <!-- Top bar: Category Title + Search -->
                            <div class="sim-pos-topbar">
                                <div class="sim-pos-topbar-left">
                                    <h2 id="activeCategoryLabel">Semua Menu</h2>
                                    <small id="visibleProductInfo" class="sim-item-count"><?= (int)$totalVariants ?> item tersedia</small>
                                </div>
                                <div class="sim-pos-topbar-right">
                                    <button class="btn btn-light btn-sm sim-sort-btn" type="button" id="flowBack" style="display:none"><?= sim_icon('ti-arrow-left', 'me-1') ?>Kembali</button>
                                    <button class="btn btn-light btn-sm sim-sort-btn" type="button" id="resetFlow"><?= sim_icon('ti-refresh-dot', 'me-1') ?>Reset</button>
                                    <div class="input-icon-start search-pos position-relative">
                                        <span class="input-icon-addon"><?= sim_icon('ti-search') ?></span>
                                        <input type="text" class="form-control" id="posSearch" placeholder="Cari produk... (⌘K)">
                                    </div>
                                </div>
                            </div>

                            <!-- Horizontal Category Tabs -->
                            <div class="sim-horizontal-cats">
                                <ul class="sim-pos-tabs" id="categoryList" role="tablist">
                                    <?php foreach ($preparedCategories as $idx => $cat): ?>
                                    <li id="cat-<?= (int)$cat['id'] ?>" class="<?= $idx===0 ? 'active' : '' ?>" data-cat="<?= (int)$cat['id'] ?>">
                                        <a href="javascript:void(0);"><img src="<?= htmlspecialchars($cat['image']) ?>" alt="<?= htmlspecialchars($cat['name']) ?>"></a>
                                        <h6><a href="javascript:void(0);"><?= htmlspecialchars($cat['name']) ?></a></h6>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <!-- Flow bar for chicken steps -->
                            <div id="flowBar" class="sim-flow-bar mb-3"></div>
                            <?php if($err !== ''): ?>
                            <div class="alert alert-danger fw-bold mb-3" style="border-radius:12px; padding:12px 16px; background:#ffe5e5; border:1px solid #ffb8b8; color:#d60000;">
                                <i class="ti ti-alert-circle me-1"></i> Gagal membuat pesanan: <?= fo_e($err) ?>
                            </div>
                            <?php endif; ?>
                            <div id="posMessage" class="sim-pos-message mb-3" style="display:none"></div>

                            <!-- Product Grid -->
                            <div class="pos-products sim-products-area">
                                <?php if (!$preparedCategories): ?>
                                    <div class="card border-0 shadow-sm"><div class="card-body text-center py-5">Belum ada produk aktif. Silakan cek menu <strong>Produk & Menu</strong>.</div></div>
                                <?php endif; ?>
                                <div id="productGrid" class="sim-kasir2-grid"></div>
                            </div>
                        </main>
                    </div>
                </div>
            </div>

            <!-- ===== RIGHT: ORDER PANEL ===== -->
            <div class="col-md-12 col-lg-5 col-xl-4 ps-0 d-lg-flex sim-pos-order-col">
                <aside class="product-order-list bg-secondary-transparent flex-fill">
                    <div class="card sim-order-card">
                        <div class="card-body">
                            <!-- Order Header -->
                            <div class="sim-order-header">
                                <div class="sim-order-header-top">
                                    <div>
                                        <span class="sim-order-title">Pesanan</span>
                                        <span class="sim-order-badge" id="itemCount">0</span>
                                    </div>
                                    <a class="sim-order-clear" href="javascript:void(0);" id="clearCart"><?= sim_icon('ti-trash') ?> Hapus</a>
                                </div>
                                <div class="sim-order-meta">
                                    <div>
                                        <small>Order</small>
                                        <strong id="draftOrderNo"><?= $orderNo ?></strong>
                                    </div>
                                    <div class="sim-order-type-toggle" style="display:none !important;">
                                        <select class="form-select form-select-sm" id="customerType" onchange="selectPickupOption(this.value)">
                                            <option value="outlet">🏪 Ambil di Outlet</option>
                                            <option value="delivery" <?= $deliveryEnabled ? '' : 'disabled' ?>>🛵 Delivery (Diantar Kurir) <?= $deliveryEnabled ? '' : '(Belum Aktif)' ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Cart Items -->
                            <div class="product-added block-section">
                                <div class="product-wrap">
                                    <div class="empty-cart" id="emptyCart">
                                        <div class="sim-empty-cart-icon"><?= sim_icon('ti-shopping-cart') ?></div>
                                        <p class="fw-bold mb-1">Keranjang kosong</p>
                                        <small>Pilih produk untuk menambahkan</small>
                                    </div>
                                    <div class="sim-cart-list" id="cartTableWrap" style="display:none">
                                          <div id="cartRows"></div>
                                      </div>
                                </div>
                            </div>

                            <!-- Payment Summary -->
                            <form action="" method="post" id="checkoutForm" onsubmit="event.preventDefault(); openCheckout(false); return false;">
                                <?= csrf_field() ?>
                                <input type="hidden" name="cart" id="cartJson"><input type="hidden" name="payment_method" id="paymentMethod" value="qris"><input type="hidden" name="pickup_type" id="pickupTypeInput" value="outlet">
                                <div class="sim-summary-section">
    <div class="form-group mb-2" style="display:none !important;">
        <label class="form-label fs-12 mb-1">Nama Pemesan</label>
        <input type="text" class="form-control form-control-sm" name="customer_name" id="customerNameHiddenOld" placeholder="Nama Anda">
    </div>
    <div class="form-group mb-2" style="display:none !important;">
        <label class="form-label fs-12 mb-1">No WhatsApp</label>
        <input type="text" class="form-control form-control-sm" name="customer_phone" id="customerPhoneHiddenOld" placeholder="08...">
    </div>
    <div class="row g-2 mb-2" id="pickupDateRowSide" style="display:none !important;">
        <div class="col-6">
            <label class="form-label fs-12 mb-1">Tgl Ambil</label>
            <input type="date" class="form-control form-control-sm" name="pickup_date" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="col-6">
            <label class="form-label fs-12 mb-1">Jam</label>
            <input type="time" class="form-control form-control-sm" name="pickup_time" value="09:00">
        </div>
    </div>
    <hr style="display:none !important;">
    <div class="sim-summary-row"><span>Subtotal</span><span id="subtotalText">Rp 0</span></div>
    <div class="sim-summary-row sim-summary-total"><span>Total</span><strong id="totalText">Rp 0</strong></div>
</div>
                                  <textarea name="notes" class="form-control mt-2 sim-notes-input" rows="1" placeholder="Catatan order..." style="display:none !important;"></textarea>

                                <!-- Payment Methods -->
                                <div class="sim-pay-section" style="display:none !important;">
                                    <div class="row align-items-center methods g-2 sim-pay-methods">
                                        <div class="col-6 d-flex"><a href="javascript:void(0);" class="payment-item active d-flex align-items-center justify-content-center p-2 flex-fill" data-pay="qris" onclick="setPayment('qris');"><?= sim_icon('ti-qrcode', 'me-1') ?><p class="fs-12 fw-medium mb-0">QRIS (Scan)</p></a></div>
                                        <div class="col-6 d-flex"><a href="javascript:void(0);" class="payment-item d-flex align-items-center justify-content-center p-2 flex-fill" data-pay="transfer" onclick="setPayment('transfer');"><?= sim_icon('ti-credit-card', 'me-1') ?><p class="fs-12 fw-medium mb-0">Transfer Bank</p></a></div>
                                    </div>
                                </div>

                                <!-- QRIS Display Box (Hidden in Sidebar for Online Order, shown cleanly inside Checkout Drawer) -->
                                <div class="sim-qris-section text-center p-3 mt-2 border rounded bg-light" id="simQrisBox" style="display: none !important;">
                                </div>

                                <!-- Uang Diterima -->
                              </form>
                        </div>
                    </div>

                    <!-- Checkout Button -->
                    <div class="sim-checkout-btn-wrap">
                        <button type="button" class="btn sim-checkout-btn" onclick="openCheckout(false)">
                            <?= sim_icon('ti-shopping-cart', 'me-2') ?>
                            <div>
                                <strong>Proses Pembayaran</strong>
                                <small><span id="itemCount2">0</span> item - <span id="totalText2">Rp 0</span></small>
                            </div>
                            <?= sim_icon('ti-chevron-right') ?>
                        </button>
                    </div>
                </aside>
            </div>
        </div>
</div><!-- end .fo-pos-wrapper -->

<!-- FLOATING PAYBOX (Mobile Only) -->

</div>


<div class="fo-floating-actions">
  <button type="button" class="fo-float-btn ai" onclick="toggleAiPanel()">✨ Dengarkan Saran AI</button>
  <a class="fo-float-btn wa" href="https://wa.me/6285794532040?text=Halo%20D%27Celup%20Pasekon%2C%20saya%20ingin%20bertanya%20tentang%20online%20order" target="_blank">💬 Hubungi Outlet</a>
</div>

<div class="fo-ai-nudge" id="aiNudgeBubble">
  <b id="aiNudgeTitle">Mau saran menu?</b>
  <p id="aiNudgeText">Aku bantu pilihkan menu yang cocok untuk Kakak.</p>
  <div class="fo-ai-nudge-actions">
    <button type="button" class="listen" onclick="toggleAiPanel(true); playAiRecommendation(); hideAiNudge();">Dengarkan</button>
    <button type="button" class="close" onclick="hideAiNudge()">Nanti</button>
  </div>
</div>

<div class="fo-ai-panel" id="aiPanel">
  <h3>Saran AI Menu Favorit</h3>
  <p id="aiRecoText">Tekan tombol dengarkan untuk mendapatkan saran menu sesuai pesanan Kakak.</p>
  <div class="fo-ai-reco-menu">
    <b id="aiRecoMenu">Saran AI Lumero</b>
    <span id="aiRecoReason">Saran akan menyesuaikan isi keranjang dan jam promo.</span>
  </div>
  <div class="fo-ai-actions">
    <button type="button" class="gold" onclick="playAiRecommendation()">Dengarkan</button>
    <button type="button" class="dark" onclick="toggleAiPanel(false)">Tutup</button>
  </div>
</div>

<div class="fo-added-modal" id="addedModal" aria-modal="true" role="dialog">
  <div class="fo-added-card">
    <div class="fo-added-icon">✓</div>
    <h2>Masuk Keranjang</h2>
    <p id="addedItemText">Menu sudah ditambahkan ke keranjang.</p>
    <div class="fo-added-actions">
      <button type="button" class="fo-added-secondary" onclick="closeAddedModal()">Lanjut Pilih Menu</button>
      <button type="button" class="fo-added-primary" onclick="goToCheckoutFromAdded()">Lanjut Bayar</button>
    </div>
  </div>
</div>


<div class="fo-pickup-confirm" id="pickupConfirmModal" aria-modal="true" role="dialog">
  <div class="fo-pickup-confirm-card">
    <h2>Atur Waktu Pengambilan</h2>
    <p>Isi data pemesan dan pilih waktu pengambilan sebelum lanjut ke pembayaran.</p>

    <div class="fo-customer-card" style="margin-top:12px">
      <h3>Data Pemesan</h3>
      <div class="fo-field"><label>Nomor WhatsApp</label><input id="customerPhoneTop" inputmode="tel" placeholder="08xxxxxxxxxx" value="<?=fo_e($memberOnline['phone'] ?? '')?>"></div>
      <div class="fo-field"><label>Nama Pemesan</label><input id="customerNameTop" placeholder="Nama Anda" value="<?=fo_e($memberOnline['name'] ?? '')?>"></div>
      <div class="fo-customer-found" id="customerFoundInfo">Data pelanggan ditemukan, nama otomatis diisi.</div>
    </div>

    <div class="fo-pickup-mode" style="margin-top:12px">
      <button type="button" class="fo-pickup-option active" data-pickup="outlet">
        <b>Ambil di Outlet</b><span>Pesanan disiapkan sesuai jam pickup</span>
      </button>
      <button type="button" class="fo-pickup-option <?= $deliveryEnabled ? '' : 'disabled' ?>" data-pickup="delivery" <?= $deliveryEnabled ? '' : 'disabled' ?>>
        <b>Delivery (Diantar Kurir)</b><span><?= $deliveryEnabled ? 'Diantar langsung ke depan pintu rumah Kakak' : 'Belum aktif' ?></span>
      </button>
    </div>

    <div class="fo-checkout-grid" style="margin-top:12px" id="outletDateTimeGrid">
      <div class="fo-field"><label>Tanggal Pengambilan</label><input type="date" id="pickupDate" min="<?=$today?>" value="<?=$today?>"></div>
      <div class="fo-field"><label>Jam Pengambilan</label><select id="pickupTime"></select><div class="fo-time-hint" id="pickupTimeHint">Menyiapkan opsi waktu pengambilan…</div></div>
    </div>

    <!-- Delivery Map & Address Section -->
    <div id="deliverySectionWrap" style="display:none; margin-top:14px;">
      <div style="margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; background:var(--dp-surface); border:1px solid var(--dp-glass-border); padding:10px 12px; border-radius:10px;">
        <div style="font-size:12px; font-weight:600; color:var(--dp-text); display:flex; align-items:center; gap:6px;">
          <span>📍</span> <span>Lokasi Pengantaran</span>
        </div>
        <button type="button" id="btnUseGps" onclick="useCurrentDeviceLocation()" style="background:var(--dp-gradient); color:#fff; border:none; padding:7px 14px; border-radius:8px; font-size:12px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:6px; box-shadow:0 2px 10px rgba(255,45,85,0.3); transition:all 0.2s;">
          🎯 Gunakan Lokasi Saat Ini (GPS)
        </button>
      </div>

      <div class="fo-field mb-2" style="position:relative;">
        <label>Cari Alamat / Patokan (Autocomplete)</label>
        <div style="display:grid; grid-template-columns: 1fr auto; gap:6px;">
          <input type="text" id="deliverySearchInput" placeholder="Ketik nama jalan / kelurahan / patokan...">
          <button type="button" class="fo-btn-search" onclick="searchDeliveryAddress()">Cari</button>
        </div>
        <div id="deliverySearchResults" class="fo-search-results" style="display:none;"></div>
      </div>
      
      <div style="margin: 8px 0; border-radius:10px; overflow:hidden; border:1px solid var(--dp-glass-border); position:relative;">
        <div id="deliveryMap" style="height:220px; width:100%; z-index:1;"></div>
        <div style="position:absolute; bottom:8px; left:8px; right:8px; z-index:400; background:rgba(15,15,19,0.85); backdrop-filter:blur(10px); padding:6px 10px; border-radius:8px; border:1px solid var(--dp-glass-border); font-size:11px; display:flex; justify-content:space-between; align-items:center;">
          <span style="color:#fff;">📍 Geser pin ke titik lokasi pasti</span>
          <span id="mapDistanceBadge" style="font-weight:800; color:var(--dp-green);">0 km</span>
        </div>
      </div>

      <div class="fo-field mb-2">
        <label>Alamat Lengkap Pengantaran</label>
        <textarea id="deliveryAddressInput" rows="2" placeholder="Nama Jalan, No. Rumah, RT/RW, Patokan detail (misal: rumah pagar hitam samping warung)..."></textarea>
      </div>

      <div id="deliveryFeeBox" style="background:var(--dp-surface); border:1px solid var(--dp-glass-border); border-radius:8px; padding:10px; margin-top:8px; display:flex; justify-content:space-between; align-items:center; font-size:12px;">
        <div>
          <b style="color:var(--dp-text); display:block;">Ongkos Kirim (<span id="deliveryDistanceText">0 km</span>)</b>
          <small style="color:var(--dp-muted); font-size:10px;" id="deliveryRadiusStatus">Dalam radius pengantaran</small>
        </div>
        <b id="deliveryFeeText" style="font-size:15px; color:var(--dp-red);">Rp 0</b>
      </div>
    </div>

    <div class="fo-pickup-summary" style="margin-top:14px;">
      <div><span>Tipe</span><b id="pickupSummaryType">Ambil di Outlet</b></div>
      <div id="pickupSummaryDateRow"><span>Tanggal</span><b id="pickupSummaryDate">-</b></div>
      <div id="pickupSummaryTimeRow"><span>Jam</span><b id="pickupSummaryTime">-</b></div>
      <div id="pickupSummaryFeeRow" style="display:none;"><span>Ongkos Kirim</span><b id="pickupSummaryFee">Rp0</b></div>
      <div><span>Total</span><b id="pickupSummaryTotal">Rp0</b></div>
    </div>
    <div class="fo-pickup-actions">
      <button type="button" class="light" onclick="closePickupConfirm()">Kembali Pilih Menu</button>
      <button type="button" class="gold" onclick="continueToPayment()">Lanjut Bayar</button>
    </div>
  </div>
</div>

<div class="fo-drawer" id="checkoutDrawer"><div class="fo-panel">
  <h2>Checkout Online Order</h2>
  <div class="fo-cart-list" id="cartList"></div>
  <div class="fo-payment-total-box">
    <div id="checkoutFeeDetailRow" style="display:none; justify-content:space-between; margin-bottom:6px; font-size:12px; color:var(--dp-muted);"><span>Ongkos Kirim:</span> <b id="checkoutFeeDetailText" style="color:var(--dp-text);">Rp0</b></div>
    <small>Total yang harus dibayar</small><b id="checkoutTotalText">Rp0</b><div class="items" id="checkoutTotalDetail">Keranjang masih kosong.</div>
  </div>
  <div class="fo-checkout-grid">
    <div class="fo-field"><label>Nama Pemesan</label><input id="customerName" placeholder="Nama Anda" value="<?=fo_e($memberOnline['name'] ?? '')?>"></div>
    <div class="fo-field"><label>Nomor WhatsApp</label><input id="customerPhone" inputmode="tel" placeholder="08xxxxxxxxxx" value="<?=fo_e($memberOnline['phone'] ?? '')?>"></div>
  </div>
  <div class="fo-field"><label>Catatan</label><textarea id="customerNote" placeholder="Catatan untuk kasir, opsional"></textarea></div>
  <div class="fo-payment">
    <button type="button" class="payBtn active" data-pay="qris">QRIS / E-Wallet</button>
    <button type="button" class="payBtn" data-pay="transfer">Transfer Bank</button>
  </div>
  <div class="fo-pay-preview active" id="qrisPreview" style="text-align:center; padding:20px;">
    <b style="font-size:15px; display:block; margin-bottom:14px; color:var(--dp-text);">Pembayaran QRIS / E-Wallet</b>
    <?php if ($isMidtransQris): ?>
      <div style="background:#fff; padding:20px; border-radius:18px; display:inline-block; box-shadow:0 8px 32px rgba(0,0,0,0.5); border:2px solid var(--dp-glass-border); max-width:100%;">
        <p style="color:#000; font-size:14px; margin:0;">QRIS Pembayaran akan otomatis muncul di layar selanjutnya setelah Anda mengklik tombol <b>Proses Pembayaran</b>.</p>
      </div>
    <?php else: ?>
      <?php if (!empty($paymentQrisImage)): ?>
        <img src="../<?= fo_e(ltrim($paymentQrisImage, '/')) ?>?v=<?= time() ?>" alt="QRIS Manual" style="width:240px; max-width:100%; border-radius:10px; box-shadow:0 8px 32px rgba(0,0,0,0.5); border:2px solid var(--dp-glass-border); background:#fff; margin: 0 auto; padding: 10px;">
        <p style="font-size:13px; color:var(--dp-muted); margin-top:12px; margin-bottom:0;">Silakan scan kode QRIS di atas untuk melakukan pembayaran.<br>Pesanan akan diproses setelah bukti pembayaran diverifikasi kasir.</p>
      <?php else: ?>
        <p style="font-size:13px; color:var(--dp-red);">QRIS Toko belum dikonfigurasi. Silakan hubungi kasir.</p>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <div class="fo-pay-preview" id="transferPreview">
    <b>Transfer Bank</b><br><br>
    <div style="font-size:14px; margin-bottom:12px; color:var(--dp-text);">
      Silakan transfer ke rekening berikut:<br>
      <b>Bank BCA</b><br>
      No. Rekening: <b style="font-size:16px; color:var(--dp-primary);"><?=$bankAccountNo?></b> 
      <button type="button" class="btn btn-sm btn-outline-secondary ms-2" data-copy="<?=$bankAccountNo?>" style="padding:2px 8px; font-size:11px;">Copy</button><br>
      Atas Nama: <b><?=$bankAccountName?></b>
    </div>
    <div class="fo-info" style="margin-top:10px">Pesanan akan diproses setelah bukti pembayaran diverifikasi kasir.</div>
  </div>
  <div class="fo-info" id="payInfo" style="margin-top:10px">Metode pembayaran otomatis: QRIS / E-Wallet.</div>
  <form method="post" id="foForm">
    <input type="hidden" name="cart" id="cartInput">
    <input type="hidden" name="pickup_date" id="pickupDateInput">
    <input type="hidden" name="pickup_time" id="pickupTimeInput">
    <input type="hidden" name="payment_method" id="paymentInput" value="">
    <input type="hidden" name="pickup_type" id="pickupTypeInput" value="outlet">
    <input type="hidden" name="customer_name" id="customerNameInput">
    <input type="hidden" name="customer_phone" id="customerPhoneInput">
    <input type="hidden" name="customer_note" id="customerNoteInput">
    <input type="hidden" name="delivery_address" id="deliveryAddressHiddenInput">
    <input type="hidden" name="delivery_lat" id="deliveryLatHiddenInput">
    <input type="hidden" name="delivery_lng" id="deliveryLngHiddenInput">
    <input type="hidden" name="delivery_fee" id="deliveryFeeHiddenInput" value="0">
    <input type="hidden" name="delivery_distance_km" id="deliveryDistanceHiddenInput" value="0">
    <input type="hidden" name="outlet_id" id="outletIdInput" value="<?= fo_e((string)current_outlet_id()) ?>">
    <button class="fo-submit">Kirim Online Order</button>
  </form>
  <button class="fo-close" type="button" onclick="closeCheckout()">Tutup</button>
</div></div>

<!-- ===== POPUP: QRIS MIDTRANS ===== -->
<div id="midtransQrisOverlay" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.82); backdrop-filter:blur(8px); align-items:center; justify-content:center;">
  <div id="midtransQrisBox" style="background:var(--dp-surface); border:1px solid var(--dp-glass-border); border-radius:24px; padding:32px 28px 24px; max-width:400px; width:calc(100vw - 32px); text-align:center; box-shadow:0 24px 80px rgba(0,0,0,0.7); position:relative; animation:foPayIn .25s ease both;">
    <button type="button" onclick="closeMidtransQris()" style="position:absolute;top:14px;right:14px;background:var(--dp-surface-2);border:1px solid var(--dp-glass-border);border-radius:50%;width:32px;height:32px;font-size:18px;cursor:pointer;color:var(--dp-muted);display:flex;align-items:center;justify-content:center;line-height:1;">&times;</button>
    <h3 style="font-size:20px; font-weight:800; color:var(--dp-text); margin:0 0 6px;">Pembayaran QRIS</h3>
    <div id="mqrisOrderNo" style="font-size:12px; font-weight:700; color:var(--dp-primary); margin-bottom:16px;"></div>
    <!-- Loading state -->
    <div id="mqrisLoading" style="padding:40px 20px;">
      <div style="width:44px;height:44px;border:3px solid var(--dp-glass-border);border-top-color:var(--dp-primary);border-radius:50%;margin:0 auto 16px;animation:spin 1s linear infinite;"></div>
      <div style="font-size:13px; font-weight:600; color:var(--dp-text-2);">Membuat kode QRIS...</div>
    </div>
    <!-- Content state -->
    <div id="mqrisContent" style="display:none;">
      <div style="background:#fff; padding:16px; border-radius:16px; display:inline-block; margin-bottom:16px; box-shadow:0 8px 24px rgba(0,0,0,0.3);">
        <img id="mqrisImg" src="" alt="QRIS Midtrans" style="width:220px; max-width:100%; height:auto; display:block; border-radius:8px;">
      </div>
      <div id="mqrisAmount" style="font-size:24px; font-weight:800; color:var(--dp-text); margin-bottom:4px;"></div>
      <div style="font-size:12px; color:var(--dp-muted); font-weight:600; margin-bottom:16px; line-height:1.5;">Scan dengan aplikasi dompet digital favorit Anda.<br><b style="color:var(--dp-text);">GoPay · OVO · Dana · ShopeePay · M-Banking</b></div>
      <div id="mqrisTimer" style="font-size:12px; color:var(--dp-muted); margin-bottom:10px; font-weight:600;"></div>
      <div id="mqrisStatusBadge" style="display:inline-flex; align-items:center; gap:6px; background:rgba(251,191,36,.12); border:1px solid rgba(251,191,36,.3); border-radius:999px; padding:6px 16px; font-size:12px; font-weight:700; color:#f59e0b; margin-bottom:16px;">
        <span style="width:7px;height:7px;border-radius:50%;background:#f59e0b;display:inline-block;animation:pulseGlow 1.4s infinite;"></span>
        Menunggu Pembayaran...
      </div>
    </div>
    <!-- Error state -->
    <div id="mqrisError" style="display:none; padding:20px; color:#ef4444; font-size:13px; font-weight:600; line-height:1.6;"></div>
    <!-- Static Fallback state -->
    <div id="mqrisStaticFallback" style="display:none; padding:0 20px 20px;">
      <b style="font-size:14px; display:block; margin-bottom:14px; color:var(--dp-text);">Silakan gunakan QRIS Cadangan berikut:</b>
      <div style="background:#fff; padding:14px; border-radius:18px; display:inline-block; box-shadow:0 8px 32px rgba(0,0,0,0.5); border:2px solid var(--dp-glass-border); max-width:100%;">
        <img src="../<?=fo_e(ltrim($paymentQrisImage,'/'))?>?v=<?=time()?>" alt="QRIS Lumero" style="width:240px; max-width:100%; height:auto; display:block; margin:0 auto; border-radius:10px; background:#fff; padding:0; border:none;">
      </div>
      <div style="margin-top:16px;">
        <a class="fo-download-btn" href="../<?=fo_e(ltrim($paymentQrisImage,'/'))?>" download="QRIS-Lumero.png" style="box-shadow:0 4px 16px rgba(255,45,85,0.4); padding:10px 24px; font-size:13px;">Download QRIS</a>
      </div>
      <div class="fo-info" style="margin-top:14px; margin-bottom:16px; font-size:12px; line-height:1.5; color:var(--dp-text-2);">
        <?=fo_e(str_ireplace('midtrans', 'Lumero', $qrisInfo))?>. Simpan bukti pembayaran untuk diverifikasi kasir.
      </div>
      <button type="button" id="mqrisFallbackDoneBtn" style="width:100%;background:var(--dp-gradient);color:#fff;border:none;border-radius:10px;padding:14px;font-size:15px;font-weight:800;cursor:pointer;box-shadow:0 4px 16px rgba(255,45,85,0.3)">Selesai & Lacak Pesanan</button>
    </div>
  </div>
</div>

<!-- ===== POPUP: SUKSES PEMBAYARAN ===== -->
<div id="paymentSuccessOverlay" style="display:none; position:fixed; inset:0; z-index:10000; background:rgba(0,0,0,0.88); backdrop-filter:blur(10px); align-items:center; justify-content:center;">
  <div style="background:var(--dp-surface); border:1px solid rgba(52,211,153,.25); border-radius:24px; padding:40px 28px 32px; max-width:380px; width:calc(100vw - 32px); text-align:center; box-shadow:0 24px 80px rgba(0,0,0,0.7), 0 0 60px rgba(52,211,153,0.12); animation:popIn .4s cubic-bezier(.4,0,.2,1) both;">
    <div style="font-size:68px; margin-bottom:14px;">🎉</div>
    <div style="font-size:24px; font-weight:800; color:var(--dp-green); margin-bottom:8px; letter-spacing:-.02em;">Pembayaran Berhasil!</div>
    <div style="font-size:14px; color:var(--dp-text-2); font-weight:600; margin-bottom:8px; line-height:1.6;">Terima kasih! Pesanan Anda sudah kami terima dan akan segera diproses oleh dapur Lumero. 🍗</div>
    <div id="successOrderNo" style="font-size:12px; color:var(--dp-muted); margin-bottom:24px;"></div>
    <div style="width:100%; background:var(--dp-surface-2); border-radius:12px; height:6px; overflow:hidden; margin-bottom:8px;">
      <div id="successProgressBar" style="height:100%; width:0%; background:linear-gradient(90deg,#34d399,#10b981); border-radius:12px; transition:width .1s linear;"></div>
    </div>
    <div style="font-size:12px; color:var(--dp-muted); font-weight:600;">Mengalihkan ke halaman lacak pesanan...</div>
  </div>
</div>


<script>
window.LUMERO_ACTIVE_OUTLETS = <?= json_encode($activeOutletsList, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) ?>;
window.LUMERO_SELECTED_OUTLET_ID = <?= (int)($activeOutletId ?? current_outlet_id()) ?>;
window.LUMERO_HAS_SELECTED_OUTLET = <?= isset($_GET['outlet_id']) || isset($_SESSION['lumero_selected_outlet_id']) ? 'true' : 'false' ?>;
window.lumero_FREE_ORDER_POPUP = <?= $orderPopup ? 'true' : 'false' ?>;
const today = <?=json_encode($today)?>;
const tomorrow = <?=json_encode($tomorrow)?>;
const serverNowTime = <?=json_encode($nowTime)?>;
const aiNarratives = {
  empty_cart:[{title:'Bingung Pilih Menu?',suggested_menu:'Ayam Crispy Varian Saus',message:'Tenang kak, jangan bingung! Kalau kaka bingung pilih menu, aku bantu ya. Di Lumero, Kakak wajib coba menu ayam crispy dengan varian saus favorit. Kriuknya mantap, sausnya lumer, aromanya menggoda, dan rasanya bikin pengen nambah. Biar makin sedap, kaka juga bisa tambahkan kentang kriwil dan Matcha, cobain deh, gak akan nyesel!!!',cta_text:'Coba ayam saus favorit'}],
  only_chicken_original:[{title:'Lengkapi Ayam Original',suggested_menu:'Kentang Kriuk dan Matcha',message:'Biar makin lengkap, tambahkan kentang kriuk dan minuman matcha segar sebagai penyempurna hidangan. Dijamin bikin ketagihan deh, hihihi...',cta_text:'Tambah kentang dan matcha'}],
  chicken_original_rice:[{title:'Ayam dan Nasi Sudah Pas',suggested_menu:'Saus Favorit + Minuman',message:'Ayam original plus nasi sudah mantap, Kak. Biar rasanya makin hidup, tambahkan varian saus favorit dan minuman segar. Sekali celup, kriuknya makin lumer di hati.',cta_text:'Tambah saus dan minuman'}],
  chicken_sauce:[{title:'Ayam Saus Sudah Mantap',suggested_menu:'Nasi + Kentang + Matcha',message:'Pilihan ayam saus Kakak sudah juara. Biar makin puas, tambahkan nasi hangat, kentang kriwil, atau Matcha segar. Lengkapnya dapet, nikmatnya makin nempel.',cta_text:'Lengkapi dengan nasi atau minuman'}],
  only_potato:[{title:'Kentang Kriwil Mantap',suggested_menu:'Ayam Crispy dan Minuman',message:'Kentangnya sudah cocok jadi teman ngemil. Biar lebih puas, pasangkan dengan ayam crispy dan minuman segar favorit Kakak.',cta_text:'Tambah ayam dan minuman'}],
  only_drink:[{title:'Minuman Segar Siap',suggested_menu:'Ayam Crispy Saus',message:'Minumannya sudah segar, Kak. Sekarang waktunya tambahkan ayam crispy varian saus favorit. Kriuknya mantap, sausnya lumer, cocok banget jadi pasangan minuman Kakak.',cta_text:'Tambah ayam crispy'}],
  drink_potato:[{title:'Minuman dan Kentang Sudah Oke',suggested_menu:'Ayam Crispy Varian Saus',message:'Minuman dan kentang sudah jadi duet yang asik. Tapi biar makin lengkap, tambahkan ayam crispy saus Lumero. Dijamin makin kenyang dan makin puas.',cta_text:'Tambah ayam saus'}],
  drink_chicken:[{title:'Ayam dan Minuman Sudah Mantap',suggested_menu:'Kentang Kriwil',message:'Ayam dan minuman Kakak sudah pas banget. Biar teksturnya makin rame, tambahkan kentang kriwil yang renyah. Jadi lengkap, gurih, segar, dan nagih.',cta_text:'Tambah kentang kriwil'}],
  all_menu:[{title:'Pesanan Sudah Lengkap',suggested_menu:'Tambahan Saus Favorit',message:'Wah, pilihan Kakak sudah lengkap banget! Ayam ada, kentang ada, minuman juga ada. Kalau mau makin lumer, tambahkan saus favorit ekstra biar setiap gigitan makin seru.',cta_text:'Tambah saus ekstra'}],
  general:[{title:'Saran Menu Lumero',suggested_menu:'Ayam Crispy Varian Saus',message:'Kakak bisa pilih ayam crispy varian saus favorit Lumero. Kriuknya mantap, sausnya lumer, dan cocok dilengkapi kentang atau minuman segar.',cta_text:'Pilih menu favorit'}]
};
const comboWindowActive = <?=$comboWindowActive ? 'true' : 'false'?>;
const sauces = <?=json_encode(array_values(array_map(fn($s)=>['id'=>(int)$s['id'],'name'=>(string)$s['name']],$data['sauces'])),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
window.cart = window.cart || [];
let cart = window.cart;
window.syncSharedCart = function(updatedCart) {
  cart = updatedCart || window.cart || [];
  window.cart = cart;
  if(typeof renderCart === 'function') renderCart();
};
let payment = 'qris';
let selectedPickupType = 'outlet';
let lastAddedName = '';

/* ── Branch Selector & GPS Haversine Recommendation ── */
function openBranchSelector() {
  window.location.href = 'select-branch.php';
}

function closeBranchSelector() {}

function detectBranchGPS() {
  const detectingBar = document.getElementById('branchDetectingBar');
  if (detectingBar) {
    detectingBar.innerHTML = '<span>⏳ Mengukur jarak GPS ke lokasi Anda...</span>';
  }
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      function(position) {
        const userLat = position.coords.latitude;
        const userLng = position.coords.longitude;
        if (detectingBar) {
          detectingBar.innerHTML = '<span>✅ Lokasi GPS akurat ditemukan</span><button type="button" onclick="detectBranchGPS()" style="background:transparent; border:none; color:var(--dp-red); font-weight:800; cursor:pointer; font-size:12px; text-decoration:underline;">Deteksi Ulang</button>';
        }
        renderBranchCards(userLat, userLng);
      },
      function(error) {
        if (detectingBar) {
          detectingBar.innerHTML = '<span>⚠️ GPS tidak aktif / izin ditolak. Menampilkan semua cabang.</span><button type="button" onclick="detectBranchGPS()" style="background:transparent; border:none; color:var(--dp-red); font-weight:800; cursor:pointer; font-size:12px; text-decoration:underline;">Coba Lagi</button>';
        }
        renderBranchCards(null, null);
      },
      { enableHighAccuracy: true, timeout: 8000 }
    );
  } else {
    if (detectingBar) {
      detectingBar.innerHTML = '<span>⚠️ Browser Anda tidak mendukung GPS.</span>';
    }
    renderBranchCards(null, null);
  }
}

function renderBranchCards(userLat, userLng) {
  const container = document.getElementById('branchCardsContainer');
  if (!container) return;
  
  let outlets = [...(window.LUMERO_ACTIVE_OUTLETS || [])];
  if (outlets.length === 0) {
    container.innerHTML = '<div style="text-align:center; padding:20px; color:var(--dp-text-2); font-size:13px;">Belum ada data cabang aktif di sistem.</div>';
    return;
  }

  outlets.forEach(o => {
    o.distance = null;
    if (userLat !== null && userLng !== null && o.latitude && o.longitude && typeof haversineDistanceKmJS === 'function') {
      o.distance = haversineDistanceKmJS(userLat, userLng, Number(o.latitude), Number(o.longitude));
    }
  });

  if (userLat !== null && userLng !== null) {
    outlets.sort((a, b) => {
      if (a.distance === null && b.distance === null) return 0;
      if (a.distance === null) return 1;
      if (b.distance === null) return -1;
      return a.distance - b.distance;
    });
  }

  let html = '';
  outlets.forEach((o, index) => {
    const isCurrent = (Number(o.id) === Number(window.LUMERO_SELECTED_OUTLET_ID));
    const distText = o.distance !== null ? `${o.distance.toFixed(1)} km dari Anda` : `Cabang Lumero`;
    const isClosest = (userLat !== null && index === 0 && o.distance !== null);
    const badgeHtml = isClosest ? `<div style="display:inline-block; background:rgba(239,68,68,0.15); color:var(--dp-red); font-size:10px; font-weight:800; padding:3px 8px; border-radius:6px; margin-bottom:6px; border:1px solid rgba(239,68,68,0.3);">🌟 REKOMENDASI TERDEKAT (${distText})</div>` : `<div style="display:inline-block; background:var(--dp-surface-2); color:var(--dp-text-2); font-size:10px; font-weight:700; padding:3px 8px; border-radius:6px; margin-bottom:6px;">📍 ${distText}</div>`;
    const borderStyle = isCurrent ? 'border:2px solid var(--dp-red);' : (isClosest ? 'border:1px solid rgba(239,68,68,0.6);' : 'border:1px solid var(--dp-glass-border);');
    const bgStyle = isCurrent ? 'background:rgba(239,68,68,0.06);' : 'background:var(--dp-surface-2);';

    html += `
    <div style="border-radius:18px; padding:16px; ${bgStyle} ${borderStyle} transition:all .2s ease; display:flex; flex-direction:column; gap:8px;">
      <div>${badgeHtml}</div>
      <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
          <div style="font-size:15px; font-weight:800; color:var(--dp-text); margin-bottom:4px;">${o.name || 'Cabang Lumero'} ${isCurrent ? '<span style="color:var(--dp-red); font-size:11px;">(Terpilih Saat Ini)</span>' : ''}</div>
          <div style="font-size:12px; color:var(--dp-text-2); line-height:1.4;">${o.address || 'Alamat cabang belum dicantumkan'}</div>
        </div>
      </div>
      <div style="display:flex; justify-content:space-between; align-items:center; margin-top:6px; pt:6px; border-top:1px dashed var(--dp-glass-border);">
        <div style="font-size:11px; color:var(--dp-muted); font-weight:600;">🕒 Jam Buka: 10:00 - ${o.closing_hour || '22:00'}</div>
        <button type="button" onclick="selectBranchItem(${o.id}, '${(o.name || '').replace(/'/g, "\\'")}', ${Number(o.latitude) || -6.9175}, ${Number(o.longitude) || 106.9275})" style="background:var(--dp-red); color:#fff; border:none; padding:8px 16px; border-radius:10px; font-size:12px; font-weight:800; cursor:pointer; box-shadow:0 4px 12px rgba(239,68,68,0.3);">
          ${isCurrent ? '✔ Terpilih' : 'Pilih Cabang Ini &rarr;'}
        </button>
      </div>
    </div>`;
  });

  container.innerHTML = html;
}

function selectBranchItem(outletId, outletName, lat, lng) {
  window.LUMERO_SELECTED_OUTLET_ID = Number(outletId);
  const inputEl = document.getElementById('outletIdInput');
  if (inputEl) inputEl.value = outletId;
  
  if (lat && lng && typeof deliveryConfig !== 'undefined') {
    deliveryConfig.outletLat = lat;
    deliveryConfig.outletLng = lng;
    if (typeof deliveryMapObj !== 'undefined' && deliveryMapObj) {
      deliveryMapObj.setView([lat, lng], 14);
    }
  }

  const badgeEl = document.getElementById('activeBranchBadge');
  if (badgeEl) {
    badgeEl.innerHTML = `📍 Cabang Terpilih: <b>${outletName}</b> <span style="font-weight:400; font-size:11px;">(Ganti)</span>`;
  }

  closeBranchSelector();
  window.location.href = 'online-order.php?outlet_id=' + Number(outletId);
}

/* ── Delivery JS Configuration & State ── */
const deliveryConfig = {
  enabled: <?= $deliveryEnabled ? 'true' : 'false' ?>,
  maxRadius: <?= (float)($deliverySettings['delivery_max_radius_km'] ?? 5) ?>,
  feeModel: '<?= fo_e($deliverySettings['delivery_fee_model'] ?? 'per_km') ?>',
  flatFee: <?= (int)($deliverySettings['delivery_flat_fee'] ?? 5000) ?>,
  perKmFee: <?= (int)($deliverySettings['delivery_per_km_fee'] ?? 3000) ?>,
  minFee: <?= (int)($deliverySettings['delivery_min_fee'] ?? 5000) ?>,
  freeAbove: <?= (int)($deliverySettings['delivery_free_above'] ?? 0) ?>,
  freeKmLimit: <?= (float)($deliverySettings['delivery_free_km_limit'] ?? 2) ?>,
  outletLat: <?= (float)($deliveryOutletCoords['lat'] ?? -6.9175) ?>,
  outletLng: <?= (float)($deliveryOutletCoords['lng'] ?? 106.9275) ?>
};
let deliveryMapObj = null;
let deliveryMarkerObj = null;
let deliveryLat = 0;
let deliveryLng = 0;
let deliveryDistanceKm = 0;
let deliveryFee = 0;

function setPickupType(type){
  if(type === 'delivery' && !deliveryConfig.enabled){
    alert('Fitur Delivery Order saat ini belum aktif.');
    return;
  }
  selectedPickupType = type || 'outlet';
  document.querySelectorAll('.fo-pickup-option').forEach(x => {
    x.classList.toggle('active', x.dataset.pickup === selectedPickupType);
  });
  const input = document.getElementById('pickupTypeInput');
  if(input) input.value = selectedPickupType;
  const ct = document.getElementById('customerType');
  if(ct && ct.value !== selectedPickupType) ct.value = selectedPickupType;
  const wrap = document.getElementById('deliverySectionWrap');
  if(wrap) wrap.style.display = selectedPickupType === 'delivery' ? 'block' : 'none';
  const sidePickupDateRow = document.getElementById('pickupDateRowSide');
  if(sidePickupDateRow) sidePickupDateRow.style.display = selectedPickupType === 'delivery' ? 'none' : 'flex';
  const dtGrid = document.getElementById('outletDateTimeGrid');
  if(dtGrid) dtGrid.style.display = selectedPickupType === 'delivery' ? 'none' : 'grid';
  if(selectedPickupType === 'delivery'){
    setTimeout(() => { initDeliveryMap(); }, 180);
  }
  updatePickupSummary();
  renderCart();
}

function initDeliveryMap() {
  if (!deliveryConfig.enabled) return;
  const mapEl = document.getElementById('deliveryMap');
  if (!mapEl) return;
  if (deliveryMapObj) {
    deliveryMapObj.invalidateSize();
    return;
  }
  const outletLat = Number(deliveryConfig.outletLat) || -6.9175;
  const outletLng = Number(deliveryConfig.outletLng) || 106.9275;
  
  deliveryMapObj = L.map('deliveryMap').setView([outletLat, outletLng], 14);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap'
  }).addTo(deliveryMapObj);

  const outletIcon = L.divIcon({
    className: 'custom-outlet-pin',
    html: '<div style="background:#ff2d55; border:2px solid #fff; border-radius:50%; width:24px; height:24px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:12px; font-weight:bold; box-shadow:0 2px 6px rgba(0,0,0,0.5);">🏠</div>',
    iconSize: [24, 24],
    iconAnchor: [12, 12]
  });
  L.marker([outletLat, outletLng], {icon: outletIcon}).addTo(deliveryMapObj).bindPopup('<b>Outlet Lumero</b>').openPopup();

  const startLat = deliveryLat || (outletLat + 0.005);
  const startLng = deliveryLng || (outletLng + 0.005);
  const pinIcon = L.divIcon({
    className: 'custom-delivery-pin',
    html: '<div style="background:#34d399; border:2px solid #fff; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; color:#1a1a2e; font-size:14px; font-weight:bold; box-shadow:0 2px 8px rgba(0,0,0,0.6); cursor:move;">📍</div>',
    iconSize: [28, 28],
    iconAnchor: [14, 14]
  });
  deliveryMarkerObj = L.marker([startLat, startLng], {icon: pinIcon, draggable: true}).addTo(deliveryMapObj);
  deliveryMarkerObj.bindPopup('<b>Titik Pengantaran</b><br><small>Geser ke rumah Anda</small>');

  deliveryMarkerObj.on('dragend', function(e){
    const pos = e.target.getLatLng();
    updateDeliveryMapInfo(pos.lat, pos.lng);
  });

  deliveryMapObj.on('click', function(e){
    deliveryMarkerObj.setLatLng(e.latlng);
    updateDeliveryMapInfo(e.latlng.lat, e.latlng.lng);
  });

  updateDeliveryMapInfo(startLat, startLng);
}

function updateDeliveryMapInfo(lat, lng) {
  deliveryLat = lat;
  deliveryLng = lng;
  const outletLat = Number(deliveryConfig.outletLat) || -6.9175;
  const outletLng = Number(deliveryConfig.outletLng) || 106.9275;
  deliveryDistanceKm = haversineDistanceKmJS(outletLat, outletLng, lat, lng);
  
  const distEl = document.getElementById('deliveryDistanceText');
  const badgeEl = document.getElementById('mapDistanceBadge');
  const statusEl = document.getElementById('deliveryRadiusStatus');
  const feeEl = document.getElementById('deliveryFeeText');

  if (distEl) distEl.textContent = deliveryDistanceKm.toFixed(2) + ' km';
  if (badgeEl) badgeEl.textContent = deliveryDistanceKm.toFixed(2) + ' km';

  if (deliveryDistanceKm > deliveryConfig.maxRadius) {
    if (statusEl) {
      statusEl.textContent = `Melebihi batas radius maksimal (${deliveryConfig.maxRadius} km)`;
      statusEl.style.color = '#ff2d55';
    }
    if (badgeEl) badgeEl.style.color = '#ff2d55';
    if (feeEl) feeEl.textContent = 'Di Luar Jangkauan';
    deliveryFee = 0;
  } else {
    if (statusEl) {
      statusEl.textContent = `Dalam radius pengantaran (maks. ${deliveryConfig.maxRadius} km)`;
      statusEl.style.color = '#34d399';
    }
    if (badgeEl) badgeEl.style.color = '#34d399';
    const subtotal = currentCartTotal();
    deliveryFee = calculateDeliveryFeeJS(subtotal, deliveryDistanceKm);
    if (feeEl) feeEl.textContent = deliveryFee === 0 ? 'GRATIS' : rupiah(deliveryFee);
  }
  updatePickupSummary();
  renderCart();
}

function haversineDistanceKmJS(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return Math.round((R * c) * 100) / 100;
}

function calculateDeliveryFeeJS(subtotal, distKm) {
  const freeAbove = Number(deliveryConfig.freeAbove || 0);
  if (freeAbove > 0 && subtotal >= freeAbove) return 0;
  
  const freeKmLimit = Number(deliveryConfig.freeKmLimit || 2);
  if (distKm <= freeKmLimit) return 0; // Jarak di bawah batas gratis

  const model = deliveryConfig.feeModel || 'per_km';
  const minFee = Number(deliveryConfig.minFee || 5000);
  if (model === 'flat') return Math.max(minFee, Number(deliveryConfig.flatFee || 5000));
  
  // per_km model (hitung selisih jarak)
  const perKm = Number(deliveryConfig.perKmFee || 3000);
  const excessKm = distKm - freeKmLimit;
  return Math.max(minFee, Math.ceil(excessKm * perKm));
}

function searchDeliveryAddress() {
  const q = document.getElementById('deliverySearchInput')?.value?.trim();
  if (!q) return;
  const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&countrycodes=id&limit=5`;
  fetch(url)
    .then(r => r.json())
    .then(data => {
      const box = document.getElementById('deliverySearchResults');
      if (!box) return;
      box.innerHTML = '';
      if (!data || !data.length) {
        box.innerHTML = '<div style="padding:8px 12px; font-size:12px; color:var(--dp-muted);">Alamat tidak ditemukan. Coba kata kunci lain atau geser pin langsung di peta.</div>';
        box.style.display = 'block';
        return;
      }
      data.forEach(item => {
        const div = document.createElement('div');
        div.className = 'fo-search-item';
        div.textContent = item.display_name;
        div.onclick = () => {
          box.style.display = 'none';
          const lat = parseFloat(item.lat);
          const lon = parseFloat(item.lon);
          if (deliveryMapObj && deliveryMarkerObj) {
            deliveryMapObj.setView([lat, lon], 16);
            deliveryMarkerObj.setLatLng([lat, lon]);
            updateDeliveryMapInfo(lat, lon);
          }
        };
        box.appendChild(div);
      });
      box.style.display = 'block';
    })
    .catch(() => {});
}

function useCurrentDeviceLocation() {
  const btn = document.getElementById('btnUseGps');
  if (!navigator.geolocation) {
    alert('Perangkat atau browser Anda tidak mendukung fitur GPS.');
    return;
  }
  const origText = btn ? btn.innerHTML : '🎯 Gunakan Lokasi Saat Ini (GPS)';
  if (btn) btn.innerHTML = '⏳ Mengambil Titik GPS...';

  navigator.geolocation.getCurrentPosition(
    position => {
      const lat = position.coords.latitude;
      const lon = position.coords.longitude;
      if (deliveryMapObj && deliveryMarkerObj) {
        deliveryMapObj.setView([lat, lon], 17);
        deliveryMarkerObj.setLatLng([lat, lon]);
        updateDeliveryMapInfo(lat, lon);
      }
      if (btn) btn.innerHTML = origText;

      // Reverse geocode via Nominatim untuk mengisi alamat lengkap
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`)
        .then(r => r.json())
        .then(data => {
          if (data && data.display_name) {
            const addrEl = document.getElementById('deliveryAddressInput');
            if (addrEl && (!addrEl.value || addrEl.value.trim() === '')) {
              addrEl.value = data.display_name;
            }
          }
        })
        .catch(() => {});
    },
    error => {
      if (btn) btn.innerHTML = origText;
      if (error.code === 1) {
        alert('Izin akses lokasi ditolak. Silakan aktifkan izin lokasi/GPS pada browser atau pengaturan HP Anda.');
      } else if (error.code === 2) {
        alert('Posisi GPS tidak dapat ditentukan. Pastikan GPS menyala di tempat terbuka.');
      } else if (error.code === 3) {
        alert('Waktu pencarian lokasi habis (timeout). Silakan coba klik tombol GPS lagi.');
      } else {
        alert('Gagal mendeteksi koordinat GPS.');
      }
    },
    { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
  );
}

function rupiah(n){ return 'Rp'+Math.round(Number(n||0)).toLocaleString('id-ID'); }
function pad(n){ return String(n).padStart(2,'0'); }
function parseHM(v){ const p=String(v||'00:00').split(':'); return {h:Number(p[0]||0),m:Number(p[1]||0)}; }
function minutesOf(v){ const p=parseHM(v); return p.h*60+p.m; }
function toHM(mins){ mins=Math.max(0,Math.round(mins)); return pad(Math.floor(mins/60))+':'+pad(mins%60); }
function escapeHtml(v){ return String(v||'').replace(/[&<>'"]/g, s=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[s])); }
function getPriceData(partId, style, sauceId, rice){
  const pm = (((priceMap.chicken||{})[partId]||{})[style]||{});
  const sauceKey = style==='sauce' ? String(sauceId||0) : '0';
  const row = ((pm[sauceKey]||{})[rice]);
  if(row) return row;
  return {price:0,hpp:0,name:'Item'};
}
function getSauceName(sauceId){ const f=sauces.find(s=>Number(s.id)===Number(sauceId)); return f?f.name:'Saus'; }
function buildChickenText(item){
  if(item.style==='sauce') return `${item.part_name} ${getSauceName(item.sauce_id)} ${item.with_rice?'+ Nasi':'Tanpa Nasi'}`;
  return `${item.part_name} Original ${item.with_rice?'+ Nasi':'Tanpa Nasi'}`;
}

function buildPickupOptions(){
  const dateInput=document.getElementById('pickupDate');
  const timeSelect=document.getElementById('pickupTime');
  const hint=document.getElementById('pickupTimeHint');
  const selectedDate=dateInput.value || today;
  const nowMin=minutesOf(serverNowTime);
  const closeMin=21*60;
  let options=[];
  let autoDate=selectedDate;
  let message='';

  if(selectedDate===today){
    if(nowMin>=closeMin){
      options=[];
      message='Outlet sudah tutup untuk hari ini. Silakan pilih pengambilan besok mulai 09:00.';
      autoDate=tomorrow;
      dateInput.value=tomorrow;
    }else{
      const first=nowMin+30;
      if(first>closeMin){
        message='Waktu tersisa kurang dari 30 menit. Opsi pengambilan dialihkan ke besok pukul 09:00-21:00.';
        autoDate=tomorrow;
        dateInput.value=tomorrow;
      }else{
        options.push(toHM(first));
        let next=first+60;
        while(next<=closeMin){ options.push(toHM(next)); next+=60; }
        if(options.length && options[options.length-1]!== '21:00') options.push('21:00');
        message='Opsi pickup hari ini dimulai 30 menit dari waktu order, lalu berinterval 1 jam sampai 21:00.';
      }
    }
  }

  if(autoDate!==today){
    options=[];
    for(let h=9;h<=21;h++){ options.push(pad(h)+':00'); }
    if(serverNowTime >= '21:00' && selectedDate===today){
      const placeholder=document.createElement('option');
      placeholder.value=''; placeholder.textContent='Outlet sudah tutup • pilih besok';
      placeholder.disabled=true;
      timeSelect.innerHTML='';
      timeSelect.appendChild(placeholder);
    }
    message = selectedDate===today && serverNowTime >= '21:00'
      ? 'Outlet sudah tutup. Slot pengambilan berikutnya tersedia besok mulai 09:00.'
      : 'Untuk tanggal ini, pickup tersedia per jam dari 09:00 sampai 21:00.';
  }

  const current=timeSelect.value;
  timeSelect.innerHTML='';
  options.forEach(t=>{
    const op=document.createElement('option');
    op.value=t; op.textContent=t+' WIB';
    timeSelect.appendChild(op);
  });
  if(!options.length){
    const op=document.createElement('option');
    op.value=''; op.textContent='Outlet sudah tutup';
    timeSelect.appendChild(op);
  }else if(options.includes(current)){
    timeSelect.value=current;
  }else{
    timeSelect.value=options[0];
  }
  hint.textContent=message;
}

function renderCart(){
  cart = window.cart || cart || [];
  window.cart = cart;
  const list = document.getElementById('cartList') || document.getElementById('cartListContainer');
  if(list) list.innerHTML = '';
  const sideList = document.getElementById('sideOrderItems');
  if(sideList) sideList.innerHTML = '';

  let total=0, count=0;
  cart.forEach((it,i)=>{
    total += Number(it.price||0) * Number(it.qty||1);
    count += Number(it.qty||1);
    const name = it.type==='chicken' ? buildChickenText(it) : it.name;
    const div=document.createElement('div');
    div.className='fo-cart-item';
    div.innerHTML=`<div><b>${escapeHtml(name)}</b><br><small>${rupiah(it.price)} x ${it.qty||1}</small></div><div class="fo-qty"><button type="button" onclick="chgQty(${i},-1)">-</button><b>${it.qty||1}</b><button type="button" onclick="chgQty(${i},1)">+</button></div>`;
    if(list) list.appendChild(div);

    if(sideList){
      const sideDiv=document.createElement('div');
      sideDiv.className='fo-cart-item';
      sideDiv.innerHTML=`<div class="fo-cart-item-left"><div class="fo-cart-item-text"><b>${escapeHtml(name)}</b><small>${rupiah(it.price)}</small></div></div><div class="fo-qty"><button type="button" onclick="chgQty(${i},-1)">-</button><span>${it.qty||1}</span><button type="button" onclick="chgQty(${i},1)">+</button></div>`;
      sideList.appendChild(sideDiv);
    }
  });
  if(!cart.length){
    if(list) list.innerHTML='<div class="fo-info">Keranjang masih kosong.</div>';
    if(sideList){
      sideList.innerHTML='<div class="fo-order-empty"><div class="icon">🛒</div><p>Belum ada menu dipilih</p><small>Klik menu di kiri untuk menambah pesanan</small></div>';
    }
  }
  const ccEl = document.getElementById('cartCount'); if(ccEl) ccEl.textContent=count;
  const ic2 = document.getElementById('itemCount2'); if(ic2) ic2.textContent=count;
  const finalTotal = total + (selectedPickupType === 'delivery' ? deliveryFee : 0);
  const ttEl = document.getElementById('totalText'); if(ttEl) ttEl.textContent=rupiah(finalTotal);
  const tt2 = document.getElementById('totalText2'); if(tt2) tt2.textContent=rupiah(finalTotal);
  const sideBadge=document.getElementById('sideCartBadge'); if(sideBadge) sideBadge.textContent=count;
  const sideTotal=document.getElementById('sideTotalText'); if(sideTotal) sideTotal.textContent=rupiah(finalTotal);
  const sideCount=document.getElementById('sideCountText'); if(sideCount) sideCount.textContent=count+' item';

  const checkoutTotal=document.getElementById('checkoutTotalText');
  if(checkoutTotal) checkoutTotal.textContent=rupiah(finalTotal);
  const checkoutFeeRow = document.getElementById('checkoutFeeDetailRow');
  const checkoutFeeText = document.getElementById('checkoutFeeDetailText');
  if(checkoutFeeRow && checkoutFeeText){
    if(selectedPickupType === 'delivery'){
      checkoutFeeRow.style.display = 'flex';
      checkoutFeeText.textContent = deliveryFee === 0 ? 'GRATIS' : rupiah(deliveryFee);
    } else {
      checkoutFeeRow.style.display = 'none';
    }
  }
  const checkoutDetail=document.getElementById('checkoutTotalDetail');
  if(checkoutDetail){
    const names=cart.slice(0,4).map(it=>it.type==='chicken'?buildChickenText(it):it.name);
    checkoutDetail.textContent = count ? (names.join(', ')+(count>4?' + '+(count-4)+' item lainnya':'')) : 'Keranjang masih kosong.';
  }
  const pickupTotal=document.getElementById('pickupSummaryTotal');
  if(pickupTotal) pickupTotal.textContent=rupiah(finalTotal);
  updateFooterDetail(finalTotal,count);
  const aiPanel=document.getElementById('aiPanel'); if(aiPanel && aiPanel.classList.contains('show')) updateAiPanelContent();
}
function updateFooterDetail(total,count){
  const el=document.getElementById('footerDetail'); if(!el) return;
  if(!count){ el.textContent='Keranjang masih kosong.'; return; }
  const names=cart.slice(0,2).map(it=>it.type==='chicken'?buildChickenText(it):it.name);
  const more=count>2?` + ${count-2} item lainnya`:'';
  el.textContent=`${count} item • ${names.join(', ')}${more} • Pilih Lanjut Bayar untuk checkout.`;
}
function showAddedModal(name){
  foPlay('foVoiceBerhasil');
  lastAddedName=name||'Menu';
  const t=document.getElementById('addedItemText'); if(t) t.textContent=`${lastAddedName} sudah masuk keranjang.`;
  const m=document.getElementById('addedModal'); if(m) m.classList.add('show');
}
function closeAddedModal(){ const m=document.getElementById('addedModal'); if(m) m.classList.remove('show'); setTimeout(showAiNudge,360); }
function goToCheckoutFromAdded(){ closeAddedModal(); openCheckout(); }
function chgQty(i,d){ cart = window.cart || cart || []; cart[i].qty=(cart[i].qty||1)+d; if(cart[i].qty<=0) cart.splice(i,1); window.cart = cart; renderCart(); }
function openCheckout(skipPickupConfirm=false){
  cart = window.cart || cart || [];
  window.cart = cart;
  if(!cart.length){
    alert('Keranjang masih kosong (dari JS openCheckout).');
    const a=foPlay('foVoiceEmptyCart');
    if(!a){
      speakAiText('Maaf Kakak, keranjangnya masih kosong, silahkan pilih menu dulu', ()=>{});
    }
    return;
  }
  if(!skipPickupConfirm){ showPickupConfirm(); return; }
  syncCustomerFields('top');
  document.getElementById('checkoutDrawer').classList.add('show');
  renderCart();
  const pm = document.getElementById('paymentMethod')?.value || 'qris';
  setPayment(pm);
  if(cart.length) playCheckoutTotalFlow();
}
function closeCheckout(){ document.getElementById('checkoutDrawer').classList.remove('show'); }
function addSimple(type,obj){ cart.push({type:type,id:obj.id,name:obj.name,price:Number(obj.price||0),qty:1}); renderCart(); showAddedModal(obj.name || 'Menu'); }
function addMenuItem(obj){ cart.push({type:'menu_item',id:Number(obj.id||0),menu_item_id:Number(obj.id||0),name:obj.name,price:Number(obj.price||0),hpp:Number(obj.hpp||0),qty:1}); renderCart(); showAddedModal(obj.name || 'Ayam 1 Ekor'); }
function addAddon(obj){ cart.push({type:'add_on',kind:obj.kind||'addon',sauce_id:obj.sauce_id||null,name:obj.name,price:Number(obj.price||0),hpp:Number(obj.hpp||0),qty:1}); renderCart(); showAddedModal(obj.name || 'Tambahan'); }
function addCombo(obj){ cart.push({type:'combo',id:Number(obj.id||0),name:obj.name,price:Number(obj.price||0),hpp:Number(obj.hpp||0),qty:1}); renderCart(); showAddedModal(obj.name || 'Combo Promo'); }

function initChickenCards(){
  document.querySelectorAll('.chicken-card').forEach(card=>{
    let style='original', rice=0, sauceId=Number(card.querySelector('.fo-sauce-card')?.dataset.sauceId||0);
    const partId=Number(card.dataset.partId||0);
    const sync=()=>{
      card.querySelectorAll('[data-style]').forEach(b=>b.classList.toggle('active',b.dataset.style===style));
      card.querySelectorAll('[data-rice]').forEach(b=>b.classList.toggle('active',Number(b.dataset.rice)===rice));
      const sauceWrap=card.querySelector('.fo-sauce-wrap');
      if(sauceWrap) sauceWrap.classList.toggle('active',style==='sauce');
      card.querySelectorAll('.fo-sauce-card').forEach(b=>b.classList.toggle('active',Number(b.dataset.sauceId)===Number(sauceId)));
      const pr=getPriceData(String(partId),style,String(style==='sauce'?sauceId:0),String(rice));
      card.querySelector('.pricePreview').textContent=rupiah(pr.price||0);
      card.querySelector('.namePreview').textContent=pr.name || buildChickenText({part_name:card.dataset.partName,style:style,sauce_id:sauceId,with_rice:rice});
    };
    card.querySelectorAll('[data-style]').forEach(b=>b.addEventListener('click',()=>{style=b.dataset.style;sync();}));
    card.querySelectorAll('[data-rice]').forEach(b=>b.addEventListener('click',()=>{rice=Number(b.dataset.rice||0);sync();}));
    card.querySelectorAll('.fo-sauce-card').forEach(b=>b.addEventListener('click',()=>{sauceId=Number(b.dataset.sauceId||0);sync();}));
    card.querySelector('.addChicken').addEventListener('click',()=>{
      const pr=getPriceData(String(partId),style,String(style==='sauce'?sauceId:0),String(rice));
      const item={type:'chicken',part_id:partId,part_name:card.dataset.partName,style:style,sauce_id:style==='sauce'?sauceId:null,with_rice:rice,qty:1,price:Number(pr.price||0)};
      cart.push(item);
      renderCart();
      showAddedModal(buildChickenText(item));
    });
    sync();
  });
}

let foAudioSettings={
  music: localStorage.getItem('lumero_online_music') !== 'off',
  guide: localStorage.getItem('lumero_online_guide_voice') !== 'off'
};

function isMusicOn(){ return foAudioSettings.music !== false; }
function isGuideOn(){ return foAudioSettings.guide !== false; }

function updateAudioToggleButtons(){
  const musicBtn=document.getElementById('toggleMusicBtn');
  const guideBtn=document.getElementById('toggleGuideBtn');
  if(musicBtn){
    musicBtn.classList.toggle('active',isMusicOn());
    musicBtn.classList.toggle('off',!isMusicOn());
    musicBtn.innerHTML=isMusicOn()?'<span>♪</span> Musik ON':'<span>♪</span> Musik OFF';
  }
  if(guideBtn){
    guideBtn.classList.toggle('active',isGuideOn());
    guideBtn.classList.toggle('off',!isGuideOn());
    guideBtn.innerHTML=isGuideOn()?'<span>🔊</span> Suara ON':'<span>🔇</span> Suara OFF';
  }
}

function stopBgm(){
  const bgm=document.getElementById('foBgm');
  if(!bgm) return;
  try{ bgm.pause(); }catch(e){}
}
function startBgm(){
  const bgm=document.getElementById('foBgm');
  if(!bgm) return;
  if(!isMusicOn()){ stopBgm(); return; }
  try{ bgm.volume=0.32; const p=bgm.play(); if(p&&p.catch) p.catch(()=>{}); }catch(e){}
}
function toggleMusicSetting(){
  foAudioSettings.music=!isMusicOn();
  localStorage.setItem('lumero_online_music', foAudioSettings.music ? 'on' : 'off');
  if(foAudioSettings.music) startBgm(); else stopBgm();
  updateAudioToggleButtons();
}
function toggleGuideSetting(){
  foAudioSettings.guide=!isGuideOn();
  localStorage.setItem('lumero_online_guide_voice', foAudioSettings.guide ? 'on' : 'off');
  if(!foAudioSettings.guide){ stopAiRecommendationAudio(); stopAllVoices(); }
  updateAudioToggleButtons();
}

function stopAllVoices(){
  document.querySelectorAll('audio.fo-voice').forEach(a=>{ try{ a.pause(); a.currentTime=0; a.onended=null; }catch(e){} });
  try{ if('speechSynthesis' in window) window.speechSynthesis.cancel(); }catch(e){}
}
function foPlay(id){
  if(!isGuideOn()) return null;
  stopAiRecommendationAudio();
  stopAllVoices();
  startBgm();
  const a=document.getElementById(id);
  if(!a) return null;
  try{ a.currentTime=0; a.volume=1; const p=a.play(); if(p&&p.catch) p.catch(()=>{}); return a; }catch(e){ return null; }
}
function currentCartTotal(){ cart = window.cart || cart || []; return cart.reduce((sum,it)=>sum+(Number(it.price||0)*Number(it.qty||1)),0); }

let pickupConfirmedForSession = false;

function normalizePhoneClient(v){
  v=String(v||'').replace(/[^0-9+]/g,'');
  if(v.indexOf('+62')===0) v='0'+v.slice(3);
  if(v.indexOf('62')===0) v='0'+v.slice(2);
  return v;
}
function syncCustomerFields(source){
  const topName=document.getElementById('customerNameTop');
  const topPhone=document.getElementById('customerPhoneTop');
  const name=document.getElementById('customerName');
  const phone=document.getElementById('customerPhone');
  if(source==='checkout'){
    if(topName && name) topName.value=name.value;
    if(topPhone && phone) topPhone.value=phone.value;
  }else{
    if(name && topName) name.value=topName.value;
    if(phone && topPhone) phone.value=topPhone.value;
    const pd = document.getElementById('pickupDate');
    const pdd = document.getElementById('pickupDateDrawer');
    if(pd && pdd) pdd.value = pd.value;
    const pt = document.getElementById('pickupTime');
    const ptd = document.getElementById('pickupTimeDrawer');
    if(pt && ptd){ ptd.innerHTML = pt.innerHTML; ptd.value = pt.value; }
    const da = document.getElementById('deliveryAddressInput');
    const dad = document.getElementById('deliveryAddressInputDrawer');
    if(da && dad) dad.value = da.value;
  }
  try{
    if(topPhone && topPhone.value) localStorage.setItem('lumero_customer_phone', normalizePhoneClient(topPhone.value));
    if(topName && topName.value) localStorage.setItem('lumero_customer_name', topName.value);
  }catch(e){}
}
function applyCustomerData(name, phone){
  const topName=document.getElementById('customerNameTop');
  const topPhone=document.getElementById('customerPhoneTop');
  const nameInput=document.getElementById('customerName');
  const phoneInput=document.getElementById('customerPhone');
  if(phone && topPhone) topPhone.value=phone;
  if(name && topName) topName.value=name;
  if(phone && phoneInput) phoneInput.value=phone;
  if(name && nameInput) nameInput.value=name;
}
function lookupCustomerByPhone(phone){
  phone=normalizePhoneClient(phone);
  if(phone.length<8) return;
  fetch('./index.php?lookup_phone='+encodeURIComponent(phone), {headers:{'Accept':'application/json'}})
    .then(r=>r.json())
    .then(data=>{
      const info=document.getElementById('customerFoundInfo');
      if(data && data.found){
        applyCustomerData(data.name||'', data.phone||phone);
        if(info){ info.textContent='Data pelanggan ditemukan. Selamat datang kembali, '+(data.name||'Pelanggan')+'.'; info.classList.add('show'); }
      }else{
        if(info){ info.textContent='Pelanggan baru. Isi nama sekali saja, berikutnya cukup nomor HP.'; info.classList.add('show'); }
        const topPhone=document.getElementById('customerPhoneTop'); if(topPhone) topPhone.value=phone;
        const phoneInput=document.getElementById('customerPhone'); if(phoneInput) phoneInput.value=phone;
      }
    }).catch(()=>{});
}

function setVideoPhoneInfo(message,type){
  const info=document.getElementById('videoPhoneInfo');
  if(info){
    info.textContent=message;
    info.classList.remove('ok','warn','err');
    if(type) info.classList.add(type);
  }
}
function lookupCustomerFromVideo(showAlert=false){
  const input=document.getElementById('videoPhoneInput');
  const phone=normalizePhoneClient(input?.value || '');
  if(!phone || phone.length<8){
    setVideoPhoneInfo('Masukkan nomor WhatsApp untuk mempercepat order.','warn');
    if(showAlert) foPlay('foVoiceMaaf');
    return Promise.resolve(false);
  }
  if(input) input.value=phone;
  setVideoPhoneInfo('Nomor tersimpan untuk order ini.','ok');

  const url='./index.php?lookup_phone='+encodeURIComponent(phone)+'&remember_phone=1&_='+(Date.now());
  return fetch(url, {headers:{'Accept':'application/json'}, credentials:'same-origin', cache:'no-store'})
    .then(r=>{
      if(!r.ok) throw new Error('HTTP '+r.status);
      return r.json();
    })
    .then(data=>{
      if(data && data.found){
        applyCustomerData(data.name||'', data.phone||phone);
        try{ localStorage.setItem('lumero_customer_phone', data.phone||phone); localStorage.setItem('lumero_customer_name', data.name||''); }catch(e){}
        setVideoPhoneInfo('Nomor dikenali. Nama akan otomatis diisi.','ok');
        return true;
      }
      applyCustomerData('', (data && data.phone) || phone);
      try{ localStorage.setItem('lumero_customer_phone', (data && data.phone) || phone); }catch(e){}
      setVideoPhoneInfo('Nomor tersimpan. Isi nama saat atur pengambilan.','warn');
      return false;
    })
    .catch(()=>{
      applyCustomerData('', phone);
      try{ localStorage.setItem('lumero_customer_phone', phone); }catch(e){}
      setVideoPhoneInfo('Nomor dipakai untuk order ini.','warn');
      return false;
    });
}

function initCustomerMemory(){
  try{
    const p=localStorage.getItem('lumero_customer_phone')||'';
    const n=localStorage.getItem('lumero_customer_name')||'';
    applyCustomerData(n,p);
    const videoPhone=document.getElementById('videoPhoneInput'); if(videoPhone && p){ videoPhone.value=p; setVideoPhoneInfo('Nomor terakhir sudah terisi.','ok'); }
    if(p) lookupCustomerByPhone(p);
  }catch(e){}
  ['customerPhoneTop','customerPhone'].forEach(id=>{
    const el=document.getElementById(id);
    if(el) el.addEventListener('blur',()=>lookupCustomerByPhone(el.value));
  });
  ['customerNameTop','customerPhoneTop'].forEach(id=>{
    const el=document.getElementById(id);
    if(el) el.addEventListener('input',()=>syncCustomerFields('top'));
  });
  ['customerName','customerPhone'].forEach(id=>{
    const el=document.getElementById(id);
    if(el) el.addEventListener('input',()=>syncCustomerFields('checkout'));
  });
}

function updatePickupSummary(){
  const typeEl=document.getElementById('pickupSummaryType');
  const dateEl=document.getElementById('pickupSummaryDate');
  const timeEl=document.getElementById('pickupSummaryTime');
  const totalEl=document.getElementById('pickupSummaryTotal');
  if(typeEl) typeEl.textContent=selectedPickupType==='outlet'?'Ambil di Outlet':'Delivery (Diantar Kurir)';
  if(dateEl) dateEl.textContent=document.getElementById('pickupDate')?.value || '-';
  if(timeEl) timeEl.textContent=(document.getElementById('pickupTime')?.value || '-')+' WIB';
  
  const feeRow = document.getElementById('pickupSummaryFeeRow');
  const feeEl = document.getElementById('pickupSummaryFee');
  if(feeRow && feeEl) {
    if(selectedPickupType === 'delivery') {
      feeRow.style.display = '';
      feeEl.textContent = deliveryFee === 0 ? 'GRATIS' : rupiah(deliveryFee);
    } else {
      feeRow.style.display = 'none';
    }
  }
  const finalTotal = currentCartTotal() + (selectedPickupType === 'delivery' ? deliveryFee : 0);
  if(totalEl) totalEl.textContent=rupiah(finalTotal);
}
function showPickupConfirm(){
  syncCustomerFields('top');
  cart = window.cart || cart || [];
  window.cart = cart;
  if(!cart.length){ alert('Keranjang masih kosong (dari JS showPickupConfirm).'); return; }
  const m=document.getElementById('pickupConfirmModal');
  buildPickupOptions();
  setPickupType(selectedPickupType);
  renderCart();
  updatePickupSummary();
  if(m) m.classList.add('show');
  setTimeout(()=>{ const p=document.getElementById('customerPhoneTop'); if(p && !p.value) p.focus(); },120);
  if(selectedPickupType === 'delivery'){
    setTimeout(()=>{ initDeliveryMap(); }, 180);
  }
  foPlay('foVoiceWaktuAmbil');
}
function closePickupConfirm(){ const m=document.getElementById('pickupConfirmModal'); if(m) m.classList.remove('show'); }
function continueToPayment(){
  syncCustomerFields('top');
  const nameVal=(document.getElementById('customerNameTop')?.value || '').trim();
  const phoneVal=normalizePhoneClient(document.getElementById('customerPhoneTop')?.value || '');
  if(!nameVal || !phoneVal){ foPlay('foVoiceMaaf'); alert('Mohon isi nama dan nomor WhatsApp terlebih dahulu.'); return; }
  if(selectedPickupType === 'outlet' && !document.getElementById('pickupTime')?.value){ alert('Jam pengambilan belum tersedia. Silakan pilih tanggal yang valid.'); return; }
  if(selectedPickupType === 'delivery'){
    const addr = document.getElementById('deliveryAddressInput')?.value?.trim() || '';
    if(!addr || deliveryLat == 0 || deliveryLng == 0){
      alert('Mohon lengkapi titik lokasi pada peta dan alamat lengkap pengantaran.');
      return;
    }
    if(deliveryDistanceKm > deliveryConfig.maxRadius){
      alert(`Jarak pengantaran (${deliveryDistanceKm} km) melebihi batas maksimal (${deliveryConfig.maxRadius} km).`);
      return;
    }
  }
  closePickupConfirm();
  pickupConfirmedForSession = true;
  openCheckout(true);
}


function terbilangIndonesia(n){
  n=Math.floor(Math.abs(Number(n)||0));
  const angka=['','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
  if(n<12) return angka[n] || 'nol';
  if(n<20) return terbilangIndonesia(n-10)+' belas';
  if(n<100) return (terbilangIndonesia(Math.floor(n/10))+' puluh '+terbilangIndonesia(n%10)).trim();
  if(n<200) return ('seratus '+terbilangIndonesia(n-100)).trim();
  if(n<1000) return (terbilangIndonesia(Math.floor(n/100))+' ratus '+terbilangIndonesia(n%100)).trim();
  if(n<2000) return ('seribu '+terbilangIndonesia(n-1000)).trim();
  if(n<1000000) return (terbilangIndonesia(Math.floor(n/1000))+' ribu '+terbilangIndonesia(n%1000)).trim();
  if(n<1000000000) return (terbilangIndonesia(Math.floor(n/1000000))+' juta '+terbilangIndonesia(n%1000000)).trim();
  return n.toLocaleString('id-ID');
}
let foIndonesianVoiceCache=null;
let foVoiceLoadPromise=null;
let foSpeechIsRunning=false;

const FO_SERVER_TTS_URL = './tts-total.php';
const FO_NUMBER_AUDIO_BASE = '<?=fo_e($freeOrderVoiceBase)?>nominal/id/';

function getVoiceScore(v){
  const lang=String(v.lang || '').toLowerCase();
  const name=String(v.name || '').toLowerCase();
  let score=0;
  if(lang==='id-id') score+=100;
  else if(lang.startsWith('id')) score+=82;
  if(/bahasa|indonesia|indonesian/.test(name)) score+=42;
  if(/google/.test(name)) score+=16;
  if(/microsoft/.test(name)) score+=14;
  if(/gadis|andika|damayanti|id-id/.test(name)) score+=12;
  if(v.localService) score+=4;
  return score;
}

function getIndonesianVoiceFromList(voices){
  voices = Array.isArray(voices) ? voices : [];
  const ranked = voices
    .map(v=>({voice:v, score:getVoiceScore(v), lang:String(v.lang||'').toLowerCase(), name:String(v.name||'').toLowerCase()}))
    .filter(x=>x.score>=80 || x.lang==='id-id' || x.lang.startsWith('id') || /bahasa|indonesia|indonesian/.test(x.name))
    .sort((a,b)=>b.score-a.score);
  return ranked.length ? ranked[0].voice : null;
}

function getIndonesianVoice(){
  if(!('speechSynthesis' in window)) return null;
  if(foIndonesianVoiceCache) return foIndonesianVoiceCache;
  const voices = window.speechSynthesis.getVoices ? window.speechSynthesis.getVoices() : [];
  foIndonesianVoiceCache = getIndonesianVoiceFromList(voices);
  return foIndonesianVoiceCache;
}

function loadIndonesianVoice(){
  if(!('speechSynthesis' in window)) return Promise.resolve(null);
  if(foIndonesianVoiceCache) return Promise.resolve(foIndonesianVoiceCache);
  if(foVoiceLoadPromise) return foVoiceLoadPromise;

  foVoiceLoadPromise = new Promise(resolve=>{
    let tries=0;
    let resolved=false;
    const finish=(voice)=>{
      if(resolved) return;
      resolved=true;
      if(voice) foIndonesianVoiceCache=voice;
      resolve(voice || null);
    };
    const check=()=>{
      tries++;
      const voices = window.speechSynthesis.getVoices ? window.speechSynthesis.getVoices() : [];
      const voice = getIndonesianVoiceFromList(voices);
      if(voice || tries>=18) finish(voice);
      else setTimeout(check,180);
    };
    try{
      const oldHandler = window.speechSynthesis.onvoiceschanged;
      window.speechSynthesis.onvoiceschanged=()=>{
        if(typeof oldHandler==='function') oldHandler();
        const voices = window.speechSynthesis.getVoices ? window.speechSynthesis.getVoices() : [];
        const voice = getIndonesianVoiceFromList(voices);
        if(voice) finish(voice);
      };
      check();
    }catch(e){ finish(null); }
  });
  return foVoiceLoadPromise;
}

if('speechSynthesis' in window){
  try{
    window.speechSynthesis.cancel();
    window.speechSynthesis.getVoices();
    loadIndonesianVoice();
  }catch(e){}
}

function totalToAudioTokens(n){
  n=Math.floor(Math.abs(Number(n)||0));
  const basic=['nol','satu','dua','tiga','empat','lima','enam','tujuh','delapan','sembilan','sepuluh','sebelas'];
  if(n<12) return [basic[n]];
  if(n<20) return [...totalToAudioTokens(n-10),'belas'];
  if(n<100){ const out=[...totalToAudioTokens(Math.floor(n/10)),'puluh']; if(n%10) out.push(...totalToAudioTokens(n%10)); return out; }
  if(n<200){ const out=['seratus']; if(n-100) out.push(...totalToAudioTokens(n-100)); return out; }
  if(n<1000){ const out=[...totalToAudioTokens(Math.floor(n/100)),'ratus']; if(n%100) out.push(...totalToAudioTokens(n%100)); return out; }
  if(n<2000){ const out=['seribu']; if(n-1000) out.push(...totalToAudioTokens(n-1000)); return out; }
  if(n<1000000){ const out=[...totalToAudioTokens(Math.floor(n/1000)),'ribu']; if(n%1000) out.push(...totalToAudioTokens(n%1000)); return out; }
  if(n<1000000000){ const out=[...totalToAudioTokens(Math.floor(n/1000000)),'juta']; if(n%1000000) out.push(...totalToAudioTokens(n%1000000)); return out; }
  return [];
}

function playAudioUrl(src, timeout=20000){
  return new Promise((resolve,reject)=>{
    if(!isGuideOn()) return reject(new Error('guide off'));
    const a=new Audio(src);
    let done=false;
    const finish=()=>{ if(done) return; done=true; resolve(true); };
    const fail=()=>{ if(done) return; done=true; reject(new Error('audio failed')); };
    a.preload='auto';
    a.volume=1;
    a.onended=finish;
    a.onerror=fail;
    try{
      const p=a.play();
      if(p&&p.catch) p.catch(fail);
      setTimeout(()=>{ if(!done) finish(); }, timeout);
    }catch(e){ fail(); }
  });
}

function playServerSideTotalTTS(total){
  if(!isGuideOn()) return Promise.reject(new Error('guide off'));
  const url=FO_SERVER_TTS_URL + '?total=' + encodeURIComponent(total) + '&v=' + Date.now();
  return playAudioUrl(url, 20000);
}

function playAudioToken(src, timeout=1800){
  return playAudioUrl(src, timeout);
}

async function speakTotalWithAudioClips(total){
  if(!isGuideOn()) throw new Error('guide off');
  const tokens=['jumlah','yang','harus','dibayar','adalah',...totalToAudioTokens(total),'rupiah'];
  if(!tokens.length) throw new Error('token kosong');
  for(const token of tokens){
    if(!isGuideOn()) throw new Error('guide off');
    const safe=String(token).toLowerCase().replace(/[^a-z0-9_-]/g,'');
    await playAudioToken(FO_NUMBER_AUDIO_BASE + safe + '.mp3');
    await new Promise(r=>setTimeout(r,60));
  }
  return true;
}

function speakIndonesianClearly(text, onDone){
  if(!isGuideOn()){ if(typeof onDone==='function') onDone(); return; }
  if(!('speechSynthesis' in window)){ if(typeof onDone==='function') onDone(); return; }

  loadIndonesianVoice().then(voice=>{
    if(!isGuideOn()){ if(typeof onDone==='function') onDone(); return; }
    try{
      window.speechSynthesis.cancel();
      foSpeechIsRunning=true;

      const u=new SpeechSynthesisUtterance(text);
      u.lang='id-ID';
      if(voice) u.voice=voice;
      u.volume=1;
      u.rate=0.70;
      u.pitch=1.02;

      let finished=false;
      const finish=()=>{
        if(finished) return;
        finished=true;
        foSpeechIsRunning=false;
        clearInterval(resumeTimer);
        if(typeof onDone==='function') onDone();
      };

      u.onend=finish;
      u.onerror=finish;

      const resumeTimer=setInterval(()=>{ try{ if(window.speechSynthesis.paused) window.speechSynthesis.resume(); }catch(e){} },350);

      setTimeout(()=>{
        try{
          if(!isGuideOn()){ finish(); return; }
          window.speechSynthesis.speak(u);
          window.speechSynthesis.resume();
        }catch(e){ finish(); }
      },220);

      const estimated=Math.max(2800, Math.min(13000, text.length*155));
      setTimeout(finish, estimated);
    }catch(e){
      foSpeechIsRunning=false;
      if(typeof onDone==='function') onDone();
    }
  }).catch(()=>{ if(typeof onDone==='function') onDone(); });
}

function speakTotalThenPayout(total){
  if(!isGuideOn()) return;
  total = Math.max(0, Math.round(Number(total)||0));
  const nominal = terbilangIndonesia(total).replace(/\s+/g,' ').trim();
  const text = '' + nominal + ' rupiah.';

  // Prioritas:
  // 1. Server-side TTS MP3 cached.
  // 2. Audio token lokal.
  // 3. Web Speech API fallback.
  playServerSideTotalTTS(total)
    .then(()=>foPlay('foVoicePayout'))
    .catch(()=>{
      speakTotalWithAudioClips(total)
        .then(()=>foPlay('foVoicePayout'))
        .catch(()=>speakIndonesianClearly(text, ()=>foPlay('foVoicePayout')));
    });
}


let aiNudgeTimer=null;
function showAiNudge(){
  const rec=pickAiNarrative();
  const b=document.getElementById('aiNudgeBubble');
  if(!b) return;
  const title=document.getElementById('aiNudgeTitle');
  const text=document.getElementById('aiNudgeText');
  if(title) title.textContent=rec.suggested_menu || rec.title || 'Saran Menu Lumero';
  if(text) text.textContent=(rec.scenario==='empty_cart' ? (rec.cta_text || 'Aku bantu pilihkan menu unggulan Lumero.') : (rec.cta_text || 'Aku punya saran menu yang cocok untuk melengkapi pesanan Kakak.'));
  b.classList.add('show');
  clearTimeout(aiNudgeTimer);
  aiNudgeTimer=setTimeout(()=>b.classList.remove('show'),9000);
}
function hideAiNudge(){
  const b=document.getElementById('aiNudgeBubble');
  if(b) b.classList.remove('show');
  clearTimeout(aiNudgeTimer);
}

function toggleAiPanel(force){
  const p=document.getElementById('aiPanel');
  if(!p) return;
  const show = typeof force==='boolean' ? force : !p.classList.contains('show');
  p.classList.toggle('show', show);
  if(show) updateAiPanelContent();
}
let aiRecommendationAudio=null;
function stopAiRecommendationAudio(){
  if(aiRecommendationAudio){
    try{ aiRecommendationAudio.pause(); aiRecommendationAudio.currentTime=0; }catch(e){}
    aiRecommendationAudio=null;
  }
}
function playAudioBlobExclusive(blob, timeout=30000){
  return new Promise((resolve,reject)=>{
    if(!isGuideOn()) return reject(new Error('guide off'));
    stopAllVoices();
    stopAiRecommendationAudio();
    startBgm();
    const url=URL.createObjectURL(blob);
    const a=new Audio(url);
    aiRecommendationAudio=a;
    let done=false;
    const finish=()=>{ if(done) return; done=true; try{ URL.revokeObjectURL(url); }catch(e){} aiRecommendationAudio=null; resolve(true); };
    const fail=()=>{ if(done) return; done=true; try{ URL.revokeObjectURL(url); }catch(e){} aiRecommendationAudio=null; reject(new Error('audio failed')); };
    a.preload='auto';
    a.volume=1;
    a.onended=finish;
    a.onerror=fail;
    try{
      const p=a.play();
      if(p&&p.catch) p.catch(fail);
      setTimeout(()=>{ if(!done) finish(); }, timeout);
    }catch(e){ fail(); }
  });
}
function speakAiText(text,onDone){
  if(!isGuideOn()){ if(typeof onDone==='function') onDone(); return; }
  stopAllVoices();
  stopAiRecommendationAudio();
  startBgm();

  fetch('./tts-text.php', {
    method:'POST',
    headers:{'Content-Type':'application/json','Accept':'audio/mpeg,application/json'},
    credentials:'same-origin',
    cache:'no-store',
    body:JSON.stringify({text:text})
  }).then(async r=>{
    const ct=(r.headers.get('content-type')||'').toLowerCase();
    if(!r.ok || !ct.includes('audio')) throw new Error(await r.text());
    return r.blob();
  }).then(blob=>{
    if(!blob || blob.size<100) throw new Error('Audio ElevenLabs kosong.');
    return playAudioBlobExclusive(blob, 30000);
  }).then(()=>{ if(typeof onDone==='function') onDone(); })
  .catch(err=>{
    console.warn('AI ElevenLabs TTS gagal:', err);
    if(typeof onDone==='function') onDone();
  });
}
function pickAiNarrative(){
  const hasAny = cart.length > 0;
  const n = it => String(it.name || it.item_name || '').toLowerCase();
  const isChicken = it => it.type==='chicken';
  const isOriginalChicken = it => isChicken(it) && it.style==='original';
  const isSauceChicken = it => isChicken(it) && it.style==='sauce';
  const isDrink = it => it.type==='matcha' || it.type==='drink' || n(it).includes('matcha') || n(it).includes('minuman');
  const isPotato = it => it.type==='kentang' || n(it).includes('kentang');
  const isRice = it => (it.type==='add_on' && it.kind==='rice') || (isChicken(it) && Number(it.with_rice||0)===1) || n(it).includes('nasi');
  const isSauceOnly = it => (it.type==='add_on' && it.kind==='sauce') || (n(it).includes('saus') && !isChicken(it) && !n(it).includes('ayam'));
  const isWholeChicken = it => it.type==='menu_item' && (/1\s*ekor|satu\s*ekor/i.test(n(it)) || n(it).includes('ayam 1 ekor'));
  const isWholeChickenSauce = it => isWholeChicken(it) && /saus|sauce|celup/i.test(n(it));

  const hasChicken = cart.some(isChicken);
  const hasOriginalChicken = cart.some(isOriginalChicken);
  const hasSauceChicken = cart.some(isSauceChicken);
  const hasDrink = cart.some(isDrink);
  const hasPotato = cart.some(isPotato);
  const hasRice = cart.some(isRice);
  const hasWholeChicken = cart.some(isWholeChicken);
  const hasWholeChickenSauce = cart.some(isWholeChickenSauce);
  const only = fn => cart.length>0 && cart.every(fn);

  let scenario='empty_cart';
  if(!hasAny) scenario = comboWindowActive ? 'promo_window' : 'empty_cart';
  else if(hasWholeChickenSauce) scenario='whole_chicken_sauce';
  else if(hasWholeChicken) scenario='whole_chicken';
  else if(hasChicken && hasDrink && hasPotato) scenario='all_menu';
  else if(hasDrink && hasPotato && !hasChicken) scenario='drink_potato';
  else if(hasDrink && hasChicken && !hasPotato) scenario='drink_chicken';
  else if(only(isSauceOnly)) scenario='only_sauce';
  else if(only(isRice)) scenario='only_rice';
  else if(only(isPotato)) scenario='only_potato';
  else if(only(isDrink)) scenario='only_drink';
  else if(hasSauceChicken) scenario='chicken_sauce';
  else if(hasOriginalChicken && hasRice && !hasSauceChicken && !hasDrink && !hasPotato) scenario='chicken_original_rice';
  else if(hasOriginalChicken && !hasRice && !hasSauceChicken && !hasDrink && !hasPotato) scenario='only_chicken_original';
  else if(hasChicken) scenario='drink_chicken';
  else scenario='general';

  if(hasAny && scenario==='empty_cart') scenario='general';

  const list=(aiNarratives[scenario] && aiNarratives[scenario].length)
    ? aiNarratives[scenario]
    : (aiNarrativeFallbacks[scenario] || aiNarrativeFallbacks.general || []);
  const picked=list.length ? list[0] : {title:'Saran AI Lumero',suggested_menu:'Ayam Crispy Varian Saus',message:'Kakak bisa pilih ayam crispy varian saus favorit Lumero. Kriuknya mantap, sausnya lumer, dan cocok dilengkapi kentang atau minuman segar.',cta_text:'Pilih menu favorit'};
  picked.scenario=scenario;
  return picked;
}
function updateAiPanelContent(){
  const rec=pickAiNarrative();
  const textEl=document.getElementById('aiRecoText');
  const menuEl=document.getElementById('aiRecoMenu');
  const reasonEl=document.getElementById('aiRecoReason');
  if(textEl) textEl.textContent=rec.message || '';
  if(menuEl) menuEl.textContent=rec.suggested_menu || rec.title || 'Saran AI Lumero';
  if(reasonEl) reasonEl.textContent=rec.cta_text || 'Direkomendasikan sesuai isi keranjang dan jam order Kakak.';
  return rec;
}
function playAiRecommendation(){
  stopAllVoices();
  stopAiRecommendationAudio();
  const rec=updateAiPanelContent();
  const text = (rec.suggested_menu ? rec.suggested_menu + '. ' : '') + (rec.message || '');
  speakAiText(text, ()=>{});
}

function playCheckoutTotalFlow(){
  if(!isGuideOn()){ startBgm(); return; }
  stopAllVoices();
  startBgm();

  const total=currentCartTotal() + (selectedPickupType === 'delivery' ? Number(deliveryFee||0) : 0);
  const audio=document.getElementById('foVoiceTotal');
  let moved=false;

  const next=()=>{
    if(moved) return;
    moved=true;
    setTimeout(()=>speakTotalThenPayout(total),260);
  };

  if(!audio){ next(); return; }
  try{
    audio.pause();
    audio.currentTime=0;
    audio.volume=1;
    audio.onended=next;
    audio.onerror=next;

    const p=audio.play();
    if(p&&p.catch) p.catch(()=>setTimeout(next,700));

    const durationMs = isFinite(audio.duration) && audio.duration>0 ? (audio.duration*1000+450) : 2600;
    setTimeout(()=>{ if(!moved) next(); }, durationMs);
  }catch(e){ next(); }
}
function getCartTotal(){ cart = window.cart || cart || []; return (cart||[]).reduce((sum,it)=>sum+(Number(it.price||0)*Number(it.qty||1)),0); }
function setPayment(method){
  payment=method;
  document.querySelectorAll('.payBtn, .payment-item').forEach(x=>x.classList.toggle('active',x.dataset.pay===method));
  document.querySelectorAll('.fo-pay-preview').forEach(x=>x.classList.remove('active'));
  const active=document.getElementById(method+'Preview'); if(active) active.classList.add('active');
  const pi=document.getElementById('paymentInput'); if(pi) pi.value=method;
  const pm=document.getElementById('paymentMethod'); if(pm) pm.value=method;
  const info=document.getElementById('payInfo');
  if(method==='transfer'){ info.innerHTML='Transfer BCA a.n. <b><?=$bankAccountName?></b> No. <b><?=$bankAccountNo?></b>. Nomor rekening bisa di-copy.'; foPlay('foVoiceTransfer'); }
  else if(method==='cash'){ info.innerHTML='Cash di outlet. Bayar tunai saat pesanan diambil.'; foPlay('foVoiceCash'); }
  else if(method==='point'){ const need=Math.ceil(getCartTotal()/<?=max(1,(int)$memberPointValue)?>); info.innerHTML='Butuh <b>'+need.toLocaleString('id-ID')+' point</b> untuk membayar total belanja ini. Saldo Anda: <b><?=number_format($memberPointBalance,0,',','.')?> point</b>.'; const p=document.getElementById('pointPreviewText'); if(p) p.innerHTML=info.innerHTML; foPlay('foVoicePayout'); }
  else { info.innerHTML='QRIS: <?=fo_e($qrisInfo)?>. Download QRIS tersedia dan bukti pembayaran diverifikasi kasir.'; foPlay('foVoiceQris'); }
}

function showCopyToast(text){ const t=document.getElementById('copyToast'); if(!t) return; t.textContent=text; t.classList.add('show'); setTimeout(()=>t.classList.remove('show'),1700); }
document.addEventListener('click',function(e){
  const btn=e.target.closest('[data-copy]'); if(!btn) return;
  const val=btn.getAttribute('data-copy')||'';
  if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(val).then(()=>showCopyToast('Nomor rekening disalin')).catch(()=>fallbackCopy(val)); }
  else fallbackCopy(val);
});
function fallbackCopy(val){ const ta=document.createElement('textarea'); ta.value=val; document.body.appendChild(ta); ta.select(); document.execCommand('copy'); ta.remove(); showCopyToast('Nomor rekening disalin'); }

document.querySelectorAll('.fo-pickup-option[data-pickup]').forEach(btn=>btn.addEventListener('click',()=>{
  if(btn.disabled) return;
  setPickupType(btn.dataset.pickup);
}));
document.querySelectorAll('.payBtn').forEach(btn=>btn.addEventListener('click',()=>setPayment(btn.dataset.pay)));
document.addEventListener('click',function(e){
  const btn=e.target.closest('.fo-pickup-option');
  if(!btn || btn.disabled) return;
  setPickupType(btn.dataset.pickup);
});

document.querySelectorAll('.fo-tab[data-target]').forEach(btn=>btn.addEventListener('click',()=>{ document.querySelectorAll('.fo-tab').forEach(x=>x.classList.remove('active')); btn.classList.add('active'); const target=document.getElementById(btn.dataset.target); if(target) target.scrollIntoView({behavior:'smooth',block:'start'}); }));
const pickupDateEl=document.getElementById('pickupDate');
if(pickupDateEl) pickupDateEl.addEventListener('change',()=>{ buildPickupOptions(); updatePickupSummary(); });
const pickupTimeEl=document.getElementById('pickupTime');
if(pickupTimeEl) pickupTimeEl.addEventListener('change',updatePickupSummary);

// ============================================================
//  MIDTRANS QRIS POPUP — State
// ============================================================
let _mqrisPollingTimer  = null;
let _mqrisCountdownTimer = null;
let _mqrisOrderNo        = '';
let _mqrisExpireAt       = 0;   // epoch ms
const MQRIS_LIFETIME_MS  = 15 * 60 * 1000; // 15 menit QRIS Midtrans
const MQRIS_POLL_INTERVAL = 3000; // poll tiap 3 detik

function _mqrisPrepareFormData(){
  cart = window.cart || cart || [];
  window.cart = cart;
  syncCustomerFields('top');
  const nameVal  = (document.getElementById('customerNameTop')?.value  || document.getElementById('customerName')?.value  || '').trim();
  const phoneVal = normalizePhoneClient(document.getElementById('customerPhoneTop')?.value || document.getElementById('customerPhone')?.value || '');

  // Validasi
  if(!cart.length)                 { foPlay('foVoiceMaaf'); alert('Keranjang masih kosong.'); return null; }
  if(!nameVal || !phoneVal)        { foPlay('foVoiceMaaf'); alert('Mohon isi nama dan nomor WhatsApp terlebih dahulu.'); return null; }
  if(!payment)                     { foPlay('foVoiceOpsiBayar'); alert('Mohon pilih metode pembayaran terlebih dahulu.'); return null; }
  if(payment==='point'){
    const need=Math.ceil(getCartTotal()/<?=max(1,(int)$memberPointValue)?>);
    const bal=<?=max(0,(int)$memberPointBalance)?>;
    if(need>bal){ alert('Point belum mencukupi. Butuh '+need.toLocaleString('id-ID')+' point.'); return null; }
  }
  if(selectedPickupType==='outlet' && !document.getElementById('pickupTime').value){
    alert('Jam pengambilan belum tersedia. Silakan pilih tanggal yang valid.'); return null;
  }
  if(selectedPickupType==='delivery'){
    const addr = document.getElementById('deliveryAddressInput')?.value?.trim() || '';
    if(!addr || deliveryLat==0 || deliveryLng==0){ alert('Mohon lengkapi titik lokasi pada peta dan alamat lengkap pengantaran.'); return null; }
    if(deliveryDistanceKm > deliveryConfig.maxRadius){ alert(`Jarak pengantaran (${deliveryDistanceKm} km) melebihi batas maksimal (${deliveryConfig.maxRadius} km).`); return null; }
  }

  // Isi hidden inputs
  const foForm = document.getElementById('foForm');
  const g = id => foForm.querySelector('#'+id) || document.getElementById(id);
  g('cartInput').value           = JSON.stringify(cart);
  g('pickupDateInput').value     = document.getElementById('pickupDateDrawer')?.value || document.getElementById('pickupDate')?.value || '';
  g('pickupTimeInput').value     = document.getElementById('pickupTimeDrawer')?.value || document.getElementById('pickupTime')?.value || '';
  g('customerNameInput').value   = nameVal;
  g('customerPhoneInput').value  = phoneVal;
  g('customerNoteInput').value   = document.getElementById('customerNote')?.value || '';
  g('pickupTypeInput').value     = selectedPickupType;
  if(selectedPickupType==='delivery'){
    g('deliveryAddressHiddenInput').value  = (document.getElementById('deliveryAddressInputDrawer')?.value || document.getElementById('deliveryAddressInput')?.value || '').trim();
    g('deliveryLatHiddenInput').value      = deliveryLat;
    g('deliveryLngHiddenInput').value      = deliveryLng;
    g('deliveryFeeHiddenInput').value      = deliveryFee;
    g('deliveryDistanceHiddenInput').value = deliveryDistanceKm;
  }
  const oi = g('outletIdInput');
  if(oi) oi.value = window.LUMERO_SELECTED_OUTLET_ID || <?= current_outlet_id() ?>;
  try{ localStorage.setItem('lumero_customer_phone', phoneVal); localStorage.setItem('lumero_customer_name', nameVal); }catch(_){}
  return new FormData(foForm);
}

function _mqrisShowLoading(){
  document.getElementById('mqrisLoading').style.display  = 'block';
  document.getElementById('mqrisContent').style.display  = 'none';
  document.getElementById('mqrisError').style.display    = 'none';
  const ov = document.getElementById('midtransQrisOverlay');
  ov.style.display = 'flex';
}
function closeMidtransQris(){
  clearInterval(_mqrisPollingTimer);
  clearInterval(_mqrisCountdownTimer);
  _mqrisPollingTimer  = null;
  _mqrisCountdownTimer = null;
  document.getElementById('midtransQrisOverlay').style.display = 'none';
  const fb = document.getElementById('mqrisStaticFallback');
  if(fb) fb.style.display = 'none';
}
function _mqrisShowSuccessPopup(orderNo){
  // Tutup QRIS popup
  closeMidtransQris();
  // Tampilkan popup sukses
  const ov = document.getElementById('paymentSuccessOverlay');
  document.getElementById('successOrderNo').textContent = 'No. Pesanan: ' + orderNo;
  ov.style.display = 'flex';
  // Progress bar animasi 3 detik lalu redirect
  const bar = document.getElementById('successProgressBar');
  let pct = 0;
  const step = 100 / 30; // 30 tick × 100ms = 3s
  const prog = setInterval(()=>{
    pct += step;
    bar.style.width = Math.min(pct, 100) + '%';
    if(pct >= 100){
      clearInterval(prog);
      window.location.href = '../order-online/lacak.php?no=' + encodeURIComponent(orderNo) + '&success=1&paid=1';
    }
  }, 100);
  // Coba mainkan suara sukses
  foPlay('foVoiceSuccess');
}

function _mqrisStartPolling(midtransOrderId, localOrderNo){
  _mqrisPollingTimer = setInterval(async ()=>{
    // Cek expiry
    if(Date.now() >= _mqrisExpireAt){
      clearInterval(_mqrisPollingTimer);
      clearInterval(_mqrisCountdownTimer);
      const badge = document.getElementById('mqrisStatusBadge');
      if(badge) badge.innerHTML = '<span style="color:#ef4444;">⛔ Kode QRIS Kadaluarsa. Silakan buat pesanan baru.</span>';
      return;
    }
    try{
      const res  = await fetch('../user/check-qris-status.php?order_id=' + encodeURIComponent(midtransOrderId), {
        headers: {'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'},
        credentials: 'same-origin', cache: 'no-store'
      });
      const data = await res.json();
      if(data.paid === true){
        clearInterval(_mqrisPollingTimer);
        clearInterval(_mqrisCountdownTimer);
        _mqrisShowSuccessPopup(localOrderNo);
      }
    } catch(_){}
  }, MQRIS_POLL_INTERVAL);
}

function _mqrisStartCountdown(){
  _mqrisExpireAt = Date.now() + MQRIS_LIFETIME_MS;
  _mqrisCountdownTimer = setInterval(()=>{
    const rem = Math.max(0, Math.floor((_mqrisExpireAt - Date.now()) / 1000));
    const m   = Math.floor(rem / 60);
    const s   = rem % 60;
    const el  = document.getElementById('mqrisTimer');
    if(el) el.textContent = `Kode berlaku: ${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
    if(rem <= 0) clearInterval(_mqrisCountdownTimer);
  }, 1000);
}

async function _mqrisSubmitOrder(formData){
  _mqrisShowLoading();
  try{
    const res  = await fetch('', {
      method: 'POST',
      body: formData,
      headers: {'X-Requested-With':'XMLHttpRequest'},
      credentials: 'same-origin'
    });
    const data = await res.json();
    if(!data.ok){
      // Gagal – tampilkan error di popup
      document.getElementById('mqrisLoading').style.display = 'none';
      const errEl = document.getElementById('mqrisError');
      errEl.style.display = 'block';
      if(data.order_no) {
        errEl.innerHTML = '⚠️ Gagal memuat QRIS Midtrans:<br><small>'+escapeHtml(data.error||'Error tidak diketahui')+'</small>';
        document.getElementById('mqrisStaticFallback').style.display = 'block';
        const doneBtn = document.getElementById('mqrisFallbackDoneBtn');
        if(doneBtn) {
          doneBtn.onclick = () => {
            window.location.href = '../order-online/lacak.php?no=' + encodeURIComponent(data.order_no) + '&success=1';
          };
        }
      } else {
        errEl.innerHTML = '⚠️ Gagal memproses pesanan:<br><small>'+escapeHtml(data.error||'Error tidak diketahui')+'</small><br><br><button onclick="closeMidtransQris()" style="background:var(--dp-red);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Tutup</button>';
      }
      return;
    }
    if(data.mode === 'qris_midtrans'){
      _mqrisOrderNo = data.order_no;
      // Tampilkan QR
      document.getElementById('mqrisLoading').style.display = 'none';
      document.getElementById('mqrisContent').style.display = 'block';
      document.getElementById('mqrisOrderNo').textContent   = 'No. Pesanan: ' + data.order_no;
      document.getElementById('mqrisAmount').textContent    = 'Rp ' + Number(data.gross_amount).toLocaleString('id-ID');
      document.getElementById('mqrisImg').src               = data.qr_url;
      _mqrisStartCountdown();
      _mqrisStartPolling(data.midtrans_order, data.order_no);
    } else {
      // Fallback: redirect langsung
      closeMidtransQris();
      window.location.href = '../order-online/lacak.php?no=' + encodeURIComponent(data.order_no) + '&success=1';
    }
  } catch(err){
    document.getElementById('mqrisLoading').style.display = 'none';
    const errEl = document.getElementById('mqrisError');
    errEl.style.display = 'block';
    errEl.innerHTML = '⚠️ Koneksi bermasalah. Periksa internet Anda.<br><br><button onclick="closeMidtransQris()" style="background:var(--dp-red);color:#fff;border:none;border-radius:8px;padding:8px 18px;font-weight:700;cursor:pointer;">Tutup</button>';
  }
}

document.getElementById('foForm').addEventListener('submit', e=>{
  e.preventDefault();
  const formData = _mqrisPrepareFormData();
  if(!formData) return;

  if(payment === 'qris' && <?= json_encode($isMidtransQris) ?>){
    // QRIS Midtrans: submit via AJAX → tampilkan popup
    _mqrisSubmitOrder(formData);
  } else {
    // Non-QRIS (transfer, point, manual qris): submit form biasa
    document.getElementById('foForm').submit();
  }
});

if(window.lumero_FREE_ORDER_POPUP){ setTimeout(()=>foPlay('foVoiceSuccess'), 500); }

updateAudioToggleButtons();
buildPickupOptions();
initCustomerMemory();
initChickenCards();
renderCart();

// Idle timeout: 120 seconds (2 minutes) of no interaction -> redirect to welcome
(function(){
  let idleTimer;
  const resetTimer = () => {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => { window.location.href = 'welcome.php?idle=1'; }, 120000);
  };
  ['mousemove','keydown','scroll','touchstart','click'].forEach(e => document.addEventListener(e, resetTimer, true));
  resetTimer();
})();
</script>
<script src="../public/assets/js/self-order-ui.js?v=<?=time()?>"></script>
</body>
</html>







