<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $section = $_POST['section'] ?? '';
    try {
        switch ($section) {
            case 'branding':
                $siteName = trim($_POST['site_name'] ?? 'SabiServe');
                save_settings(['site_name' => mb_substr($siteName, 0, 60) ?: 'SabiServe']);
                $logo = handle_upload('logo', ['image/jpeg', 'image/png', 'image/webp'], MAX_BRAND_BYTES, 'logo');
                if ($logo) { $old = setting('logo'); if ($old && file_exists(UPLOAD_DIR . $old)) @unlink(UPLOAD_DIR . $old); save_settings(['logo' => $logo]); }
                $favicon = handle_upload('favicon', ['image/jpeg', 'image/png', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'], MAX_BRAND_BYTES, 'favicon');
                if ($favicon) { $old = setting('favicon'); if ($old && file_exists(UPLOAD_DIR . $old)) @unlink(UPLOAD_DIR . $old); save_settings(['favicon' => $favicon]); }
                flash('success', 'Branding updated — logo and favicon are live across the site.');
                break;
            case 'paystack':
                $pub = trim($_POST['paystack_public_key'] ?? ''); $sec = trim($_POST['paystack_secret_key'] ?? '');
                $amount = max(100, (int)($_POST['subscription_amount'] ?? 2000));
                $pairs = ['subscription_amount' => (string)$amount];
                // only overwrite keys when a value is typed (secret stays masked otherwise)
                if ($pub !== '') $pairs['paystack_public_key'] = mb_substr($pub, 0, 200);
                if ($sec !== '') $pairs['paystack_secret_key'] = mb_substr($sec, 0, 200);
                save_settings($pairs);
                flash('success', 'Paystack settings saved. Test a payment from the Subscribe page.');
                break;
            case 'maintenance':
                $on = isset($_POST['maintenance_mode']) && $_POST['maintenance_mode'] === '1';
                save_settings(['maintenance_mode' => $on ? '1' : '0']);
                flash($on ? 'info' : 'success', $on ? 'Maintenance mode is ON — visitors, customers, providers and staff now see the maintenance page. Admins can still log in.' : 'Maintenance mode is OFF — the website is live for everyone.');
                break;
            case 'social':
                $wa = preg_replace('/\D/', '', $_POST['whatsapp_number'] ?? '2348030567553');
                save_settings([
                    'whatsapp_number' => $wa ?: '2348030567553',
                    'facebook_url' => mb_substr(trim($_POST['facebook_url'] ?? ''), 0, 300),
                    'instagram_url' => mb_substr(trim($_POST['instagram_url'] ?? ''), 0, 300),
                    'office_address' => mb_substr(trim($_POST['office_address'] ?? ''), 0, 300),
                ]);
                flash('success', 'Social links and office details updated.');
                break;
        }
    } catch (RuntimeException $e) { $errors[] = $e->getMessage(); }
    redirect('/admin/settings.php' . ($errors ? '?' : ''));
}

$dashRole = 'admin'; $dashTitle = 'Website Settings'; $activeTab = 'settings';
require_once __DIR__ . '/../includes/dash_top.php';
$s = settings_all();
$logo = $s['logo'] ?? ''; $favicon = $s['favicon'] ?? '';
$maint = ($s['maintenance_mode'] ?? '0') === '1';
$pubKey = $s['paystack_public_key'] ?? ''; $secKey = $s['paystack_secret_key'] ?? '';
?>
<?php foreach ($errors as $e): ?><div class="flash flash-error" style="position:static;transform:none;margin-bottom:12px"><span><?= h($e) ?></span></div><?php endforeach; ?>

<!-- BRANDING -->
<form class="panel" method="post" action="/admin/settings.php" enctype="multipart/form-data">
  <?= csrf_field() ?><input type="hidden" name="section" value="branding">
  <div class="panel-head"><h2>🖼️ Website Logo &amp; Favicon</h2><span class="hint">Shown in the navbar, dashboards, footer and browser tab</span></div>
  <div class="grid-2" style="grid-template-columns:1fr 1fr">
    <div>
      <label class="fl" for="site_name">Website name</label>
      <input class="form-control" id="site_name" name="site_name" value="<?= h($s['site_name'] ?? 'SabiServe') ?>" maxlength="60">
      <label class="fl" for="logo" style="margin-top:16px">Website logo <span class="hint">(JPG/PNG/WEBP · max 2MB · square or 4:3)</span></label>
      <input class="form-control" id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp">
      <div style="margin-top:12px"><span class="hint">Current logo:</span><br><img src="<?= h(brand_src($logo, '/assets/img/logo-mark.svg')) ?>" alt="Current logo" width="96" height="96" style="border-radius:16px;border:1px solid var(--line);margin-top:6px;background:#fff;object-fit:contain"></div>
    </div>
    <div>
      <label class="fl" for="favicon">Favicon <span class="hint">(browser tab icon · JPG/PNG/WEBP/ICO · max 2MB · 32×32 or 512×512)</span></label>
      <input class="form-control" id="favicon" name="favicon" type="file" accept="image/jpeg,image/png,image/webp,image/x-icon,image/vnd.microsoft.icon">
      <div style="margin-top:12px"><span class="hint">Current favicon:</span><br><img src="<?= h(brand_src($favicon, '/assets/img/favicon.svg')) ?>" alt="Current favicon" width="64" height="64" style="border-radius:12px;border:1px solid var(--line);margin-top:6px;background:#fff;object-fit:contain"></div>
      <p class="hint" style="margin-top:14px">Tip: a bold amber “S” on emerald works beautifully as a favicon — it’s our default until you upload yours.</p>
    </div>
  </div>
  <button class="btn btn-emerald" style="margin-top:16px" type="submit">Save Branding</button>
</form>

<!-- PAYSTACK -->
<form class="panel" method="post" action="/admin/settings.php">
  <?= csrf_field() ?><input type="hidden" name="section" value="paystack">
  <div class="panel-head"><h2>💳 Paystack Integration</h2><span class="pill <?= ($pubKey && $secKey) ? 'pill-green' : 'pill-amber' ?>"><?= ($pubKey && $secKey) ? 'Keys configured' : 'Not configured' ?></span></div>
  <p class="hint" style="margin-bottom:16px">Get your keys from your <a href="https://dashboard.paystack.com/#/settings/developers" target="_blank" rel="noopener"><strong>Paystack Dashboard → Settings → API Keys &amp; Webhooks</strong></a>. Use test keys (<code>pk_test_…</code> / <code>sk_test_…</code>) first, then switch to live keys when ready. Secret key is stored on your server only — never shown again.</p>
  <div class="form-grid">
    <div class="form-group full"><label class="fl" for="paystack_public_key">Paystack Public Key</label><input class="form-control" id="paystack_public_key" name="paystack_public_key" value="<?= h($pubKey) ?>" placeholder="pk_live_YOUR_PUBLIC_KEY" autocomplete="off" spellcheck="false"></div>
    <div class="form-group full"><label class="fl" for="paystack_secret_key">Paystack Secret Key</label><input class="form-control" id="paystack_secret_key" name="paystack_secret_key" type="password" placeholder="<?= $secKey ? '•••••••••••••••• (saved — type to replace)' : 'sk_live_YOUR_SECRET_KEY' ?>" autocomplete="new-password" spellcheck="false"></div>
    <div class="form-group"><label class="fl" for="subscription_amount">Monthly subscription (₦)</label><input class="form-control" id="subscription_amount" name="subscription_amount" type="number" min="100" step="50" value="<?= h($s['subscription_amount'] ?? '2000') ?>" required><span class="hint">Default is ₦2,000 for both customers and providers.</span></div>
    <div class="form-group"><label class="fl">Webhook URL</label><input class="form-control" value="<?= h((isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'sabiserve.com.ng') . '/paystack-webhook.php') ?>" disabled><span class="hint">Paste this in Paystack → Settings → Webhooks to receive payment events server-to-server.</span></div>
  </div>
  <button class="btn btn-emerald" style="margin-top:16px" type="submit">Save Paystack Settings</button>
</form>

<!-- MAINTENANCE MODE -->
<form class="panel" method="post" action="/admin/settings.php" style="border-color:<?= $maint ? '#FCD34D' : 'var(--line)' ?>;<?= $maint ? 'box-shadow:0 0 0 3px rgba(252,211,77,.35)' : '' ?>">
  <?= csrf_field() ?><input type="hidden" name="section" value="maintenance">
  <input type="hidden" name="maintenance_mode" value="0">
  <div class="panel-head"><h2>🛠️ Maintenance Mode</h2><span class="pill <?= $maint ? 'pill-amber' : 'pill-green' ?>"><?= $maint ? 'Currently ON' : 'Currently OFF' ?></span></div>
  <p style="color:var(--muted);max-width:640px;margin-bottom:16px">When ON, the public website shows an elegant “We’ll be right back” page and only <strong>administrators</strong> can log in and use the site. Use it for deploys, migrations or emergencies.</p>
  <label class="switch">
    <input type="checkbox" name="maintenance_mode" value="1" <?= $maint ? 'checked' : '' ?> onchange="this.form.submit()">
    <span class="track" aria-hidden="true"></span>
    <span style="font-family:var(--font-display);font-weight:700;color:var(--green-950)"><?= $maint ? 'Maintenance is ON — click to go live' : 'Maintenance is OFF — click to enable' ?></span>
  </label>
  <div style="margin-top:16px"><button class="btn <?= $maint ? 'btn-amber' : 'btn-outline' ?> btn-sm" type="submit"><?= $maint ? 'Turn off maintenance' : 'Save maintenance setting' ?></button></div>
</form>

<!-- SOCIAL -->
<form class="panel" method="post" action="/admin/settings.php">
  <?= csrf_field() ?><input type="hidden" name="section" value="social">
  <div class="panel-head"><h2>📱 WhatsApp &amp; Social Media</h2><span class="hint">Powers the floating WhatsApp button and footer icons</span></div>
  <div class="form-grid">
    <div class="form-group"><label class="fl" for="whatsapp_number">WhatsApp number <span class="hint">(international format, digits only)</span></label><input class="form-control" id="whatsapp_number" name="whatsapp_number" value="<?= h($s['whatsapp_number'] ?? '2348030567553') ?>" placeholder="2348030567553"></div>
    <div class="form-group"><label class="fl" for="office_address">Office address</label><input class="form-control" id="office_address" name="office_address" value="<?= h($s['office_address'] ?? 'No 8 Kess Rubber Factory Street, Ughelli, Delta State') ?>"></div>
    <div class="form-group"><label class="fl" for="facebook_url">Facebook page URL</label><input class="form-control" id="facebook_url" name="facebook_url" value="<?= h($s['facebook_url'] ?? 'https://www.facebook.com/sabiserve.ng') ?>" placeholder="https://www.facebook.com/…"></div>
    <div class="form-group"><label class="fl" for="instagram_url">Instagram profile URL</label><input class="form-control" id="instagram_url" name="instagram_url" value="<?= h($s['instagram_url'] ?? 'https://www.instagram.com/sabiserve.ng') ?>" placeholder="https://www.instagram.com/…"></div>
  </div>
  <button class="btn btn-emerald" style="margin-top:16px" type="submit">Save Social Links</button>
</form>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
