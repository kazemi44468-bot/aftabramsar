<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) { http_response_code(503); echo json_encode(['ok'=>false,'error'=>'سرویس ثبت درخواست هنوز پیکربندی نشده است.']); exit; }
$config = require $configFile;
$origin = $config['allowed_origin'] ?? '';
if ($origin !== '') { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); }
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
try {
 if (($config['driver'] ?? 'mysql') === 'sqlite') {
  $dir = dirname($config['sqlite_path']); if (!is_dir($dir)) mkdir($dir, 0750, true);
  $pdo = new PDO('sqlite:' . $config['sqlite_path']);
 } else {
  $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['host'], (int)($config['port'] ?? 3306), $config['database'], $config['charset'] ?? 'utf8mb4');
  $pdo = new PDO($dsn, $config['username'], $config['password']);
 }
 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
 $action = $_GET['action'] ?? '';
 if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'submit') {
  session_start();
  $now = time();
  if (!empty($_SESSION['last_request_submit']) && $now - (int)$_SESSION['last_request_submit'] < 20) {
   http_response_code(429); echo json_encode(['ok'=>false,'error'=>'لطفاً چند لحظه بعد دوباره تلاش کنید.']); exit;
  }
  $input = json_decode(file_get_contents('php://input'), true) ?? [];
  $type = trim((string)($input['request_type'] ?? ''));
  $name = trim((string)($input['full_name'] ?? ''));
  $mobileRaw = strtr((string)($input['mobile'] ?? ''), ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9']);
  $mobile = preg_replace('/[^0-9+]/', '', $mobileRaw);
  $subject = trim((string)($input['subject'] ?? ''));
  $description = trim((string)($input['description'] ?? ''));
  if ($type === '' || mb_strlen($name) < 3 || !preg_match('/^[0-9+]{10,15}$/', $mobile) || mb_strlen($subject) < 3 || mb_strlen($description) < 5) {
   http_response_code(422); echo json_encode(['ok'=>false,'error'=>'لطفاً همه فیلدهای ضروری را با اطلاعات معتبر تکمیل کنید.']); exit;
  }
  if (mb_strlen($name)>190 || mb_strlen($subject)>190 || mb_strlen($description)>5000) {
   http_response_code(422); echo json_encode(['ok'=>false,'error'=>'حجم یکی از فیلدها بیش از حد مجاز است.']); exit;
  }
  $code = 'WV-' . strtoupper(bin2hex(random_bytes(8)));
  $stamp = date('Y-m-d H:i:s');
  $stmt = $pdo->prepare('INSERT INTO requests (tracking_code,request_type,full_name,mobile,subject,description,status,admin_note,created_at,updated_at) VALUES (:code,:type,:name,:mobile,:subject,:description,:status,:note,:created,:updated)');
  $stmt->execute([':code'=>$code,':type'=>$type,':name'=>$name,':mobile'=>$mobile,':subject'=>$subject,':description'=>$description,':status'=>'ثبت اولیه',':note'=>'',':created'=>$stamp,':updated'=>$stamp]);
  $_SESSION['last_request_submit'] = $now;
  echo json_encode(['ok'=>true,'tracking_code'=>$code,'status'=>'ثبت اولیه','created_at'=>$stamp], JSON_UNESCAPED_UNICODE); exit;
 }
 if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'reports') {
  $stmt = $pdo->query('SELECT year,month,title,cumulative_percent,summary,event_text,interview_text,actions_json,gallery_json,next_steps,created_at FROM monthly_reports WHERE published = 1 ORDER BY year DESC, month DESC LIMIT 120');
  echo json_encode(['ok'=>true,'items'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE); exit;
 }
 if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'news') {
  $stmt = $pdo->query('SELECT title,category,body,published_at FROM announcements WHERE published = 1 ORDER BY published_at DESC LIMIT 100');
  echo json_encode(['ok'=>true,'items'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE); exit;
 }
 if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'track') {
  $code = strtoupper(trim((string)($_GET['code'] ?? '')));
  if (!preg_match('/^WV-[A-F0-9]{16}$/', $code)) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'کد پیگیری معتبر نیست.']); exit; }
  $stmt = $pdo->prepare('SELECT tracking_code,request_type,subject,status,created_at,updated_at FROM requests WHERE tracking_code = :code LIMIT 1');
  $stmt->execute([':code'=>$code]);
  $row = $stmt->fetch();
  if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'error'=>'پرونده‌ای با این کد پیگیری پیدا نشد.']); exit; }
  echo json_encode(['ok'=>true,'item'=>$row], JSON_UNESCAPED_UNICODE); exit;
 }
 http_response_code(400); echo json_encode(['ok'=>false,'error'=>'درخواست نامعتبر است.']);
} catch (Throwable $e) {
 http_response_code(500); error_log($e->getMessage());
 echo json_encode(['ok'=>false,'error'=>'در حال حاضر امکان ارتباط با پایگاه داده وجود ندارد.'], JSON_UNESCAPED_UNICODE);
}
