<?php
/**
 * Paystack webhook receiver.
 * Set this URL in Paystack Dashboard → Settings → Webhooks:
 *   https://yourdomain.com/paystack-webhook.php
 */
require_once __DIR__ . '/includes/functions.php';
$secret = trim(setting('paystack_secret_key', ''));
$raw = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

header('Content-Type: application/json');
if ($secret === '' || $signature === '' || !hash_equals(hash_hmac('sha512', (string)$raw, $secret), $signature)) {
    http_response_code(401); echo json_encode(['status' => 'invalid signature']); exit;
}
$event = json_decode((string)$raw, true);
if (($event['event'] ?? '') === 'charge.success') {
    $data = $event['data'] ?? [];
    $reference = $data['reference'] ?? '';
    $amount = (int)($data['amount'] ?? 0);
    $st = db()->prepare('SELECT * FROM subscriptions WHERE reference = ? LIMIT 1');
    $st->execute([$reference]); $sub = $st->fetch();
    if ($sub && $sub['status'] !== 'active' && $amount >= (int)$sub['amount'] * 100) {
        $base = db()->query("SELECT ends_at FROM subscriptions WHERE user_id = " . (int)$sub['user_id'] . " AND status = 'active' AND ends_at > NOW() ORDER BY ends_at DESC LIMIT 1")->fetchColumn();
        $start = $base ? max(strtotime($base), time()) : time();
        $end = strtotime('+30 days', $start);
        db()->prepare("UPDATE subscriptions SET status = 'active', starts_at = FROM_UNIXTIME(?), ends_at = FROM_UNIXTIME(?) WHERE id = ?")->execute([$start, $end, $sub['id']]);
    }
}
http_response_code(200); echo json_encode(['status' => 'ok']);
