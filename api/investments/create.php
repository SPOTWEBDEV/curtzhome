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

$plan = strtolower($data['plan']);
$db   = getDB();

// Load plan config from DB
$planStmt = $db->prepare("SELECT * FROM investment_plans WHERE id = ?");
$planStmt->execute([$plan]);
$planConfig = $planStmt->fetch();
if (!$planConfig) jsonError('Invalid investment plan.');

$rate   = floatval($planConfig['rate']);
$minUSD = floatval($planConfig['min_usd']);

$currency = strtoupper($data['currency'] ?? 'USD');
if (!in_array($currency, ['USD', 'GBP', 'EUR', 'NGN'])) $currency = 'USD';

$symbols = ['USD' => '$', 'GBP' => '£', 'EUR' => '€', 'NGN' => '₦'];
$symbol  = $symbols[$currency] ?? '$';

$amount = floatval($data['amount']);
if ($amount <= 0) jsonError('Amount must be greater than zero.');
if ($amount < $minUSD) {
    jsonError("Minimum investment for the " . ucfirst($plan) . " plan is $" . number_format($minUSD));
}

$tenure    = intval($data['tenure_months']);
$tenureMin = intval($planConfig['tenure_min']);
$tenureMax = intval($planConfig['tenure_max']);
if ($tenure < $tenureMin || $tenure > $tenureMax) {
    jsonError("Tenure for the " . ucfirst($plan) . " plan must be between {$tenureMin} and {$tenureMax} months.");
}

// ... rest of the file stays exactly the same

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
