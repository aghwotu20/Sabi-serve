<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);
$dashRole = 'admin'; $dashTitle = 'Admin Overview'; $activeTab = 'overview';
require_once __DIR__ . '/../includes/dash_top.php';

$counts = [
    'users' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'providers' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'provider'")->fetchColumn(),
    'staff' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'staff'")->fetchColumn(),
    'listings' => (int) db()->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
    'enquiries' => (int) db()->query("SELECT COUNT(*) FROM enquiries")->fetchColumn(),
    'pending_enquiries' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'pending'")->fetchColumn(),
    'active_subs' => (int) db()->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND ends_at > NOW()")->fetchColumn(),
    'revenue' => (float) db()->query("SELECT COALESCE(SUM(amount), 0) FROM subscriptions WHERE status = 'active'")->fetchColumn(),
    'new_messages' => (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
    'hidden_reviews' => (int) db()->query("SELECT COUNT(*) FROM reviews WHERE status = 'hidden'")->fetchColumn(),
];
$subPrice = subscription_amount();
$mrr = $counts['active_subs'] * $subPrice;
$recentSignups = db()->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6")->fetchAll();
$recentSubs = db()->query("SELECT s.*, u.full_name, u.email FROM subscriptions s JOIN users u ON u.id = s.user_id WHERE s.status = 'active' ORDER BY s.created_at DESC LIMIT 6")->fetchAll();
// 7-day signup sparkline (CSS bars)
$week = db()->query("SELECT DATE(created_at) d, COUNT(*) c FROM users WHERE created_at >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(created_at)")->fetchAll();
$byDay = []; foreach ($week as $w) $byDay[$w['d']] = (int)$w['c'];
$days = []; for ($i = 6; $i >= 0; $i--) { $d = date('Y-m-d', strtotime("-$i days")); $days[$d] = $byDay[$d] ?? 0; }
$maxDay = max(1, max($days));
?>
<div class="stat-cards">
  <div class="stat-card"><div class="k">Customers</div><div class="v"><?= $counts['users'] ?></div><div class="d">Registered service users</div></div>
  <div class="stat-card"><div class="k">Providers</div><div class="v"><?= $counts['providers'] ?></div><div class="d"><?= $counts['listings'] ?> active listings</div></div>
  <div class="stat-card"><div class="k">Staff</div><div class="v"><?= $counts['staff'] ?></div><div class="d"><a href="/admin/staff.php">Manage staff →</a></div></div>
  <div class="stat-card"><div class="k">Active Subscriptions</div><div class="v"><?= $counts['active_subs'] ?></div><div class="d">Est. MRR ₦<?= number_format($mrr) ?></div></div>
</div>
<div class="stat-cards">
  <div class="stat-card"><div class="k">Subscription Revenue</div><div class="v">₦<?= number_format($counts['revenue']) ?></div><div class="d">Lifetime (verified payments)</div></div>
  <div class="stat-card"><div class="k">Enquiries</div><div class="v"><?= $counts['enquiries'] ?></div><div class="d"><?= $counts['pending_enquiries'] ?> awaiting response</div></div>
  <div class="stat-card"><div class="k">New Messages</div><div class="v"><?= $counts['new_messages'] ?></div><div class="d"><a href="/admin/messages.php">View inbox →</a></div></div>
  <div class="stat-card"><div class="k">Hidden Reviews</div><div class="v"><?= $counts['hidden_reviews'] ?></div><div class="d"><a href="/admin/reviews.php">Moderation queue →</a></div></div>
</div>

<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>Signups — last 7 days</h2><a class="btn btn-outline btn-sm" href="/admin/users.php">All users →</a></div>
    <div style="display:flex;gap:10px;align-items:flex-end;height:140px;padding:8px 4px">
      <?php foreach ($days as $d => $c): ?>
        <div style="flex:1;text-align:center"><div style="background:linear-gradient(180deg,var(--green-700),var(--green-900));border-radius:8px 8px 4px 4px;height:<?= max(6, (int)($c / $maxDay * 110)) ?>px;width:100%;max-width:44px;margin:0 auto" title="<?= (int)$c ?> signups"></div><div style="font-size:.68rem;color:var(--muted);margin-top:6px"><?= date('D', strtotime($d)) ?></div><div style="font-size:.72rem;font-weight:700;color:var(--green-900)"><?= (int)$c ?></div></div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Latest verified payments</h2><a class="btn btn-outline btn-sm" href="/admin/subscriptions.php">All →</a></div>
    <?php if (!$recentSubs): ?><div class="empty">No verified payments yet. Once Paystack confirms a payment, it appears here.</div><?php else: ?>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Member</th><th>Amount</th><th>Until</th></tr></thead><tbody>
      <?php foreach ($recentSubs as $s): ?><tr><td><?= h($s['full_name']) ?><br><span class="hint"><?= h($s['email']) ?></span></td><td>₦<?= number_format((float)$s['amount']) ?></td><td><?= $s['ends_at'] ? date('j M Y', strtotime($s['ends_at'])) : '—' ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h2>Recent registrations</h2><a class="btn btn-outline btn-sm" href="/admin/users.php">Manage users →</a></div>
  <div class="table-wrap"><table class="tbl"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead><tbody>
    <?php foreach ($recentSignups as $s): ?><tr><td><?= h($s['full_name']) ?></td><td><?= h($s['email']) ?></td><td><span class="badge-role badge-<?= h($s['role']) ?>"><?= h($s['role']) ?></span></td><td><?= time_ago($s['created_at']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
