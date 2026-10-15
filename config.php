<?php
// ============================================================
// Central configuration (non-secret).
// Secret mail/DB credentials live in config.mail.php (private,
// git-ignored) or environment variables — never in public code.
// ============================================================

// Site branding / defaults (admin can override profile in DB).
define('SITE_NAME', 'Portfolio');
define('SITE_URL', ''); // leave empty to auto-detect; e.g. 'http://localhost/project/portfolio'

// Upload limits.
define('MAX_IMAGE_MB', 4);
define('MAX_RESUME_MB', 8);
define('MAX_PROJECT_FILE_MB', 10);

// Allowed extensions.
define('ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
define('ALLOWED_RESUME_EXT', ['pdf', 'doc', 'docx']);
define('ALLOWED_PROJECT_FILE_EXT', [
  'php', 'html', 'htm', 'css', 'js', 'ts', 'json', 'md', 'txt',
  'sql', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'go', 'rb', 'xml',
  'yml', 'yaml', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'pdf', 'zip'
]);

// Text files that can be previewed in the on-site code browser.
define('PREVIEWABLE_EXT', [
  'php', 'html', 'htm', 'css', 'js', 'ts', 'json', 'md', 'txt',
  'sql', 'py', 'java', 'c', 'cpp', 'h', 'cs', 'go', 'rb', 'xml', 'yml', 'yaml'
]);

/**
 * Get an env var with fallback. Works with real env vars and with
 * values defined in the private config.mail.php file.
 */
function env_or($key, $default = '') {
  $v = getenv($key);
  if ($v !== false && $v !== '') return $v;
  if (isset($GLOBALS['PRIVATE_CFG'][$key])) return $GLOBALS['PRIVATE_CFG'][$key];
  return $default;
}

// Load private config if present (DB overrides + SMTP secrets).
$GLOBALS['PRIVATE_CFG'] = [];
$private = __DIR__ . '/config.mail.php';
if (is_file($private)) {
  $arr = include $private;
  if (is_array($arr)) $GLOBALS['PRIVATE_CFG'] = $arr;
}
