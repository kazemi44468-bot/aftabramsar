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
  $fields = ['year','month','title','cumulative_percent','summary','event_text','interview_text','actions_json','gallery_json','next_steps','published','created_at'];
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
