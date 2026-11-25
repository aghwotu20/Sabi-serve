<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['staff', 'admin']);
$tab = $_GET['tab'] ?? 'overview'; $tab = in_array($tab, ['overview', 'listings', 'reviews', 'messages'], true) ? $tab : 'overview';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'listing_toggle':
            db()->prepare("UPDATE listings SET status = IF(status = 'suspended', 'active', 'suspended') WHERE id = ?")->execute([(int)$_POST['listing_id']]);
            flash('info', 'Listing moderation status updated.');
            break;
        case 'review_toggle':
            db()->prepare("UPDATE reviews SET status = IF(status = 'visible', 'hidden', 'visible') WHERE id = ?")->execute([(int)$_POST['review_id']]);
            flash('info', 'Review visibility updated.');
            break;
        case 'message_read':
            db()->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?")->execute([(int)$_POST['message_id']]);
            flash('info', 'Marked as read.');
            break;
    }
    redirect('/staff/index.php?tab=' . $tab);
}

$dashRole = 'staff'; $dashTitle = match ($tab) { 'listings' => 'Moderate Listings', 'reviews' => 'Moderate Reviews', 'messages' => 'Contact Messages', default => 'Staff Dashboard' }; $activeTab = $tab;
require_once __DIR__ . '/../includes/dash_top.php';

$stats = [
    'listings' => (int) db()->query("SELECT COUNT(*) FROM listings WHERE status = 'active'")->fetchColumn(),
    'pending_enquiries' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE status = 'pending'")->fetchColumn(),
    'new_messages' => (int) db()->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn(),
    'providers' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'provider' AND status = 'active'")->fetchColumn(),
];
?>
<?php if ($tab === 'overview'): ?>
  <div class="stat-cards">
    <div class="stat-card"><div class="k">Active Listings</div><div class="v"><?= $stats['listings'] ?></div><div class="d">Across all providers</div></div>
    <div class="stat-card"><div class="k">Pending Enquiries</div><div class="v"><?= $stats['pending_enquiries'] ?></div><div class="d">Awaiting provider replies</div></div>
    <div class="stat-card"><div class="k">New Messages</div><div class="v"><?= $stats['new_messages'] ?></div><div class="d"><a href="/staff/index.php?tab=messages">Read messages →</a></div></div>
    <div class="stat-card"><div class="k">Active Providers</div><div class="v"><?= $stats['providers'] ?></div><div class="d">Live on the marketplace</div></div>
  </div>
  <div class="panel"><div class="panel-head"><h2>Your moderation toolkit</h2></div><p style="color:var(--muted)">Use the tabs to suspend suspicious listings, hide fake or abusive reviews, and respond to customer messages. Staff accounts cannot change website settings, payment keys, or suspend other staff — that is reserved for administrators.</p></div>

<?php elseif ($tab === 'listings'): ?>
  <div class="panel"><div class="panel-head"><h2>All listings</h2><span class="hint">Suspend anything that looks fake, fraudulent or off-category.</span></div>
  <div class="table-wrap"><table class="tbl"><thead><tr><th>Listing</th><th>Provider</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach (db()->query("SELECT l.*, u.full_name AS provider, u.business_name, c.name AS category_name FROM listings l JOIN users u ON u.id = l.provider_id LEFT JOIN categories c ON c.id = l.category_id ORDER BY l.created_at DESC LIMIT 100") as $l): $pn = $l['business_name'] ?: $l['provider']; ?>
      <tr><td><strong><?= h($l['title']) ?></strong><br><span class="hint"><?= h($l['category_name'] ?? '') ?></span></td><td><?= h($pn) ?></td><td><span class="pill <?= $l['status'] === 'active' ? 'pill-green' : ($l['status'] === 'suspended' ? 'pill-red' : 'pill-grey') ?>"><?= h(ucfirst($l['status'])) ?></span></td>
        <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="listing_toggle"><input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>"><button class="btn <?= $l['status'] === 'suspended' ? 'btn-emerald' : 'btn-danger' ?> btn-sm" type="submit"><?= $l['status'] === 'suspended' ? 'Restore' : 'Suspend' ?></button></form></td></tr>
    <?php endforeach; ?>
  </tbody></table></div></div>

<?php elseif ($tab === 'reviews'): ?>
  <div class="panel"><div class="panel-head"><h2>Latest reviews</h2><span class="hint">Hide fake or abusive reviews; restore genuine ones.</span></div>
  <?php foreach (db()->query("SELECT r.*, u.full_name AS reviewer, p.full_name AS provider, p.business_name FROM reviews r JOIN users u ON u.id = r.user_id JOIN users p ON p.id = r.provider_id ORDER BY r.created_at DESC LIMIT 60") as $r): $pn = $r['business_name'] ?: $r['provider']; ?>
    <div class="review-item"><div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap"><div><strong><?= h($pn) ?></strong> <span class="hint">reviewed by <?= h($r['reviewer']) ?> · <?= time_ago($r['created_at']) ?></span><br><span class="stars" style="margin:6px 0"><?= star_rating((float)$r['rating']) ?></span><p style="color:var(--muted)"><?= nl2br(h($r['comment'])) ?></p></div>
      <form method="post" style="align-self:center"><?= csrf_field() ?><input type="hidden" name="action" value="review_toggle"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>"><button class="btn <?= $r['status'] === 'visible' ? 'btn-danger' : 'btn-emerald' ?> btn-sm" type="submit"><?= $r['status'] === 'visible' ? 'Hide' : 'Restore' ?></button></form></div></div>
  <?php endforeach; ?></div>

<?php elseif ($tab === 'messages'): ?>
  <div class="panel"><div class="panel-head"><h2>Contact messages</h2></div>
  <?php $msgs = db()->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 60")->fetchAll();
  if (!$msgs): ?><div class="empty">No messages yet.</div><?php else: foreach ($msgs as $m): ?>
    <div class="review-item"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><div><strong><?= h($m['subject']) ?></strong> <span class="pill <?= $m['status'] === 'new' ? 'pill-amber' : 'pill-grey' ?>"><?= h(ucfirst($m['status'])) ?></span><br><span class="hint"><?= h($m['name']) ?> · <a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a> · <?= time_ago($m['created_at']) ?></span></div>
      <?php if ($m['status'] === 'new'): ?><form method="post" style="align-self:center"><?= csrf_field() ?><input type="hidden" name="action" value="message_read"><input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Mark read</button></form><?php endif; ?></div><p style="margin-top:8px;color:var(--muted)"><?= nl2br(h($m['message'])) ?></p></div>
  <?php endforeach; endif; ?></div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
