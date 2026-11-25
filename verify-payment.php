<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Verifying payment — SabiServe';
$me = require_login();
$reference = trim($_GET['reference'] ?? '');
$ok = false; $message = 'We could not verify that payment.';

// find our own pending record — never trust client input alone
$st = db()->prepare('SELECT * FROM subscriptions WHERE reference = ? LIMIT 1');
$st->execute([$reference]);
$sub = $st->fetch();

if ($reference === '' || !$sub || (int)$sub['user_id'] !== (int)$me['id']) {
    $message = 'Unknown payment reference for this account.';
} elseif ($sub['status'] === 'active') {
    $ok = true; $message = 'Payment already verified — your subscription is active.';
} else {
    $secretKey = trim(setting('paystack_secret_key', ''));
    $expectedAmount = (int)$sub['amount'] * 100; // kobo
    if ($secretKey === '') {
        $message = 'Paystack is not configured on this server. Contact support with reference ' . h($reference) . '.';
    } else {
        $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secretKey, 'Cache-Control: no-cache'], CURLOPT_TIMEOUT => 20]);
        $resp = curl_exec($ch); $http = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $data = json_decode((string)$resp, true);
        if ($http === 200 && ($data['status'] ?? false) && ($data['data']['status'] ?? '') === 'success' && (int)($data['data']['amount'] ?? 0) >= $expectedAmount) {
            // compute window: extend any current active subscription
            $base = db()->query("SELECT ends_at FROM subscriptions WHERE user_id = " . (int)$me['id'] . " AND status = 'active' AND ends_at > NOW() ORDER BY ends_at DESC LIMIT 1")->fetchColumn();
            $start = $base ? max(strtotime($base), time()) : time();
            $end = strtotime('+30 days', $start);
            db()->prepare("UPDATE subscriptions SET status = 'active', starts_at = FROM_UNIXTIME(?), ends_at = FROM_UNIXTIME(?) WHERE id = ?")->execute([$start, $end, $sub['id']]);
            $ok = true; $message = 'Payment confirmed! Your subscription is active until ' . date('j F Y', $end) . '. Welcome to full SabiServe access.';
        } else {
            $message = 'Paystack could not confirm this payment (status: ' . h($data['data']['status'] ?? 'unknown') . '). If you were debited, contact support with reference ' . h($reference) . '.';
        }
    }
}
require_once __DIR__ . '/includes/public_header.php';
?>
<section class="section"><div class="container" style="max-width:640px;text-align:center">
  <?php if ($ok): ?>
    <svg width="84" height="84" viewBox="0 0 84 84" style="margin:0 auto 18px"><circle cx="42" cy="42" r="40" fill="#ECFDF5" stroke="#10B981" stroke-width="3"/><path d="M26 43l11 11 21-24" fill="none" stroke="#065F46" stroke-width="4.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    <h1 style="font-size:1.7rem;margin-bottom:10px">Payment successful</h1>
    <p style="color:var(--muted);margin-bottom:22px"><?= h($message) ?></p>
    <a class="btn btn-emerald" href="<?= h(dashboard_url($me['role'])) ?>">Go to my Dashboard</a>
  <?php else: ?>
    <svg width="84" height="84" viewBox="0 0 84 84" style="margin:0 auto 18px"><circle cx="42" cy="42" r="40" fill="#FEF2F2" stroke="#DC2626" stroke-width="3"/><path d="M30 30l24 24M54 30L30 54" stroke="#B91C1C" stroke-width="4.5" stroke-linecap="round"/></svg>
    <h1 style="font-size:1.7rem;margin-bottom:10px">Verification needed</h1>
    <p style="color:var(--muted);margin-bottom:22px"><?= $message ?></p>
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap"><a class="btn btn-amber" href="<?= base_url('subscribe.php') ?>">Try again</a><a class="btn btn-outline" href="mailto:support@sabiserve.com.ng">Contact support</a></div>
  <?php endif; ?>
</div></section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
