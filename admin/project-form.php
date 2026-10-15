<?php
// admin/project-form.php — create / edit a project (thumb, tags, urls, snippet).
require_once __DIR__ . '/_layout.php';
$id = (int)($_GET['id'] ?? 0);
$proj = ['title'=>'','slug'=>'','description'=>'','category'=>'Web','tags'=>'','live_url'=>'','github_url'=>'','code_snippet'=>'','code_lang'=>'php','featured'=>0,'sort_order'=>0,'thumbnail_path'=>''];
if ($id) {
  $st = $conn->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
  $st->bind_param('i', $id); $st->execute();
  $row = $st->get_result()->fetch_assoc(); $st->close();
  if ($row) $proj = $row;
  else { flash('Project not found.', 'err'); header('Location: projects.php'); exit; }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) { flash('Bad token.', 'err'); header('Location: projects.php'); exit; }
  try {
    $thumb = $proj['thumbnail_path'];
    if (!empty($_FILES['thumb']['name'])) {
      $new = save_upload('thumb', 'projects', ALLOWED_IMAGE_EXT, MAX_IMAGE_MB);
      if ($thumb) delete_upload($thumb);
      $thumb = $new;
    }
    if (!empty($_POST['remove_thumb'])) { delete_upload($thumb); $thumb = ''; }
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($slug === '') $slug = slugify($title);
    $slug = slugify($slug);
    $desc = trim($_POST['description'] ?? '');
    $cat = trim($_POST['category'] ?? 'Web') ?: 'Web';
    $tags = trim($_POST['tags'] ?? '');
    $live = trim($_POST['live_url'] ?? '');
    $gh = trim($_POST['github_url'] ?? '');
    foreach (['live' => $live, 'gh' => $gh] as $u) {
      if ($u !== '' && !filter_var($u, FILTER_VALIDATE_URL)) throw new RuntimeException('Live/GitHub URLs must be valid http(s) URLs or empty.');
    }
    $code = (string)($_POST['code_snippet'] ?? '');
    $lang = preg_replace('/[^a-z0-9#+_-]/i', '', (string)($_POST['code_lang'] ?? 'php')) ?: 'php';
    $feat = !empty($_POST['featured']) ? 1 : 0;
    $ord = (int)($_POST['sort_order'] ?? 0);
    if ($title === '' || $desc === '') throw new RuntimeException('Title and description are required.');
    if ($id) {
      // keep slug unique (excluding self)
      $st = $conn->prepare("SELECT id FROM projects WHERE slug = ? AND id <> ? LIMIT 1");
      $st->bind_param('si', $slug, $id); $st->execute();
      if ($st->get_result()->fetch_assoc()) $slug .= '-' . $id;
      $st->close();
      $st = $conn->prepare("UPDATE projects SET title=?, slug=?, description=?, thumbnail_path=?, category=?, tags=?, live_url=?, github_url=?, code_snippet=?, code_lang=?, featured=?, sort_order=? WHERE id=?");
      $st->bind_param('ssssssssssiii', $title, $slug, $desc, $thumb, $cat, $tags, $live, $gh, $code, $lang, $feat, $ord, $id);
      $st->execute(); $st->close();
      flash('Project updated.');
    } else {
      $st = $conn->prepare("SELECT id FROM projects WHERE slug = ? LIMIT 1");
      $st->bind_param('s', $slug); $st->execute();
      if ($st->get_result()->fetch_assoc()) $slug .= '-' . time();
      $st->close();
      $st = $conn->prepare("INSERT INTO projects (title, slug, description, thumbnail_path, category, tags, live_url, github_url, code_snippet, code_lang, featured, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
      $st->bind_param('ssssssssssii', $title, $slug, $desc, $thumb, $cat, $tags, $live, $gh, $code, $lang, $feat, $ord);
      $st->execute(); $id = (int)$st->insert_id; $st->close();
      flash('Project created — now attach source files.');
    }
    header('Location: project-files.php?id=' . $id); exit;
  } catch (Throwable $ex) {
    flash($ex->getMessage(), 'err');
    foreach (['title','description','category','tags','live_url','github_url','code_snippet','code_lang'] as $k) {
      if (isset($_POST[$k])) $proj[$k] = $_POST[$k];
    }
    $proj['slug'] = $_POST['slug'] ?? $proj['slug'];
    $proj['featured'] = !empty($_POST['featured']) ? 1 : 0;
    $proj['sort_order'] = (int)($_POST['sort_order'] ?? 0);
  }
}
admin_head($id ? 'Edit project' : 'New project');
?>
<h1 style="margin-top:0"><?= $id ? 'Edit project' : 'New project' ?></h1>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<div class="card">
  <div class="grid cols-2">
    <div class="field"><label class="lbl">Title *</label><input class="in" name="title" required value="<?= e($proj['title']) ?>"></div>
    <div class="field"><label class="lbl">Slug <span class="muted">(auto if empty)</span></label><input class="in" name="slug" value="<?= e($proj['slug']) ?>"></div>
  </div>
  <div class="field"><label class="lbl">Description *</label><textarea class="in" name="description" rows="4" required><?= e($proj['description']) ?></textarea></div>
  <div class="grid cols-2">
    <div class="field"><label class="lbl">Category <span class="muted">(drives the front-end filter)</span></label><input class="in" name="category" value="<?= e($proj['category']) ?>"></div>
    <div class="field"><label class="lbl">Tags <span class="muted">(comma separated)</span></label><input class="in" name="tags" value="<?= e($proj['tags']) ?>" placeholder="PHP, MySQL, Bootstrap"></div>
    <div class="field"><label class="lbl">Live / demo URL</label><input class="in" name="live_url" value="<?= e($proj['live_url']) ?>" placeholder="https://…"></div>
    <div class="field"><label class="lbl">GitHub URL</label><input class="in" name="github_url" value="<?= e($proj['github_url']) ?>" placeholder="https://github.com/…"></div>
  </div>
  <div class="grid cols-2">
    <div class="field"><label class="lbl">Thumbnail (≤ <?= MAX_IMAGE_MB ?> MB)</label>
      <?php if (!empty($proj['thumbnail_path'])): ?><p><img src="../<?= e($proj['thumbnail_path']) ?>" alt="" style="max-width:220px;border-radius:10px" loading="lazy"><br><label><input type="checkbox" name="remove_thumb" value="1"> remove</label></p><?php endif; ?>
      <input type="file" name="thumb" accept=".jpg,.jpeg,.png,.webp,.gif"></div>
    <div class="field"><label class="lbl">Sort / Featured</label>
      <input class="in" type="number" name="sort_order" value="<?= (int)$proj['sort_order'] ?>" style="margin-bottom:8px">
      <label><input type="checkbox" name="featured" value="1" <?= !empty($proj['featured']) ? 'checked' : '' ?>> ⭐ Featured (sorts first)</label></div>
  </div>
  <div class="grid cols-2">
    <div class="field"><label class="lbl">Code language</label><input class="in" name="code_lang" value="<?= e($proj['code_lang']) ?>" placeholder="php"></div>
  </div>
  <div class="field"><label class="lbl">Featured code snippet <span class="muted">(shown on the project page)</span></label>
    <textarea class="in" name="code_snippet" rows="8" style="font-family:monospace"><?= e($proj['code_snippet']) ?></textarea></div>
  <button class="btn btn-primary">Save &amp; manage files →</button>
  <a href="projects.php" style="margin-left:10px">Cancel</a>
</div>
</form>
<?php admin_foot(); ?>
