<?php
// project.php — single project: details + browsable source code + downloads.
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/helpers.php';

$slug = trim((string)($_GET['slug'] ?? ''));
$id = (int)($_GET['id'] ?? 0);
if ($slug === '' && $id <= 0) { header('Location: index.php#projects'); exit; }

if ($slug !== '') {
  $st = $conn->prepare("SELECT * FROM projects WHERE slug = ? LIMIT 1");
  $st->bind_param('s', $slug); $st->execute();
  $proj = $st->get_result()->fetch_assoc(); $st->close();
} else {
  $st = $conn->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
  $st->bind_param('i', $id); $st->execute();
  $proj = $st->get_result()->fetch_assoc(); $st->close();
}
if (!$proj) { http_response_code(404); echo 'Project not found. <a href="index.php#projects">Back</a>'; exit; }
$pid = (int)$proj['id'];

// Files for this project.
$files = [];
$st = $conn->prepare("SELECT id, original_name, stored_path, mime_type, file_size FROM project_files WHERE project_id = ? ORDER BY original_name");
$st->bind_param('i', $pid); $st->execute();
$res = $st->get_result(); while ($r = $res->fetch_assoc()) $files[] = $r; $st->close();

// Optional file preview (?view=<file id>).
$preview = null; $previewErr = '';
if (isset($_GET['view'])) {
  $fid = (int)$_GET['view'];
  foreach ($files as $f) {
    if ((int)$f['id'] === $fid) {
      $ext = strtolower(pathinfo($f['original_name'], PATHINFO_EXTENSION));
      $abs = realpath(__DIR__ . '/' . $f['stored_path']);
      $root = realpath(__DIR__ . '/uploads');
      if (!$abs || !$root || strpos($abs, $root) !== 0 || !is_file($abs)) $previewErr = 'File not found on disk.';
      elseif (!in_array($ext, PREVIEWABLE_EXT, true)) $previewErr = 'Preview not available for .' . $ext . ' — use download.';
      elseif (filesize($abs) > 400 * 1024) $previewErr = 'File too large to preview — use download.';
      else $preview = ['meta' => $f, 'code' => file_get_contents($abs), 'ext' => $ext];
      break;
    }
  }
  if (!$preview && !$previewErr) $previewErr = 'Unknown file.';
}

$tags = array_filter(array_map('trim', explode(',', (string)$proj['tags'])));
$profile = get_profile($conn);
$title = $proj['title'] . ' — ' . $profile['name'];
?>
<!doctype html>
<html lang="en" data-theme="dark">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title>
<meta name="description" content="<?= e(mb_strimwidth(strip_tags($proj['description']), 0, 160, '…')) ?>">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<div class="bg-glow" aria-hidden="true"></div><div class="bg-stars" aria-hidden="true"></div>
<nav class="nav" aria-label="Primary"><div class="nav-inner">
  <a class="brand" href="index.php#top"><span class="dot" aria-hidden="true"></span><?= e($profile['name']) ?></a>
  <div class="nav-links" data-nav-links>
    <a href="index.php#projects">← All projects</a><a href="index.php#contact">Contact</a><a href="admin/login.php">Admin</a>
  </div>
  <div style="margin-left:auto;display:flex;gap:8px">
    <button class="icon-btn" data-theme-toggle aria-label="Toggle dark / light mode">🌓</button>
    <button class="icon-btn menu-btn" data-menu aria-expanded="false" aria-label="Open menu">☰</button>
  </div>
</div></nav>
<main id="main"><section class="block"><div class="wrap">
  <p><a href="index.php#projects">← Back to projects</a></p>
  <span class="kicker"><?= e($proj['category'] ?: 'Project') ?></span>
  <h1 style="margin:.3rem 0"><?= e($proj['title']) ?></h1>
  <?php if ($tags): ?><div class="tags" style="margin:.4rem 0"><?php foreach ($tags as $t): ?><span class="tag"><?= e($t) ?></span><?php endforeach; ?></div><?php endif; ?>
  <div class="grid cols-2" style="margin-top:16px">
    <div>
      <?php if (!empty($proj['thumbnail_path']) && is_file(__DIR__ . '/' . $proj['thumbnail_path'])): ?>
        <img src="<?= e($proj['thumbnail_path']) ?>" alt="<?= e($proj['title']) ?> screenshot" loading="lazy" style="border-radius:16px;border:1px solid var(--line)">
      <?php else: ?>
        <div class="card" style="font-size:3rem;text-align:center" role="img" aria-label="No screenshot">🧩</div>
      <?php endif; ?>
      <div class="proj-links" style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">
        <?php if ($proj['live_url']): ?><a class="btn btn-primary btn-sm" href="<?= e($proj['live_url']) ?>" target="_blank" rel="noopener">Live demo ↗</a><?php endif; ?>
        <?php if ($proj['github_url']): ?><a class="btn btn-ghost btn-sm" href="<?= e($proj['github_url']) ?>" target="_blank" rel="noopener">GitHub</a><?php endif; ?>
      </div>
    </div>
    <div class="card"><h2 style="margin-top:0">About this project</h2><p><?= nl2br(e($proj['description'])) ?></p></div>
  </div>

  <!-- Code snippet -->
  <?php if (!empty($proj['code_snippet'])): ?>
  <h2>Featured code <span class="muted" style="font-size:.85rem">(<?= e($proj['code_lang']) ?>)</span></h2>
  <pre class="code"><code><?= e($proj['code_snippet']) ?></code></pre>
  <?php endif; ?>

  <!-- Uploaded files / source browser -->
  <h2>Source files <?= $files ? '<span class="muted" style="font-size:.85rem">(' . count($files) . ')</span>' : '' ?></h2>
  <?php if (!$files): ?>
    <div class="card"><p class="muted">No files uploaded for this project yet.</p></div>
  <?php else: ?>
    <div class="card">
      <ul class="file-list">
      <?php foreach ($files as $f): ?>
        <li>📄 <strong><?= e($f['original_name']) ?></strong>
          <span class="muted">(<?= e(human_size((int)$f['file_size'])) ?>)</span>
          <?php $ext = strtolower(pathinfo($f['original_name'], PATHINFO_EXTENSION)); ?>
          <?php if (in_array($ext, PREVIEWABLE_EXT, true)): ?>
            · <a href="project.php?slug=<?= e($proj['slug']) ?>&view=<?= (int)$f['id'] ?>#code">View</a>
          <?php endif; ?>
          · <a href="<?= e($f['stored_path']) ?>" download>Download</a></li>
      <?php endforeach; ?>
      </ul>
    </div>
    <?php if ($previewErr): ?><div class="alert warn" id="code"><?= e($previewErr) ?></div><?php endif; ?>
    <?php if ($preview): ?>
      <h3 id="code">📖 <?= e($preview['meta']['original_name']) ?></h3>
      <pre class="code"><code><?= e($preview['code']) ?></code></pre>
    <?php endif; ?>
  <?php endif; ?>
</div></section></main>
<footer class="site"><div class="wrap"><div>© <span data-year><?= date('Y') ?></span> <?= e($profile['name']) ?></div><div><a href="index.php#top">↑ Top</a></div></div></footer>
<script src="assets/js/main.js" defer></script>
</body></html>
<?php $conn->close(); ?>
