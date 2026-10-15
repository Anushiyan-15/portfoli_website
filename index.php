<?php
// index.php — public portfolio front page (data-driven from MySQL).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

// If tables are missing, point to the installer instead of fatal-erroring.
$missing = false;
$probe = @$conn->query("SELECT 1 FROM profile LIMIT 1");
if (!$probe) $missing = true;

$profile = $missing ? [
  'name' => '[your name]', 'role' => '[e.g., Full-stack developer / UI designer / Student]',
  'tagline' => 'Run install.php to set up the database.',
  'bio' => '', 'photo_path' => '', 'resume_path' => '', 'email' => 'you@example.com',
  'location' => '', 'availability' => '', 'linkedin' => '', 'github' => '', 'twitter' => '', 'website' => '',
] : get_profile($conn);

function rows($conn, $sql) {
  $out = [];
  $r = @$conn->query($sql);
  if ($r) while ($row = $r->fetch_assoc()) $out[] = $row;
  return $out;
}

$skills = $missing ? [] : rows($conn, "SELECT * FROM skills ORDER BY skill_group, sort_order, id");
$projects = $missing ? [] : rows($conn, "SELECT * FROM projects ORDER BY featured DESC, sort_order, created_at DESC");
$exp = $missing ? [] : rows($conn, "SELECT * FROM experience WHERE type='experience' ORDER BY sort_order, id");
$edu = $missing ? [] : rows($conn, "SELECT * FROM experience WHERE type='education' ORDER BY sort_order, id");
$testimonials = $missing ? [] : rows($conn, "SELECT * FROM testimonials WHERE is_visible=1 ORDER BY sort_order, id");
$posts = $missing ? [] : rows($conn, "SELECT * FROM posts WHERE is_published=1 ORDER BY created_at DESC LIMIT 6");

// Group skills.
$grouped = ['Frontend' => [], 'Backend' => [], 'Tools' => []];
foreach ($skills as $s) { $g = $s['skill_group']; if (!isset($grouped[$g])) $grouped[$g] = []; $grouped[$g][] = $s; }
// Project categories for filter.
$cats = [];
foreach ($projects as $p) { $c = trim($p['category'] ?: 'Web'); if (!in_array($c, $cats, true)) $cats[] = $c; }
sort($cats);

$status = $_GET['status'] ?? '';
$siteTitle = $profile['name'] . ' | ' . $profile['role'];
$desc = mb_substr($profile['tagline'] ?: strip_tags($profile['bio'] ?? ''), 0, 160);
$canonical = base_url() . '/index.php';
$photo = !empty($profile['photo_path']) ? e($profile['photo_path']) : '';
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($siteTitle) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="author" content="<?= e($profile['name']) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<!-- Open Graph / Twitter -->
<meta property="og:type" content="website">
<meta property="og:title" content="<?= e($siteTitle) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($photo): ?><meta property="og:image" content="<?= e(base_url() . '/' . $profile['photo_path']) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<meta name="theme-color" content="#0b1020" media="(prefers-color-scheme: dark)">
<meta name="theme-color" content="#f4f6fb" media="(prefers-color-scheme: light)">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🤖</text></svg>">
<!-- Fonts: editable choice — Sora (display) + Inter (body) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Person","name":<?= json_encode($profile['name']) ?>,"jobTitle":<?= json_encode($profile['role']) ?>,"email":<?= json_encode($profile['email']) ?>,"address":<?= json_encode($profile['location']) ?>}
</script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="bg-glow" aria-hidden="true"></div>
<div class="bg-stars" aria-hidden="true"></div>

<nav class="nav" aria-label="Primary">
  <div class="nav-inner">
    <a class="brand" href="#top"><span class="dot" aria-hidden="true"></span><?= e($profile['name']) ?></a>
    <div class="nav-links" data-nav-links>
      <a href="#about">About</a>
      <a href="#skills">Skills</a>
      <a href="#projects">Projects</a>
      <a href="#journey">Journey</a>
      <?php if ($posts): ?><a href="#blog">Blog</a><?php endif; ?>
      <a href="#contact">Contact</a>
      <a href="admin/login.php">Admin</a>
    </div>
    <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
      <button class="icon-btn" data-theme-toggle aria-pressed="false" aria-label="Toggle dark / light mode" title="Dark / light mode">🌓</button>
      <button class="icon-btn menu-btn" data-menu aria-expanded="false" aria-label="Open menu">☰</button>
    </div>
  </div>
