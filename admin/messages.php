<?php
// admin/messages.php — inbox for contact_submissions.
require_once __DIR__ . '/_layout.php';
if (isset($_GET['read'])) {
  $id=(int)$_GET['read'];
  $st=$conn->prepare("UPDATE contact_submissions SET is_read=1 WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $st->close();
  header('Location: messages.php'); exit;
}
if (isset($_GET['delete'])) {
  if(!csrf_check($_GET['csrf']??'')) flash('Bad token.','err');
  else{ $id=(int)$_GET['delete']; $st=$conn->prepare("DELETE FROM contact_submissions WHERE id=?"); $st->bind_param('i',$id); $st->execute(); $st->close(); flash('Deleted.'); }
  header('Location: messages.php'); exit;
}
$rows=[]; $r=$conn->query("SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 200");
while($x=$r->fetch_assoc()) $rows[]=$x;
admin_head('Messages');
?>
<h1 style="margin-top:0">✉️ Messages</h1>
<div class="card" style="overflow:auto"><table class="table">
<thead><tr><th>Date</th><th>From</th><th>Message</th><th></th></tr></thead><tbody>
<?php foreach($rows as $m): ?>
<tr<?= $m['is_read']?'':' style="background:color-mix(in srgb,var(--accent) 8%,transparent)"' ?>>
<td style="white-space:nowrap"><?= e($m['created_at']) ?></td>
<td><strong><?= e($m['name']) ?></strong><br><a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
<?php if(!empty($m['subject'])): ?><br><span class="muted"><?= e($m['subject']) ?></span><?php endif; ?></td>
<td><?= nl2br(e(mb_strimwidth($m['message'],0,300,'…'))) ?></td>
<td style="white-space:nowrap">
<?php if(!$m['is_read']): ?><a href="messages.php?read=<?= (int)$m['id'] ?>">Mark read</a> · <?php endif; ?>
<a href="messages.php?delete=<?= (int)$m['id'] ?>&csrf=<?= e(csrf_token()) ?>" onclick="return confirm('Delete?')">Del</a></td></tr>
<?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="4" class="muted">Inbox empty.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_foot(); ?>
