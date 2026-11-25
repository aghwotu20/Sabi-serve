<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_login();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'profile';
    if ($action === 'profile') {
        $full = trim($_POST['full_name'] ?? ''); $business = trim($_POST['business_name'] ?? '');
        $phone = trim($_POST['phone'] ?? ''); $location = trim($_POST['location'] ?? ''); $bio = trim($_POST['bio'] ?? '');
        if ($full === '') $errors[] = 'Name cannot be empty.';
        if (!$errors) {
            $passport = $u['passport'];
            try {
                $new = handle_upload('passport', ['image/jpeg', 'image/png', 'image/webp'], MAX_PASSPORT_BYTES, 'passport');
            } catch (RuntimeException $e) { $errors[] = $e->getMessage(); $new = null; }
            if ($new) {
                if ($passport && file_exists(UPLOAD_DIR . $passport)) @unlink(UPLOAD_DIR . $passport);
                $passport = $new;
            }
            if (!$errors) {
                db()->prepare('UPDATE users SET full_name = ?, business_name = ?, phone = ?, location = ?, bio = ?, passport = ? WHERE id = ?')
                    ->execute([mb_substr($full, 0, 150), $u['role'] === 'provider' ? mb_substr($business, 0, 150) : $u['business_name'], mb_substr($phone, 0, 30), mb_substr($location, 0, 120), $bio !== '' ? $bio : null, $passport, $u['id']]);
                flash('success', 'Profile updated successfully.');
                redirect('/account/profile.php');
            }
        }
    } elseif ($action === 'password') {
        $cur = $_POST['current_password'] ?? ''; $new = $_POST['new_password'] ?? ''; $new2 = $_POST['new_password_confirm'] ?? '';
        if (!password_verify($cur, $u['password_hash'])) $errors[] = 'Your current password is incorrect.';
        elseif (strlen($new) < 8) $errors[] = 'New password must be at least 8 characters.';
        elseif ($new !== $new2) $errors[] = 'New passwords do not match.';
        else {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $u['id']]);
            flash('success', 'Password changed. Use it next time you log in.');
            redirect('/account/profile.php');
        }
    }
}
$dashRole = $u['role'];
$dashTitle = 'My Profile'; $activeTab = 'profile';
require_once __DIR__ . '/../includes/dash_top.php';
// refresh user data
$st = db()->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([$u['id']]); $u = $st->fetch();
?>
<?php foreach ($errors as $e): ?><div class="flash flash-error" style="position:static;transform:none;margin-bottom:12px"><span><?= h($e) ?></span></div><?php endforeach; ?>

<div class="grid-2">
  <form class="panel" method="post" action="/account/profile.php" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="action" value="profile">
    <div class="panel-head"><h2>Profile details</h2><span class="badge-role badge-<?= h($u['role']) ?>"><?= h(ucfirst($u['role'])) ?></span></div>

    <div style="display:flex;gap:22px;align-items:center;margin-bottom:22px;flex-wrap:wrap">
      <?= avatar_html($u['passport'], $u['full_name'], 120) ?>
      <div class="form-group" style="flex:1;min-width:220px">
        <label class="fl" for="passport">Passport photograph</label>
        <input class="form-control" id="passport" name="passport" type="file" accept="image/jpeg,image/png,image/webp">
        <span class="hint">JPG, PNG or WEBP · max 2MB · square photos look best. Your photo appears on your dashboard and (for providers) your public profile.</span>
      </div>
    </div>

    <div class="form-grid">
      <div class="form-group"><label class="fl" for="full_name">Full name *</label><input class="form-control" id="full_name" name="full_name" required value="<?= h($u['full_name']) ?>"></div>
      <?php if ($u['role'] === 'provider'): ?><div class="form-group"><label class="fl" for="business_name">Business name</label><input class="form-control" id="business_name" name="business_name" value="<?= h($u['business_name']) ?>"></div><?php endif; ?>
      <div class="form-group"><label class="fl" for="email">Email <span class="hint">(cannot be changed)</span></label><input class="form-control" id="email" type="email" value="<?= h($u['email']) ?>" disabled></div>
      <div class="form-group"><label class="fl" for="phone">Phone / WhatsApp</label><input class="form-control" id="phone" name="phone" value="<?= h($u['phone']) ?>" placeholder="0803 000 0000"></div>
      <div class="form-group"><label class="fl" for="location">City / State</label><input class="form-control" id="location" name="location" value="<?= h($u['location']) ?>" placeholder="Lagos"></div>
      <div class="form-group full"><label class="fl" for="bio">Bio<?= $u['role'] === 'provider' ? ' — what makes you great' : '' ?></label><textarea class="form-control" id="bio" name="bio" maxlength="900"><?= h($u['bio']) ?></textarea></div>
    </div>
    <button class="btn btn-emerald" style="margin-top:16px" type="submit">Save changes</button>
  </form>

  <form class="panel" method="post" action="/account/profile.php">
    <?= csrf_field() ?><input type="hidden" name="action" value="password">
    <div class="panel-head"><h2>Change password</h2><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--green-800)" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
    <div class="form-group" style="margin-bottom:14px"><label class="fl" for="current_password">Current password</label><input class="form-control" id="current_password" name="current_password" type="password" required autocomplete="current-password"></div>
    <div class="form-group" style="margin-bottom:14px"><label class="fl" for="new_password">New password <span class="hint">(min 8 chars)</span></label><input class="form-control" id="new_password" name="new_password" type="password" required minlength="8" autocomplete="new-password"></div>
    <div class="form-group" style="margin-bottom:14px"><label class="fl" for="new_password_confirm">Confirm new password</label><input class="form-control" id="new_password_confirm" name="new_password_confirm" type="password" required autocomplete="new-password"></div>
    <button class="btn btn-outline" type="submit">Update password</button>
    <p class="hint" style="margin-top:14px">Tip: use a unique passphrase you don’t use anywhere else.</p>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
