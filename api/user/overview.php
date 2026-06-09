<?php
require_once __DIR__ . '/../config.php';
$auth = requireAuth();
$db   = getDB();
$uid  = $auth['user_id'];

$inv  = $db->prepare("SELECT COUNT(*) cnt, COALESCE(SUM(amount),0) total, COALESCE(SUM(expected_return),0) ret FROM investments WHERE user_id=?");
$inv->execute([$uid]); $invData = $inv->fetch();

$prop = $db->prepare("SELECT COUNT(*) cnt FROM property_purchases WHERE user_id=?");
$prop->execute([$uid]); $propData = $prop->fetch();

$recent = $db->prepare("
    (SELECT 'investment' type, plan name, amount, status, created_at FROM investments WHERE user_id=? ORDER BY created_at DESC LIMIT 3)
    UNION ALL
    (SELECT 'property' type, property_name name, price amount, status, created_at FROM property_purchases WHERE user_id=? ORDER BY created_at DESC LIMIT 3)
    ORDER BY created_at DESC LIMIT 5
");
$recent->execute([$uid,$uid]);

jsonSuccess([
    'total_invested'    => $invData['total'],
    'total_returns'     => $invData['ret'],
    'investment_count'  => $invData['cnt'],
    'total_properties'  => $propData['cnt'],
    'recent'            => $recent->fetchAll()
]);
?>
