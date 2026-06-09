<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$data = input();
if (empty($data['email']) || empty($data['password'])) jsonError('Email and password are required.');

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([strtolower(trim($data['email']))]);
$user = $stmt->fetch();

if (!$user || !password_verify($data['password'], $user['password_hash'])) {
    jsonError('Invalid email or password.');
}
if ($user['status'] === 'suspended') {
    jsonError('Your account has been suspended. Please contact support.');
}

$token = jwtEncode([
    'user_id' => $user['id'],
    'email'   => $user['email'],
    'role'    => $user['role'],
    'name'    => $user['first_name']
]);

unset($user['password_hash']);

jsonSuccess([
    'token' => $token,
    'user'  => $user
]);
?>
