<?php
require "PHPMailer/PHPMailerAutoload.php";

$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');


if ($isHttps) {

    // Production (HTTPS)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'curtzhom_curtzhom_curtz_home');
    define('DB_USER', 'curtzhom_curtzhom_curtz_home');
    define('DB_PASS', 'curtzhom_curtzhom_curtz_home');

    define('APP_URL', 'https://curtzhome.top');

} else {

    // Local Development (HTTP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'curtzhome');
    define('DB_USER', 'root');
    define('DB_PASS', '');

    define('APP_URL', 'http://localhost/estate-site');
}

define('DB_CHARSET', 'utf8mb4');
define('JWT_SECRET', 'CurtzHome_S3cr3t_K3y_2025!');
define('APP_NAME', 'Curtz Home');
define('APP_EMAIL', 'support@curtzhome.top');

/*
|--------------------------------------------------------------------------
| Headers
|--------------------------------------------------------------------------
*/
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn  = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
    $opts = [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false];
    try { $pdo = new PDO($dsn, DB_USER, DB_PASS, $opts); }
    catch (PDOException $e) { jsonError('Database error: '.$e->getMessage(), 500); }
    return $pdo;
}

function jsonSuccess($data=[], int $code=200): void {
    http_response_code($code);
    echo json_encode(['success'=>true,'data'=>$data]);
    exit;
}
function jsonError(string $msg, int $code=400, $errors=null): void {
    http_response_code($code);
    $r = ['success'=>false,'message'=>$msg];
    if ($errors) $r['errors'] = $errors;
    echo json_encode($r);
    exit;
}

function jwtEncode(array $payload): string {
    $h = base64_encode(json_encode(['alg'=>'HS256','typ'=>'JWT']));
    $payload['iat'] = time(); $payload['exp'] = time()+86400*7;
    $p = base64_encode(json_encode($payload));
    $s = base64_encode(hash_hmac('sha256', "$h.$p", JWT_SECRET, true));
    return "$h.$p.$s";
}
function jwtDecode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts)!==3) return null;
    [$h,$p,$s] = $parts;
    if (!hash_equals(base64_encode(hash_hmac('sha256',"$h.$p",JWT_SECRET,true)),$s)) return null;
    $payload = json_decode(base64_decode($p), true);
    return ($payload && $payload['exp']>time()) ? $payload : null;
}

function requireAuth(): array {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? (getallheaders()['Authorization'] ?? '');
    $token = str_replace('Bearer ','',$auth);
    $payload = jwtDecode($token);
    if (!$payload) jsonError('Unauthorised. Please log in.',401);
    return $payload;
}
function requireAdmin(): array {
    $user = requireAuth();
    if (($user['role']??'')!=='admin') jsonError('Admin access required.',403);
    return $user;
}

function input(): array {
    $json = json_decode(file_get_contents('php://input'), true);
    return $json ?? array_merge($_POST,$_GET);
}

function sendMail(string $to, string $subject, string $html): bool {
     $mail = new PHPMailer();
         $mail->IsSMTP();
         $mail->SMTPAuth = true;

         $mail->SMTPSecure = 'ssl'; // Using 'ssl' with port 465 as per your original configuration
         $mail->Host = 'mail.curtzhome.top';
         $mail->Port = 465; // Or 587 if using 'tls'
         $mail->Username = 'support@curtzhome.top';
         $mail->Password = 'support@curtzhome.top'; // Use your actual email password

         $mail->IsHTML(true);
         $mail->From = 'support@curtzhome.top';
         $mail->FromName = 'Curtz Home Support';
         $mail->Sender = 'support@curtzhome.top';
         $mail->AddReplyTo('support@curtzhome.top', 'Curtz Home Support');
         $mail->Subject = $subject;
         $mail->Body = $html;
         $mail->AddAddress($to);

         // Enable SMTP debugging
        //  $mail->SMTPDebug = 2; // 0 = off, 1 = client messages, 2 = client and server messages
        //  $mail->Debugoutput = 'html'; // Output format for debugging

         if (!$mail->Send()) {
                  // Log error or handle failure
                  error_log('Email sending failed: ' . $mail->ErrorInfo);
                  return false;
         }

         return true;
}

function emailWrap(string $title, string $body): string {
    return <<<HTML
<!DOCTYPE html><html><head><meta charset="UTF-8">
<style>body{margin:0;background:#0B1629;font-family:Arial,sans-serif}.w{max-width:600px;margin:0 auto;background:#12243D;border-top:3px solid #C9A84C}.h{padding:2rem;background:#060F1E;text-align:center}.logo{font-size:1.5rem;font-weight:700;color:#C9A84C}.logo span{color:#F7F3ED;font-weight:300}.b{padding:2.5rem}h2{color:#F7F3ED;font-size:1.3rem;margin:0 0 1rem}p{color:#8A9BB0;line-height:1.7;font-size:.9rem;margin:0 0 1rem}.box{background:rgba(201,168,76,.08);border-left:3px solid #C9A84C;padding:1rem 1.2rem;margin:1.5rem 0}.box p{color:#F7F3ED}.btn{display:inline-block;background:#C9A84C;color:#0B1629;padding:.7rem 2rem;text-decoration:none;font-weight:700;font-size:.8rem;letter-spacing:.1em;text-transform:uppercase}.f{padding:1.5rem;background:#060F1E;text-align:center}.f p{color:#3D5068;font-size:.75rem;margin:0}</style>
</head><body><div class="w">
<div class="h"><div class="logo">Curtz <span>Home</span></div></div>
<div class="b"><h2>$title</h2>$body</div>
<div class="f"><p>&copy; 2025 Curtz Home. All rights reserved.</p></div>
</div></body></html>
HTML;
}
?>
