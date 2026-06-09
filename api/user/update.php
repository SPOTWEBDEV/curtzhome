<?php
require_once __DIR__ . '/../config.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);
$auth = requireAuth();
$data = input();
$db   = getDB();

$fields = []; $params = [];
$allowed = ['first_name','last_name','phone','address'];
foreach ($allowed as $f) {
    if (isset($data[$f])) { $fields[] = "$f=?"; $params[] = $data[$f]; }
}

if (!empty($data['new_pw'])) {
    if (strlen($data['new_pw']) < 8) jsonError('New password must be at least 8 characters.');
    $cur = $db->prepare("SELECT password_hash FROM users WHERE id=?");
    $cur->execute([$auth['user_id']]); $row = $cur->fetch();
    if (!password_verify($data['current_pw'] ?? '', $row['password_hash'])) jsonError('Current password is incorrect.');
    $fields[] = "password_hash=?"; $params[] = password_hash($data['new_pw'], PASSWORD_BCRYPT, ['cost'=>12]);
}

if (empty($fields)) jsonError('No fields to update.');
$params[] = $auth['user_id'];
$db->prepare("UPDATE users SET ".implode(',',$fields)." WHERE id=?")->execute($params);

$stmt = $db->prepare("SELECT id,first_name,last_name,email,phone,address,role,status FROM users WHERE id=?");
$stmt->execute([$auth['user_id']]);
jsonSuccess(['user' => $stmt->fetch(), 'message' => 'Profile updated successfully.']);
?>
