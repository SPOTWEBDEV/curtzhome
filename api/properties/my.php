<?php
require_once __DIR__ . '/../config.php';
$auth = requireAuth();
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM property_purchases WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$auth['user_id']]);
jsonSuccess(['properties' => $stmt->fetchAll()]);
?>
