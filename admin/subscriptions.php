<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);
$amount = subscription_amount();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'manual_activate') {
        $memberId = (int)($_POST['member_id'] ?? 0);
        $st = db()->prepare("SELECT id, role FROM users WHERE id = ? AND role IN ('user','provider')"); $st->execute([$memberId]); $member = $st->fetch();
        if (!$member) { flash('error', 'Choose a valid customer or provider.'); }
        else {
            $days = max(1, min(366, (int)($_POST['days'] ?? 30)));
            $amt = max(0, (float)($_POST['amount'] ?? $amount));
            $base = db()->prepare("SELECT ends_at FROM subscriptions WHERE user_id = ? AND status = 'active' AND ends_at > NOW() ORDER BY ends_at DESC LIMIT 1");
            $base->execute([$memberId]); $current = $base->fetchColumn();
            $start = $current ? max(strtotime($current), time()) : time();
            $end = strtotime("+$days days", $start);
            $ref = 'ADMIN-' . strtoupper(bin2hex(random_bytes(4))) . '-' . time();
            db()->prepare("INSERT INTO subscriptions (user_id, role, plan, amount, reference, status, starts_at, ends_at) VALUES (?, ?, 'monthly', ?, ?, 'active', FROM_UNIXTIME(?), FROM_UNIXTIME(?))")->execute([$memberId, $member['role'], $amt, $ref, $start, $end]);
            flash('success', 'Subscription activated manually for ' . $days . ' days (ref ' . $ref . ').');
        }
    }
    redirect('/admin/subscriptions.php');
}
$dashRole = 'admin'; $dashTitle = 'Subscriptions & Revenue'; $activeTab = 'subscriptions';
require_once __DIR__ . '/../includes/dash_top.php';

$active = (int) db()->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND ends_at > NOW()")->fetchColumn();
$totalRevenue = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status = 'active'")->fetchColumn();
$monthRevenue = (float) db()->query("SELECT COALESCE(SUM(amount),0) FROM subscriptions WHERE status = 'active' AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn();
$mrr = $active * $amount;
$subs = db()->query("SELECT s.*, u.full_name, u.email, u.role FROM subscriptions s JOIN users u ON u.id = s.user_id ORDER BY s.created_at DESC LIMIT 120")->fetchAll();
$members = db()->query("SELECT id, full_name, email, role FROM users WHERE role IN ('user','provider') AND status = 'active' ORDER BY full_name")->fetchAll();
?>
<div class="stat-cards">
  <div class="stat-card"><div class="k">Active Subscribers</div><div class="v"><?= $active ?></div><div class="d">Customers + providers</div></div>
  <div class="stat-card"><div class="k">Est. Monthly Revenue</div><div class="v">₦<?= number_format($mrr) ?></div><div class="d"><?= $active ?> × ₦<?= number_format($amount) ?></div></div>
  <div class="stat-card"><div class="k">Revenue This Month</div><div class="v">₦<?= number_format($monthRevenue) ?></div><div class="d">Verified payments</div></div>
  <div class="stat-card"><div class="k">Lifetime Revenue</div><div class="v">₦<?= number_format($totalRevenue) ?></div><div class="d">All verified payments</div></div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>Subscription ledger</h2><span class="hint">Latest 120 records</span></div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Member</th><th>Ref</th><th>Amount</th><th>Status</th><th>Ends</th></tr></thead><tbody>
      <?php foreach ($subs as $s): ?>
        <tr><td><?= h($s['full_name']) ?><br><span class="hint"><?= h($s['email']) ?> · <?= h($s['role']) ?></span></td><td><span class="hint"><?= h($s['reference']) ?></span></td><td>₦<?= number_format((float)$s['amount']) ?></td>
          <td><span class="pill <?= $s['status'] === 'active' ? 'pill-green' : ($s['status'] === 'pending' ? 'pill-amber' : 'pill-grey') ?>"><?= h(ucfirst($s['status'])) ?></span></td>
          <td><?= $s['ends_at'] ? date('j M Y', strtotime($s['ends_at'])) : '—' ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </div>
  <form class="panel" method="post" action="/admin/subscriptions.php">
    <?= csrf_field() ?><input type="hidden" name="action" value="manual_activate">
    <div class="panel-head"><h2>Manual activation</h2><span class="hint">For bank transfers &amp; cash</span></div>
    <div class="form-group" style="margin-bottom:12px"><label class="fl" for="member_id">Member *</label><select class="form-control" id="member_id" name="member_id" required><option value="">Select customer or provider…</option><?php foreach ($members as $m): ?><option value="<?= (int)$m['id'] ?>"><?= h($m['full_name']) ?> — <?= h($m['email']) ?> (<?= h($m['role']) ?>)</option><?php endforeach; ?></select></div>
    <div class="form-grid">
      <div class="form-group"><label class="fl" for="days">Days</label><input class="form-control" id="days" name="days" type="number" min="1" max="366" value="30"></div>
      <div class="form-group"><label class="fl" for="amount">Amount (₦)</label><input class="form-control" id="amount" name="amount" type="number" min="0" step="50" value="<?= (int)$amount ?>"></div>
    </div>
    <button class="btn btn-amber btn-block" style="margin-top:14px" type="submit">Activate Subscription</button>
    <p class="hint" style="margin-top:12px">Adds days on top of any remaining active subscription. The reference is logged as <code>ADMIN-…</code>.</p>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
