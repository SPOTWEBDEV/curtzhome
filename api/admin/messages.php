<?php
require_once __DIR__ . '/../config.php';
$admin  = requireAdmin();
$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $page=max(1,intval($_GET['page']??1)); $limit=20; $offset=($page-1)*$limit;
    $total = $db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
    $stmt  = $db->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute();
    jsonSuccess(['messages'=>$stmt->fetchAll(),'total'=>(int)$total]);
}
if ($method === 'POST') {
    $data = input();
    if (empty($data['id'])) jsonError('id required.');
    $db->prepare("UPDATE contact_messages SET status=? WHERE id=?")->execute([$data['status']??'read',$data['id']]);
    if (!empty($data['reply'])) {
        $msg = $db->prepare("SELECT * FROM contact_messages WHERE id=?");
        $msg->execute([$data['id']]); $msg = $msg->fetch();
        if ($msg) {
            $name = trim("{$msg['first_name']} {$msg['last_name']}") ?: 'there';
            $replyBody = emailWrap("Re: Your Enquiry", "
<p>Dear $name,</p>" . nl2br(htmlspecialchars($data['reply'])) . "
<p>Best regards,<br/><strong>Curtz Home Team</strong></p>
");
            sendMail($msg['email'], 'Re: Your Curtz Home Enquiry', $replyBody);
            $db->prepare("UPDATE contact_messages SET status='replied' WHERE id=?")->execute([$data['id']]);
        }
    }
    jsonSuccess(['message'=>'Updated.']);
}
jsonError('Method not allowed',405);
?>
