<?php
// process-contact.php — validated contact form → DB + PHPMailer SMTP.
// Secrets come from env vars or config.mail.php (never hard-coded).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

function back($status, $anchor = 'contact') {
  header("Location: index.php?status=" . urlencode($status) . "#" . $anchor);
  exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { back('invalid'); }

// --- CSRF (tolerate legacy forms without token: only enforce if token sent) ---
if (!empty($_POST['csrf']) && (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], (string)$_POST['csrf']))) {
  back('invalid');
}
// --- Honeypot ---
if (!empty($_POST['website'])) { back('success'); } // pretend success to bots

// --- Validate + sanitize ---
$name    = trim((string)($_POST['name'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$subject = trim((string)($_POST['subject'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $message === '' || mb_strlen($name) > 120
    || mb_strlen($message) > 5000 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  back('invalid');
}
if ($subject !== '' && mb_strlen($subject) > 255) $subject = mb_substr($subject, 0, 255);

// Basic header-injection guard.
foreach ([$name, $email, $subject] as $v) {
  if (preg_match('/[\r\n]/', $v)) back('invalid');
}

// --- 1) Always persist to DB (prepared statement) ---
$dbSaved = false;
if (isset($conn) && !$conn->connect_error) {
  // Compatible with both legacy (name,email,message) and new (subject,is_read) schemas.
  $hasSubject = false;
  $col = $conn->query("SHOW COLUMNS FROM contact_submissions LIKE 'subject'");
  if ($col && $col->num_rows) $hasSubject = true;
  if ($hasSubject) {
    $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, subject, message) VALUES (?,?,?,?)");
    if ($stmt) { $stmt->bind_param('ssss', $name, $email, $subject, $message); $dbSaved = $stmt->execute(); $stmt->close(); }
  } else {
    $stmt = $conn->prepare("INSERT INTO contact_submissions (name, email, message) VALUES (?,?,?)");
    if ($stmt) { $stmt->bind_param('sss', $name, $email, $message); $dbSaved = $stmt->execute(); $stmt->close(); }
  }
}

// --- 2) Send via PHPMailer SMTP (graceful fallback: DB-only) ---
$mailHost = env_or('MAIL_HOST', '');
$mailUser = env_or('MAIL_USER', '');
$mailPass = env_or('MAIL_PASS', '');
$mailPort = (int)env_or('MAIL_PORT', '587');
$mailSec  = strtolower((string)env_or('MAIL_SECURE', 'tls'));
$mailFrom = env_or('MAIL_FROM', $mailUser);
$mailFromName = env_or('MAIL_FROM_NAME', 'Portfolio Contact');
$mailTo   = env_or('MAIL_TO', '');
$mailToName = env_or('MAIL_TO_NAME', 'Site Owner');

if ($mailHost === '' || $mailUser === '' || $mailPass === '' || $mailTo === '' || $mailFrom === '') {
  // No SMTP configured: keep the message (already in DB) and tell the user.
  error_log('Contact form: SMTP not configured; message saved to DB only.');
  back($dbSaved ? 'success' : 'error');
}

$pmFiles = [__DIR__ . '/lib/PHPMailer/PHPMailer.php', __DIR__ . '/lib/PHPMailer/SMTP.php', __DIR__ . '/lib/PHPMailer/Exception.php'];
foreach ($pmFiles as $f) { if (!is_file($f)) { error_log('Contact form: PHPMailer missing at ' . $f); back($dbSaved ? 'success' : 'error'); } }
require_once $pmFiles[2]; require_once $pmFiles[0]; require_once $pmFiles[1];

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_subject = htmlspecialchars($subject !== '' ? $subject : 'New portfolio message', ENT_QUOTES, 'UTF-8');
$safe_message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

$html = '<div style="font-family:Arial,sans-serif;max-width:640px;margin:auto">'
  . '<h2 style="color:#1e3c72">New portfolio message</h2>'
  . '<p><strong>Name:</strong> ' . $safe_name . '<br><strong>Email:</strong> ' . $safe_email
  . '<br><strong>Subject:</strong> ' . $safe_subject . '</p>'
  . '<div style="background:#f4f6f8;padding:14px;border-radius:8px">' . $safe_message . '</div></div>';
$text = "New portfolio message\nName: $name\nEmail: $email\nSubject: " . ($subject !== '' ? $subject : '(none)') . "\n\n$message";

try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = $mailHost;
  $mail->Port = $mailPort > 0 ? $mailPort : 587;
  $mail->SMTPAuth = true;
  $mail->Username = $mailUser;
  $mail->Password = $mailPass;
  if ($mailSec === 'ssl') $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
  elseif ($mailSec === 'tls') $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  else $mail->SMTPSecure = '';
  $mail->CharSet = 'UTF-8';
  $mail->Timeout = 20;
  $mail->setFrom($mailFrom, $mailFromName);
  $mail->addAddress($mailTo, $mailToName);
  $mail->addReplyTo($email, $name);
  $mail->Subject = 'Portfolio: ' . ($subject !== '' ? $subject : 'message from ' . $name);
  $mail->isHTML(true);
  $mail->Body = $html;
  $mail->AltBody = $text;
  $mail->send();
  back('success');
} catch (Exception $ex) {
  // Never leak SMTP details to visitors; log server-side.
  error_log('Contact PHPMailer error: ' . $ex->getMessage());
  // Message is already in DB — report success if saved, else generic error.
  back($dbSaved ? 'success' : 'error');
}
