<?php
// admin/_layout.php — shared admin shell (auth guard + nav + flash).
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_admin();
$flash = flash();
$profile = get_profile($conn);
$unread = 0;
$r = @$conn->query("SELECT COUNT(*) c FROM contact_submissions WHERE is_read=0");
if ($r) $unread = (int)$r->fetch_assoc()['c'];
function admin_head($title) {
  global $flash, $unread;
  // One-time welcome loader right after login (flag set in login.php).
  $welcome = !empty($_SESSION['just_logged_in']);
  unset($_SESSION['just_logged_in']);
  $who = $_SESSION['admin_user'] ?? 'admin';
?>
<!doctype html><html lang="en" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Admin</title>
<meta name="robots" content="noindex, nofollow">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.admin-grid{display:grid;gap:14px}@media(min-width:900px){.admin-grid{grid-template-columns:220px 1fr}}
.side{position:sticky;top:76px;align-self:start}
.side a{display:block;padding:.55rem .8rem;border-radius:10px;color:var(--muted);text-decoration:none;font-weight:600}
.side a:hover,.side a.on{background:color-mix(in srgb,var(--brand) 16%,transparent);color:var(--text)}
.stat{font-size:1.8rem;font-weight:800}
.avatar-edit{position:relative;display:inline-block}
.avatar-edit img{width:150px;height:150px;border-radius:50%;object-fit:cover;border:3px solid var(--accent)}
.avatar-edit .cam{position:absolute;right:6px;bottom:6px;width:42px;height:42px;border-radius:50%;
  background:linear-gradient(135deg,var(--brand),var(--accent));color:#fff;border:2px solid var(--bg);
  display:flex;align-items:center;justify-content:center;font-size:1.2rem;cursor:pointer}
.badge{background:var(--bad);color:#fff;border-radius:999px;font-size:.72rem;padding:.1rem .5rem;margin-left:.3rem}
/* ---- post-login welcome loader ---- */
.welcome-loader{position:fixed;inset:0;z-index:300;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;
  background:radial-gradient(700px 400px at 50% 18%,rgba(139,92,246,.28),transparent 60%),color-mix(in srgb,var(--bg) 90%,transparent);
  backdrop-filter:blur(10px);transition:opacity .45s ease,visibility .45s}
.welcome-loader.hide{opacity:0;visibility:hidden;pointer-events:none}
.welcome-loader .bot{font-size:3.2rem;animation:botbob 1.1s ease-in-out infinite;filter:drop-shadow(0 10px 24px rgba(34,211,238,.45))}
@keyframes botbob{0%,100%{transform:translateY(0) rotate(-4deg)}50%{transform:translateY(-12px) rotate(4deg)}}
.welcome-loader .loadbar{width:min(320px,70vw);height:10px;border-radius:999px;background:color-mix(in srgb,var(--muted) 25%,transparent);overflow:hidden;border:1px solid var(--line)}
.welcome-loader .loadbar>span{display:block;height:100%;width:40%;border-radius:999px;background:linear-gradient(90deg,var(--brand),var(--accent));animation:wslide 1s ease-in-out infinite}
@keyframes wslide{0%{margin-left:-40%}100%{margin-left:100%}}
@media (prefers-reduced-motion:reduce){.welcome-loader .bot,.welcome-loader .loadbar>span{animation:none}.welcome-loader{transition:none}}
</style></head>
<body class="admin-3d">
<!-- Full-page animated 3D background for admin (mouse-follow, global) -->
<canvas class="bg3d-canvas" data-bg3d aria-hidden="true"></canvas>
<div class="bg-glow" aria-hidden="true"></div>
<div class="bg-stars" aria-hidden="true"></div>
<span class="admin-orb a1" aria-hidden="true"></span>
<span class="admin-orb a2" aria-hidden="true"></span>
<span class="admin-orb a3" aria-hidden="true"></span>
<?php if ($welcome): ?>
<!-- Loading screen after login: greets the user, then fades out -->
<div class="welcome-loader" data-welcome role="status" aria-live="polite" aria-label="Loading dashboard">
  <div class="bot" aria-hidden="true">🤖</div>
  <strong>Welcome back, <?= e($who) ?>!</strong>
  <div class="loadbar" aria-hidden="true"><span></span></div>
  <span class="muted" data-welcome-text>Loading dashboard…</span>
</div>
<script>
(function () {
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var el = document.querySelector('[data-welcome]');
  var txt = document.querySelector('[data-welcome-text]');
  var steps = ['Loading dashboard…', 'Checking messages…', 'Polishing the robot…', 'Ready!'];
  var i = 0;
  function done() { if (el) el.classList.add('hide'); setTimeout(function () { if (el) el.remove(); }, 600); }
  if (!el) return;
  if (reduce) { done(); return; }
  var t = setInterval(function () {
    i++;
    if (i < steps.length && txt) txt.textContent = steps[i];
    if (i >= steps.length - 1) { clearInterval(t); setTimeout(done, 450); }
  }, 420);
  window.addEventListener('load', function () { setTimeout(function () { if (i < steps.length - 1) return; }, 0); });
  // Failsafe: never trap the user behind the loader.
  setTimeout(done, 4000);
})();
</script>
<?php endif; ?>
<a class="skip-link" href="#main">Skip to content</a>
<nav class="nav" aria-label="Admin"><div class="nav-inner">
  <a class="brand" href="../index.php"><span class="dot"></span>Admin</a>
  <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
    <span class="muted" style="font-size:.85rem"><?= e($_SESSION['admin_user'] ?? 'admin') ?></span>
    <button class="icon-btn" data-theme-toggle aria-label="Toggle theme">🌓</button>
    <a class="icon-btn" href="logout.php" aria-label="Log out" style="text-decoration:none">⎋</a>
  </div>
</div></nav>
<main id="main"><section class="block"><div class="wrap"><div class="admin-grid">
<div class="card side">
  <a href="index.php">📊 Dashboard</a>
  <a href="profile.php">👤 Profile &amp; Photo</a>
  <a href="projects.php">🧩 Projects</a>
  <a href="skills.php">🛠 Skills</a>
  <a href="experience.php">📅 Experience</a>
  <a href="testimonials.php">💬 Testimonials</a>
  <a href="posts.php">✍️ Blog Posts</a>
  <a href="messages.php">✉️ Messages<?php if ($unread): ?><span class="badge"><?= $unread ?> new</span><?php endif; ?></a>
  <a href="password.php">🔑 Password</a>
  <a href="../index.php" target="_blank">🌐 View site ↗</a>
</div>
<div>
<?php if ($flash): ?><div class="alert <?= $flash['type'] === 'err' ? 'err' : 'ok' ?>" role="alert"><?= e($flash['msg']) ?></div><?php endif; ?>
<?php } // end admin_head
function admin_foot() { ?>
</div></div></div></section></main>
<script src="../assets/js/main.js" defer></script>
<script src="../assets/js/bg-3d.js" defer></script>
<script>
  // highlight current side link
  var here = location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.side a').forEach(function (a) {
    if (a.getAttribute('href') === here) a.classList.add('on');
  });
  // instant avatar preview (messaging-app style)
  document.querySelectorAll('[data-avatar-input]').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var img = document.querySelector('[data-avatar-preview]');
      if (img && inp.files && inp.files[0]) img.src = URL.createObjectURL(inp.files[0]);
    });
  });
</script>
</body></html>
<?php } ?>
