<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Join SabiServe — Create Your Account';
$errors = [];
$role = $_GET['role'] ?? ($_POST['role'] ?? 'user');
$role = in_array($role, ['user', 'provider'], true) ? $role : 'user';
if (is_logged_in()) redirect(dashboard_url(current_user()['role']));
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $role = in_array($_POST['role'] ?? '', ['user', 'provider'], true) ? $_POST['role'] : 'user';
    $full = trim($_POST['full_name'] ?? ''); $business = trim($_POST['business_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? '')); $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? ''); $categoryId = (int)($_POST['category_id'] ?? 0);
    $bio = trim($_POST['bio'] ?? '');
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password_confirm'] ?? '';

    if ($full === '') $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($phone === '') $errors[] = 'Please enter a phone number (WhatsApp preferred).';
    if (strlen($pw) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pw !== $pw2) $errors[] = 'Passwords do not match.';
    if ($role === 'provider' && $categoryId <= 0) $errors[] = 'Please choose your primary service category.';
    if (!$errors) {
        $exists = db()->prepare('SELECT id FROM users WHERE email = ?'); $exists->execute([$email]);
        if ($exists->fetchColumn()) $errors[] = 'An account with that email already exists. Try logging in instead.';
    }
    // optional passport upload at signup
    $passport = null;
    if (!$errors) { try { $passport = handle_upload('passport', ['image/jpeg', 'image/png', 'image/webp'], MAX_PASSPORT_BYTES, 'passport'); } catch (RuntimeException $e) { $errors[] = $e->getMessage(); } }
    if (!$errors) {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        $st = db()->prepare('INSERT INTO users (role, full_name, business_name, email, phone, password_hash, passport, location, bio, category_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'active\')');
        $st->execute([$role, mb_substr($full, 0, 150), $role === 'provider' ? mb_substr($business, 0, 150) : null, $email, mb_substr($phone, 0, 30), $hash, $passport, mb_substr($location, 0, 120), $bio !== '' ? $bio : null, $role === 'provider' ? $categoryId : null]);
        $newId = (int)db()->lastInsertId();
        $st = db()->prepare('SELECT * FROM users WHERE id = ?'); $st->execute([$newId]); $u = $st->fetch();
        login_user($u);
        flash('success', 'Welcome to SabiServe, ' . $full . '! One last step — activate your ₦2,000/month subscription.');
        redirect('/subscribe.php');
    }
}
require_once __DIR__ . '/includes/public_header.php';
?>
<div class="auth-wrap">
  <aside class="auth-art" aria-hidden="true">
    <img src="/assets/img/logo-mark.svg" alt="" width="64" height="64" style="border-radius:16px">
    <h2>Join Nigeria’s boldest services marketplace</h2>
    <ul>
      <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Customers: trusted pros at your fingertips, one ₦2,000/month subscription.</span></li>
      <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Providers: showcase your work, get discovered, grow your income.</span></li>
      <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Verified badges, real reviews, WhatsApp-first support.</span></li>
    </ul>
    <p style="color:#FDE68A;font-size:.85rem;margin-top:8px">Registration takes less than 2 minutes.</p>
  </aside>
  <div class="auth-form-side">
    <div class="auth-box" style="width:min(540px,100%)">
      <h1>Create your account</h1>
      <p class="sub">I am joining as…</p>
      <?php foreach ($errors as $e): ?><div class="flash flash-error" style="position:static;transform:none;margin-bottom:12px"><span><?= h($e) ?></span></div><?php endforeach; ?>
      <div class="role-tabs" data-role-group role="tablist" aria-label="Account type">
        <button type="button" class="role-tab" data-role="user" aria-pressed="<?= $role === 'user' ? 'true' : 'false' ?>"><strong>Customer</strong><small>I need services</small></button>
        <button type="button" class="role-tab" data-role="provider" aria-pressed="<?= $role === 'provider' ? 'true' : 'false' ?>"><strong>Provider</strong><small>I offer services</small></button>
        <input type="hidden" name="role" value="<?= h($role) ?>">
      </div>
      <form method="post" action="<?= base_url('register.php') ?>" enctype="multipart/form-data" id="regForm">
        <?= csrf_field() ?>
        <input type="hidden" name="role" id="roleField" value="<?= h($role) ?>">
        <div class="form-grid">
          <div class="form-group"><label class="fl" for="full_name">Full name *</label><input class="form-control" id="full_name" name="full_name" required value="<?= h($_POST['full_name'] ?? '') ?>" placeholder="Chinedu Okafor"></div>
          <div class="form-group" id="businessWrap" <?= $role === 'provider' ? '' : 'style="display:none"' ?>><label class="fl" for="business_name">Business name</label><input class="form-control" id="business_name" name="business_name" value="<?= h($_POST['business_name'] ?? '') ?>" placeholder="Chinedu Plumbing Services"></div>
          <div class="form-group"><label class="fl" for="email">Email *</label><input class="form-control" id="email" name="email" type="email" required value="<?= h($_POST['email'] ?? '') ?>" placeholder="you@example.com"></div>
          <div class="form-group"><label class="fl" for="phone">Phone / WhatsApp *</label><input class="form-control" id="phone" name="phone" required value="<?= h($_POST['phone'] ?? '') ?>" placeholder="0803 000 0000"></div>
          <div class="form-group"><label class="fl" for="location">City / State</label><input class="form-control" id="location" name="location" value="<?= h($_POST['location'] ?? '') ?>" placeholder="Lagos"></div>
          <div class="form-group" id="categoryWrap" <?= $role === 'provider' ? '' : 'style="display:none"' ?>><label class="fl" for="category_id">Primary service *</label><select class="form-control" id="category_id" name="category_id"><option value="">Select category…</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($_POST['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
          <div class="form-group full" id="bioWrap" <?= $role === 'provider' ? '' : 'style="display:none"' ?>><label class="fl" for="bio">Short bio — what makes you great?</label><textarea class="form-control" id="bio" name="bio" maxlength="800" placeholder="Certified electrician with 6 years fixing homes and offices across Lagos…"><?= h($_POST['bio'] ?? '') ?></textarea></div>
          <div class="form-group"><label class="fl" for="password">Password * <span class="hint">(min 8 chars)</span></label><input class="form-control" id="password" name="password" type="password" required minlength="8" placeholder="Create a strong password" autocomplete="new-password"></div>
          <div class="form-group"><label class="fl" for="password_confirm">Confirm password *</label><input class="form-control" id="password_confirm" name="password_confirm" type="password" required placeholder="Repeat it" autocomplete="new-password"></div>
          <div class="form-group full"><label class="fl" for="passport">Passport photograph <span class="hint">(JPG/PNG/WEBP, max 2MB — optional now, easy in your dashboard)</span></label><input class="form-control" id="passport" name="passport" type="file" accept="image/jpeg,image/png,image/webp"></div>
        </div>
        <button class="btn btn-amber btn-block" style="margin-top:16px;padding:14px;font-size:1rem" type="submit">Create Account &amp; Continue <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        <p class="hint" style="margin-top:12px;text-align:center">By registering you agree to our <a href="<?= base_url('terms.php') ?>">Terms</a> &amp; <a href="<?= base_url('privacy.php') ?>">Privacy Policy</a>.</p>
      </form>
      <p style="text-align:center;margin-top:14px;font-size:.92rem">Already have an account? <a href="<?= base_url('login.php') ?>"><strong>Log in</strong></a></p>
    </div>
  </div>
</div>
<script>
(function () {
  var hidden = document.getElementById('roleField');
  var business = document.getElementById('businessWrap'), category = document.getElementById('categoryWrap'), bio = document.getElementById('bioWrap');
  document.querySelectorAll('.role-tab[data-role]').forEach(function (tab) {
    tab.addEventListener('click', function () {
      hidden.value = tab.dataset.role;
      var isProv = tab.dataset.role === 'provider';
      business.style.display = isProv ? '' : 'none'; category.style.display = isProv ? '' : 'none'; bio.style.display = isProv ? '' : 'none';
    });
  });
})();
</script>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
