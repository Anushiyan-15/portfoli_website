<?php
// admin/login.php — 3D animated admin login + loading screen on submit.
// On success sets $_SESSION['just_logged_in'] so _layout.php shows a
// welcome loader on the dashboard.
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
if (is_admin()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // tiny brute-force brake: max 8 attempts per 5 min per session.
  $_SESSION['login_tries'] = ($_SESSION['login_tries'] ?? 0) + 1;
  $_SESSION['login_first'] = $_SESSION['login_first'] ?? time();
  if ($_SESSION['login_tries'] > 8 && time() - $_SESSION['login_first'] < 300) {
    $err = 'Too many attempts — wait a few minutes.';
  } else {
    $u = trim($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');
    $st = $conn->prepare("SELECT id, username, password_hash FROM users WHERE username = ? LIMIT 1");
    $st->bind_param('s', $u); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    if ($row && password_verify($p, $row['password_hash'])) {
      // Rehash if needed.
      if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        $nh = password_hash($p, PASSWORD_DEFAULT);
        $up = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $up->bind_param('si', $nh, $row['id']); $up->execute(); $up->close();
      }
      session_regenerate_id(true);
      $_SESSION['admin_id'] = (int)$row['id'];
      $_SESSION['admin_user'] = $row['username'];
      $_SESSION['login_tries'] = 0;
      $_SESSION['just_logged_in'] = true; // triggers welcome loader in _layout.php
      header('Location: index.php'); exit;
    }
    $err = 'Invalid username or password.';
  }
}
?>
<!doctype html><html lang="en" data-theme="dark"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin login · 3D Secure Access</title><meta name="robots" content="noindex,nofollow">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
/* ---- 3D login scene ---- */
.login-page{min-height:100vh;display:flex;align-items:center;padding:28px 0;position:relative;overflow:hidden}
.login-shell{display:grid;gap:18px;width:100%;align-items:stretch}
@media(min-width:960px){.login-shell{grid-template-columns:1.1fr .9fr}}
.login-3d{position:relative;min-height:380px}
.login-3d canvas{width:100%;height:420px;display:block;touch-action:manipulation}
@media(min-width:960px){.login-3d canvas{height:520px}}
.orb{position:absolute;border-radius:50%;filter:blur(2px);opacity:.8;pointer-events:none;animation:float 7s ease-in-out infinite}
.orb.o1{width:90px;height:90px;left:6%;top:8%;background:radial-gradient(circle at 30% 30%,#22d3ee,#6c5ce7);animation-delay:0s}
.orb.o2{width:56px;height:56px;right:10%;top:22%;background:radial-gradient(circle at 30% 30%,#f0abfc,#6c5ce7);animation-delay:1.4s}
.orb.o3{width:34px;height:34px;left:14%;bottom:12%;background:radial-gradient(circle at 30% 30%,#99f6e0,#22d3ee);animation-delay:2.6s}
@keyframes float{0%,100%{transform:translateY(0) scale(1)}50%{transform:translateY(-18px) scale(1.06)}}
.login-card{backdrop-filter:blur(16px)}
.login-card h1{margin:.2rem 0}
.pw-wrap{position:relative}
.pw-wrap .show-btn{position:absolute;right:8px;top:50%;transform:translateY(-50%);border:1px solid var(--line);background:var(--card);color:var(--text);border-radius:10px;padding:.3rem .6rem;cursor:pointer;font-size:.85rem}
/* ---- full-screen loading overlay (login submit) ---- */
.loader{position:fixed;inset:0;z-index:300;display:none;align-items:center;justify-content:center;flex-direction:column;gap:14px;
  background:radial-gradient(700px 400px at 50% 20%,rgba(139,92,246,.25),transparent 60%),color-mix(in srgb,var(--bg) 88%,transparent);backdrop-filter:blur(8px)}
.loader.show{display:flex}
.bot{font-size:3.4rem;animation:botbob 1.1s ease-in-out infinite;filter:drop-shadow(0 10px 24px rgba(34,211,238,.45))}
@keyframes botbob{0%,100%{transform:translateY(0) rotate(-4deg)}50%{transform:translateY(-12px) rotate(4deg)}}
.loadbar{width:min(320px,70vw);height:10px;border-radius:999px;background:color-mix(in srgb,var(--muted) 25%,transparent);overflow:hidden;border:1px solid var(--line)}
.loadbar>span{display:block;height:100%;width:30%;border-radius:999px;background:linear-gradient(90deg,var(--brand),var(--accent));animation:slide 1.1s ease-in-out infinite}
@keyframes slide{0%{margin-left:-30%}100%{margin-left:100%}}
.loader p{color:var(--muted);font-weight:600;margin:0}
@media (prefers-reduced-motion:reduce){.orb,.bot,.loadbar>span{animation:none}}
</style></head>
<body class="admin-3d">
<a class="skip-link" href="#login-form">Skip to login</a>
<!-- Full-page animated 3D background (mouse-follow, global) -->
<canvas class="bg3d-canvas" data-bg3d aria-hidden="true"></canvas>
<div class="bg-glow" aria-hidden="true"></div>
<div class="bg-stars" aria-hidden="true"></div>
<span class="admin-orb a1" aria-hidden="true"></span>
<span class="admin-orb a2" aria-hidden="true"></span>
<span class="admin-orb a3" aria-hidden="true"></span>

<main class="login-page"><div class="wrap login-shell">
  <!-- Left: interactive 3D robot stage -->
  <div class="stage login-3d reveal in" aria-label="3D secure access illustration">
    <span class="orb o1" aria-hidden="true"></span>
    <span class="orb o2" aria-hidden="true"></span>
    <span class="orb o3" aria-hidden="true"></span>
    <canvas data-robot width="640" height="520" aria-label="Interactive 3D robot guard. Press Enter to greet."></canvas>
    <div class="speech" data-speech role="status" aria-live="polite"></div>
    <span class="stage-hint" data-stage-hint>Loading 3D guard…</span>
  </div>

  <!-- Right: glass login card -->
  <div class="card login-card">
    <div style="display:flex;align-items:center;gap:10px;justify-content:space-between">
      <span class="kicker">Restricted area</span>
      <button class="icon-btn" data-theme-toggle aria-label="Toggle dark / light mode" title="Theme">🌓</button>
    </div>
    <h1>🤖 Welcome back</h1>
    <p class="muted" style="margin-top:0">Sign in to manage your portfolio. The guard robot watches for bots.</p>
    <?php if ($err): ?><div class="alert err" role="alert"><?= e($err) ?></div><?php endif; ?>
    <form method="post" id="login-form" data-login-form novalidate>
      <div class="field"><label class="lbl" for="u">Username</label>
        <input class="in" id="u" name="username" required autocomplete="username" autofocus placeholder="admin"></div>
      <div class="field"><label class="lbl" for="p">Password</label>
        <div class="pw-wrap">
          <input class="in" id="p" type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
          <button type="button" class="show-btn" data-show-pw aria-label="Show password">Show</button>
        </div></div>
      <button class="btn btn-primary" data-login-btn style="width:100%;justify-content:center" type="submit">Log in →</button>
      <p class="muted" id="caps-warn" style="display:none;font-size:.85rem">⚠️ Caps Lock looks ON — passwords are case-sensitive.</p>
    </form>
    <p style="margin-top:14px"><a href="../index.php">← Back to site</a></p>
    <p class="muted" style="font-size:.82rem">Default after install: <code>admin</code> / <code>ChangeMe123!</code> — change it after login.</p>
  </div>
</div></main>

<!-- Loading screen shown while credentials are verified -->
<div class="loader" data-loader role="alert" aria-live="assertive" aria-label="Signing you in">
  <div class="bot" aria-hidden="true">🤖</div>
  <strong>Unlocking dashboard…</strong>
  <div class="loadbar" aria-hidden="true"><span></span></div>
  <p data-loader-text>Contacting server…</p>
</div>

<script src="../assets/js/main.js" defer></script>
<script src="../assets/js/bg-3d.js" defer></script>
<script src="../assets/js/robot-three.js" defer></script>
<script>
(function () {
  var form = document.querySelector('[data-login-form]');
  var loader = document.querySelector('[data-loader]');
  var loaderText = document.querySelector('[data-loader-text]');
  var btn = document.querySelector('[data-login-btn]');
  var msgs = ['Contacting server…', 'Verifying credentials…', 'Waking the robot…', 'Unlocking dashboard…'];
  var i = 0, timer = null;

  if (form && loader) {
    form.addEventListener('submit', function (ev) {
      var u = document.getElementById('u'), p = document.getElementById('p');
      if (!u.value.trim() || !p.value) return; // let HTML5/required handle it
      loader.classList.add('show');
      if (btn) { btn.disabled = true; btn.textContent = 'Signing in…'; }
      timer = setInterval(function () {
        i = (i + 1) % msgs.length;
        if (loaderText) loaderText.textContent = msgs[i];
      }, 700);
      // Safety: if navigation stalls (e.g. slow server), don't trap the user.
      setTimeout(function () {
        if (loader.classList.contains('show') && !document.hidden) {
          if (btn) { btn.disabled = false; btn.textContent = 'Log in →'; }
        }
      }, 15000);
    });
  }

  // Show/hide password (familiar UX, keyboard accessible).
  var pw = document.getElementById('p');
  var showBtn = document.querySelector('[data-show-pw]');
  if (showBtn && pw) {
    showBtn.addEventListener('click', function () {
      var show = pw.type === 'password';
      pw.type = show ? 'text' : 'password';
      showBtn.textContent = show ? 'Hide' : 'Show';
      pw.focus();
    });
  }

  // Caps-lock hint.
  var capsWarn = document.getElementById('caps-warn');
  if (pw && capsWarn) {
    pw.addEventListener('keyup', function (e) {
      var on = e.getModifierState && e.getModifierState('CapsLock');
      capsWarn.style.display = on ? '' : 'none';
    });
  }
})();
</script>
</body></html>
