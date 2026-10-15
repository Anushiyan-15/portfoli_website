<?php
// admin/index.php — dashboard with counts + quick links.
require_once __DIR__ . '/_layout.php';
function count_of($conn, $t) {
  $r = @$conn->query("SELECT COUNT(*) c FROM `$t`");
  return $r ? (int)$r->fetch_assoc()['c'] : 0;
}
$stats = [
  'Projects' => count_of($conn, 'projects'),
  'Skills' => count_of($conn, 'skills'),
  'Timeline entries' => count_of($conn, 'experience'),
  'Testimonials' => count_of($conn, 'testimonials'),
  'Blog posts' => count_of($conn, 'posts'),
  'Messages' => count_of($conn, 'contact_submissions'),
];
admin_head('Dashboard');
?>
<h1 style="margin-top:0">📊 Dashboard</h1>
<p class="muted">Welcome, <?= e($_SESSION['admin_user'] ?? 'admin') ?>. Edit profile details, photo and resume under <strong>Profile &amp; Photo</strong>; everything else from the side menu.</p>
<div class="grid cols-3">
<?php foreach ($stats as $k => $v): ?>
  <div class="card"><div class="muted"><?= e($k) ?></div><div class="stat"><?= $v ?></div></div>
<?php endforeach; ?>
</div>
<div class="card" style="margin-top:14px">
  <h3 style="margin-top:0">⚙️ Setup checklist</h3>
  <ol>
    <li>Copy <code>config.mail.example.php → config.mail.php</code> and add SMTP credentials (contact form needs <code>MAIL_HOST/USER/PASS/TO/FROM</code>).</li>
    <li>Delete or restrict <code>install.php</code> after first run.</li>
    <li>Change the default admin password (<a href="password.php">here</a>).</li>
    <li>Uploads live in <code>uploads/</code> (PHP execution is blocked there via <code>.htaccess</code>).</li>
  </ol>
</div>
<?php admin_foot(); ?>
