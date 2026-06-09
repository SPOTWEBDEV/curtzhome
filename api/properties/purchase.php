<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$auth = requireAuth();
$data = input();

$required = ['property_name', 'full_name', 'phone'];
foreach ($required as $f) {
    if (empty($data[$f])) jsonError("Field '$f' is required.");
}

$db = getDB();

$stmt = $db->prepare("INSERT INTO property_purchases
    (user_id, property_id, property_name, location, purchase_type, price, deposit_amount,
     payment_method, tx_ref, full_name, phone, nin, source_of_funds, address, notes)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([
    $auth['user_id'],
    $data['property_id']    ?? null,
    $data['property_name'],
    $data['location']       ?? null,
    $data['purchase_type']  ?? 'outright',
    $data['price']          ?? null,
    $data['deposit_amount'] ?? null,
    $data['payment_method'] ?? 'bank_transfer',
    $data['tx_ref']         ?? null,
    $data['full_name'],
    $data['phone'],
    $data['nin']            ?? null,
    $data['source_of_funds']?? null,
    $data['address']        ?? null,
    $data['notes']          ?? null,
]);
$purchaseId = $db->lastInsertId();

// Fetch user
$uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$auth['user_id']]);
$user = $uStmt->fetch();

$ref        = 'PUR-' . str_pad($purchaseId, 6, '0', STR_PAD_LEFT);
$priceFmt   = $data['price']          ? '$' . number_format(floatval($data['price']))          : 'To be confirmed';
$depositFmt = $data['deposit_amount'] ? '$' . number_format(floatval($data['deposit_amount'])) : 'N/A';

// ── Email to buyer ──────────────────────────────────────────────────────────
$clientBody = emailWrap("Property Purchase Request Received", "
<p>Dear {$user['first_name']},</p>
<p>We have received your property purchase request. A dedicated consultant has been assigned and will contact you within <strong>24 hours</strong>.</p>
<div class='box'>
  <p><strong>Reference:</strong> {$ref}</p>
  <p><strong>Property:</strong> {$data['property_name']}</p>
  <p><strong>Location:</strong> " . ($data['location'] ?? 'N/A') . "</p>
  <p><strong>Purchase Type:</strong> " . ucfirst($data['purchase_type'] ?? 'Outright') . "</p>
  <p><strong>Listed Price:</strong> {$priceFmt}</p>
  <p><strong>Initial Deposit:</strong> {$depositFmt}</p>
</div>
<p>Your consultant will reach you at <strong>{$user['phone']}</strong> or via email reply.</p>
<p>Thank you for choosing Curtz Home.</p>
<p>Warm regards,<br/><strong>The Curtz Home Property Team</strong></p>
");
sendMail($user['email'], "Property Purchase Request — {$ref} | Curtz Home", $clientBody);

// ── Email to admin ──────────────────────────────────────────────────────────
$adminBody = emailWrap("New Property Purchase Request", "
<p>A new property purchase request has been submitted.</p>
<div class='box'>
  <p><strong>Buyer:</strong> {$user['first_name']} {$user['last_name']} ({$user['email']})</p>
  <p><strong>Property:</strong> {$data['property_name']}</p>
  <p><strong>Price:</strong> {$priceFmt} | <strong>Deposit:</strong> {$depositFmt}</p>
  <p><strong>Purchase Type:</strong> " . ucfirst($data['purchase_type'] ?? 'Outright') . "</p>
  <p><strong>Payment Ref:</strong> " . ($data['tx_ref'] ?? 'N/A') . "</p>
  <p><strong>Reference:</strong> {$ref}</p>
</div>
<p>Log in to the admin dashboard to process this request.</p>
");
sendMail(APP_EMAIL, "New Property Purchase: {$data['property_name']} — {$ref}", $adminBody);

jsonSuccess([
    'message'  => 'Purchase request submitted! Our team will contact you within 24 hours.',
    'purchase' => ['id' => $purchaseId, 'ref' => $ref, 'status' => 'pending']
], 201);
?>
