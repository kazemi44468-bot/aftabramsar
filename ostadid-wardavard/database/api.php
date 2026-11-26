<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) { http_response_code(503); echo json_encode(['ok'=>false,'error'=>'Database configuration is missing']); exit; }
$config = require $configFile;
$origin = $config['allowed_origin'] ?? '';
if ($origin !== '') { header('Access-Control-Allow-Origin: ' . $origin); header('Vary: Origin'); }
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = preg_replace('/^Bearer\\s+/i', '', $auth);
if (empty($config['api_token']) || !hash_equals((string)$config['api_token'], (string)$token)) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'Unauthorized']); exit; }
try {
 if (($config['driver'] ?? 'mysql') === 'sqlite') {
  $dir = dirname($config['sqlite_path']);
  if (!is_dir($dir)) mkdir($dir, 0750, true);
  $pdo = new PDO('sqlite:' . $config['sqlite_path']);
 } else {
  $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['host'], (int)($config['port'] ?? 3306), $config['database'], $config['charset'] ?? 'utf8mb4');
  $pdo = new PDO($dsn, $config['username'], $config['password']);
 }
 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
 $method = $_SERVER['REQUEST_METHOD'];
 $action = $_GET['action'] ?? '';
 if ($action === 'health' && $method === 'GET') { echo json_encode(['ok'=>true,'driver'=>$config['driver'],'time'=>date(DATE_ATOM)]); exit; }
 if ($action === 'upload_image' && $method === 'POST') {
  if (empty($_FILES['image']) || !is_uploaded_file($_FILES['image']['tmp_name'])) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'فایل تصویر دریافت نشد.']); exit; }
  $file = $_FILES['image'];
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int)$file['size'] > 15 * 1024 * 1024) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'حجم تصویر باید کمتر از ۱۵ مگابایت باشد.']); exit; }
  $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($file['tmp_name']);
  $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
  if (!isset($allowed[$mime])) { http_response_code(415); echo json_encode(['ok'=>false,'error'=>'فقط تصویر JPG، PNG یا WebP مجاز است.']); exit; }
  $dir = dirname(__DIR__) . '/assets/reports';
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'ساخت پوشه تصاویر ممکن نشد.']); exit; }
  $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
  if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'ذخیره تصویر انجام نشد.']); exit; }
  echo json_encode(['ok'=>true,'url'=>'../assets/reports/' . $name], JSON_UNESCAPED_UNICODE); exit;
 }
 if ($action === 'upload_video' && $method === 'POST') {
  if (empty($_FILES['video']) || !is_uploaded_file($_FILES['video']['tmp_name'])) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'فایل ویدئو دریافت نشد.']); exit; }
  $file = $_FILES['video'];
  if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int)$file['size'] > 100 * 1024 * 1024) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'حجم فایل باید کمتر از ۱۰۰ مگابایت باشد.']); exit; }
  $finfo = new finfo(FILEINFO_MIME_TYPE); $mime = $finfo->file($file['tmp_name']);
  $allowed = ['video/mp4'=>'mp4','video/webm'=>'webm','video/quicktime'=>'mov'];
  if (!isset($allowed[$mime])) { http_response_code(415); echo json_encode(['ok'=>false,'error'=>'فقط ویدئوی MP4، WebM یا MOV مجاز است.']); exit; }
  $dir = dirname(__DIR__) . '/assets/videos';
  if (!is_dir($dir) && !mkdir($dir, 0755, true)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'ساخت پوشه ویدئو ممکن نشد.']); exit; }
  $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
  if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>'ذخیره ویدئو انجام نشد.']); exit; }
  echo json_encode(['ok'=>true,'url'=>'../assets/videos/' . $name], JSON_UNESCAPED_UNICODE); exit;
 }
 $tables = ['requests','monthly_reports','announcements'];
 if (!in_array($action, $tables, true)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Unknown action']); exit; }
 if ($method === 'GET') {
  $stmt = $pdo->query('SELECT * FROM ' . $action . ' ORDER BY id DESC LIMIT 500');
  echo json_encode(['ok'=>true,'items'=>$stmt->fetchAll()], JSON_UNESCAPED_UNICODE); exit;
 }
 $input = json_decode(file_get_contents('php://input'), true) ?? [];
 $op = $input['op'] ?? 'create';
 if ($op === 'delete') {
  $stmt = $pdo->prepare('DELETE FROM ' . $action . ' WHERE id = :id');
  $stmt->execute([':id'=>(int)($input['id'] ?? 0)]);
  echo json_encode(['ok'=>true,'deleted'=>$stmt->rowCount()]); exit;
 }
 if ($action === 'requests') {
  $fields = ['tracking_code','request_type','full_name','mobile','subject','description','status','admin_note','created_at','updated_at'];
 } elseif ($action === 'monthly_reports') {
  $fields = ['year','month','title','cumulative_percent','planned_percent','schedule_note','summary','event_text','interview_text','interviews_json','strengths_json','weaknesses_json','news_json','videos_json','actions_json','gallery_json','next_steps','published','created_at'];
 } else {
  $fields = ['title','category','body','published_at','published'];
 }
 $values = [];
 foreach ($fields as $field) { $values[$field] = $input[$field] ?? ''; }
 if ($op === 'update') {
  $set = implode(', ', array_map(fn($f)=>$f.' = :'.$f, $fields));
  $values['id'] = (int)($input['id'] ?? 0);
  $stmt = $pdo->prepare('UPDATE ' . $action . ' SET ' . $set . ' WHERE id = :id');
 } else {
  $cols = implode(',', $fields);
  $marks = implode(',', array_map(fn($f)=>':'.$f, $fields));
  $stmt = $pdo->prepare('INSERT INTO ' . $action . ' (' . $cols . ') VALUES (' . $marks . ')');
 }
 $stmt->execute(array_combine(array_map(fn($f)=>':'.$f, array_keys($values)), array_values($values)));
 echo json_encode(['ok'=>true,'id'=>$op === 'update' ? $values['id'] : (int)$pdo->lastInsertId()]);
} catch (Throwable $e) {
 http_response_code(500);
 error_log($e->getMessage());
 echo json_encode(['ok'=>false,'error'=>'Database operation failed']);
}
