<?php
require_once __DIR__ . '/../config.php';
$admin  = requireAdmin();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? '';
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = 20; $offset = ($page-1)*$limit;
    $where  = $status ? "WHERE i.status=?" : "";
    $params = $status ? [$status] : [];
    $total  = $db->prepare("SELECT COUNT(*) FROM investments i $where");
    $total->execute($params); $total = $total->fetchColumn();
    $stmt = $db->prepare("SELECT i.*, u.first_name, u.last_name, u.email, u.phone
        FROM investments i JOIN users u ON u.id=i.user_id
        $where ORDER BY i.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    jsonSuccess(['investments'=>$stmt->fetchAll(),'total'=>(int)$total]);
}

if ($method === 'POST') {
    $data = input();
    if (empty($data['id'])) jsonError('Investment id required.');
    $newStatus   = $data['status']     ?? null;
    $adminNote   = $data['admin_note'] ?? null;
    $db->prepare("UPDATE investments SET status=COALESCE(?,status), admin_note=COALESCE(?,admin_note) WHERE id=?")
       ->execute([$newStatus, $adminNote, $data['id']]);

    // Notify user of status change
    if ($newStatus) {
        $inv = $db->prepare("SELECT i.*, u.first_name, u.last_name, u.email FROM investments i JOIN users u ON u.id=i.user_id WHERE i.id=?");
        $inv->execute([$data['id']]); $inv = $inv->fetch();
        if ($inv) {
            $statusMap = ['active'=>'Activated','completed'=>'Completed','cancelled'=>'Cancelled','pending'=>'Pending Review'];
            $label = $statusMap[$newStatus] ?? ucfirst($newStatus);
            $ref   = 'INV-' . str_pad($inv['id'], 6, '0', STR_PAD_LEFT);
            $body  = emailWrap("Investment $label — $ref", "
<p>Dear {$inv['first_name']},</p>
<p>Your investment status has been updated to <strong>$label</strong>.</p>
<div class='box'>
  <p><strong>Reference:</strong> $ref</p>
  <p><strong>Plan:</strong> " . ucfirst($inv['plan']) . " ({$inv['rate']}% p.a.)</p>
  <p><strong>Amount:</strong> ₦" . number_format($inv['amount']) . "</p>
  <p><strong>Status:</strong> $label</p>" .
  ($adminNote ? "<p><strong>Note from us:</strong> $adminNote</p>" : "") . "
</div>
<p>Log in to your dashboard to view full details.</p>
<p>Curtz Home Team</p>
");
            sendMail($inv['email'], "Investment $label — $ref | Curtz Home", $body);
        }
    }

    $db->prepare("INSERT INTO admin_logs (admin_id,action,target,target_id,detail) VALUES (?,?,?,?,?)")
       ->execute([$admin['user_id'],'update_investment','investments',$data['id'],"Status: $newStatus"]);
    jsonSuccess(['message'=>'Investment updated.']);
}

jsonError('Method not allowed',405);
?>
