<?php
// admin/testimonials.php — CRUD; hidden on front page when empty/invisible.
require_once __DIR__ . '/_layout.php';
$edit=null;
if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $st=$conn->prepare("SELECT * FROM testimonials WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $edit=$st->get_result()->fetch_assoc(); $st->close(); }
if(isset($_GET['delete'])){ if(!csrf_check($_GET['csrf']??'')) flash('Bad token.','err'); else{ $id=(int)$_GET['delete']; $st=$conn->prepare("SELECT avatar_path FROM testimonials WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $a=$st->get_result()->fetch_assoc(); $st->close(); $st=$conn->prepare("DELETE FROM testimonials WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $st->close(); if($a) delete_upload($a['avatar_path']??''); flash('Deleted.'); } header('Location: testimonials.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_check($_POST['csrf']??'')) flash('Bad token.','err');
  else{
    $id=(int)($_POST['id']??0);
    $cur=''; if($id){ $st=$conn->prepare("SELECT avatar_path FROM testimonials WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $c=$st->get_result()->fetch_assoc(); $st->close(); $cur=$c['avatar_path']??''; }
    try{
      if(!empty($_FILES['avatar']['name'])){ $n=save_upload('avatar','avatars',ALLOWED_IMAGE_EXT,MAX_IMAGE_MB); if($cur) delete_upload($cur); $cur=$n; }
      $author=trim($_POST['author']??''); $role=trim($_POST['role']??''); $quote=trim($_POST['quote']??'');
      $vis=!empty($_POST['is_visible'])?1:0; $ord=(int)($_POST['sort_order']??0);
      if($author===''||$quote==='') throw new RuntimeException('Author + quote required.');
      if($id){ $st=$conn->prepare("UPDATE testimonials SET author=?,role=?,quote=?,avatar_path=?,is_visible=?,sort_order=? WHERE id=?"); $st->bind_param('sssssii',$author,$role,$quote,$cur,$vis,$ord,$id); $st->execute(); $st->close(); }
      else{ $st=$conn->prepare("INSERT INTO testimonials (author,role,quote,avatar_path,is_visible,sort_order) VALUES (?,?,?,?,?,?)"); $st->bind_param('sssssi',$author,$role,$quote,$cur,$vis,$ord); $st->execute(); $st->close(); }
      flash('Saved.');
    }catch(Throwable $ex){ flash($ex->getMessage(),'err'); }
  }
  header('Location: testimonials.php'); exit;
}
$rows=[]; $r=$conn->query("SELECT * FROM testimonials ORDER BY sort_order,id"); while($x=$r->fetch_assoc()) $rows[]=$x;
admin_head('Testimonials');
?>
<h1 style="margin-top:0">💬 Testimonials</h1>
<p class="muted">Section is omitted from the public site when there are no visible entries.</p>
<div class="grid cols-2">
<div class="card"><h3 style="margin-top:0"><?= $edit?'Edit':'Add' ?></h3>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="field"><label class="lbl">Author *</label><input class="in" name="author" required value="<?= e($edit['author']??'') ?>"></div>
<div class="field"><label class="lbl">Role / company</label><input class="in" name="role" value="<?= e($edit['role']??'') ?>"></div>
<div class="field"><label class="lbl">Quote *</label><textarea class="in" name="quote" rows="3" required><?= e($edit['quote']??'') ?></textarea></div>
<div class="field"><label class="lbl">Avatar (optional)</label><input type="file" name="avatar" accept=".jpg,.jpeg,.png,.webp,.gif"></div>
<div class="grid cols-2"><div class="field"><label class="lbl">Order</label><input class="in" type="number" name="sort_order" value="<?= (int)($edit['sort_order']??0) ?>"></div>
<div class="field"><label class="lbl">Visible</label><br><label><input type="checkbox" name="is_visible" value="1" <?= ($edit['is_visible']??1)?'checked':'' ?>> Show publicly</label></div></div>
<button class="btn btn-primary btn-sm"><?= $edit?'Save':'Add' ?></button>
<?php if($edit): ?><a href="testimonials.php" style="margin-left:8px">Cancel</a><?php endif; ?>
</form></div>
<div class="card"><h3 style="margin-top:0">All</h3><table class="table"><tbody>
<?php foreach($rows as $t): ?><tr><td><strong><?= e($t['author']) ?></strong> <?= $t['is_visible']?'':'<span class="tag">hidden</span>' ?><br><span class="muted"><?= e(mb_strimwidth($t['quote'],0,90,'…')) ?></span></td>
<td style="white-space:nowrap"><a href="testimonials.php?edit=<?= (int)$t['id'] ?>">Edit</a> · <a href="testimonials.php?delete=<?= (int)$t['id'] ?>&csrf=<?= e(csrf_token()) ?>" onclick="return confirm('Delete?')">Del</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td class="muted">None yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php admin_foot(); ?>
