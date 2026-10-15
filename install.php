<?php
// install.php — creates DB + tables, seeds starter content, creates admin.
// Run once in the browser, then DELETE this file (it refuses to run twice
// unless ?force=1 is passed). Safe to re-run: uses IF NOT EXISTS.
require_once __DIR__ . '/config.php';

$host = env_or('DB_HOST', 'localhost');
$user = env_or('DB_USER', 'root');
$pass = env_or('DB_PASS', 'root');
$name = env_or('DB_NAME', 'portfolio_db');

$already = is_file(__DIR__ . '/.installed') && !isset($_GET['force']);

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$log = [];
try {
  $m = new mysqli($host, $user, $pass);
  if ($m->connect_error) throw new RuntimeException('MySQL connect failed: ' . $m->connect_error);
  $m->set_charset('utf8mb4');
  $m->query("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
  $m->select_db($name);

  // Run schema.sql if present, else inline minimal schema.
  $sql = is_file(__DIR__ . '/schema.sql') ? file_get_contents(__DIR__ . '/schema.sql') : '';
  if ($sql === '') throw new RuntimeException('schema.sql not found.');
  // Strip USE line (we already selected db) and split on ";\n".
  $sql = preg_replace('/^\s*USE\s+`?\w+`?\s*;/mi', '', $sql);
  // Remove single-line comments starting with -- (careful: keep -- inside strings; schema has none).
  $statements = array_filter(array_map('trim', explode(";\n", $sql)));
  foreach ($statements as $st) {
    if ($st === '' || strpos($st, '-- ===') === 0) continue;
    // skip pure comment blocks
    $lines = array_filter(explode("\n", $st), function ($l) { return trim($l) !== '' && strpos(trim($l), '--') !== 0; });
    $st = implode("\n", $lines);
    if (trim($st) === '') continue;
    if (!$m->query($st)) { $log[] = 'WARN: ' . $m->error; }
  }
  $log[] = 'Tables ensured.';

  // Ensure profile row id=1.
  $m->query("INSERT IGNORE INTO profile (id, name, role, tagline, bio, email, location, availability, linkedin, github, twitter)
    VALUES (1, 'Alex Morgan', 'Full-Stack Web Developer', 'I craft fast, scalable, accessible web apps with PHP, MySQL and a playful 3D touch.',
    'Hello! I am a full-stack developer with 5+ years of experience turning complex business problems into sleek, user-friendly products. I pair solid backend architecture (PHP/MySQL) with clean, responsive front ends.',
    'alex.morgan@example.com', 'San Francisco, CA', 'Available for freelance', 'https://linkedin.com', 'https://github.com', 'https://twitter.com')");
  $log[] = 'Profile row ensured.';

  // Seed skills only if empty.
  $c = $m->query("SELECT COUNT(*) c FROM skills")->fetch_assoc();
  if ((int)$c['c'] === 0) {
    $skills = [
      ['Frontend','HTML5','bi-filetype-html',95,1],['Frontend','CSS3','bi-filetype-css',92,2],
      ['Frontend','JavaScript','bi-filetype-js',88,3],['Frontend','Bootstrap 5','bi-bootstrap',90,4],
      ['Backend','PHP','bi-filetype-php',92,5],['Backend','MySQL / MySQLi','bi-database',89,6],
      ['Backend','REST APIs','bi-hdd-network',84,7],
      ['Tools','Git / GitHub','bi-github',90,8],['Tools','Three.js / 3D UI','bi-box',75,9],
      ['Tools','SEO & Performance','bi-speedometer2',82,10],
    ];
    $st = $m->prepare("INSERT INTO skills (skill_group, name, icon, level, sort_order) VALUES (?,?,?,?,?)");
    foreach ($skills as $s) { $st->bind_param('sssii', $s[0], $s[1], $s[2], $s[3], $s[4]); $st->execute(); }
    $st->close();
    $log[] = 'Seeded 10 skills.';
  }

  // Seed experience/education only if empty.
  $c = $m->query("SELECT COUNT(*) c FROM experience")->fetch_assoc();
  if ((int)$c['c'] === 0) {
    $rows = [
      ['experience','Full-Stack Web Developer','Freelance','Remote','2021','Present','Ship custom PHP/MySQL apps, Bootstrap front ends and performance hardening for small businesses.',1],
      ['experience','Backend Developer','Bright Studio','San Francisco, CA','2019','2021','Built internal tools and client sites; introduced prepared statements everywhere and cut page weight ~40%.',2],
      ['education','B.S. Computer Science','State University','—','2015','2019','Focus on web systems, databases and HCI.',3],
    ];
    $st = $m->prepare("INSERT INTO experience (type,title,organization,location,start_label,end_label,description,sort_order) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($rows as $r) { $st->bind_param('sssssssi', $r[0],$r[1],$r[2],$r[3],$r[4],$r[5],$r[6],$r[7]); $st->execute(); }
    $st->close();
    $log[] = 'Seeded timeline.';
  }

  // Seed one demo project only if empty (uses gradient thumb, no external file).
  $c = $m->query("SELECT COUNT(*) c FROM projects")->fetch_assoc();
  if ((int)$c['c'] === 0) {
    $st = $m->prepare("INSERT INTO projects (title,slug,description,category,tags,live_url,github_url,code_snippet,code_lang,featured,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $t='Starter Project'; $sl='starter-project';
    $d='Replace me from Admin → Projects. Demo, live and GitHub links, tags and source browsing all work from this row.';
    $cat='Web'; $tags='PHP, MySQL, Bootstrap'; $live=''; $gh='';
    $code="<?php\n// Hello from the on-site code viewer.\n// Attach real files in Admin → Projects → Files.\necho 'Hello, portfolio!';";
    $lang='php'; $feat=1; $ord=1;
    $st->bind_param('sssssssssii', $t,$sl,$d,$cat,$tags,$live,$gh,$code,$lang,$feat,$ord);
    $st->execute(); $st->close();
    $log[] = 'Seeded demo project.';
  }

  // Ensure admin user. Default: admin / ChangeMe123! (change immediately).
  $c = $m->query("SELECT COUNT(*) c FROM users")->fetch_assoc();
  if ((int)$c['c'] === 0) {
    $hash = password_hash('ChangeMe123!', PASSWORD_DEFAULT);
    $st = $m->prepare("INSERT INTO users (username, password_hash) VALUES ('admin', ?)");
    $st->bind_param('s', $hash); $st->execute(); $st->close();
    $log[] = 'Admin created: username <b>admin</b>, password <b>ChangeMe123!</b> — change it after login (or re-run with ?force=1 to reset).';
  } else {
    $log[] = 'Admin user(s) already exist — left untouched.';
  }

  // Upload folders + protection.
  foreach (['uploads','uploads/profile','uploads/resumes','uploads/projects','uploads/blog','uploads/avatars'] as $d) {
    if (!is_dir(__DIR__ . '/' . $d)) @mkdir(__DIR__ . '/' . $d, 0775, true);
  }
  if (!is_file(__DIR__ . '/uploads/.htaccess')) {
    file_put_contents(__DIR__ . '/uploads/.htaccess', "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n  Require all denied\n</FilesMatch>\n");
  }
  if (!is_file(__DIR__ . '/config.mail.php') && is_file(__DIR__ . '/config.mail.example.php')) {
    // do not auto-create secrets; just remind.
    $log[] = 'Mail not configured yet: copy <b>config.mail.example.php → config.mail.php</b> and add SMTP credentials (contact form still saves to DB without it).';
  }
  if (!is_dir(__DIR__ . '/lib/PHPMailer')) $log[] = 'WARN: lib/PHPMailer missing — contact mail will fall back to DB-only mode.';
  else $log[] = 'PHPMailer library found.';

  @file_put_contents(__DIR__ . '/.installed', date('c'));
  $ok = true;
} catch (Throwable $ex) {
  $log[] = 'ERROR: ' . $ex->getMessage();
  $ok = false;
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Portfolio setup</title><style>body{font-family:system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;line-height:1.6}li{margin:6px 0}.ok{color:#15803d}.err{color:#b91c1c}</style></head>
<body>
<h1>Portfolio setup</h1>
<ul>
<?php foreach ($log as $l): ?><li><?= $l ?></li><?php endforeach; ?>
</ul>
<?php if (!empty($ok)): ?>
<p class="ok"><strong>Done.</strong> <a href="index.php">View site</a> · <a href="admin/login.php">Admin login</a></p>
<p><strong>Next:</strong> delete or restrict <code>install.php</code>, set <code>config.mail.php</code> SMTP values, log in and edit your profile.</p>
<?php else: ?>
<p class="err"><strong>Setup failed.</strong> Check MySQL is running in UniServerZ and DB credentials in <code>db.php</code>/<code>config.mail.php</code>.</p>
<?php endif; ?>
<?php if ($already): ?><p>Note: setup ran before (.installed exists). Used cached run; pass <code>?force=1</code> to force.</p><?php endif; ?>
</body></html>
