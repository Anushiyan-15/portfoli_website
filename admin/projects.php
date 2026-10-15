<?php
// admin/projects.php — list + delete.
require_once __DIR__ . '/_layout.php';
if (isset($_GET['delete'])) {
  if (!csrf_check($_GET['csrf'] ?? '')) { flash('Bad token.', 'err'); }
  else {
    $id = (int)$_GET['delete'];
    $st = $conn->prepare("SELECT thumbnail_path FROM projects WHERE id = ?");
    $st->bind_param('i', $id); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    // collect file paths before cascade delete
    $paths = [];
    $st = $conn->prepare("SELECT stored_path FROM project_files WHERE project_id = ?");
    $st->bind_param('i', $id); $st->execute();
    $rr = $st->get_result(); while ($x = $rr->fetch_assoc()) $paths[] = $x['stored_path']; $st->close();
    $st = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $st->bind_param('i', $id); $st->execute(); $st->close();
    if ($row) delete_upload($row['thumbnail_path'] ?? '');
    foreach ($paths as $p) delete_upload($p);
    flash('Project deleted.');
  }
  header('Location: projects.php'); exit;
}
$rows = [];
$r = $conn->query("SELECT p.*, (SELECT COUNT(*) FROM project_files f WHERE f.project_id = p.id) AS nfiles FROM projects p ORDER BY sort_order, created_at DESC");
while ($row = $r->fetch_assoc()) $rows[] = $row;
admin_head('Projects');
?>
<h1 style="margin-top:0">🧩 Projects</h1>
<p><a class="btn btn-primary btn-sm" href="project-form.php">+ New project</a></p>
<div class="card" style="overflow:auto">
<table class="table"><thead><tr><th>Title</th><th>Category</th><th>Links</th><th>Files</th><th></th></tr></thead>
<tbody>
<?php foreach ($rows as $p): ?>
<tr>
  <td><strong><?= e($p['title']) ?></strong><br><span class="muted"><?= e($p['slug']) ?><?= $p['featured'] ? ' · ⭐ featured' : '' ?></span></td>
  <td><?= e($p['category']) ?><br><span class="muted"><?= e($p['tags']) ?></span></td>
  <td style="font-size:.82rem"><?= $p['live_url'] ? 'live ✓<br>' : '' ?><?= $p['github_url'] ? 'gh ✓' : '' ?></td>
  <td><?= (int)$p['nfiles'] ?></td>
  <td style="white-space:nowrap">
    <a href="../project.php?slug=<?= e($p['slug']) ?>" target="_blank">View</a> ·
    <a href="project-form.php?id=<?= (int)$p['id'] ?>">Edit</a> ·
    <a href="project-files.php?id=<?= (int)$p['id'] ?>">Files</a> ·
    <a href="projects.php?delete=<?= (int)$p['id'] ?>&csrf=<?= e(csrf_token()) ?>" onclick="return confirm('Delete this project?')">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="5" class="muted">No projects yet.</td></tr><?php endif; ?>
</tbody></table>
</div>
<?php admin_foot(); ?>
