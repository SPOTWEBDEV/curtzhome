<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$db = getDB();
$uid = intval($_GET['id'] ?? 0);
if (!$uid) jsonError('User id required.');

$user = $db->prepare("SELECT id,first_name,last_name,email,phone,role,status,address,nin,created_at FROM users WHERE id=?");
$user->execute([$uid]); $user = $user->fetch();
if (!$user) jsonError('User not found.',404);

$investments = $db->prepare("SELECT * FROM investments WHERE user_id=? ORDER BY created_at DESC");
$investments->execute([$uid]);

$purchases = $db->prepare("SELECT * FROM property_purchases WHERE user_id=? ORDER BY created_at DESC");
$purchases->execute([$uid]);

$stats = $db->prepare("SELECT COALESCE(SUM(amount),0) total, COUNT(*) cnt FROM investments WHERE user_id=?");
$stats->execute([$uid]); $stats = $stats->fetch();

jsonSuccess([
    'user'        => $user,
    'investments' => $investments->fetchAll(),
    'purchases'   => $purchases->fetchAll(),
    'stats'       => $stats,
]);
?>