</nav>

<main id="main">
<?php if ($missing): ?>
<div class="wrap"><div class="alert warn" role="alert" style="margin-top:18px">
  Database tables not found. <a href="install.php"><strong>Run install.php</strong></a> to create them and seed starter content.
</div></div>
<?php endif; ?>

<!-- ============ HERO ============ -->
<header class="hero" id="top">
  <div class="wrap hero-grid">
    <div>
      <span class="eyebrow"><span class="pulse" aria-hidden="true"></span><?= e($profile['availability'] ?: 'Open to opportunities') ?></span>
      <h1>Hi, I'm <span class="grad"><?= e($profile['name']) ?></span></h1>
      <p class="role"><?= e($profile['role']) ?></p>
      <p class="tagline"><?= e($profile['tagline']) ?></p>
      <div class="hero-cta">
        <a class="btn btn-primary" href="#projects">View Projects →</a>
        <a class="btn btn-ghost" href="#contact">Contact Me</a>
        <?php if (!empty($profile['resume_path'])): ?>
        <a class="btn btn-ghost" href="<?= e($profile['resume_path']) ?>" download>⤓ Resume</a>
        <?php endif; ?>
      </div>
      <div class="hero-meta">
        <?php if ($profile['location']): ?><span>📍 <?= e($profile['location']) ?></span><?php endif; ?>
        <?php if ($profile['email']): ?><span>✉️ <a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a></span><?php endif; ?>
      </div>
    </div>
    <div>
      <!-- Interactive 3D robot stage. Keyboard-focusable; click/Enter = wave. -->
      <div class="stage">
        <canvas data-robot width="640" height="440" aria-label="Interactive 3D robot"></canvas>
        <div class="speech" data-speech role="status" aria-live="polite"></div>
        <div class="stage-actions">
          <button class="icon-btn" id="robot-wave" type="button" aria-label="Ask robot to wave">👋</button>
        </div>
        <span class="stage-hint" data-stage-hint>Loading 3D…</span>
      </div>
    </div>
  </div>
</header>

<!-- ============ ABOUT ============ -->
<section class="block" id="about" aria-labelledby="about-h">
  <div class="wrap">
    <div class="sec-head reveal"><span class="kicker">About</span><h2 id="about-h">About Me</h2></div>
    <div class="about-grid">
      <div class="avatar-wrap reveal">
        <?php if (!empty($profile['photo_path']) && is_file(__DIR__ . '/' . $profile['photo_path'])): ?>
          <img class="avatar" src="<?= e($profile['photo_path']) ?>" alt="Portrait of <?= e($profile['name']) ?>" loading="lazy" width="230" height="230">
        <?php else: ?>
          <div class="avatar-fallback" role="img" aria-label="Profile photo placeholder">👤</div>
          <p class="muted" style="font-size:.85rem">Add a photo in Admin → Profile.</p>
        <?php endif; ?>
      </div>
      <div class="reveal">
        <p style="font-size:1.08rem"><?= nl2br(e($profile['bio'] ?: 'Short bio goes here — edit me in Admin → Profile.')) ?></p>
        <div class="facts">
          <div class="fact"><strong>Name:</strong> <?= e($profile['name']) ?></div>
          <div class="fact"><strong>Email:</strong> <a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a></div>
          <div class="fact"><strong>Location:</strong> <?= e($profile['location'] ?: '—') ?></div>
          <div class="fact"><strong>Availability:</strong> <?= e($profile['availability'] ?: '—') ?></div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap">
          <?php if (!empty($profile['resume_path'])): ?>
          <a class="btn btn-primary btn-sm" href="<?= e($profile['resume_path']) ?>" download>⤓ Download CV / Resume</a>
          <?php endif; ?>
          <?php if (!empty($profile['linkedin'])): ?><a class="btn btn-ghost btn-sm" href="<?= e($profile['linkedin']) ?>" target="_blank" rel="noopener">LinkedIn</a><?php endif; ?>
          <?php if (!empty($profile['github'])): ?><a class="btn btn-ghost btn-sm" href="<?= e($profile['github']) ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ SKILLS ============ -->
