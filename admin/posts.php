<?php
// admin/posts.php — minimal blog CRUD (published flag; hidden when empty).
require_once __DIR__ . '/_layout.php';
$edit=null;
if(isset($_GET['edit'])){ $id=(int)$_GET['edit']; $st=$conn->prepare("SELECT * FROM posts WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $edit=$st->get_result()->fetch_assoc(); $st->close(); }
if(isset($_GET['delete'])){ if(!csrf_check($_GET['csrf']??'')) flash('Bad token.','err'); else{ $id=(int)$_GET['delete']; $st=$conn->prepare("SELECT cover_path FROM posts WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $a=$st->get_result()->fetch_assoc(); $st->close(); $st=$conn->prepare("DELETE FROM posts WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $st->close(); if($a) delete_upload($a['cover_path']??''); flash('Deleted.'); } header('Location: posts.php'); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(!csrf_check($_POST['csrf']??'')) flash('Bad token.','err');
  else{
    $id=(int)($_POST['id']??0); $cur='';
    if($id){ $st=$conn->prepare("SELECT cover_path FROM posts WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $c=$st->get_result()->fetch_assoc(); $st->close(); $cur=$c['cover_path']??''; }
    try{
      if(!empty($_FILES['cover']['name'])){ $n=save_upload('cover','blog',ALLOWED_IMAGE_EXT,MAX_IMAGE_MB); if($cur) delete_upload($cur); $cur=$n; }
      $title=trim($_POST['title']??''); $slug=trim($_POST['slug']??''); if($slug==='') $slug=slugify($title); $slug=slugify($slug);
      $ex=trim($_POST['excerpt']??''); $body=(string)($_POST['body']??''); $pub=!empty($_POST['is_published'])?1:0;
      if($title===''||$body==='') throw new RuntimeException('Title + body required.');
      if($id){
        $st=$conn->prepare("SELECT id FROM posts WHERE slug=? AND id<>? LIMIT 1"); $st->bind_param('si',$slug,$id); $st->execute();
        if($st->get_result()->fetch_assoc()) $slug.='-'.$id; $st->close();
        $st=$conn->prepare("UPDATE posts SET title=?,slug=?,excerpt=?,body=?,cover_path=?,is_published=? WHERE id=?"); $st->bind_param('sssssii',$title,$slug,$ex,$body,$cur,$pub,$id); $st->execute(); $st->close();
      }else{
        $st=$conn->prepare("SELECT id FROM posts WHERE slug=? LIMIT 1"); $st->bind_param('s',$slug); $st->execute();
        if($st->get_result()->fetch_assoc()) $slug.='-'.time(); $st->close();
        $st=$conn->prepare("INSERT INTO posts (title,slug,excerpt,body,cover_path,is_published) VALUES (?,?,?,?,?,?)"); $st->bind_param('sssssi',$title,$slug,$ex,$body,$cur,$pub); $st->execute(); $st->close();
      }
      flash('Saved.');
    }catch(Throwable $e){ flash($e->getMessage(),'err'); }
  }
  header('Location: posts.php'); exit;
}
$rows=[]; $r=$conn->query("SELECT id,title,slug,is_published,created_at FROM posts ORDER BY created_at DESC"); while($x=$r->fetch_assoc()) $rows[]=$x;
admin_head('Blog posts');
?>
<h1 style="margin-top:0">✍️ Blog Posts</h1>
<p class="muted">Blog section is hidden publicly until at least one post is published.</p>
<div class="grid cols-2">
<div class="card"><h3 style="margin-top:0"><?= $edit?'Edit post':'New post' ?></h3>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)($edit['id']??0) ?>">
<div class="field"><label class="lbl">Title *</label><input class="in" name="title" required value="<?= e($edit['title']??'') ?>"></div>
<div class="field"><label class="lbl">Slug (auto if empty)</label><input class="in" name="slug" value="<?= e($edit['slug']??'') ?>"></div>
<div class="field"><label class="lbl">Excerpt</label><input class="in" name="excerpt" maxlength="300" value="<?= e($edit['excerpt']??'') ?>"></div>
<div class="field"><label class="lbl">Body *</label><textarea class="in" name="body" rows="8"><?= e($edit['body']??'') ?></textarea></div>
<div class="field"><label class="lbl">Cover (optional)</label><input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp,.gif"></div>
<div class="field"><label><input type="checkbox" name="is_published" value="1" <?= ($edit['is_published']??1)?'checked':'' ?>> Published</label></div>
<button class="btn btn-primary btn-sm"><?= $edit?'Save':'Publish' ?></button>
<?php if($edit): ?><a href="posts.php" style="margin-left:8px">Cancel</a><?php endif; ?>
</form></div>
<div class="card"><h3 style="margin-top:0">All posts</h3><table class="table"><tbody>
<?php foreach($rows as $p): ?><tr><td><strong><?= e($p['title']) ?></strong> <?= $p['is_published']?'':'<span class="tag">draft</span>' ?><br><span class="muted"><?= e($p['created_at']) ?></span></td>
<td style="white-space:nowrap"><a href="posts.php?edit=<?= (int)$p['id'] ?>">Edit</a> · <a href="posts.php?delete=<?= (int)$p['id'] ?>&csrf=<?= e(csrf_token()) ?>" onclick="return confirm('Delete?')">Del</a></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td class="muted">None yet.</td></tr><?php endif; ?>
</tbody></table></div></div>
<?php admin_foot(); ?>
