<?php
require_once dirname(__DIR__).'/config/app.php';
require_once ROOT.'/config/database.php';
ini_set('session.use_strict_mode', '1');
session_name('nexora_session');
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off','path'=>BASE_URL ?: '/']);
session_start();
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: no-store');
if (isset($_SESSION['last_seen']) && time()-$_SESSION['last_seen'] > 7200) $_SESSION=[];
$_SESSION['last_seen']=time();
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
set_exception_handler(function(Throwable $e) {
    error_log((string)$e); http_response_code(500);
    if (wants_json()) { header('Content-Type: application/json'); echo json_encode(['ok'=>false,'message'=>'Unable to complete this request. Please try again.']); }
    else echo '<!doctype html><meta name="viewport" content="width=device-width"><title>NEXORA — setup</title><main style="font:18px sans-serif;max-width:650px;margin:10vh auto;padding:25px"><h1>NEXORA STORE</h1><p>We could not complete this request. If you are setting up the store, import database.sql and check config/database.php. Otherwise, please try again.</p><a href="'.e(url('index.php')).'">Return to store</a></main>';
});
function e(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function url(string $path=''): string { return BASE_URL.'/'.ltrim($path,'/'); }
function go(string $path): never { header('Location: '.url($path)); exit; }
function q(string $sql,array $params=[]): PDOStatement { $stmt=db()->prepare($sql); $stmt->execute($params); return $stmt; }
function one(string $sql,array $params=[]): ?array { return q($sql,$params)->fetch() ?: null; }
function all(string $sql,array $params=[]): array { return q($sql,$params)->fetchAll(); }
function money(mixed $amount): string { return '₱'.number_format((float)$amount,2); }
function cents(mixed $amount): int { return (int)round((float)$amount*100); }
function price(array $p): float { return (float)($p['sale_price'] ?? $p['price']); }
function post(string $key,string $default=''): string { return is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : $default; }
function get(string $key,string $default=''): string { return is_string($_GET[$key] ?? null) ? trim($_GET[$key]) : $default; }
function csrf(): string { return '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function check_csrf(): void { if (!hash_equals($_SESSION['csrf'],post('csrf'))) { http_response_code(403); if(wants_json()) json_response(['ok'=>false,'message'=>'Your session changed. Refresh the page.'],403); exit('Invalid form token. Please reload the page.'); } }
function wants_json(): bool { return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'; }
function json_response(array $data,int $status=200): never { http_response_code($status); header('Content-Type: application/json'); echo json_encode($data,JSON_HEX_TAG|JSON_HEX_AMP); exit; }
function flash(string $message): void { $_SESSION['flash']=$message; }
function user(): ?array {
    static $loaded=false,$user=null;
    if (!$loaded) {
        $loaded=true;
        if (!empty($_SESSION['user_id'])) $user=one('SELECT * FROM users WHERE id=? AND active=1',[$_SESSION['user_id']]);
        // Password changes/resets invalidate previously authenticated sessions.
        if ($user && !hash_equals(hash('sha256',$user['password']),$_SESSION['user_auth'] ?? '')) $user=null;
        if(!$user) unset($_SESSION['user_id'],$_SESSION['user_auth']);
    }
    return $user;
}
function require_user(): array { $u=user(); if(!$u) { if(wants_json()) json_response(['ok'=>false,'message'=>'Please sign in to continue.','redirect'=>url('login.php')],401); flash('Please sign in to continue.'); go('login.php'); } return $u; }
function admin(): ?array {
    $a=empty($_SESSION['admin_id']) ? null : one('SELECT * FROM admins WHERE id=?',[$_SESSION['admin_id']]);
    if($a && !hash_equals(hash('sha256',$a['password']),$_SESSION['admin_auth'] ?? '')) $a=null;
    if(!$a) unset($_SESSION['admin_id'],$_SESSION['admin_auth']);
    return $a;
}
function require_admin(): array { $a=admin(); if(!$a) go('admin/login.php'); if($a['must_change_password'] && basename($_SERVER['SCRIPT_NAME'])!=='password.php') go('admin/password.php'); return $a; }
function rate_limit(string $name,int $limit=15,int $seconds=900): void {
    $key=hash('sha256',$name.'|'.($_SERVER['REMOTE_ADDR'] ?? 'local'));
    q('INSERT INTO rate_limits(bucket,attempts,started_at) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(started_at < DATE_SUB(NOW(), INTERVAL ? SECOND),1,attempts+1), started_at=IF(started_at < DATE_SUB(NOW(), INTERVAL ? SECOND),NOW(),started_at)',[$key,$seconds,$seconds]);
    if ((int)one('SELECT attempts FROM rate_limits WHERE bucket=?',[$key])['attempts']>$limit) throw new DomainException('Too many attempts. Please wait a few minutes and try again.');
}
function require_text(string $value,string $label,int $min=1,int $max=255): string { if(mb_strlen($value)<$min || mb_strlen($value)>$max) throw new DomainException("$label must contain $min–$max characters."); return $value; }
function email_value(string $email): string { if(strlen($email)>190 || !filter_var($email,FILTER_VALIDATE_EMAIL)) throw new DomainException('Enter a valid email address.'); return strtolower($email); }
function password_value(string $password): string { if(strlen($password)<10 || strlen($password)>72) throw new DomainException('Use a password of 10–72 characters.'); return $password; }
function phone_value(string $phone): string { if(!preg_match('/^[+0-9()\s-]{7,30}$/',$phone)) throw new DomainException('Enter a valid phone number.'); return $phone; }
function categories(): array { return all('SELECT * FROM categories ORDER BY id'); }
function product(int $id,bool $lock=false): ?array { return one('SELECT p.*,c.name category_name,(SELECT COUNT(*) FROM reviews r WHERE r.product_id=p.id) review_count FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.active=1'.($lock?' FOR UPDATE':''),[$id]); }
function options(string $csv): array { return array_values(array_filter(array_map('trim',explode(',',$csv)))); }
function image_url(string $path): string { return url(preg_match('~^(assets/images/|uploads/(products|categories)/)[a-zA-Z0-9_.-]+$~',$path)?$path:'assets/images/electronics.svg'); }
// Upload only actual raster images; never trust the submitted filename or extension.
function upload_image(string $field,string $folder='products'): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error']===UPLOAD_ERR_NO_FILE) return null;
    $file=$_FILES[$field];
    if ($file['error']!==UPLOAD_ERR_OK || $file['size']>4*1024*1024) throw new DomainException('Image uploads must be under 4 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $dimensions=@getimagesize($file['tmp_name']);
    if(!isset($types[$mime]) || !$dimensions || $dimensions[0]>6000 || $dimensions[1]>6000) throw new DomainException('Upload a valid JPG, PNG or WebP image up to 6000 pixels per side.');
    $path='uploads/'.$folder.'/'.bin2hex(random_bytes(16)).'.'.$types[$mime];
    if(!move_uploaded_file($file['tmp_name'],ROOT.'/'.$path)) throw new DomainException('The upload directory is not writable.');
    return $path;
}
require_once ROOT.'/includes/cart-functions.php';

function validated_address(): array {
 $a=[]; foreach(['province','city','barangay','street','postal_code'] as $field) $a[$field]=require_text(post($field),ucfirst(str_replace('_',' ',$field)),1,$field==='street'?255:($field==='postal_code'?12:100)); return $a;
}
function save_address(int $uid,array $a): void {
 q('INSERT INTO addresses(user_id,province,city,barangay,street,postal_code) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE province=VALUES(province),city=VALUES(city),barangay=VALUES(barangay),street=VALUES(street),postal_code=VALUES(postal_code)',array_merge([$uid],array_values($a)));
}