<section class="block" id="skills" aria-labelledby="skills-h">
  <div class="wrap">
    <div class="sec-head reveal"><span class="kicker">Capabilities</span><h2 id="skills-h">Skills</h2>
      <p class="muted">Grouped into Frontend, Backend and Tools — managed in Admin → Skills.</p></div>
    <?php if (!$grouped['Frontend'] && !$grouped['Backend'] && !$grouped['Tools']): ?>
      <div class="card reveal"><p class="muted">No skills yet — add some in the admin area.</p></div>
    <?php else: ?>
    <div class="grid cols-3">
      <?php foreach ($grouped as $g => $list): ?>
      <div class="card skill-group reveal">
        <h3><?= e($g) ?></h3>
        <?php if (!$list): ?><p class="muted">—</p><?php endif; ?>
        <?php foreach ($list as $s): ?>
        <div class="skill">
          <div class="skill-top"><span><?= e($s['name']) ?></span><span class="muted"><?= (int)$s['level'] ?>%</span></div>
          <div class="bar" role="progressbar" aria-valuenow="<?= (int)$s['level'] ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= e($s['name']) ?> proficiency"><span data-level="<?= (int)$s['level'] ?>"></span></div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ PROJECTS ============ -->
<section class="block" id="projects" aria-labelledby="projects-h">
  <div class="wrap">
    <div class="sec-head reveal"><span class="kicker">Selected work</span><h2 id="projects-h">Projects</h2>
      <p class="muted">Filterable gallery. Open any card for live demo, GitHub and browsable source code.</p></div>
    <?php if (!$projects): ?>
      <div class="card reveal"><p class="muted">No projects yet — publish some from Admin → Projects.</p></div>
    <?php else: ?>
    <div class="filters reveal" role="group" aria-label="Filter projects by category">
      <button class="filter-btn" data-filter="all" aria-pressed="true">All</button>
      <?php foreach ($cats as $c): ?>
      <button class="filter-btn" data-filter="<?= e($c) ?>" aria-pressed="false"><?= e($c) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="proj-grid">
      <?php foreach ($projects as $p):
        $tags = array_filter(array_map('trim', explode(',', (string)$p['tags'])));
        $thumb = !empty($p['thumbnail_path']) && is_file(__DIR__ . '/' . $p['thumbnail_path']) ? $p['thumbnail_path'] : '';
      ?>
      <article class="card tilt proj reveal" data-category="<?= e(trim($p['category'] ?: 'Web')) ?>">
        <?php if ($thumb): ?>
          <img class="proj-thumb" src="<?= e($thumb) ?>" alt="<?= e($p['title']) ?> thumbnail" loading="lazy">
        <?php else: ?>
          <div class="proj-thumb" role="img" aria-label="<?= e($p['title']) ?> artwork placeholder"
               style="display:flex;align-items:center;justify-content:center;font-size:3rem">🧩</div>
        <?php endif; ?>
        <div class="proj-body">
          <h3><?= e($p['title']) ?></h3>
          <p><?= e(mb_strimwidth(strip_tags($p['description']), 0, 140, '…')) ?></p>
          <?php if ($tags): ?><div class="tags"><?php foreach ($tags as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div><?php endif; ?>
          <div class="proj-links">
            <a class="btn btn-primary btn-sm" href="project.php?slug=<?= e($p['slug']) ?>">Details + Code</a>
            <?php if (!empty($p['live_url'])): ?><a class="btn btn-ghost btn-sm" href="<?= e($p['live_url']) ?>" target="_blank" rel="noopener">Live ↗</a><?php endif; ?>
            <?php if (!empty($p['github_url'])): ?><a class="btn btn-ghost btn-sm" href="<?= e($p['github_url']) ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
      <div class="card" data-empty style="display:none"><p class="muted">No projects in this category.</p></div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- ============ JOURNEY (experience + education) ============ -->
<section class="block" id="journey" aria-labelledby="journey-h">
  <div class="wrap">
    <div class="sec-head reveal"><span class="kicker">Journey</span><h2 id="journey-h">Experience &amp; Education</h2></div>
    <div class="grid cols-2">
      <div class="reveal">
        <h3>💼 Experience</h3>
        <?php if (!$exp): ?><p class="muted">Add roles in Admin → Experience.</p><?php endif; ?>
        <ol class="timeline">
          <?php foreach ($exp as $x): ?>
          <li><span class="t-dot" aria-hidden="true"></span>
            <div class="t-card"><h3><?= e($x['title']) ?></h3>
              <div class="t-meta"><?= e($x['organization']) ?><?php if ($x['location']): ?> · <?= e($x['location']) ?><?php endif; ?> · <?= e($x['start_label']) ?> – <?= e($x['end_label']) ?></div>
              <?php if ($x['description']): ?><p class="muted" style="margin:.4rem 0 0"><?= nl2br(e($x['description'])) ?></p><?php endif; ?>
            </div></li>
          <?php endforeach; ?>
        </ol>
      </div>
      <div class="reveal">
        <h3>🎓 Education</h3>
        <?php if (!$edu): ?><p class="muted">Add education in Admin → Experience.</p><?php endif; ?>
        <ol class="timeline">
          <?php foreach ($edu as $x): ?>
          <li><span class="t-dot" aria-hidden="true"></span>
            <div class="t-card"><h3><?= e($x['title']) ?></h3>
              <div class="t-meta"><?= e($x['organization']) ?> · <?= e($x['start_label']) ?> – <?= e($x['end_label']) ?></div>
              <?php if ($x['description']): ?><p class="muted" style="margin:.4rem 0 0"><?= nl2br(e($x['description'])) ?></p><?php endif; ?>
            </div></li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  </div>
</section>

<!-- ============ TESTIMONIALS (omitted when empty) ============ -->
<?php if ($testimonials): ?>
<section class="block" id="love" aria-labelledby="love-h">
  <div class="wrap">
    <div class="sec-head reveal"><span class="kicker">Kind words</span><h2 id="love-h">Testimonials &amp; Achievements</h2></div>
    <div class="grid cols-3">
      <?php foreach ($testimonials as $t): ?>
      <figure class="card tilt reveal" style="margin:0">
        <blockquote class="quote">“<?= nl2br(e($t['quote'])) ?>”</blockquote>
        <figcaption class="muted">— <strong><?= e($t['author']) ?></strong><?php if ($t['role']): ?>, <?= e($t['role']) ?><?php endif; ?></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ BLOG (omitted when empty) ============ -->
<?php if ($posts): ?>
<section class="block" id="blog" aria-labelledby="blog-h">
  <div class="wrap">
    <div class="sec-head reveal"><span class="kicker">Writing</span><h2 id="blog-h">Blog &amp; Articles</h2>
      <p class="muted">Managed in Admin → Posts. Hidden automatically when empty.</p></div>
    <div class="posts">
      <?php foreach ($posts as $post): ?>
      <article class="card reveal">
        <?php if (!empty($post['cover_path']) && is_file(__DIR__ . '/' . $post['cover_path'])): ?>
          <img class="post-cover" src="<?= e($post['cover_path']) ?>" alt="" loading="lazy">
        <?php endif; ?>
        <h3 style="margin:.6rem 0 .3rem"><?= e($post['title']) ?></h3>
        <?php if ($post['excerpt']): ?><p class="muted"><?= e($post['excerpt']) ?></p><?php endif; ?>
        <p class="muted" style="font-size:.82rem"><?= e(date('M j, Y', strtotime($post['created_at']))) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ============ CONTACT ============ -->
<section class="block" id="contact" aria-labelledby="contact-h">
  <div class="wrap">
    <div class="sec-head reveal"><span class="kicker">Say hello</span><h2 id="contact-h">Contact</h2>
      <p class="muted">Validated form → PHPMailer SMTP. Messages are also saved so nothing is lost.</p></div>
    <?php if ($status === 'success'): ?><div class="alert ok" role="alert">Thank you! Your message has been sent successfully.</div>
    <?php elseif ($status === 'error'): ?><div class="alert err" role="alert">Something went wrong sending your message — it was saved and I’ll follow up. Please try again later.</div>
    <?php elseif ($status === 'invalid'): ?><div class="alert warn" role="alert">Please fill out all fields with valid information.</div>
    <?php elseif ($status === 'email_error'): ?><div class="alert warn" role="alert">Message saved, but email delivery failed. I’ll still receive it.</div>
    <?php endif; ?>
    <div class="contact-grid">
      <div class="card reveal">
        <h3 style="margin-top:0">Direct links</h3>
        <p class="muted">Prefer email or socials? Reach me here:</p>
        <p><strong>Email:</strong> <a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a></p>
        <div class="social-row">
          <?php if ($profile['linkedin']): ?><a href="<?= e($profile['linkedin']) ?>" target="_blank" rel="noopener" aria-label="LinkedIn">in LinkedIn</a><?php endif; ?>
          <?php if ($profile['github']): ?><a href="<?= e($profile['github']) ?>" target="_blank" rel="noopener" aria-label="GitHub">⌨ GitHub</a><?php endif; ?>
          <?php if ($profile['twitter']): ?><a href="<?= e($profile['twitter']) ?>" target="_blank" rel="noopener" aria-label="Twitter / X">𝕏 Twitter</a><?php endif; ?>
          <?php if ($profile['website']): ?><a href="<?= e($profile['website']) ?>" target="_blank" rel="noopener">🌐 Website</a><?php endif; ?>
        </div>
        <p class="muted" style="font-size:.85rem;margin-top:12px">📍 <?= e($profile['location'] ?: 'Remote-friendly') ?> · <?= e($profile['availability'] ?: 'Open to work') ?></p>
      </div>
      <div class="card reveal">
        <form action="process-contact.php" method="post" novalidate>
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <div class="hp" aria-hidden="true"><label>Leave empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
          <div class="field"><label class="lbl" for="cname">Your name</label>
            <input class="in" id="cname" name="name" required maxlength="120" autocomplete="name" placeholder="Jane Doe"></div>
          <div class="field"><label class="lbl" for="cemail">Email</label>
            <input class="in" id="cemail" type="email" name="email" required maxlength="160" autocomplete="email" placeholder="jane@example.com"></div>
          <div class="field"><label class="lbl" for="csubj">Subject (optional)</label>
            <input class="in" id="csubj" name="subject" maxlength="255" placeholder="Project inquiry"></div>
          <div class="field"><label class="lbl" for="cmsg">Message</label>
            <textarea class="in" id="cmsg" name="message" rows="5" required maxlength="5000" placeholder="Tell me about your project…"></textarea></div>
          <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Send message ✉️</button>
        </form>
      </div>
    </div>
  </div>
</section>
</main>

<!-- ============ FOOTER ============ -->
<footer class="site">
  <div class="wrap">
    <div>© <span data-year><?= date('Y') ?></span> <?= e($profile['name']) ?> · Built with PHP, MySQL &amp; Three.js</div>
    <div class="social-row">
      <?php if ($profile['linkedin']): ?><a href="<?= e($profile['linkedin']) ?>" target="_blank" rel="noopener" aria-label="LinkedIn">in</a><?php endif; ?>
      <?php if ($profile['github']): ?><a href="<?= e($profile['github']) ?>" target="_blank" rel="noopener" aria-label="GitHub">⌨</a><?php endif; ?>
      <?php if ($profile['twitter']): ?><a href="<?= e($profile['twitter']) ?>" target="_blank" rel="noopener" aria-label="Twitter / X">𝕏</a><?php endif; ?>
      <a href="#top" aria-label="Back to top">↑ Top</a>
    </div>
  </div>
</footer>

<script src="assets/js/main.js" defer></script>
<script src="assets/js/robot-three.js" defer></script>
<script>
  // Wave button triggers the same robot greeting (works even before Three loads).
  document.getElementById('robot-wave')?.addEventListener('click', function () {
    var c = document.querySelector('[data-robot]');
    if (c) c.dispatchEvent(new Event('click'));
  });
</script>
</body>
</html>
<?php if (isset($conn)) $conn->close(); ?>
