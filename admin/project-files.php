<?php
// admin/project-files.php — upload / list / delete source files for one project.
require_once __DIR__ . '/_layout.php';
$id = (int)($_GET['id'] ?? 0);
$st = $conn->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
$st->bind_param('i', $id); $st->execute();
$proj = $st->get_result()->fetch_assoc(); $st->close();
if (!$proj) { flash('Project not found.', 'err'); header('Location: projects.php'); exit; }

// Delete a file.
if (isset($_GET['del'])) {
  if (!csrf_check($_GET['csrf'] ?? '')) flash('Bad token.', 'err');
  else {
    $fid = (int)$_GET['del'];
    $st = $conn->prepare("SELECT stored_path FROM project_files WHERE id = ? AND project_id = ?");
    $st->bind_param('ii', $fid, $id); $st->execute();
    $row = $st->get_result()->fetch_assoc(); $st->close();
    if ($row) {
      $st = $conn->prepare("DELETE FROM project_files WHERE id = ?");
      $st->bind_param('i', $fid); $st->execute(); $st->close();
      delete_upload($row['stored_path']);
      flash('File deleted.');
    }
  }
  header('Location: project-files.php?id=' . $id); exit;
}

// Upload (multiple).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) flash('Bad token.', 'err');
  else {
    $ok = 0; $errs = [];
    $dir = upload_dir('projects/' . $id);
    $n = count($_FILES['files']['name'] ?? []);
    for ($i = 0; $i < $n; $i++) {
      if (($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
      if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) { $errs[] = 'Upload error on file #' . ($i + 1); continue; }
      $orig = basename((string)$_FILES['files']['name'][$i]);
      $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
      $size = (int)$_FILES['files']['size'][$i];
      if (!in_array($ext, ALLOWED_PROJECT_FILE_EXT, true)) { $errs[] = "$orig: .$ext not allowed."; continue; }
      if ($size > MAX_PROJECT_FILE_MB * 1024 * 1024) { $errs[] = "$orig: too large."; continue; }
      $stored = 'uploads/projects/' . $id . '/' . bin2hex(random_bytes(8)) . '.' . $ext;
      $tmp = $_FILES['files']['tmp_name'][$i];
      // Light malware hygiene: never allow php uploads to be web-executable —
      // uploads/.htaccess already denies *.php; we also prefix php content check.
      if (!@move_uploaded_file($tmp, __DIR__ . '/../' . $stored)) { $errs[] = "$orig: could not store."; continue; }
      $mime = @mime_content_type(__DIR__ . '/../' . $stored) ?: 'application/octet-stream';
      $st = $conn->prepare("INSERT INTO project_files (project_id, original_name, stored_path, mime_type, file_size) VALUES (?,?,?,?,?)");
      $st->bind_param('isssi', $id, $orig, $stored, $mime, $size);
      $st->execute(); $st->close();
      $ok++;
    }
    flash($ok ? "Uploaded $ok file(s)." . ($errs ? ' Issues: ' . implode(' ', $errs) : '') : ('Nothing uploaded. ' . implode(' ', $errs)), $ok ? 'ok' : 'err');
  }
  header('Location: project-files.php?id=' . $id); exit;
}

$files = [];
$st = $conn->prepare("SELECT * FROM project_files WHERE project_id = ? ORDER BY original_name");
$st->bind_param('i', $id); $st->execute();
$rr = $st->get_result(); while ($x = $rr->fetch_assoc()) $files[] = $x; $st->close();

admin_head('Project files');
?>
<h1 style="margin-top:0">📁 Files — <?= e($proj['title']) ?></h1>
<p><a href="project-form.php?id=<?= $id ?>">← Edit project</a> · <a href="../project.php?slug=<?= e($proj['slug']) ?>" target="_blank">View public page ↗</a></p>
<div class="card">
  <h3 style="margin-top:0">Upload source / assets</h3>
  <p class="muted">Allowed: <?= e(implode(', ', ALLOWED_PROJECT_FILE_EXT)) ?> · ≤ <?= MAX_PROJECT_FILE_MB ?> MB each. Text files (<?= e(implode(', ', PREVIEWABLE_EXT)) ?>) get an on-site <strong>View</strong> button.</p>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="file" name="files[]" multiple required>
    <button class="btn btn-primary btn-sm" style="margin-top:10px">Upload</button>
  </form>
</div>
<div class="card" style="margin-top:14px">
  <h3 style="margin-top:0">Attached files (<?= count($files) ?>)</h3>
  <?php if (!$files): ?><p class="muted">None yet.</p><?php endif; ?>
  <table class="table"><tbody>
  <?php foreach ($files as $f): ?>
    <tr><td>📄 <strong><?= e($f['original_name']) ?></strong><br><span class="muted"><?= e(human_size((int)$f['file_size'])) ?> · <?= e($f['mime_type']) ?></span></td>
    <td style="white-space:nowrap"><a href="../<?= e($f['stored_path']) ?>" download>Download</a> ·
      <a href="project-files.php?id=<?= $id ?>&del=<?= (int)$f['id'] ?>&csrf=<?= e(csrf_token()) ?>" onclick="return confirm('Delete file?')">Delete</a></td></tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php admin_foot(); ?>
