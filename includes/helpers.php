<?php
// includes/helpers.php — small shared utilities (escaping, urls, csrf, auth).
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/** HTML-escape. */
function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

/** Base URL of the portfolio (auto-detected unless SITE_URL set). */
function base_url() {
  if (defined('SITE_URL') && SITE_URL !== '') return rtrim(SITE_URL, '/');
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $dir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
  // admin/* pages sit one level deeper — step back up to portfolio root.
  if (substr($dir, -6) === '/admin') $dir = substr($dir, 0, -6);
  return ($https ? 'https://' : 'http://') . $host . ($dir === '/' ? '' : $dir);
}

/** Slugify a title. */
function slugify($s) {
  $s = strtolower(trim((string)$s));
  $s = preg_replace('/[^a-z0-9]+/', '-', $s);
  return trim($s, '-') ?: 'item-' . time();
}

/** CSRF token helpers. */
function csrf_token() {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function csrf_check($tok) {
  return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$tok);
}

/** Admin auth. */
function is_admin() { return !empty($_SESSION['admin_id']); }
function require_admin() {
  if (!is_admin()) { header('Location: login.php'); exit; }
}

/** Flash message (one-shot). */
function flash($msg = null, $type = 'ok') {
  if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return; }
  $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

/** Fetch the single profile row. */
function get_profile(mysqli $conn) {
  $r = $conn->query("SELECT * FROM profile WHERE id = 1 LIMIT 1");
  if ($r && $r->num_rows) return $r->fetch_assoc();
  return [
    'name' => '[your name]', 'role' => '[e.g., Full-stack developer / UI designer / Student]',
    'tagline' => 'I build fast, accessible web experiences.',
    'bio' => '', 'photo_path' => '', 'resume_path' => '', 'email' => 'you@example.com',
    'phone' => '', 'location' => '', 'availability' => '',
    'linkedin' => '', 'github' => '', 'twitter' => '', 'website' => '', 'tone' => '',
  ];
}

/** Ensure an upload subfolder exists; return absolute path. */
function upload_dir($sub) {
  $p = __DIR__ . '/../uploads/' . trim($sub, '/');
  if (!is_dir($p)) @mkdir($p, 0775, true);
  return $p;
}

/**
 * Handle a single uploaded file.
 * Returns relative path (e.g. 'uploads/profile/abc.jpg') or throws.
 */
function save_upload($field, $sub, array $allowedExt, $maxMb) {
  if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (code ' . $f['error'] . ').');
  if ($f['size'] > $maxMb * 1024 * 1024) throw new RuntimeException("File too large (max {$maxMb} MB).");
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt, true)) throw new RuntimeException('File type .' . $ext . ' is not allowed.');
  // MIME sniff for images.
  if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
    $info = @getimagesize($f['tmp_name']);
    if ($info === false) throw new RuntimeException('File is not a valid image.');
  }
  $dir = upload_dir($sub);
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) {
    throw new RuntimeException('Could not store uploaded file.');
  }
  return 'uploads/' . trim($sub, '/') . '/' . $name;
}

/** Delete a previously stored relative upload path. */
function delete_upload($rel) {
  if (!$rel) return;
  $abs = realpath(__DIR__ . '/../' . $rel);
  $root = realpath(__DIR__ . '/../uploads');
  if ($abs && $root && strpos($abs, $root) === 0 && is_file($abs)) @unlink($abs);
}

/** Human file size. */
function human_size($bytes) {
  $u = ['B','KB','MB','GB']; $i = 0;
  while ($bytes >= 1024 && $i < 3) { $bytes /= 1024; $i++; }
  return round($bytes, 1) . ' ' . $u[$i];
}
