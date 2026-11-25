<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Subscription — ₦2,000/month | SabiServe';
$me = require_login();
if (in_array($me['role'], ['staff', 'admin'], true)) { flash('info', 'Staff and admin accounts do not require a subscription.'); redirect(dashboard_url($me['role'])); }

$amount = subscription_amount();
$activeSub = active_subscription((int)$me['id']);
$publicKey = trim(setting('paystack_public_key', ''));
$secretKey = trim(setting('paystack_secret_key', ''));
$paystackReady = $publicKey !== '' && $secretKey !== '';

// create a pending reference for this checkout session
$reference = 'SS-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
if (!$activeSub && $paystackReady && ($_SERVER['REQUEST_METHOD'] === 'GET')) {
    // expire stale pendings older than 1 hour for this user to avoid clutter
    db()->prepare("DELETE FROM subscriptions WHERE user_id = ? AND status = 'pending' AND created_at < NOW() - INTERVAL 1 HOUR")->execute([$me['id']]);
    db()->prepare('INSERT INTO subscriptions (user_id, role, plan, amount, reference, status) VALUES (?, ?, \'monthly\', ?, ?, \'pending\')')->execute([$me['id'], $me['role'], $amount, $reference]);
}
$badge = subscription_badge((int)$me['id']);
$dashboard = dashboard_url($me['role']);
require_once __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero" style="padding:44px 0"><div class="container"><span class="eyebrow" style="color:var(--amber-300)">Membership</span><h1 style="margin-top:10px">One subscription. Unlimited access.</h1><p style="color:#A7F3D0;margin-top:8px">₦2,000 a month — for customers and providers alike. Cancel anytime.</p></div></section>
<section class="section" style="padding-top:44px">
  <div class="plan-card">
    <div class="plan-left">
      <span class="eyebrow" style="color:var(--amber-300)">SabiServe Monthly</span>
      <div class="plan-price">₦<?= number_format($amount) ?><small> / month</small></div>
      <p style="color:#A7F3D0;font-size:.9rem">Less than one bottle of zobo. More valuable than a thousand referrals.</p>
      <ul class="plan-list" style="margin-top:22px;color:#D1FAE5">
        <?php if ($me['role'] === 'provider'): ?>
          <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Appear in search results &amp; category pages</li>
          <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Unlimited service listings on your profile</li>
          <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Receive &amp; respond to customer enquiries</li>
        <?php else: ?>
          <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Send unlimited enquiries to providers</li>
          <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Save favourite providers &amp; track requests</li>
          <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Verified reviews that build trust</li>
        <?php endif; ?>
        <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>WhatsApp-first support from our Lagos team</li>
        <li><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>Secure payments via Paystack — we never see your card</li>
      </ul>
    </div>
    <div class="plan-right">
      <h2 style="font-size:1.3rem">Your subscription</h2>
      <div class="sub-banner <?= $badge['active'] ? 'ok' : 'warn' ?>" style="margin:14px 0 18px">
        <div><strong><?= $badge['active'] ? 'You are subscribed' : 'Subscription required' ?></strong><br><span class="hint"><?= h($badge['label']) ?></span></div>
        <?php if ($badge['active']): ?><a class="btn btn-emerald btn-sm" href="<?= h($dashboard) ?>">Go to Dashboard</a><?php endif; ?>
      </div>
      <?php if (!$badge['active']): ?>
        <?php if (!$paystackReady): ?>
          <div class="flash flash-info" style="position:static;transform:none"><span>Online payment is being set up. Please check back shortly or contact <a href="mailto:support@sabiserve.com.ng"><strong>support@sabiserve.com.ng</strong></a> to activate your subscription manually.</span></div>
        <?php else: ?>
          <p style="color:var(--muted);font-size:.92rem;margin-bottom:16px">Click below — Paystack’s secure popup opens instantly. Your subscription activates automatically after payment.</p>
          <button id="paystackBtn" class="btn btn-amber btn-block" style="font-size:1.05rem;padding:16px">Pay ₦<?= number_format($amount) ?> with Paystack <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></button>
          <p class="hint" style="margin-top:14px;text-align:center">🔒 Secured by <strong>Paystack</strong> · Cards, bank transfer &amp; USSD</p>
          <script src="https://js.paystack.co/v1/inline.js"></script>
          <script>
            document.getElementById('paystackBtn').addEventListener('click', function () {
              var handler = PaystackPop.setup({
                key: <?= json_encode($publicKey) ?>,
                email: <?= json_encode($me['email']) ?>,
                amount: <?= (int)$amount * 100 ?>,
                currency: 'NGN',
                ref: <?= json_encode($reference) ?>,
                metadata: { custom_fields: [{ display_name: 'Account', variable_name: 'account_id', value: <?= (int)$me['id'] ?> }, { display_name: 'Role', variable_name: 'role', value: <?= json_encode($me['role']) ?> }] },
                callback: function (response) { window.location.href = '/verify-payment.php?reference=' + encodeURIComponent(response.reference); },
                onClose: function () { /* user closed popup; pending reference retained */ }
              });
              handler.openIframe();
            });
          </script>
        <?php endif; ?>
      <?php else: ?>
        <p style="color:var(--muted);font-size:.92rem">Want to renew early? Renewing adds 30 more days from your current expiry date.</p>
        <?php if ($paystackReady): ?>
          <button id="paystackBtn" class="btn btn-emerald btn-block" style="margin-top:10px">Renew for ₦<?= number_format($amount) ?></button>
          <script src="https://js.paystack.co/v1/inline.js"></script>
          <script>
            document.getElementById('paystackBtn').addEventListener('click', function () {
              var handler = PaystackPop.setup({ key: <?= json_encode($publicKey) ?>, email: <?= json_encode($me['email']) ?>, amount: <?= (int)$amount * 100 ?>, currency: 'NGN', ref: <?= json_encode($reference) ?>, callback: function (response) { window.location.href = '/verify-payment.php?reference=' + encodeURIComponent(response.reference); }, onClose: function () {} });
              handler.openIframe();
            });
          </script>
        <?php endif; ?>
      <?php endif; ?>
      <div style="margin-top:18px;border-top:1px dashed var(--line);padding-top:14px"><p class="hint">Need an invoice or help? <a href="mailto:support@sabiserve.com.ng">support@sabiserve.com.ng</a> or WhatsApp <a href="https://wa.me/<?= h(preg_replace('/\D/', '', setting('whatsapp_number', '2348030567553'))) ?>">0803 056 7553</a></p></div>
    </div>
  </div>
</section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
