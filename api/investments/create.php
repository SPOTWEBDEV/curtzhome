<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$auth = requireAuth();
$data = input();

// Validate required fields
$required = ['plan', 'amount', 'tenure_months', 'payout_freq'];
foreach ($required as $f) {
    if (empty($data[$f])) jsonError("Field '$f' is required.");
}

$planRates = ['starter' => 18, 'growth' => 26, 'elite' => 35];
$plan = strtolower($data['plan']);
if (!isset($planRates[$plan])) jsonError('Invalid investment plan.');

$currency = strtoupper($data['currency'] ?? 'USD');
if (!in_array($currency, ['USD', 'GBP', 'EUR', 'NGN'])) $currency = 'USD';

// Currency symbol map
$symbols = ['USD' => '$', 'GBP' => '£', 'EUR' => '€', 'NGN' => '₦'];
$symbol  = $symbols[$currency] ?? '$';

$amount = floatval($data['amount']);

// Min amounts in USD; for other currencies convert at rough parity check
$minAmounts = ['starter' => 500, 'growth' => 2000, 'elite' => 10000]; // in USD equiv
if ($amount <= 0) jsonError('Amount must be greater than zero.');
if ($amount < $minAmounts[$plan]) {
    jsonError("Minimum investment for the " . ucfirst($plan) . " plan is {$symbol}" . number_format($minAmounts[$plan]));
}

$rate   = $planRates[$plan];
$tenure = intval($data['tenure_months']);
if ($tenure < 1 || $tenure > 36) jsonError('Tenure must be between 1 and 36 months.');

$expectedReturn = round($amount * ($rate / 100) * ($tenure / 12), 2);
$totalValue     = round($amount + $expectedReturn, 2);
$maturityDate   = date('Y-m-d', strtotime("+{$tenure} months"));

$db = getDB();

$stmt = $db->prepare("INSERT INTO investments
    (user_id, plan, amount, rate, tenure_months, payout_freq,
     expected_return, total_value, maturity_date,
     payment_method, tx_ref, currency)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
$stmt->execute([
    $auth['user_id'], $plan, $amount, $rate, $tenure,
    $data['payout_freq']    ?? 'monthly',
    $expectedReturn, $totalValue, $maturityDate,
    $data['payment_method'] ?? 'bank_transfer',
    $data['tx_ref']         ?? null,
    $currency
]);
$investId = $db->lastInsertId();

// Fetch user
$uStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$uStmt->execute([$auth['user_id']]);
$user = $uStmt->fetch();

$planLabel  = ucfirst($plan);
$amountFmt  = $symbol . number_format($amount, 2);
$returnFmt  = $symbol . number_format($expectedReturn, 2);
$totalFmt   = $symbol . number_format($totalValue, 2);
$ref        = 'INV-' . str_pad($investId, 6, '0', STR_PAD_LEFT);
$matDate    = date('d M Y', strtotime($maturityDate));

// ── Email to investor ───────────────────────────────────────────────────────
$clientBody = emailWrap("Investment Confirmed — {$planLabel} Plan", "
<p>Dear {$user['first_name']},</p>
<p>Your investment request has been received and is currently being processed. Here are your investment details:</p>
<div class='box'>
  <p><strong>Reference:</strong> {$ref}</p>
  <p><strong>Plan:</strong> {$planLabel} ({$rate}% per annum)</p>
  <p><strong>Currency:</strong> {$currency}</p>
  <p><strong>Amount Invested:</strong> {$amountFmt}</p>
  <p><strong>Tenure:</strong> {$tenure} months</p>
  <p><strong>Expected Return:</strong> {$returnFmt}</p>
  <p><strong>Total at Maturity:</strong> {$totalFmt}</p>
  <p><strong>Maturity Date:</strong> {$matDate}</p>
</div>
<p>Our team will verify your payment and activate your investment within <strong>24 business hours</strong>.
You will receive a follow-up email once your investment is activated.</p>
<p>Questions? Email us at <a href='mailto:info@curtzhome.com' style='color:#C9A84C;'>info@curtzhome.com</a></p>
<p>Warm regards,<br/><strong>The Curtz Home Investment Team</strong></p>
");
sendMail($user['email'], "Investment Confirmed — {$planLabel} Plan | Curtz Home", $clientBody);

// ── Email to admin ──────────────────────────────────────────────────────────
$adminBody = emailWrap("New Investment Submitted", "
<p>A new investment has been placed on the platform.</p>
<div class='box'>
  <p><strong>Investor:</strong> {$user['first_name']} {$user['last_name']} ({$user['email']})</p>
  <p><strong>Plan:</strong> {$planLabel} | <strong>Currency:</strong> {$currency}</p>
  <p><strong>Amount:</strong> {$amountFmt} | <strong>Tenure:</strong> {$tenure} months</p>
  <p><strong>Payment Method:</strong> " . ($data['payment_method'] ?? 'N/A') . "</p>
  <p><strong>Tx Ref:</strong> " . ($data['tx_ref'] ?? 'N/A') . "</p>
  <p><strong>Reference:</strong> {$ref}</p>
</div>
<p>Log in to the admin dashboard to review and activate this investment.</p>
");
sendMail(APP_EMAIL, "New Investment: {$amountFmt} — {$planLabel} Plan", $adminBody);

jsonSuccess([
    'message'    => 'Investment submitted successfully! A confirmation email has been sent to you.',
    'investment' => [
        'id'              => $investId,
        'ref'             => $ref,
        'plan'            => $planLabel,
        'currency'        => $currency,
        'symbol'          => $symbol,
        'amount'          => $amount,
        'expected_return' => $expectedReturn,
        'total_value'     => $totalValue,
        'maturity_date'   => $maturityDate,
        'status'          => 'pending'
    ]
], 201);
?>
