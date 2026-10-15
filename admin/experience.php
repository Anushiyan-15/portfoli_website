<?php
// admin/experience.php — CRUD for experience + education timeline.
require_once __DIR__ . '/_layout.php';
$edit = null;
if (isset($_GET['edit'])) {
  $id = (int)$_GET['edit'];
  $st = $conn->prepare("SELECT * FROM experience WHERE id = ?");
  $st->bind_param('i', $id); $st->execute();
  $edit = $st->get_result()->fetch_assoc(); $st->close();
}
if (isset($_GET['delete'])) {
  if (!csrf_check($_GET['csrf'] ?? '')) flash('Bad token.', 'err');
  else { $id=(int)$_GET['delete']; $st=$conn->prepare("DELETE FROM experience WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $st->close(); flash('Entry deleted.'); }
  header('Location: experience.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) flash('Bad token.', 'err');
  else {
    $id=(int)($_POST['id']??0);
    $type = ($_POST['type'] ?? 'experience') === 'education' ? 'education' : 'experience';
    $title=trim($_POST['title']??''); $org=trim($_POST['organization']??''); $loc=trim($_POST['location']??'');
    $s=trim($_POST['start_label']??''); $en=trim($_POST['end_label']??'Present') ?: 'Present';
    $desc=trim($_POST['description']??''); $ord=(int)($_POST['sort_order']??0);
    if ($title===''||$org==='') flash('Title + organization required.','err');
    elseif ($id) { $st=$conn->prepare("UPDATE experience SET type=?,title=?,organization=?,location=?,start_label=?,end_label=?,description=?,sort_order=? WHERE id=?"); $st->bind_param('sssssssii',$type,$title,$org,$loc,$s,$en,$desc,$ord,$id); $st->execute(); $st->close(); flash('Saved.'); }
    else { $st=$conn->prepare("INSERT INTO experience (type,title,organization,location,start_label,end_label,description,sort_order) VALUES (?,?,?,?,?,?,?,?)"); $st->bind_param('sssssssi',$type,$title,$org,$loc,$s,$en,$desc,$ord); $st->execute(); $st->close(); flash('Added.'); }
  }
  header('Location: experience.php'); exit;
}
$rows=[]; $r=$conn->query("SELECT * FROM experience ORDER BY type, sort_order, id");
while($x=$r->fetch_assoc()) $rows[]=$x;
admin_head('Experience');
?>
<h1 style="margin-top:0">📅 Experience &amp; Education</h1>
<div class="grid cols-2">
<div class="card">
<h3 style="margin-top:0"><?= $edit?'Edit entry':'Add entry' ?></h3>
<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="grid cols-2">
<div class="field"><label class="lbl">Type</label><select class="in" name="type">
<option value="experience" <?= ($edit['type']??'experience')==='experience'?'selected':'' ?>>experience</option>
<option value="education" <?= ($edit['type']??'')==='education'?'selected':'' ?>>education</option></select></div>
<div class="field"><label class="lbl">Order</label><input class="in" type="number" name="sort_order" value="<?= (int)($edit['sort_order']??0) ?>"></div>
</div>
<div class="field"><label class="lbl">Title *</label><input class="in" name="title" required value="<?= e($edit['title']??'') ?>" placeholder="Senior Developer / B.S. Computer Science"></div>
<div class="field"><label class="lbl">Organization *</label><input class="in" name="organization" required value="<?= e($edit['organization']??'') ?>"></div>
<div class="grid cols-2">
<div class="field"><label class="lbl">Location</label><input class="in" name="location" value="<?= e($edit['location']??'') ?>"></div>
<div class="field"><label class="lbl">Start</label><input class="in" name="start_label" value="<?= e($edit['start_label']??'') ?>" placeholder="2021"></div>
</div>
<div class="field"><label class="lbl">End</label><input class="in" name="end_label" value="<?= e($edit['end_label']??'Present') ?>" placeholder="Present"></div>
<div class="field"><label class="lbl">Description</label><textarea class="in" name="description" rows="3"><?= e($edit['description']??'') ?></textarea></div>
<button class="btn btn-primary btn-sm"><?= $edit?'Save':'Add' ?></button>
<?php if($edit): ?><a href="experience.php" style="margin-left:8px">Cancel</a><?php endif; ?>
</form></div>
<div class="card"><h3 style="margin-top:0">Timeline</h3><table class="table"><tbody>
<?php foreach($rows as $x): ?><tr><td><span class="tag"><?= e($x['type']) ?></span> <strong><?= e($x['title']) ?></strong><br><span class="muted"><?= e($x['organization']) ?> · <?= e($x['start_label']) ?>–<?= e($x['end_label']) ?></span></td>
<td style="white-space:nowrap"><a href="experience.php?edit=<?= (int)$x['id'] ?>">Edit</a> · <a href="experience.php?delete=<?= (int)$x['id'] ?>&csrf=<?= e(csrf_token()) ?>" onclick="return confirm('Delete?')">Del</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td class="muted">None yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php admin_foot(); ?>
