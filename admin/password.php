<?php
// admin/password.php — change admin password.
require_once __DIR__ . '/_layout.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) flash('Bad token.', 'err');
  else {
    $cur=(string)($_POST['current']??''); $nw=(string)($_POST['new']??''); $cf=(string)($_POST['confirm']??'');
    $st=$conn->prepare("SELECT password_hash FROM users WHERE id=?"); $st->bind_param('i',$_SESSION['admin_id']); $st->execute();
    $row=$st->get_result()->fetch_assoc(); $st->close();
    if (!$row || !password_verify($cur,$row['password_hash'])) flash('Current password is wrong.','err');
    elseif (strlen($nw)<8) flash('New password must be at least 8 characters.','err');
    elseif ($nw!==$cf) flash('New passwords do not match.','err');
    else{
      $h=password_hash($nw,PASSWORD_DEFAULT);
      $st=$conn->prepare("UPDATE users SET password_hash=? WHERE id=?"); $st->bind_param('si',$h,$_SESSION['admin_id']); $st->execute(); $st->close();
      flash('Password changed.');
    }
  }
  header('Location: password.php'); exit;
}
admin_head('Password');
?>
<h1 style="margin-top:0">🔑 Change password</h1>
<div class="card" style="max-width:480px">
<form method="post">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<div class="field"><label class="lbl">Current password</label><input class="in" type="password" name="current" required autocomplete="current-password"></div>
<div class="field"><label class="lbl">New password (≥8 chars)</label><input class="in" type="password" name="new" required autocomplete="new-password"></div>
<div class="field"><label class="lbl">Confirm new</label><input class="in" type="password" name="confirm" required autocomplete="new-password"></div>
<button class="btn btn-primary btn-sm">Update</button>
</form></div>
<?php admin_foot(); ?>
