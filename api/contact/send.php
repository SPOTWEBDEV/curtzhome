<?php
require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$data = input();
if (empty($data['email']) || empty($data['message'])) jsonError('Email and message are required.');
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) jsonError('Invalid email address.');

$db   = getDB();
$stmt = $db->prepare("INSERT INTO contact_messages (first_name,last_name,email,phone,interest,message) VALUES (?,?,?,?,?,?)");
$stmt->execute([
    $data['first_name'] ?? '',
    $data['last_name']  ?? '',
    $data['email'],
    $data['phone']      ?? '',
    $data['interest']   ?? 'general',
    $data['message']
]);

$name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? '')) ?: 'there';

// Auto-reply to user
$autoBody = emailWrap("We've Received Your Message", "
<p>Dear $name,</p>
<p>Thank you for reaching out to Curtz Home. We have received your message and one of our team members will get back to you within <strong>24 hours</strong>.</p>
<div class='box'><p><strong>Your message:</strong><br/>" . nl2br(htmlspecialchars($data['message'])) . "</p></div>
<p>In the meantime, feel free to browse our <a href='".APP_URL."/housing.html' style='color:#C9A84C;'>latest properties</a> or explore our <a href='".APP_URL."/investment.html' style='color:#C9A84C;'>investment plans</a>.</p>
<p>Warm regards,<br/><strong>The Curtz Home Team</strong></p>
");
sendMail($data['email'], 'We received your message — Curtz Home', $autoBody);

// Notify admin
$adminBody = emailWrap("New Contact Message", "
<div class='box'>
  <p><strong>From:</strong> $name ({$data['email']})</p>
  <p><strong>Phone:</strong> " . ($data['phone'] ?? 'N/A') . "</p>
  <p><strong>Interest:</strong> " . ucfirst($data['interest'] ?? 'general') . "</p>
  <p><strong>Message:</strong><br/>" . nl2br(htmlspecialchars($data['message'])) . "</p>
</div>
");
sendMail(APP_EMAIL, "New Contact Message from $name", $adminBody);

jsonSuccess(['message' => "Message sent! We'll be in touch within 24 hours."]);
?>
