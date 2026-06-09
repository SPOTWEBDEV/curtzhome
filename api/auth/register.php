<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$data = input();

// Validate
$required = ['first_name','last_name','email','phone','password'];
foreach ($required as $f) {
    if (empty($data[$f])) jsonError("Field '$f' is required.");
}
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) jsonError('Invalid email address.');
if (strlen($data['password']) < 8) jsonError('Password must be at least 8 characters.');

$db = getDB();

// Check duplicate email
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$data['email']]);
if ($stmt->fetch()) jsonError('An account with this email already exists.');

// Insert user
$hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
$stmt = $db->prepare("INSERT INTO users (first_name,last_name,email,phone,password_hash) VALUES (?,?,?,?,?)");
$stmt->execute([
    trim($data['first_name']),
    trim($data['last_name']),
    strtolower(trim($data['email'])),
    $data['phone'],
    $hash
]);
$userId = $db->lastInsertId();

// Welcome email
$name = $data['first_name'];
$body = emailWrap("Welcome to Curtz Home, $name!", "
<p>Thank you for creating your Curtz Home account. We're thrilled to have you join our community of investors and homeowners.</p>
<div class='box'><p>Your account is now active. You can log in at any time to explore properties, track investments, and manage your portfolio.</p></div>
<p>If you have any questions, our team is always available to assist you.</p>
<p>Warm regards,<br/><strong>The Curtz Home Team</strong></p>
");
sendMail($data['email'], 'Welcome to Curtz Home', $body);

jsonSuccess(['message' => 'Account created successfully. Please sign in.'], 201);
?>
