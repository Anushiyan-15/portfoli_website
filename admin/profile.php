<?php
// admin/profile.php — edit bio/details + messaging-app-style photo + resume.
require_once __DIR__ . '/_layout.php';
$profile = get_profile($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!csrf_check($_POST['csrf'] ?? '')) { flash('Security token expired — try again.', 'err'); header('Location: profile.php'); exit; }
  try {
    // Photo upload (camera-badge UX; instant preview via JS).
    if (!empty($_FILES['photo']['name'])) {
      $new = save_upload('photo', 'profile', ALLOWED_IMAGE_EXT, MAX_IMAGE_MB);
      delete_upload($profile['photo_path'] ?? '');
      $profile['photo_path'] = $new;
    }
    if (!empty($_POST['remove_photo'])) { delete_upload($profile['photo_path'] ?? ''); $profile['photo_path'] = ''; }
    if (!empty($_FILES['resume']['name'])) {
      $new = save_upload('resume', 'resumes', ALLOWED_RESUME_EXT, MAX_RESUME_MB);
      delete_upload($profile['resume_path'] ?? '');
      $profile['resume_path'] = $new;
    }
    if (!empty($_POST['remove_resume'])) { delete_upload($profile['resume_path'] ?? ''); $profile['resume_path'] = ''; }

    $fields = ['name','role','tagline','bio','email','phone','location','availability','linkedin','github','twitter','website','tone'];
    $data = [];
    foreach ($fields as $f) $data[$f] = trim((string)($_POST[$f] ?? ''));
    if ($data['name'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
      throw new RuntimeException('Name and a valid email are required.');
    }
    $st = $conn->prepare("UPDATE profile SET name=?, role=?, tagline=?, bio=?, photo_path=?, resume_path=?, email=?, phone=?, location=?, availability=?, linkedin=?, github=?, twitter=?, website=?, tone=? WHERE id=1");
    $st->bind_param('sssssssssssssss', $data['name'], $data['role'], $data['tagline'], $data['bio'], $profile['photo_path'], $profile['resume_path'], $data['email'], $data['phone'], $data['location'], $data['availability'], $data['linkedin'], $data['github'], $data['twitter'], $data['website'], $data['tone']);
    $st->execute(); $st->close();
    flash('Profile saved.');
  } catch (Throwable $ex) {
    flash($ex->getMessage(), 'err');
  }
  header('Location: profile.php'); exit;
}

admin_head('Profile & Photo');
$avatar = !empty($profile['photo_path']) && is_file(__DIR__ . '/../' . $profile['photo_path']) ? '../' . $profile['photo_path'] : '';
?>
<h1 style="margin-top:0">👤 Profile &amp; Photo</h1>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
<div class="card" style="text-align:center;margin-bottom:14px">
  <!-- Familiar messaging-app pattern: tap the camera badge to change photo -->
  <div class="avatar-edit">
    <?php if ($avatar): ?>
      <img src="<?= e($avatar) ?>" alt="Current profile photo preview" data-avatar-preview>
    <?php else: ?>
      <img src="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='80'>👤</text></svg>" alt="No profile photo yet" data-avatar-preview>
    <?php endif; ?>
    <label class="cam" title="Change photo" aria-label="Change profile photo">📷
      <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp,.gif" data-avatar-input hidden>
    </label>
  </div>
  <p class="muted">Tap the 📷 badge to pick a new photo (JPG/PNG/WebP ≤ <?= MAX_IMAGE_MB ?> MB). Preview updates instantly; press <strong>Save</strong> to keep it.</p>
  <label style="font-size:.88rem"><input type="checkbox" name="remove_photo" value="1"> Remove current photo</label>
</div>
<div class="card">
  <div class="grid cols-2">
    <div class="field"><label class="lbl">Name *</label><input class="in" name="name" value="<?= e($profile['name']) ?>" required maxlength="120"></div>
    <div class="field"><label class="lbl">Role * <span class="muted">(e.g. Full-stack developer)</span></label><input class="in" name="role" value="<?= e($profile['role']) ?>" maxlength="160"></div>
  </div>
  <div class="field"><label class="lbl">Tagline</label><input class="in" name="tagline" value="<?= e($profile['tagline']) ?>" maxlength="255"></div>
  <div class="field"><label class="lbl">Bio</label><textarea class="in" name="bio" rows="4"><?= e($profile['bio']) ?></textarea></div>
  <div class="grid cols-2">
    <div class="field"><label class="lbl">Email *</label><input class="in" type="email" name="email" value="<?= e($profile['email']) ?>" required></div>
    <div class="field"><label class="lbl">Phone</label><input class="in" name="phone" value="<?= e($profile['phone']) ?>"></div>
    <div class="field"><label class="lbl">Location</label><input class="in" name="location" value="<?= e($profile['location']) ?>"></div>
    <div class="field"><label class="lbl">Availability</label><input class="in" name="availability" value="<?= e($profile['availability']) ?>" placeholder="Available for freelance"></div>
    <div class="field"><label class="lbl">LinkedIn URL</label><input class="in" name="linkedin" value="<?= e($profile['linkedin']) ?>" placeholder="https://…"></div>
    <div class="field"><label class="lbl">GitHub URL</label><input class="in" name="github" value="<?= e($profile['github']) ?>" placeholder="https://…"></div>
    <div class="field"><label class="lbl">Twitter/X URL</label><input class="in" name="twitter" value="<?= e($profile['twitter']) ?>"></div>
    <div class="field"><label class="lbl">Website</label><input class="in" name="website" value="<?= e($profile['website']) ?>"></div>
  </div>
  <div class="field"><label class="lbl">Tone <span class="muted">(professional / creative / minimal — placeholder)</span></label><input class="in" name="tone" value="<?= e($profile['tone'] ?? '') ?>"></div>
  <div class="field"><label class="lbl">Resume/CV (PDF/DOC ≤ <?= MAX_RESUME_MB ?> MB)</label>
    <?php if (!empty($profile['resume_path'])): ?><p>Current: <a href="../<?= e($profile['resume_path']) ?>" target="_blank">view</a> <label><input type="checkbox" name="remove_resume" value="1"> remove</label></p><?php endif; ?>
    <input type="file" name="resume" accept=".pdf,.doc,.docx"></div>
  <button class="btn btn-primary">Save profile</button>
</div>
</form>
<?php admin_foot(); ?>
