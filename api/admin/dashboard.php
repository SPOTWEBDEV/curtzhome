<?php
require_once __DIR__ . '/../config.php';
requireAdmin();
$db = getDB();

$users   = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$invAmt  = $db->query("SELECT COALESCE(SUM(amount),0) FROM investments")->fetchColumn();
$invCnt  = $db->query("SELECT COUNT(*) FROM investments")->fetchColumn();
$pending = $db->query("SELECT COUNT(*) FROM investments WHERE status='pending'")->fetchColumn();
$propCnt = $db->query("SELECT COUNT(*) FROM property_purchases")->fetchColumn();
$propPen = $db->query("SELECT COUNT(*) FROM property_purchases WHERE status='pending'")->fetchColumn();
$msgs    = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status='new'")->fetchColumn();

$recentInv = $db->query("
    SELECT i.*, u.first_name, u.last_name, u.email
    FROM investments i JOIN users u ON u.id=i.user_id
    ORDER BY i.created_at DESC LIMIT 8
")->fetchAll();

$recentProp = $db->query("
    SELECT p.*, u.first_name, u.last_name, u.email
    FROM property_purchases p JOIN users u ON u.id=p.user_id
    ORDER BY p.created_at DESC LIMIT 8
")->fetchAll();

jsonSuccess([
    'users'           => (int)$users,
    'total_invested'  => (float)$invAmt,
    'investments'     => (int)$invCnt,
    'pending_inv'     => (int)$pending,
    'properties'      => (int)$propCnt,
    'pending_prop'    => (int)$propPen,
    'new_messages'    => (int)$msgs,
    'recent_investments' => $recentInv,
    'recent_purchases'   => $recentProp,
]);
?>
