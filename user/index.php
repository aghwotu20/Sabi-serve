<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['user']);
$tab = $_GET['tab'] ?? 'overview'; $tab = in_array($tab, ['overview', 'enquiries', 'saved'], true) ? $tab : 'overview';
$dashRole = 'user'; $dashTitle = match ($tab) { 'enquiries' => 'My Enquiries', 'saved' => 'Saved Providers', default => 'Customer Dashboard' }; $activeTab = $tab;
require_once __DIR__ . '/../includes/dash_top.php';

$uid = (int)$u['id'];
$stats = [
    'enquiries' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE user_id = $uid")->fetchColumn(),
    'pending'   => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE user_id = $uid AND status = 'pending'")->fetchColumn(),
    'saved'     => (int) db()->query("SELECT COUNT(*) FROM saved_providers WHERE user_id = $uid")->fetchColumn(),
    'reviews'   => (int) db()->query("SELECT COUNT(*) FROM reviews WHERE user_id = $uid")->fetchColumn(),
];
$sub = active_subscription($uid);
?>
<div class="sub-banner <?= $sub ? 'ok' : 'warn' ?>">
  <div><strong><?= $sub ? 'Your subscription is active' : 'Activate your ₦2,000 monthly subscription' ?></strong><br><span class="hint"><?= $sub ? 'Renews ' . date('j F Y', strtotime($sub['ends_at'])) . ' · Unlimited enquiries &amp; reviews' : 'Unlock enquiries, reviews and saved providers.' ?></span></div>
  <?php if (!$sub): ?><a class="btn btn-amber" href="/subscribe.php">Subscribe Now</a><?php else: ?><a class="btn btn-emerald btn-sm" href="/subscribe.php">Renew Early</a><?php endif; ?>
</div>

<?php if ($tab === 'overview'): ?>
  <div class="stat-cards">
    <div class="stat-card"><div class="k">Enquiries Sent</div><div class="v"><?= $stats['enquiries'] ?></div><div class="d">Total requests to providers</div></div>
    <div class="stat-card"><div class="k">Awaiting Reply</div><div class="v"><?= $stats['pending'] ?></div><div class="d">Pending provider response</div></div>
    <div class="stat-card"><div class="k">Saved Providers</div><div class="v"><?= $stats['saved'] ?></div><div class="d">Your trusted shortlist</div></div>
    <div class="stat-card"><div class="k">Reviews Written</div><div class="v"><?= $stats['reviews'] ?></div><div class="d">Helping the community choose</div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>Find your next trusted pro</h2><a class="btn btn-amber btn-sm" href="/browse.php">Browse Services</a></div>
    <p style="color:var(--muted)">Need a plumber, tailor, tutor or cleaner? Browse verified providers across Nigeria, compare reviews and send an enquiry in minutes.</p>
  </div>
<?php elseif ($tab === 'enquiries'): ?>
  <div class="panel">
    <div class="panel-head"><h2>My enquiries</h2></div>
    <?php
    $st = db()->prepare("SELECT e.*, u.full_name AS provider_name, u.business_name, u.passport FROM enquiries e JOIN users u ON u.id = e.provider_id WHERE e.user_id = ? ORDER BY e.created_at DESC");
    $st->execute([$uid]); $rows = $st->fetchAll();
    if (!$rows): ?><div class="empty">No enquiries yet. <a href="/browse.php"><strong>Find a provider →</strong></a></div><?php else: ?>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Provider</th><th>Subject</th><th>Status</th><th>Sent</th></tr></thead><tbody>
      <?php foreach ($rows as $e): $pn = $e['business_name'] ?: $e['provider_name']; ?>
        <tr><td><a href="/provider.php?id=<?= (int)$e['provider_id'] ?>"><strong><?= h($pn) ?></strong></a></td><td><?= h($e['subject']) ?><br><span class="hint"><?= h(mb_strimwidth((string)$e['message'], 0, 80, '…')) ?></span><?php if ($e['provider_reply']): ?><div class="hint" style="margin-top:6px;color:var(--green-800)"><strong>Provider:</strong> <?= h(mb_strimwidth((string)$e['provider_reply'], 0, 140, '…')) ?></div><?php endif; ?></td><td><span class="pill <?= $e['status'] === 'responded' ? 'pill-green' : ($e['status'] === 'closed' ? 'pill-grey' : 'pill-amber') ?>"><?= h(ucfirst($e['status'])) ?></span></td><td><?= time_ago($e['created_at']) ?></td></tr>
      <?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
  </div>
<?php elseif ($tab === 'saved'): ?>
  <div class="panel">
    <div class="panel-head"><h2>Saved providers</h2></div>
    <?php
    $st = db()->prepare("SELECT u.*, c.name AS category_name, (SELECT COALESCE(AVG(rating),0) FROM reviews WHERE provider_id = u.id AND status='visible') AS avg_rating FROM saved_providers s JOIN users u ON u.id = s.provider_id LEFT JOIN categories c ON c.id = u.category_id WHERE s.user_id = ? ORDER BY s.created_at DESC");
    $st->execute([$uid]); $saved = $st->fetchAll();
    if (!$saved): ?><div class="empty">Nothing saved yet. Tap “☆ Save provider” on any profile.</div><?php else: ?>
    <div class="p-grid">
      <?php foreach ($saved as $p): $pn = $p['business_name'] ?: $p['full_name']; ?>
        <article class="p-card"><div class="p-card-top"><?= avatar_html($p['passport'], $pn, 64, 'p-avatar') ?><div><div class="p-name"><?= h($pn) ?></div><div class="p-cat"><?= h($p['category_name'] ?? 'General') ?></div></div></div><div class="p-foot"><a class="btn btn-ghost btn-sm" href="/provider.php?id=<?= (int)$p['id'] ?>">View Profile</a><form method="post" action="/save-provider.php"><?= csrf_field() ?><input type="hidden" name="provider_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="back" value="/user/index.php?tab=saved"><button class="btn btn-outline btn-sm" type="submit">Remove</button></form></div></article>
      <?php endforeach; ?>
    </div><?php endif; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
