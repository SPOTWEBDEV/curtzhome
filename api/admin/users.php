<?php
require_once __DIR__ . '/../config.php';
$admin = requireAdmin();
$db    = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = $_GET['search'] ?? '';
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;
    $where  = $search ? "WHERE (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)" : "";
    $params = $search ? ["%$search%","%$search%","%$search%","%$search%"] : [];

    $total = $db->prepare("SELECT COUNT(*) FROM users $where");
    $total->execute($params); $total = $total->fetchColumn();

    $stmt = $db->prepare("SELECT u.*, 
        (SELECT COUNT(*) FROM investments WHERE user_id=u.id) inv_count,
        (SELECT COALESCE(SUM(amount),0) FROM investments WHERE user_id=u.id) inv_total,
        (SELECT COUNT(*) FROM property_purchases WHERE user_id=u.id) prop_count
        FROM users u $where ORDER BY u.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    jsonSuccess(['users'=>$stmt->fetchAll(),'total'=>(int)$total,'page'=>$page,'limit'=>$limit]);
}

if ($method === 'POST') {
    $data = input();
    if (empty($data['user_id'])) jsonError('user_id required.');
    $allowed = ['status','role','first_name','last_name','phone'];
    $fields=[]; $params=[];
    foreach($allowed as $f) { if(isset($data[$f])){ $fields[]="$f=?"; $params[]=$data[$f]; } }
    if (empty($fields)) jsonError('Nothing to update.');
    $params[] = $data['user_id'];
    $db->prepare("UPDATE users SET ".implode(',',$fields)." WHERE id=?")->execute($params);
    $db->prepare("INSERT INTO admin_logs (admin_id,action,target,target_id,detail) VALUES (?,?,?,?,?)")
       ->execute([$admin['user_id'],'update_user','users',$data['user_id'],json_encode($data)]);
    jsonSuccess(['message'=>'User updated.']);
}

jsonError('Method not allowed',405);
?>
