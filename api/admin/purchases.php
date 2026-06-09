<?php
require_once __DIR__ . '/../config.php';
$admin  = requireAdmin();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $status = $_GET['status'] ?? '';
    $page   = max(1, intval($_GET['page'] ?? 1));
    $limit  = 20; $offset = ($page-1)*$limit;
    $where  = $status ? "WHERE p.status=?" : "";
    $params = $status ? [$status] : [];
    $total  = $db->prepare("SELECT COUNT(*) FROM property_purchases p $where");
    $total->execute($params); $total = $total->fetchColumn();
    $stmt = $db->prepare("SELECT p.*, u.first_name, u.last_name, u.email, u.phone as user_phone
        FROM property_purchases p JOIN users u ON u.id=p.user_id
        $where ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);
    jsonSuccess(['purchases'=>$stmt->fetchAll(),'total'=>(int)$total]);
}

if ($method === 'POST') {
    $data = input();
    if (empty($data['id'])) jsonError('Purchase id required.');
    $newStatus = $data['status']     ?? null;
    $adminNote = $data['admin_note'] ?? null;
    $db->prepare("UPDATE property_purchases SET status=COALESCE(?,status), admin_note=COALESCE(?,admin_note) WHERE id=?")
       ->execute([$newStatus, $adminNote, $data['id']]);

    if ($newStatus) {
        $pur = $db->prepare("SELECT p.*, u.first_name, u.last_name, u.email FROM property_purchases p JOIN users u ON u.id=p.user_id WHERE p.id=?");
        $pur->execute([$data['id']]); $pur = $pur->fetch();
        if ($pur) {
            $statusMap = ['processing'=>'In Processing','completed'=>'Completed','cancelled'=>'Cancelled','pending'=>'Under Review'];
            $label = $statusMap[$newStatus] ?? ucfirst($newStatus);
            $ref   = 'PUR-' . str_pad($pur['id'], 6, '0', STR_PAD_LEFT);
            $body  = emailWrap("Property Purchase Update — $ref", "
<p>Dear {$pur['first_name']},</p>
<p>The status of your property purchase request has been updated to <strong>$label</strong>.</p>
<div class='box'>
  <p><strong>Reference:</strong> $ref</p>
  <p><strong>Property:</strong> {$pur['property_name']}</p>
  <p><strong>Status:</strong> $label</p>" .
  ($adminNote ? "<p><strong>Note from us:</strong> $adminNote</p>" : "") . "
</div>
<p>Our property consultant will contact you shortly with further details.</p>
<p>Curtz Home Team</p>
");
            sendMail($pur['email'], "Property Purchase $label — $ref | Curtz Home", $body);
        }
    }
    jsonSuccess(['message'=>'Purchase updated.']);
}
jsonError('Method not allowed',405);
?>
