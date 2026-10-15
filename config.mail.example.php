<?php
// ============================================================
// PRIVATE config — DO NOT commit. Copy this file to
//   config.mail.php
// and fill in real values. config.mail.php is git-ignored and
// never loaded from a public URL (see uploads/.htaccess + README).
//
// You can ALSO set these as environment variables instead;
// env vars take precedence over this file.
// ============================================================
return [
  // --- Database (optional overrides; defaults match db.php) ---
  // 'DB_HOST' => 'localhost',
  // 'DB_USER' => 'root',
  // 'DB_PASS' => 'root',
  // 'DB_NAME' => 'portfolio_db',

  // --- SMTP for PHPMailer (required for contact form mail) ---
  // Gmail example: HOST=smtp.gmail.com, PORT=587, SECURE=tls
  // Outlook: smtp.office365.com:587/tls. MailerSend relay via SMTP also works.
  'MAIL_HOST'   => 'smtp.example.com',
  'MAIL_PORT'   => '587',
  'MAIL_SECURE' => 'tls',        // 'tls' | 'ssl' | '' (plain)
  'MAIL_USER'   => 'you@example.com',
  'MAIL_PASS'   => 'super-secret-password',
  'MAIL_FROM'   => 'you@example.com',
  'MAIL_FROM_NAME' => 'Portfolio Contact',
  'MAIL_TO'     => 'you@example.com',  // where contact messages go
  'MAIL_TO_NAME'=> 'Site Owner',
];
