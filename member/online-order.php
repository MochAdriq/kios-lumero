<?php
require_once __DIR__.'/../helpers/functions.php';
require_once __DIR__.'/../core/Database.php';
$pdo = Database::connection();
require_once __DIR__.'/../helpers/free_order_helper.php';
require_once __DIR__.'/../config/loyalty.php';
date_default_timezone_set('Asia/Jakarta');
try{ if(isset($pdo) && $pdo instanceof PDO) $pdo->exec("SET time_zone = '+07:00'"); }catch(Throwable $e){}
if(function_exists('ensure_today_stock')) ensure_today_stock();
fo_ensure_tables($pdo);
loyalty_ensure_tables($pdo);
$memberOnline = null;
if(!empty($_SESSION['member_id'])) $memberOnline = loyalty_member_by_id($pdo,(int)$_SESSION['member_id']);
if(!$memberOnline){ header('Location: index.php'); exit; }
$memberPointBalance=(int)($memberOnline['total_points'] ?? 0);
$memberPointValue=max(1,(int)(loyalty_settings($pdo)['redeem_point_value'] ?? 500));


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
$paymentQrisImage=trim((string)(function_exists('get_setting')?get_setting('payment_qris_image','assets/img/payment/qris-dana.jpeg'):'assets/img/payment/qris-dana.jpeg'));
if($paymentQrisImage==='') $paymentQrisImage='assets/img/payment/qris-dana.jpeg';
$bankName='BCA';
$bankAccountName='Sri Kusma Dewi';
$bankAccountNo='0382731393';

$freeOrderVideo='public/assets/video/self-order-cover.mp4';
$freeOrderPoster='public/assets/images/pos-products/dclup-pasekon.png';
$freeOrderVoiceBase='../public/assets/audio/';

