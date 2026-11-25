<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Log In — SabiServe';
$errors = []; $portal = $_GET['portal'] ?? ($_POST['portal'] ?? 'member');
$next = $_GET['next'] ?? ($_POST['next'] ?? '');
if (is_logged_in()) redirect(dashboard_url(current_user()['role']));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    // simple throttle
    $_SESSION['login_attempts'] ??= [];
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], fn($t) => $t > time() - 600);
    if (count($_SESSION['login_attempts']) >= 5) {
        $errors[] = 'Too many attempts. Please wait 10 minutes or contact support.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? '')); $password = $_POST['password'] ?? '';
        $st = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1'); $st->execute([$email]); $u = $st->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) {
            $_SESSION['login_attempts'][] = time();
            $errors[] = 'Incorrect email or password.';
        } elseif ($u['status'] === 'suspended') {
            $errors[] = 'This account has been suspended. Contact support@sabiserve.com.ng.';
        } else {
            // portal check: members = user|provider, staff, admin
            $ok = $portal === 'admin' ? $u['role'] === 'admin' : ($portal === 'staff' ? in_array($u['role'], ['staff','admin'], true) : in_array($u['role'], ['user','provider'], true));
            if (!$ok) {
                $errors[] = $portal === 'admin' ? 'That is not an administrator account. Try the Staff / Member portal.' : ($portal === 'staff' ? 'That is not a staff account. Try the Member portal.' : 'This looks like a staff or admin account — please use the Staff or Admin tab.');
            } else {
                $_SESSION['login_attempts'] = [];
                login_user($u);
                flash('success', 'Welcome back, ' . $u['full_name'] . '!');
                $target = (is_string($next) && str_starts_with($next, '/') && !str_starts_with($next, '//')) ? $next : dashboard_url($u['role']);
                redirect($target);
            }
        }
    }
}
require_once __DIR__ . '/includes/public_header.php';
$portal = in_array($portal, ['member', 'staff', 'admin'], true) ? $portal : 'member';
?>
<div class="auth-wrap">
  <aside class="auth-art" aria-hidden="true">
    <img src="/assets/img/logo-mark.svg" alt="" width="64" height="64" style="border-radius:16px">
    <h2>Welcome back to SabiServe</h2>
    <ul>
      <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Customers: track enquiries, save providers and leave reviews.</span></li>
      <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Providers: manage listings, receive job requests, grow your rating.</span></li>
      <li><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg><span>Staff &amp; Admins: moderate the marketplace and keep everyone safe.</span></li>
    </ul>
    <p style="color:#FDE68A;font-size:.85rem;margin-top:8px">One login page. Three portals. Zero wahala.</p>
  </aside>
  <div class="auth-form-side">
    <div class="auth-box">
      <h1>Log in to your account</h1>
      <p class="sub">Choose your portal and sign in — visitors, staff and admins all welcome.</p>
      <?php foreach ($errors as $e): ?><div class="flash flash-error" style="position:static;transform:none;margin-bottom:12px"><span><?= h($e) ?></span></div><?php endforeach; ?>
      <div class="role-tabs" data-role-group role="tablist" aria-label="Choose portal">
        <button type="button" class="role-tab" data-role="member" aria-pressed="<?= $portal === 'member' ? 'true' : 'false' ?>"><strong>Customer / Provider</strong><small>Website visitors</small></button>
        <button type="button" class="role-tab" data-role="staff" aria-pressed="<?= $portal === 'staff' ? 'true' : 'false' ?>"><strong>Staff</strong><small>Moderation team</small></button>
        <button type="button" class="role-tab" data-role="admin" aria-pressed="<?= $portal === 'admin' ? 'true' : 'false' ?>" style="grid-column:1/-1"><strong>Administrator</strong><small>Full control — settings, staff, subscriptions</small></button>
        <input type="hidden" name="portal" value="<?= h($portal) ?>" data-portal-input>
      </div>
      <form method="post" action="<?= base_url('login.php') ?>" autocomplete="on">
        <?= csrf_field() ?>
        <input type="hidden" name="portal" id="portalField" value="<?= h($portal) ?>">
        <input type="hidden" name="next" value="<?= h($next) ?>">
        <div class="form-group" style="margin-bottom:14px"><label class="fl" for="email">Email address</label><input class="form-control" id="email" name="email" type="email" required autofocus placeholder="you@example.com" autocomplete="email"></div>
        <div class="form-group" style="margin-bottom:6px; position:relative"><label class="fl" for="password">Password</label>
          <input class="form-control" id="password" name="password" type="password" required placeholder="••••••••" autocomplete="current-password">
          <button type="button" class="btn btn-ghost btn-sm" data-toggle-password="#password" style="position:absolute;right:6px;bottom:5px;padding:7px 10px" aria-label="Toggle password visibility" aria-pressed="false"><svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg></button>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-bottom:16px"><a href="<?= base_url('contact.php') ?>" style="font-size:.83rem;font-weight:600">Forgot password?</a></div>
        <button class="btn btn-emerald btn-block" type="submit" style="width:100%;justify-content:center;padding:14px">Log In Securely <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></button>
      </form>
      <p class="divider">new to sabiserve?</p>
      <a class="btn btn-amber btn-block" href="<?= base_url('register.php') ?>" style="justify-content:center">Create your free account</a>
    </div>
  </div>
</div>
<script>
  // keep the tab selection in sync with the hidden portal field
  (function () {
    var tabs = document.querySelectorAll('.role-tab[data-role]');
    var hidden = document.getElementById('portalField');
    tabs.forEach(function (t) { t.addEventListener('click', function () { hidden.value = t.dataset.role; }); });
  })();
</script>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
