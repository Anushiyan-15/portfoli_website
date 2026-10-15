<?php
// admin/skills.php — CRUD grouped Frontend / Backend / Tools.
require_once __DIR__ . '/_layout.php';
$edit = null;
if (isset($_GET['edit'])) {
  $id = (int)$_GET['edit'];
  $st = $conn->prepare("SELECT * FROM skills WHERE id = ?");
  $st->bind_param('i', $id); $st->execute();
  $edit = $st->get_result()->fetch_assoc(); $st->close();
}
if (isset($_GET['delete'])) {
  if (!csrf_check($_GET['csrf'] ?? '')) flash('Bad token.', 'err');
  else { $id = (int)$_GET['delete']; $st = $conn->prepare("DELETE FROM skills WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $st->close(); flash('Skill deleted.'); }
  header('Location: skills.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) flash('Bad token.', 'err');
  else {
    $id = (int)($_POST['id'] ?? 0);
    $g = $_POST['skill_group'] ?? 'Frontend';
    if (!in_array($g, ['Frontend','Backend','Tools'], true)) $g = 'Frontend';
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-code-slash') ?: 'bi-code-slash';
    $level = max(0, min(100, (int)($_POST['level'] ?? 80)));
    $ord = (int)($_POST['sort_order'] ?? 0);
    if ($name === '') flash('Name required.', 'err');
    elseif ($id) { $st=$conn->prepare("UPDATE skills SET skill_group=?,name=?,icon=?,level=?,sort_order=? WHERE id=?"); $st->bind_param('sssiii',$g,$name,$icon,$level,$ord,$id); $st->execute(); $st->close(); flash('Skill updated.'); }
    else { $st=$conn->prepare("INSERT INTO skills (skill_group,name,icon,level,sort_order) VALUES (?,?,?,?,?)"); $st->bind_param('sssii',$g,$name,$icon,$level,$ord); $st->execute(); $st->close(); flash('Skill added.'); }
  }
  header('Location: skills.php'); exit;
}
$rows = [];
$r = $conn->query("SELECT * FROM skills ORDER BY skill_group, sort_order, id");
while ($x = $r->fetch_assoc()) $rows[] = $x;
admin_head('Skills');
?>
<h1 style="margin-top:0">🛠 Skills</h1>
<div class="grid cols-2">
<div class="card">
  <h3 style="margin-top:0"><?= $edit ? 'Edit skill' : 'Add skill' ?></h3>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
    <div class="field"><label class="lbl">Group</label>
      <select class="in" name="skill_group">
        <?php foreach (['Frontend','Backend','Tools'] as $g): ?>
        <option <?= ($edit['skill_group'] ?? 'Frontend') === $g ? 'selected' : '' ?>><?= $g ?></option>
        <?php endforeach; ?>
      </select></div>
    <div class="field"><label class="lbl">Name *</label><input class="in" name="name" required value="<?= e($edit['name'] ?? '') ?>"></div>
    <div class="grid cols-2">
      <div class="field"><label class="lbl">Icon <span class="muted">(bootstrap-icon class)</span></label><input class="in" name="icon" value="<?= e($edit['icon'] ?? 'bi-code-slash') ?>"></div>
      <div class="field"><label class="lbl">Level %</label><input class="in" type="number" min="0" max="100" name="level" value="<?= (int)($edit['level'] ?? 80) ?>"></div>
    </div>
    <div class="field"><label class="lbl">Order</label><input class="in" type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></div>
    <button class="btn btn-primary btn-sm"><?= $edit ? 'Save' : 'Add' ?></button>
    <?php if ($edit): ?><a href="skills.php" style="margin-left:8px">Cancel</a><?php endif; ?>
  </form>
</div>
<div class="card">
  <h3 style="margin-top:0">Current</h3>
  <table class="table"><tbody>
  <?php $last=''; foreach ($rows as $s): ?>
    <?php if ($s['skill_group'] !== $last) { $last=$s['skill_group']; echo '<tr><td colspan="3"><strong>'.e($last).'</strong></td></tr>'; } ?>
    <tr><td><?= e($s['name']) ?> <span class="muted"><?= (int)$s['level'] ?>%</span></td>
    <td><a href="skills.php?edit=<?= (int)$s['id'] ?>">Edit</a> ·
        <a href="skills.php?delete=<?= (int)$s['id'] ?>&csrf=<?= e(csrf_token()) ?>" onclick="return confirm('Delete?')">Del</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td class="muted">None yet.</td></tr><?php endif; ?>
  </tbody></table>
</div>
</div>
<?php admin_foot(); ?>