if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
    $customerName=trim((string)($_POST['customer_name'] ?? ''));
    $customerPhone=fo_normalize_phone(trim((string)($_POST['customer_phone'] ?? '')));
    $pickupDate=trim((string)($_POST['pickup_date'] ?? $today));
    $pickupTime=trim((string)($_POST['pickup_time'] ?? '09:00'));
    $paymentMethod=trim((string)($_POST['payment_method'] ?? ''));
    $pickupType=trim((string)($_POST['pickup_type'] ?? 'outlet'));
    if($pickupType!=='outlet') $pickupType='outlet';
    $note=trim((string)($_POST['customer_note'] ?? ''));
    $cart=json_decode($_POST['cart'] ?? '[]', true);
    if($customerName==='') throw new Exception('Nama pemesan wajib diisi.');
    if($customerPhone==='') throw new Exception('Nomor WhatsApp wajib diisi.');
    if(!is_array($cart) || count($cart)<=0) throw new Exception('Keranjang masih kosong.');
    if(!fo_valid_date($pickupDate) || strtotime($pickupDate)<strtotime($today)) throw new Exception('Tanggal pengambilan tidak valid.');
    if(!fo_valid_time($pickupTime)) throw new Exception('Jam pengambilan tidak valid.');
    if($paymentMethod !== 'qris') throw new Exception('Pilihan pembayaran untuk Self-Order adalah QRIS.');

    $calc=fo_normalize_cart($pdo,$cart);
    if(!$calc['items']) throw new Exception('Keranjang tidak valid.');

    $pdo->beginTransaction();
    $no=fo_next_no($pdo);
    $memberId=(int)($memberOnline['id'] ?? 0);
    $subtotalOnline=(int)$calc['subtotal'];
    $redeemPoints=0; $redeemAmount=0; $pointValue=$memberPointValue; $paymentStatus='unpaid'; $totalDue=$subtotalOnline;
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
    $st=$pdo->prepare("INSERT INTO free_orders (pre_order_no,customer_name,customer_phone,member_id,pickup_type,pickup_date,pickup_time,payment_method,payment_status,order_status,subtotal,discount,total,total_hpp,loyalty_points_redeemed,loyalty_point_value,loyalty_redeem_amount,customer_note,cart_json,stock_reserved) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    $st->execute([$no,$customerName,$customerPhone,$memberId ?: null,$pickupType,$pickupDate,$pickupTime.':00',$paymentMethod,$paymentStatus,'new',$subtotalOnline,$redeemAmount,$totalDue,$calc['total_hpp'],$redeemPoints,$pointValue,$redeemAmount,$note,json_encode($cart,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),0]);
    $freeOrderId=(int)$pdo->lastInsertId();

    $ins=$pdo->prepare("INSERT INTO free_order_items (free_order_id,item_type,chicken_part_id,chicken_style,sauce_id,with_rice,matcha_variant_id,kentang_variant_id,menu_item_id,item_name,qty,price,hpp,line_total,line_hpp,payload_json) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
    foreach($calc['items'] as $ni){
      $ins->execute([$freeOrderId,$ni['type'],$ni['chicken_part_id'],$ni['chicken_style'],$ni['sauce_id'],$ni['with_rice'],$ni['matcha_variant_id'],$ni['kentang_variant_id'],$ni['menu_item_id'] ?? null,$ni['item_name'],$ni['qty'],$ni['price'],$ni['hpp'],$ni['price']*$ni['qty'],$ni['hpp']*$ni['qty'],json_encode($ni['payload'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
    }
    fo_upsert_customer($pdo,$customerPhone,$customerName,$no);
    $pdo->commit();

    header('Location: ../order-online/lacak.php?no='.urlencode($no).'&success=1'); exit;
  }catch(Throwable $e){
    if(isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
    $err=$e->getMessage();
  }
}
// order berhasil diarahkan ke halaman lacak pesanan.

$data = function_exists('fo_load_pos_menu_data') ? fo_load_pos_menu_data($pdo) : (function_exists('load_menu_data') ? load_menu_data() : ['parts'=>[],'sauces'=>[],'kentang'=>[],'matcha'=>[]]);
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
    'empty_cart'=>[['title'=>'Bingung Pilih Menu?','suggested_menu'=>'Ayam Crispy Varian Saus','message'=>'Tenang kak, jangan bingung! Kalau kaka bingung pilih menu, aku bantu ya. Di D’Celup, Kakak wajib coba menu ayam crispy dengan varian saus favorit. Kriuknya mantap, sausnya lumer, aromanya menggoda, dan rasanya bikin pengen nambah. Biar makin sedap, kaka juga bisa tambahkan kentang kriwil dan Matcha, cobain deh, gak akan nyesel!!!','cta_text'=>'Coba ayam saus favorit']],
    'only_chicken_original'=>[['title'=>'Lengkapi Ayam Original','suggested_menu'=>'Kentang Kriuk dan Matcha','message'=>'Biar makin lengkap, tambahkan kentang kriuk dan minuman matcha segar sebagai penyempurna hidangan. Dijamin bikin ketagihan deh, hihihi...','cta_text'=>'Tambah kentang dan matcha']],
    'chicken_original_rice'=>[['title'=>'Ayam dan Nasi Sudah Pas','suggested_menu'=>'Saus Favorit + Minuman','message'=>'Ayam original plus nasi sudah mantap, Kak. Biar rasanya makin hidup, tambahkan varian saus favorit dan minuman segar. Sekali celup, kriuknya makin lumer di hati.','cta_text'=>'Tambah saus dan minuman']],
    'chicken_sauce'=>[['title'=>'Ayam Saus Sudah Mantap','suggested_menu'=>'Nasi + Kentang + Matcha','message'=>'Pilihan ayam saus Kakak sudah juara. Biar makin puas, tambahkan nasi hangat, kentang kriwil, atau Matcha segar. Lengkapnya dapet, nikmatnya makin nempel.','cta_text'=>'Lengkapi dengan nasi atau minuman']],
    'only_potato'=>[['title'=>'Kentang Kriwil Mantap','suggested_menu'=>'Ayam Crispy dan Minuman','message'=>'Kentangnya sudah cocok jadi teman ngemil. Biar lebih puas, pasangkan dengan ayam crispy dan minuman segar favorit Kakak.','cta_text'=>'Tambah ayam dan minuman']],
    'only_drink'=>[['title'=>'Minuman Segar Siap','suggested_menu'=>'Ayam Crispy Saus','message'=>'Minumannya sudah segar, Kak. Sekarang waktunya tambahkan ayam crispy varian saus favorit. Kriuknya mantap, sausnya lumer, cocok banget jadi pasangan minuman Kakak.','cta_text'=>'Tambah ayam crispy']],
    'drink_potato'=>[['title'=>'Minuman dan Kentang Sudah Oke','suggested_menu'=>'Ayam Crispy Varian Saus','message'=>'Minuman dan kentang sudah jadi duet yang asik. Tapi biar makin lengkap, tambahkan ayam crispy saus D’Celup. Dijamin makin kenyang dan makin puas.','cta_text'=>'Tambah ayam saus']],
    'drink_chicken'=>[['title'=>'Ayam dan Minuman Sudah Mantap','suggested_menu'=>'Kentang Kriwil','message'=>'Ayam dan minuman Kakak sudah pas banget. Biar teksturnya makin rame, tambahkan kentang kriwil yang renyah. Jadi lengkap, gurih, segar, dan nagih.','cta_text'=>'Tambah kentang kriwil']],
    'all_menu'=>[['title'=>'Pesanan Sudah Lengkap','suggested_menu'=>'Tambahan Saus Favorit','message'=>'Wah, pilihan Kakak sudah lengkap banget! Ayam ada, kentang ada, minuman juga ada. Kalau mau makin lumer, tambahkan saus favorit ekstra biar setiap gigitan makin seru.','cta_text'=>'Tambah saus ekstra']],
    'only_sauce'=>[['title'=>'Sausnya Sudah Siap','suggested_menu'=>'Ayam Crispy Original','message'=>'Saus favoritnya sudah dipilih, Kak. Sekarang tinggal pasangkan dengan ayam crispy original yang kriuknya mantap. Biar sausnya punya pasangan terbaik.','cta_text'=>'Tambah ayam crispy']],
    'only_rice'=>[['title'=>'Nasinya Sudah Siap','suggested_menu'=>'Ayam Crispy Saus','message'=>'Nasi hangatnya sudah siap, Kak. Biar jadi hidangan lengkap, tambahkan ayam crispy varian saus favorit. Kriuk, lumer, dan bikin makan makin semangat.','cta_text'=>'Tambah ayam saus']],
    'whole_chicken'=>[['title'=>'Ayam 1 Ekor Mantap','suggested_menu'=>'Saus Ekstra dan Minuman','message'=>'Wah, 1 ekor ayam sudah pilihan mantap untuk rame-rame. Biar makin seru, tambahkan saus ekstra dan minuman segar supaya semua kebagian rasa favorit.','cta_text'=>'Tambah saus dan minuman']],
    'whole_chicken_sauce'=>[['title'=>'Ayam 1 Ekor Saus Juara','suggested_menu'=>'Nasi dan Matcha','message'=>'Ayam 1 ekor plus saus sudah paket yang menggoda banget. Biar makin lengkap untuk disantap bareng, tambahkan nasi hangat dan Matcha segar.','cta_text'=>'Tambah nasi dan matcha']],
    'promo_window'=>[['title'=>'Jam Promo Spesial','suggested_menu'=>'Combo Hemat Jam Spesial','message'=>'Kakak lagi masuk jam promo nih! Ini waktu paling pas ambil paket combo hemat. Ayamnya nikmat, nasinya ada, harganya lebih bersahabat, dan rasanya tetap juara.','cta_text'=>'Ambil combo promo sekarang']],
    'general'=>[['title'=>'Saran Menu D’Celup','suggested_menu'=>'Ayam Crispy Varian Saus','message'=>'Kakak bisa pilih ayam crispy varian saus favorit D’Celup. Kriuknya mantap, sausnya lumer, dan cocok dilengkapi kentang atau minuman segar.','cta_text'=>'Pilih menu favorit']]
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
<html lang="id">
<head>
<link rel="icon" type="image/png" href="../public/assets/images/pos-products/icon-192.png">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ============================================
   Lumero SELF-ORDER – DARK PREMIUM CINEMATIC
   Adapted from POS kasir2-theme
   ============================================ */

/* ── Design Tokens (copas dari POS) ── */
:root{
  --dp-bg:#0f0f13;--dp-bg-2:#16161d;--dp-surface:#1a1a2e;--dp-surface-2:#22223a;
  --dp-surface-hover:#2a2a45;--dp-glass:rgba(26,26,46,.72);--dp-glass-border:rgba(255,255,255,.06);
  --dp-red:#ff2d55;--dp-red-glow:rgba(255,45,85,.25);--dp-red-soft:rgba(255,45,85,.12);
  --dp-orange:#ff6b35;--dp-gradient:linear-gradient(135deg,#ff2d55,#ff6b35);
  --dp-gradient-subtle:linear-gradient(135deg,rgba(255,45,85,.08),rgba(255,107,53,.08));
  --dp-green:#34d399;--dp-green-soft:rgba(52,211,153,.12);
  --dp-text:#f1f1f5;--dp-text-2:#c4c4d4;--dp-muted:#6b6b85;
  --dp-line:rgba(255,255,255,.06);--dp-shadow:0 4px 24px rgba(0,0,0,.4);
  --dp-shadow-glow:0 0 30px rgba(255,45,85,.15);--dp-radius:16px;--dp-radius-sm:10px;
  --dp-font:'Plus Jakarta Sans','Inter',system-ui,sans-serif;
  --dp-transition:.25s cubic-bezier(.4,0,.2,1);
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
.fo-pos-wrapper{display:flex;height:100vh;overflow:hidden}
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
.fo-modal{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:70;display:grid;place-items:center;padding:18px}
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
.fo-floating-actions{position:fixed;right:14px;bottom:90px;z-index:54;display:grid;gap:10px;justify-items:end}
.fo-float-btn{border:0;border-radius:999px;padding:10px 14px;box-shadow:0 8px 24px rgba(0,0,0,.4);font-weight:800;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;font-size:12px}
.fo-float-btn.ai{background:var(--dp-gradient);color:#fff}
.fo-float-btn.wa{background:var(--dp-green);color:#fff}
.fo-ai-panel{position:fixed;right:14px;bottom:180px;width:min(92vw,390px);z-index:56;display:none;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:16px;box-shadow:var(--dp-shadow);color:var(--dp-text)}
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

.fo-ai-nudge{position:fixed;right:16px;bottom:148px;z-index:55;width:min(88vw,330px);display:none;background:var(--dp-surface);border:1px solid var(--dp-glass-border);border-radius:var(--dp-radius);padding:14px;box-shadow:var(--dp-shadow);animation:aiNudgeIn .35s ease both;color:var(--dp-text)}
.fo-ai-nudge.show{display:block}
.fo-ai-nudge:after{content:"";position:absolute;right:32px;bottom:-10px;width:20px;height:20px;background:var(--dp-surface);border-right:1px solid var(--dp-glass-border);border-bottom:1px solid var(--dp-glass-border);transform:rotate(45deg)}
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
</style>
</head>
<body>
<div class="fo-video-overlay" id="freeOrderVideoOverlay" aria-modal="true" role="dialog">
  <video id="freeOrderVideoPlayer" autoplay muted loop playsinline preload="auto" poster="../<?=fo_e($freeOrderPoster)?>">
    <source src="../<?=fo_e($freeOrderVideo)?>" type="video/mp4">
  </video>
  <div class="fo-video-content">
    <div class="fo-video-badge"><img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero"></div>
    <h2 class="fo-video-title">Online Order Lumero</h2>
    <p class="fo-video-subtitle">Pesan dulu dari HP, pilih jam ambil yang paling pas, lalu bayar via QRIS, transfer, atau cash di outlet.</p>
    <div class="fo-video-phone-box" style="display: none;">
      <label>Nomor WhatsApp Pelanggan</label>
      <div class="fo-video-phone-row single">
        <input id="videoPhoneInput" inputmode="tel" placeholder="08xxxxxxxxxx" value="<?=fo_e($memberOnline['phone'] ?? '')?>">
      </div>
      <div class="fo-video-phone-info" id="videoPhoneInfo">Masukkan nomor WhatsApp untuk mempercepat order.</div>
    </div>
    <button type="button" class="fo-start-btn" id="startFreeOrderBtn">Mulai Online Order</button>
  </div>
</div>

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
  <!-- LEFT PANEL: Catalog -->
  <div class="fo-pos-left">
    <!-- Header Bar -->
    <header class="fo-header-bar">
      <div class="fo-brand">
        <img src="../public/assets/images/pos-products/icon-192.png" alt="Lumero">
        <div>
          <h1>Lumero SELF-ORDER</h1>
          <small>Pesan Cepat • Tanpa Antre • Ambil di Outlet</small>
        </div>
      </div>
      <div class="fo-header-actions">
        <div class="fo-audio-toggles">
          <button type="button" class="fo-audio-toggle active" id="toggleMusicBtn" onclick="toggleMusicSetting()"><span>♪</span> Musik ON</button>
          <button type="button" class="fo-audio-toggle active" id="toggleGuideBtn" onclick="toggleGuideSetting()"><span>🔊</span> Suara ON</button>
        </div>
        <a class="fo-track-link" href="../order-online/lacak.php">📍 Lacak Pesanan</a>
        <button type="button" class="fo-cart-pill" onclick="openCheckout()" style="border:none;cursor:pointer;">
          <span class="fo-cart-icon">🛒</span>
          <span id="cartCount">0</span>
        </button>
      </div>
    </header>

    <!-- Main Scrollable Catalog -->
    <main class="fo-products-scroll">
      <?php if($err): ?>
        <div class="fo-alert err"><?=fo_e($err)?></div>
      <?php endif; ?>

      <!-- Category Filter Tabs -->
      <div style="margin-bottom:18px;">
        <nav class="fo-toolbar">
          <button type="button" class="fo-tab active" data-target="ayam">🍗 Ayam Crispy</button>
          <button type="button" class="fo-tab" data-target="kentang">🍟 Kentang</button>
          <button type="button" class="fo-tab" data-target="matcha">🍵 Matcha & Kopi</button>
          <button type="button" class="fo-tab" data-target="tambahan">🔥 Ayam 1 Ekor</button>
          <button type="button" class="fo-tab" data-target="addon">🍚 Nasi & Saus</button>
          <?php if($comboWindowActive): ?><button type="button" class="fo-tab" data-target="combo">🎉 Combo Promo</button><?php endif; ?>
          <button type="button" class="fo-tab" onclick="openCheckout()" style="border-color:rgba(255,45,85,.4);color:var(--dp-red);">🛒 Lihat Keranjang</button>
        </nav>
      </div>

  <section class="fo-section" id="ayam">
    <div class="fo-section-head"><div><h3>Ayam Crispy</h3><p>Pilih bagian ayam, tipe original atau plus saus, lalu pilih tanpa nasi atau + nasi.</p></div></div>
    <div class="fo-grid">
      <?php foreach($data['parts'] as $p): $partId=(int)$p['id']; $name=trim((string)$p['name']); $img=fo_img_part($name); ?>
      <article class="fo-card chicken-card" data-part-id="<?=$partId?>" data-part-name="<?=fo_e($name)?>" data-part-image="<?=fo_e($img)?>">
        <img class="hero" src="../public/assets/images/pos-products/<?=fo_e($img)?>" alt="<?=fo_e($name)?>">
        <div class="meta"><h4><?=fo_e($name)?></h4><span class="fo-badge-inline fo-badge-ready">Tersedia</span></div>
        <div class="fo-option-groups">
          <div class="fo-toggle-grid">
            <button type="button" class="fo-toggle-btn" data-style="original">Original</button>
            <button type="button" class="fo-toggle-btn" data-style="sauce">Plus Saus</button>
          </div>
          <div class="fo-toggle-grid">
            <button type="button" class="fo-toggle-btn" data-rice="0">Tanpa Nasi</button>
            <button type="button" class="fo-toggle-btn" data-rice="1">+ Nasi</button>
          </div>
          <div class="fo-sauce-wrap">
            <div class="fo-sauce-grid">
              <?php foreach($data['sauces'] as $s): $sid=(int)$s['id']; ?>
              <button type="button" class="fo-sauce-card" data-sauce-id="<?=$sid?>" data-sauce-name="<?=fo_e($s['name'])?>">
                <div class="thumb"><img src="../public/assets/images/pos-products/<?=fo_e($img)?>" alt="<?=fo_e($s['name'])?>"></div>
                <div><b><?=fo_e($s['name'])?></b><small>Varian saus untuk <?=fo_e($name)?></small></div>
              </button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <div class="fo-pricebar">
          <div>
            <span class="fo-price pricePreview">Rp0</span>
            <div class="fo-subprice namePreview">Memuat harga…</div>
          </div>
          <button type="button" class="fo-add-btn addChicken">Tambah ke keranjang</button>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="fo-section" id="tambahan">
    <div class="fo-section-head"><div><h3>Ayam 1 Ekor</h3><p>Pilihan ayam utuh untuk keluarga atau rombongan, harga mengikuti data menu di kasir.</p></div></div>
    <div class="fo-extra-grid">
      <?php foreach($wholeChickenMenus as $mi): $mid=(int)$mi['id']; $name=trim((string)$mi['name']); $desc=trim((string)($mi['description'] ?? '')); ?>
      <article class="fo-extra-card">
        <img src="<?=fo_e(fo_menu_item_img($mi))?>" onerror="this.src='../public/assets/images/pos-products/original.png'" alt="<?=fo_e($name)?>">
        <div class="content">
          <span class="fo-extra-badge">Ayam 1 Ekor</span>
          <h4><?=fo_e($name)?></h4>
          <?php if($desc): ?><p><?=fo_e($desc)?></p><?php else: ?><p>Menu tambahan praktis untuk keluarga atau rombongan.</p><?php endif; ?>
          <span class="price"><?=fo_money((int)$mi['price'])?></span>
          <button class="fo-add-btn" type="button" onclick='addMenuItem(<?=json_encode(["id"=>$mid,"name"=>$name,"price"=>(int)$mi["price"],"hpp"=>(int)$mi["hpp"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'>Tambah ke Keranjang</button>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="fo-section" id="addon">
    <div class="fo-section-head"><div><h3>Tambahan Nasi & Saus</h3><p>Tambah nasi atau saus favorit secara terpisah agar pesanan makin lengkap.</p></div></div>
    <div class="fo-addon-grid">
      <article class="fo-addon-card">
        <div class="addon-icon">🍚</div>
        <h4>Nasi Putih</h4>
        <p>Tambahan nasi untuk melengkapi ayam crispy.</p>
        <span class="price"><?=fo_money($extraRicePrice)?></span>
        <button class="fo-add-btn" type="button" onclick='addAddon(<?=json_encode(["kind"=>"rice","name"=>"Nasi Putih","price"=>$extraRicePrice,"hpp"=>$extraRiceHpp],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'>Tambah Nasi</button>
      </article>
      <?php foreach($data['sauces'] as $s): $sid=(int)$s['id']; $sname=trim((string)$s['name']); ?>
      <article class="fo-addon-card">
        <div class="addon-icon">🥣</div>
        <h4><?=fo_e($sname)?></h4>
        <p>Saus tambahan untuk rasa yang lebih lumer dan mantap.</p>
        <span class="price"><?=fo_money($extraSaucePrice)?></span>
        <button class="fo-add-btn" type="button" onclick='addAddon(<?=json_encode(["kind"=>"sauce","sauce_id"=>$sid,"name"=>"Saus ".$sname,"price"=>$extraSaucePrice,"hpp"=>$extraSauceHpp],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'>Tambah Saus</button>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <?php if($comboWindowActive): ?>
  <section class="fo-section" id="combo">
    <div class="fo-section-head"><div><h3>Combo Promo Jam Spesial</h3><p>Promo combo hanya muncul pada 11:00–11:30, 15:00–15:30, dan 19:00–21:00.</p></div></div>
    <div class="fo-combo-window">
      <div class="fo-combo-grid">
        <?php foreach($comboMenus as $cm): ?>
        <article class="fo-combo-card">
          <img src="<?=fo_e(fo_combo_img($cm))?>" onerror="this.src='../public/assets/images/pos-products/original.png'" alt="<?=fo_e($cm['title'])?>">
          <div>
            <h4><?=fo_e($cm['title'])?></h4>
            <p><?=fo_e($cm['description'] ?? '')?></p>
            <span class="price"><?=fo_money((int)$cm['price'])?></span>
            <button class="fo-add-btn" type="button" onclick='addCombo(<?=json_encode(["id"=>(int)$cm["id"],"name"=>(string)$cm["title"],"price"=>(int)$cm["price"],"hpp"=>(int)$cm["hpp"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'>Tambah Combo</button>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="fo-section" id="kentang">
    <div class="fo-section-head"><div><h3>Menu Kentang</h3><p>Menu tambahan dengan margin bagus untuk melengkapi order ayam.</p></div></div>
    <div class="fo-simple-grid">
      <?php foreach($data['kentang'] as $k): $name=trim((string)$k['name']); ?>
      <article class="fo-simple-card">
        <img src="../public/assets/images/pos-products/kentang-dcelup.png" alt="<?=fo_e($name)?>">
        <h4><?=fo_e($name)?></h4>
        <span class="price"><?=fo_money((int)$k['price'])?></span>
        <button class="fo-add-btn" type="button" onclick='addSimple("kentang",<?=json_encode(["id"=>(int)$k["id"],"name"=>$name,"price"=>(int)$k["price"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'>Tambah</button>
      </article>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="fo-section" id="matcha">
    <div class="fo-section-head"><div><h3>Menu Matcha</h3><p>Minuman favorit yang mudah di-upsell bersama ayam atau kentang.</p></div></div>
    <div class="fo-simple-grid">
      <?php foreach($data['matcha'] as $m): $name=trim((string)$m['name']); $img=fo_img_matcha($name); ?>
      <article class="fo-simple-card">
        <img src="../public/assets/images/pos-products/matcha/<?=fo_e($img)?>" alt="<?=fo_e($name)?>">
        <h4>Matcha <?=fo_e($name)?></h4>
        <span class="price"><?=fo_money((int)$m['price'])?></span>
        <button class="fo-add-btn" type="button" onclick='addSimple("matcha",<?=json_encode(["id"=>(int)$m["id"],"name"=>"Matcha ".$name,"price"=>(int)$m["price"]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>)'>Tambah</button>
      </article>
      <?php endforeach; ?>
    </div>
  </section>
  <div class="fo-matcha-spacer"></div>
</main>
</div><!-- end .fo-pos-left -->

<!-- RIGHT SIDEBAR: Order Panel (Desktop POS Style) -->
<aside class="fo-pos-right">
  <div class="fo-order-header">
    <div class="fo-order-header-top">
      <div class="fo-order-title">Keranjang Pesanan <span class="fo-order-badge" id="sideCartBadge">0</span></div>
      <button type="button" class="fo-order-clear" onclick="cart=[]; renderCart();">🗑️ Kosongkan</button>
    </div>
  </div>
  <div class="fo-order-meta">
    <small>METODE BAYAR</small>
    <strong>QRIS (Midtrans) Eksklusif</strong>
  </div>
  <div class="fo-order-items" id="sideOrderItems">
    <div class="fo-order-empty">
      <div class="icon">🛒</div>
      <p>Belum ada menu dipilih</p>
      <small>Klik menu di kiri untuk menambah pesanan</small>
    </div>
  </div>
  <div class="fo-summary-section">
    <div class="fo-summary-total" style="display:flex;justify-content:space-between;align-items:center;">
      <span>Total Pesanan</span>
      <strong id="sideTotalText">Rp0</strong>
    </div>
  </div>
  <div class="fo-checkout-btn-wrap">
    <button type="button" class="fo-checkout-btn" onclick="openCheckout()">
      <strong>Lanjut Bayar QRIS →</strong>
      <small id="sideCountText">0 item</small>
    </button>
  </div>
</aside>
</div><!-- end .fo-pos-wrapper -->

<!-- FLOATING PAYBOX (Mobile Only) -->
<div class="fo-paybox">
  <div class="fo-pay-inner">
    <div class="fo-total">
      <small>Total Online Order</small>
      <b id="totalText">Rp0</b>
      <div class="fo-footer-detail" id="footerDetail">Keranjang masih kosong.</div>
    </div>
    <button class="fo-checkout" type="button" onclick="openCheckout()">Lanjut Bayar</button>
  </div>
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
    <b id="aiRecoMenu">Saran AI D’Celup</b>
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
      <button type="button" class="fo-pickup-option disabled" data-pickup="delivery" disabled>
        <b>Delivery</b><span>Coming soon</span>
      </button>
    </div>

    <div class="fo-checkout-grid" style="margin-top:12px">
      <div class="fo-field"><label>Tanggal Pengambilan</label><input type="date" id="pickupDate" min="<?=$today?>" value="<?=$today?>"></div>
      <div class="fo-field"><label>Jam Pengambilan</label><select id="pickupTime"></select><div class="fo-time-hint" id="pickupTimeHint">Menyiapkan opsi waktu pengambilan…</div></div>
    </div>

    <div class="fo-pickup-summary">
      <div><span>Tipe</span><b id="pickupSummaryType">Ambil di Outlet</b></div>
      <div><span>Tanggal</span><b id="pickupSummaryDate">-</b></div>
      <div><span>Jam</span><b id="pickupSummaryTime">-</b></div>
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
  <div class="fo-payment-total-box"><small>Total yang harus dibayar</small><b id="checkoutTotalText">Rp0</b><div class="items" id="checkoutTotalDetail">Keranjang masih kosong.</div></div>
  <div class="fo-checkout-grid">
    <div class="fo-field"><label>Nama Pemesan</label><input id="customerName" placeholder="Nama Anda" value="<?=fo_e($memberOnline['name'] ?? '')?>"></div>
    <div class="fo-field"><label>Nomor WhatsApp</label><input id="customerPhone" inputmode="tel" placeholder="08xxxxxxxxxx" value="<?=fo_e($memberOnline['phone'] ?? '')?>"></div>
  </div>
  <div class="fo-field"><label>Catatan</label><textarea id="customerNote" placeholder="Catatan untuk kasir, opsional"></textarea></div>
  <div class="fo-payment">
    <button type="button" class="payBtn active" data-pay="qris">QRIS (Midtrans)</button>
  </div>
  <div class="fo-pay-preview active" id="qrisPreview">
    <b>Scan QRIS Lumero</b><br><br>
    <img src="../<?=fo_e(ltrim($paymentQrisImage,'/'))?>?v=<?=time()?>" alt="QRIS Lumero">
    <div style="margin-top:12px"><a class="fo-download-btn" href="../<?=fo_e(ltrim($paymentQrisImage,'/'))?>" download="QRIS-Dcelup.png">Download QRIS</a></div>
    <div class="fo-info" style="margin-top:10px"><?=fo_e($qrisInfo)?>. Simpan bukti pembayaran untuk diverifikasi kasir.</div>
  </div>
  <div class="fo-info" id="payInfo" style="margin-top:10px">Metode pembayaran otomatis: QRIS (Midtrans).</div>
  <form method="post" id="foForm">
    <input type="hidden" name="cart" id="cartInput">
    <input type="hidden" name="pickup_date" id="pickupDateInput">
    <input type="hidden" name="pickup_time" id="pickupTimeInput">
    <input type="hidden" name="payment_method" id="paymentInput" value="">
    <input type="hidden" name="pickup_type" id="pickupTypeInput" value="outlet">
    <input type="hidden" name="customer_name" id="customerNameInput">
    <input type="hidden" name="customer_phone" id="customerPhoneInput">
    <input type="hidden" name="customer_note" id="customerNoteInput">
    <button class="fo-submit">Kirim Online Order</button>
  </form>
  <button class="fo-close" type="button" onclick="closeCheckout()">Tutup</button>
</div></div>


<script>
window.DCELUP_FREE_ORDER_POPUP = <?= $orderPopup ? 'true' : 'false' ?>;
const today = <?=json_encode($today)?>;
const tomorrow = <?=json_encode($tomorrow)?>;
const serverNowTime = <?=json_encode($nowTime)?>;
const priceMap = <?=json_encode($priceMap,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
const aiNarratives = <?=json_encode($aiNarratives,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
const aiNarrativeFallbacks = {
  empty_cart:[{title:'Bingung Pilih Menu?',suggested_menu:'Ayam Crispy Varian Saus',message:'Tenang kak, jangan bingung! Kalau kaka bingung pilih menu, aku bantu ya. Di D’Celup, Kakak wajib coba menu ayam crispy dengan varian saus favorit. Kriuknya mantap, sausnya lumer, aromanya menggoda, dan rasanya bikin pengen nambah. Biar makin sedap, kaka juga bisa tambahkan kentang kriwil dan Matcha, cobain deh, gak akan nyesel!!!',cta_text:'Coba ayam saus favorit'}],
  only_chicken_original:[{title:'Lengkapi Ayam Original',suggested_menu:'Kentang Kriuk dan Matcha',message:'Biar makin lengkap, tambahkan kentang kriuk dan minuman matcha segar sebagai penyempurna hidangan. Dijamin bikin ketagihan deh, hihihi...',cta_text:'Tambah kentang dan matcha'}],
  chicken_original_rice:[{title:'Ayam dan Nasi Sudah Pas',suggested_menu:'Saus Favorit + Minuman',message:'Ayam original plus nasi sudah mantap, Kak. Biar rasanya makin hidup, tambahkan varian saus favorit dan minuman segar. Sekali celup, kriuknya makin lumer di hati.',cta_text:'Tambah saus dan minuman'}],
  chicken_sauce:[{title:'Ayam Saus Sudah Mantap',suggested_menu:'Nasi + Kentang + Matcha',message:'Pilihan ayam saus Kakak sudah juara. Biar makin puas, tambahkan nasi hangat, kentang kriwil, atau Matcha segar. Lengkapnya dapet, nikmatnya makin nempel.',cta_text:'Lengkapi dengan nasi atau minuman'}],
  only_potato:[{title:'Kentang Kriwil Mantap',suggested_menu:'Ayam Crispy dan Minuman',message:'Kentangnya sudah cocok jadi teman ngemil. Biar lebih puas, pasangkan dengan ayam crispy dan minuman segar favorit Kakak.',cta_text:'Tambah ayam dan minuman'}],
  only_drink:[{title:'Minuman Segar Siap',suggested_menu:'Ayam Crispy Saus',message:'Minumannya sudah segar, Kak. Sekarang waktunya tambahkan ayam crispy varian saus favorit. Kriuknya mantap, sausnya lumer, cocok banget jadi pasangan minuman Kakak.',cta_text:'Tambah ayam crispy'}],
  drink_potato:[{title:'Minuman dan Kentang Sudah Oke',suggested_menu:'Ayam Crispy Varian Saus',message:'Minuman dan kentang sudah jadi duet yang asik. Tapi biar makin lengkap, tambahkan ayam crispy saus D’Celup. Dijamin makin kenyang dan makin puas.',cta_text:'Tambah ayam saus'}],
  drink_chicken:[{title:'Ayam dan Minuman Sudah Mantap',suggested_menu:'Kentang Kriwil',message:'Ayam dan minuman Kakak sudah pas banget. Biar teksturnya makin rame, tambahkan kentang kriwil yang renyah. Jadi lengkap, gurih, segar, dan nagih.',cta_text:'Tambah kentang kriwil'}],
  all_menu:[{title:'Pesanan Sudah Lengkap',suggested_menu:'Tambahan Saus Favorit',message:'Wah, pilihan Kakak sudah lengkap banget! Ayam ada, kentang ada, minuman juga ada. Kalau mau makin lumer, tambahkan saus favorit ekstra biar setiap gigitan makin seru.',cta_text:'Tambah saus ekstra'}],
  only_sauce:[{title:'Sausnya Sudah Siap',suggested_menu:'Ayam Crispy Original',message:'Saus favoritnya sudah dipilih, Kak. Sekarang tinggal pasangkan dengan ayam crispy original yang kriuknya mantap. Biar sausnya punya pasangan terbaik.',cta_text:'Tambah ayam crispy'}],
  only_rice:[{title:'Nasinya Sudah Siap',suggested_menu:'Ayam Crispy Saus',message:'Nasi hangatnya sudah siap, Kak. Biar jadi hidangan lengkap, tambahkan ayam crispy varian saus favorit. Kriuk, lumer, dan bikin makan makin semangat.',cta_text:'Tambah ayam saus'}],
  whole_chicken:[{title:'Ayam 1 Ekor Mantap',suggested_menu:'Saus Ekstra dan Minuman',message:'Wah, 1 ekor ayam sudah pilihan mantap untuk rame-rame. Biar makin seru, tambahkan saus ekstra dan minuman segar supaya semua kebagian rasa favorit.',cta_text:'Tambah saus dan minuman'}],
  whole_chicken_sauce:[{title:'Ayam 1 Ekor Saus Juara',suggested_menu:'Nasi dan Matcha',message:'Ayam 1 ekor plus saus sudah paket yang menggoda banget. Biar makin lengkap untuk disantap bareng, tambahkan nasi hangat dan Matcha segar.',cta_text:'Tambah nasi dan matcha'}],
  promo_window:[{title:'Jam Promo Spesial',suggested_menu:'Combo Hemat Jam Spesial',message:'Kakak lagi masuk jam promo nih! Ini waktu paling pas ambil paket combo hemat. Ayamnya nikmat, nasinya ada, harganya lebih bersahabat, dan rasanya tetap juara.',cta_text:'Ambil combo promo sekarang'}],
  general:[{title:'Saran Menu D’Celup',suggested_menu:'Ayam Crispy Varian Saus',message:'Kakak bisa pilih ayam crispy varian saus favorit D’Celup. Kriuknya mantap, sausnya lumer, dan cocok dilengkapi kentang atau minuman segar.',cta_text:'Pilih menu favorit'}]
};
const comboWindowActive = <?=$comboWindowActive ? 'true' : 'false'?>;
const sauces = <?=json_encode(array_values(array_map(fn($s)=>['id'=>(int)$s['id'],'name'=>(string)$s['name']],$data['sauces'])),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)?>;
let cart = [];
let payment = '';
let selectedPickupType = 'outlet';
let lastAddedName = '';

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
  const list=document.getElementById('cartList'); list.innerHTML='';
  const sideList=document.getElementById('sideOrderItems');
  if(sideList) sideList.innerHTML='';

  let total=0, count=0;
  cart.forEach((it,i)=>{
    total += Number(it.price||0) * Number(it.qty||1);
    count += Number(it.qty||1);
    const name = it.type==='chicken' ? buildChickenText(it) : it.name;
    const div=document.createElement('div');
    div.className='fo-cart-item';
    div.innerHTML=`<div><b>${escapeHtml(name)}</b><br><small>${rupiah(it.price)} x ${it.qty||1}</small></div><div class="fo-qty"><button type="button" onclick="chgQty(${i},-1)">-</button><b>${it.qty||1}</b><button type="button" onclick="chgQty(${i},1)">+</button></div>`;
    list.appendChild(div);

    if(sideList){
      const sideDiv=document.createElement('div');
      sideDiv.className='fo-cart-item';
      sideDiv.innerHTML=`<div class="fo-cart-item-left"><div class="fo-cart-item-text"><b>${escapeHtml(name)}</b><small>${rupiah(it.price)}</small></div></div><div class="fo-qty"><button type="button" onclick="chgQty(${i},-1)">-</button><span>${it.qty||1}</span><button type="button" onclick="chgQty(${i},1)">+</button></div>`;
      sideList.appendChild(sideDiv);
    }
  });
  if(!cart.length){
    list.innerHTML='<div class="fo-info">Keranjang masih kosong.</div>';
    if(sideList){
      sideList.innerHTML='<div class="fo-order-empty"><div class="icon">🛒</div><p>Belum ada menu dipilih</p><small>Klik menu di kiri untuk menambah pesanan</small></div>';
    }
  }
  document.getElementById('cartCount').textContent=count;
  document.getElementById('totalText').textContent=rupiah(total);
  const sideBadge=document.getElementById('sideCartBadge'); if(sideBadge) sideBadge.textContent=count;
  const sideTotal=document.getElementById('sideTotalText'); if(sideTotal) sideTotal.textContent=rupiah(total);
  const sideCount=document.getElementById('sideCountText'); if(sideCount) sideCount.textContent=count+' item';

  const checkoutTotal=document.getElementById('checkoutTotalText');
  if(checkoutTotal) checkoutTotal.textContent=rupiah(total);
  const checkoutDetail=document.getElementById('checkoutTotalDetail');
  if(checkoutDetail){
    const names=cart.slice(0,4).map(it=>it.type==='chicken'?buildChickenText(it):it.name);
    checkoutDetail.textContent = count ? (names.join(', ')+(count>4?' + '+(count-4)+' item lainnya':'')) : 'Keranjang masih kosong.';
  }
  const pickupTotal=document.getElementById('pickupSummaryTotal');
  if(pickupTotal) pickupTotal.textContent=rupiah(total);
  updateFooterDetail(total,count);
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
function chgQty(i,d){ cart[i].qty=(cart[i].qty||1)+d; if(cart[i].qty<=0) cart.splice(i,1); renderCart(); }
function openCheckout(skipPickupConfirm=false){
  if(!cart.length){
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
  setPayment('qris');
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
  music: localStorage.getItem('dcelup_online_music') !== 'off',
  guide: localStorage.getItem('dcelup_online_guide_voice') !== 'off'
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
  localStorage.setItem('dcelup_online_music', foAudioSettings.music ? 'on' : 'off');
  if(foAudioSettings.music) startBgm(); else stopBgm();
  updateAudioToggleButtons();
}
function toggleGuideSetting(){
  foAudioSettings.guide=!isGuideOn();
  localStorage.setItem('dcelup_online_guide_voice', foAudioSettings.guide ? 'on' : 'off');
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
function currentCartTotal(){ return cart.reduce((sum,it)=>sum+(Number(it.price||0)*Number(it.qty||1)),0); }

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
  }
  try{
    if(topPhone && topPhone.value) localStorage.setItem('dcelup_customer_phone', normalizePhoneClient(topPhone.value));
    if(topName && topName.value) localStorage.setItem('dcelup_customer_name', topName.value);
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
        try{ localStorage.setItem('dcelup_customer_phone', data.phone||phone); localStorage.setItem('dcelup_customer_name', data.name||''); }catch(e){}
        setVideoPhoneInfo('Nomor dikenali. Nama akan otomatis diisi.','ok');
        return true;
      }
      applyCustomerData('', (data && data.phone) || phone);
      try{ localStorage.setItem('dcelup_customer_phone', (data && data.phone) || phone); }catch(e){}
      setVideoPhoneInfo('Nomor tersimpan. Isi nama saat atur pengambilan.','warn');
      return false;
    })
    .catch(()=>{
      applyCustomerData('', phone);
      try{ localStorage.setItem('dcelup_customer_phone', phone); }catch(e){}
      setVideoPhoneInfo('Nomor dipakai untuk order ini.','warn');
      return false;
    });
}

function initCustomerMemory(){
  try{
    const p=localStorage.getItem('dcelup_customer_phone')||'';
    const n=localStorage.getItem('dcelup_customer_name')||'';
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
  if(typeEl) typeEl.textContent=selectedPickupType==='outlet'?'Ambil di Outlet':'Delivery';
  if(dateEl) dateEl.textContent=document.getElementById('pickupDate')?.value || '-';
  if(timeEl) timeEl.textContent=(document.getElementById('pickupTime')?.value || '-')+' WIB';
  if(totalEl) totalEl.textContent=rupiah(currentCartTotal());
}
function showPickupConfirm(){
  syncCustomerFields('top');
  if(!cart.length){ alert('Keranjang masih kosong.'); return; }
  const m=document.getElementById('pickupConfirmModal');
  buildPickupOptions();
  renderCart();
  updatePickupSummary();
  if(m) m.classList.add('show');
  setTimeout(()=>{ const p=document.getElementById('customerPhoneTop'); if(p && !p.value) p.focus(); },120);
  foPlay('foVoiceWaktuAmbil');
}
function closePickupConfirm(){ const m=document.getElementById('pickupConfirmModal'); if(m) m.classList.remove('show'); }
function continueToPayment(){
  syncCustomerFields('top');
  const nameVal=(document.getElementById('customerNameTop')?.value || '').trim();
  const phoneVal=normalizePhoneClient(document.getElementById('customerPhoneTop')?.value || '');
  if(!nameVal || !phoneVal){ foPlay('foVoiceMaaf'); alert('Mohon isi nama dan nomor WhatsApp terlebih dahulu.'); return; }
  if(!document.getElementById('pickupTime')?.value){ alert('Jam pengambilan belum tersedia. Silakan pilih tanggal yang valid.'); return; }
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
  if(title) title.textContent=rec.suggested_menu || rec.title || 'Saran Menu D’Celup';
  if(text) text.textContent=(rec.scenario==='empty_cart' ? (rec.cta_text || 'Aku bantu pilihkan menu unggulan D’Celup.') : (rec.cta_text || 'Aku punya saran menu yang cocok untuk melengkapi pesanan Kakak.'));
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
  const picked=list.length ? list[0] : {title:'Saran AI D’Celup',suggested_menu:'Ayam Crispy Varian Saus',message:'Kakak bisa pilih ayam crispy varian saus favorit D’Celup. Kriuknya mantap, sausnya lumer, dan cocok dilengkapi kentang atau minuman segar.',cta_text:'Pilih menu favorit'};
  picked.scenario=scenario;
  return picked;
}
function updateAiPanelContent(){
  const rec=pickAiNarrative();
  const textEl=document.getElementById('aiRecoText');
  const menuEl=document.getElementById('aiRecoMenu');
  const reasonEl=document.getElementById('aiRecoReason');
  if(textEl) textEl.textContent=rec.message || '';
  if(menuEl) menuEl.textContent=rec.suggested_menu || rec.title || 'Saran AI D’Celup';
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

  const total=currentCartTotal();
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
function getCartTotal(){ return (cart||[]).reduce((sum,it)=>sum+(Number(it.price||0)*Number(it.qty||1)),0); }
function setPayment(method){
  payment=method;
  document.querySelectorAll('.payBtn').forEach(x=>x.classList.toggle('active',x.dataset.pay===method));
  document.querySelectorAll('.fo-pay-preview').forEach(x=>x.classList.remove('active'));
  const active=document.getElementById(method+'Preview'); if(active) active.classList.add('active');
  document.getElementById('paymentInput').value=method;
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
  selectedPickupType=btn.dataset.pickup || 'outlet';
  document.querySelectorAll('.fo-pickup-option').forEach(x=>x.classList.toggle('active',x===btn));
  const input=document.getElementById('pickupTypeInput'); if(input) input.value=selectedPickupType;
}));
document.querySelectorAll('.payBtn').forEach(btn=>btn.addEventListener('click',()=>setPayment(btn.dataset.pay)));
// pickup option delegated v9
document.addEventListener('click',function(e){
  const btn=e.target.closest('.fo-pickup-option');
  if(!btn || btn.disabled) return;
  selectedPickupType=btn.dataset.pickup || 'outlet';
  document.querySelectorAll('.fo-pickup-option').forEach(x=>x.classList.toggle('active',x===btn));
  updatePickupSummary();
});

document.querySelectorAll('.fo-tab[data-target]').forEach(btn=>btn.addEventListener('click',()=>{ document.querySelectorAll('.fo-tab').forEach(x=>x.classList.remove('active')); btn.classList.add('active'); const target=document.getElementById(btn.dataset.target); if(target) target.scrollIntoView({behavior:'smooth',block:'start'}); }));
const pickupDateEl=document.getElementById('pickupDate');
if(pickupDateEl) pickupDateEl.addEventListener('change',()=>{ buildPickupOptions(); updatePickupSummary(); });
const pickupTimeEl=document.getElementById('pickupTime');
if(pickupTimeEl) pickupTimeEl.addEventListener('change',updatePickupSummary);

document.getElementById('foForm').addEventListener('submit',e=>{
  syncCustomerFields('top');
  const nameVal=(document.getElementById('customerNameTop')?.value || document.getElementById('customerName')?.value || '').trim();
  const phoneVal=normalizePhoneClient(document.getElementById('customerPhoneTop')?.value || document.getElementById('customerPhone')?.value || '');
  if(!cart.length){ e.preventDefault(); alert('Keranjang masih kosong.'); return; }
  if(!nameVal || !phoneVal){ e.preventDefault(); foPlay('foVoiceMaaf'); alert('Mohon isi nama dan nomor WhatsApp terlebih dahulu.'); return; }
  if(!payment){ e.preventDefault(); foPlay('foVoiceOpsiBayar'); alert('Mohon pilih metode pembayaran terlebih dahulu.'); return; }
  if(payment==='point'){ const need=Math.ceil(getCartTotal()/<?=max(1,(int)$memberPointValue)?>); const bal=<?=max(0,(int)$memberPointBalance)?>; if(need>bal){ e.preventDefault(); alert('Point belum mencukupi. Butuh '+need.toLocaleString('id-ID')+' point untuk membayar total belanja ini.'); return; } }
  if(!document.getElementById('pickupTime').value){ e.preventDefault(); alert('Jam pengambilan belum tersedia. Silakan pilih tanggal yang valid.'); return; }
  try{ localStorage.setItem('dcelup_customer_phone', phoneVal); localStorage.setItem('dcelup_customer_name', nameVal); }catch(err){}
  document.getElementById('cartInput').value=JSON.stringify(cart);
  document.getElementById('pickupDateInput').value=document.getElementById('pickupDate').value;
  document.getElementById('pickupTimeInput').value=document.getElementById('pickupTime').value;
  document.getElementById('customerNameInput').value=nameVal;
  document.getElementById('customerPhoneInput').value=phoneVal;
  document.getElementById('customerNoteInput').value=document.getElementById('customerNote').value;
  document.getElementById('pickupTypeInput').value=selectedPickupType;
});

(function(){
  const overlay=document.getElementById('freeOrderVideoOverlay');
  const player=document.getElementById('freeOrderVideoPlayer');
  const start=document.getElementById('startFreeOrderBtn');
  function showCover(){ if(!overlay) return; overlay.classList.add('show'); document.body.style.overflow='hidden'; if(player){ try{player.currentTime=0; player.muted=true; const p=player.play(); if(p&&p.catch)p.catch(()=>{});}catch(e){} } }
  function hideCover(){ startBgm(); const vp=document.getElementById('videoPhoneInput'); if(vp && vp.value){ lookupCustomerFromVideo(false); } if(!overlay) return; overlay.classList.remove('show'); document.body.style.overflow=''; if(player){ try{player.pause();}catch(e){} } foPlay('foVoiceWelcome'); }
  if(!window.DCELUP_FREE_ORDER_POPUP) setTimeout(showCover,180);
  if(start) start.addEventListener('click', hideCover);
})();
if(window.DCELUP_FREE_ORDER_POPUP){ setTimeout(()=>foPlay('foVoiceSuccess'), 500); }

updateAudioToggleButtons();
buildPickupOptions();
initCustomerMemory();
initChickenCards();
renderCart();
</script>
</body>
</html>
