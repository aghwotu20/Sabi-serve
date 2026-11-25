<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['provider']);
$uid = (int)$u['id'];
$tab = $_GET['tab'] ?? 'overview'; $tab = in_array($tab, ['overview', 'listings', 'enquiries'], true) ? $tab : 'overview';
$errors = [];

// ---------- POST actions ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    require_active_subscription($u);
    switch ($action) {
        case 'listing_create':
            $title = trim($_POST['title'] ?? ''); $desc = trim($_POST['description'] ?? ''); $categoryId = (int)($_POST['category_id'] ?? 0);
            $location = trim($_POST['location'] ?? ''); $price = trim($_POST['price_hint'] ?? '');
            if ($title === '' || $categoryId <= 0) { flash('error', 'Please provide a title and category.'); break; }
            db()->prepare('INSERT INTO listings (provider_id, category_id, title, description, location, price_hint, status) VALUES (?, ?, ?, ?, ?, ?, \'active\')')
                ->execute([$uid, $categoryId, mb_substr($title, 0, 180), $desc, mb_substr($location, 0, 120), mb_substr($price, 0, 120)]);
            flash('success', 'Listing published. Customers can now see it.');
            break;
        case 'listing_pause':
            db()->prepare("UPDATE listings SET status = IF(status = 'active', 'paused', 'active') WHERE id = ? AND provider_id = ?")->execute([(int)$_POST['listing_id'], $uid]);
            flash('info', 'Listing visibility updated.');
            break;
        case 'listing_delete':
            db()->prepare('DELETE FROM listings WHERE id = ? AND provider_id = ?')->execute([(int)$_POST['listing_id'], $uid]);
            flash('info', 'Listing deleted.');
            break;
        case 'enquiry_reply':
            $eid = (int)($_POST['enquiry_id'] ?? 0); $reply = trim($_POST['provider_reply'] ?? '');
            if ($reply === '') { flash('error', 'Please write a reply.'); break; }
            db()->prepare("UPDATE enquiries SET provider_reply = ?, status = 'responded', updated_at = NOW() WHERE id = ? AND provider_id = ?")->execute([$reply, $eid, $uid]);
            flash('success', 'Reply sent. The customer will see it in their dashboard.');
            break;
        case 'enquiry_close':
            db()->prepare("UPDATE enquiries SET status = 'closed', updated_at = NOW() WHERE id = ? AND provider_id = ?")->execute([(int)$_POST['enquiry_id'], $uid]);
            flash('info', 'Enquiry closed.');
            break;
    }
    redirect('/provider/index.php?tab=' . $tab);
}

$dashRole = 'provider'; $dashTitle = match ($tab) { 'listings' => 'My Listings', 'enquiries' => 'Customer Enquiries', default => 'Provider Dashboard' }; $activeTab = $tab;
require_once __DIR__ . '/../includes/dash_top.php';

$sub = active_subscription($uid);
$stats = [
    'listings' => (int) db()->query("SELECT COUNT(*) FROM listings WHERE provider_id = $uid AND status = 'active'")->fetchColumn(),
    'enquiries' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE provider_id = $uid")->fetchColumn(),
    'new' => (int) db()->query("SELECT COUNT(*) FROM enquiries WHERE provider_id = $uid AND status = 'pending'")->fetchColumn(),
    'rating' => (float) db()->query("SELECT COALESCE(AVG(rating),0) FROM reviews WHERE provider_id = $uid AND status='visible'")->fetchColumn(),
    'review_count' => (int) db()->query("SELECT COUNT(*) FROM reviews WHERE provider_id = $uid AND status='visible'")->fetchColumn(),
];
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
?>
<div class="sub-banner <?= $sub ? 'ok' : 'warn' ?>">
  <div><strong><?= $sub ? 'Subscription active — you are visible to customers' : 'Activate your ₦2,000/month subscription' ?></strong><br><span class="hint"><?= $sub ? 'Renews ' . date('j F Y', strtotime($sub['ends_at'])) : 'You will not receive enquiries until you subscribe.' ?></span></div>
  <?php if (!$sub): ?><a class="btn btn-amber" href="/subscribe.php">Subscribe Now</a><?php else: ?><a class="btn btn-emerald btn-sm" href="/subscribe.php">Renew Early</a><?php endif; ?>
</div>

<?php if ($tab === 'overview'): ?>
  <div class="stat-cards">
    <div class="stat-card"><div class="k">Active Listings</div><div class="v"><?= $stats['listings'] ?></div><div class="d"><a href="/provider/index.php?tab=listings">Manage listings →</a></div></div>
    <div class="stat-card"><div class="k">New Enquiries</div><div class="v"><?= $stats['new'] ?></div><div class="d"><a href="/provider/index.php?tab=enquiries">Respond now →</a></div></div>
    <div class="stat-card"><div class="k">Total Enquiries</div><div class="v"><?= $stats['enquiries'] ?></div><div class="d">Lifetime requests</div></div>
    <div class="stat-card"><div class="k">Your Rating</div><div class="v"><?= number_format($stats['rating'], 1) ?> ★</div><div class="d"><?= $stats['review_count'] ?> visible reviews</div></div>
  </div>
  <div class="panel">
    <div class="panel-head"><h2>How to win more customers</h2></div>
    <ul style="margin-left:20px;color:var(--muted)"><li>Complete your profile — a passport photo builds 3× more trust.</li><li>Reply to enquiries within 2 hours; SabiServe boosts responsive providers.</li><li>Ask happy customers to leave a review — ratings drive ranking.</li></ul>
  </div>

<?php elseif ($tab === 'listings'): ?>
  <div class="grid-2">
    <div class="panel">
      <div class="panel-head"><h2>Your listings</h2></div>
      <?php $st = db()->prepare("SELECT l.*, c.name AS category_name FROM listings l LEFT JOIN categories c ON c.id = l.category_id WHERE l.provider_id = ? ORDER BY l.created_at DESC"); $st->execute([$uid]); $rows = $st->fetchAll();
      if (!$rows): ?><div class="empty">No listings yet — publish your first service.</div><?php else: ?>
      <div class="table-wrap"><table class="tbl"><thead><tr><th>Service</th><th>Price</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        <?php foreach ($rows as $l): ?>
          <tr><td><strong><?= h($l['title']) ?></strong><br><span class="hint"><?= h($l['category_name'] ?? '') ?></span></td><td><?= h($l['price_hint'] ?: 'On request') ?></td><td><span class="pill <?= $l['status'] === 'active' ? 'pill-green' : ($l['status'] === 'paused' ? 'pill-grey' : 'pill-red') ?>"><?= h(ucfirst($l['status'])) ?></span></td>
            <td><div class="action-row">
              <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="listing_pause"><input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $l['status'] === 'active' ? 'Pause' : 'Activate' ?></button></form>
              <form method="post" data-confirm-form><?= csrf_field() ?><input type="hidden" name="action" value="listing_delete"><input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete this listing?">Delete</button></form>
            </div></td></tr>
        <?php endforeach; ?>
      </tbody></table></div><?php endif; ?>
    </div>
    <form class="panel" method="post" action="/provider/index.php?tab=listings">
      <?= csrf_field() ?><input type="hidden" name="action" value="listing_create">
      <div class="panel-head"><h2>Publish a service</h2></div>
      <div class="form-group" style="margin-bottom:12px"><label class="fl" for="title">Service title *</label><input class="form-control" id="title" name="title" required maxlength="180" placeholder="e.g. Same-day pipe leak repairs"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="fl" for="category_id">Category *</label><select class="form-control" id="category_id" name="category_id" required><option value="">Select…</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group" style="margin-bottom:12px"><label class="fl" for="price_hint">Price guide</label><input class="form-control" id="price_hint" name="price_hint" placeholder="e.g. From ₦15,000 / visit"></div>
      <div class="form-group" style="margin-bottom:12px"><label class="fl" for="location">Service areas</label><input class="form-control" id="location" name="location" placeholder="e.g. Lekki, Ajah, VI" value="<?= h($u['location']) ?>"></div>
      <div class="form-group" style="margin-bottom:16px"><label class="fl" for="description">Describe the service</label><textarea class="form-control" id="description" name="description" maxlength="1200" placeholder="What’s included, response time, tools, experience…"></textarea></div>
      <button class="btn btn-emerald btn-block" type="submit">Publish Listing</button>
    </form>
  </div>

<?php elseif ($tab === 'enquiries'): ?>
  <div class="panel">
    <div class="panel-head"><h2>Customer enquiries</h2><span class="pill pill-amber"><?= $stats['new'] ?> awaiting you</span></div>
    <?php $st = db()->prepare("SELECT e.*, u.full_name AS customer, u.phone FROM enquiries e JOIN users u ON u.id = e.user_id WHERE e.provider_id = ? ORDER BY e.created_at DESC"); $st->execute([$uid]); $rows = $st->fetchAll();
    if (!$rows): ?><div class="empty">No enquiries yet. Once customers reach out, you’ll answer right here.</div><?php else: foreach ($rows as $e): ?>
      <div class="review-item">
        <div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap"><div><strong><?= h($e['subject']) ?></strong><br><span class="hint">From <?= h($e['customer']) ?><?= $e['phone'] ? ' · ' . h($e['phone']) : '' ?> · <?= time_ago($e['created_at']) ?></span></div><span class="pill <?= $e['status'] === 'responded' ? 'pill-green' : ($e['status'] === 'closed' ? 'pill-grey' : 'pill-amber') ?>"><?= h(ucfirst($e['status'])) ?></span></div>
        <p style="margin:10px 0;color:var(--muted)"><?= nl2br(h($e['message'])) ?></p>
        <?php if ($e['provider_reply']): ?><div style="background:var(--green-50);border:1px solid var(--line);border-radius:12px;padding:10px 14px;margin-bottom:10px"><span class="hint"><strong>Your reply:</strong></span><br><?= nl2br(h($e['provider_reply'])) ?></div><?php endif; ?>
        <?php if ($e['status'] !== 'closed'): ?>
        <form method="post" action="/provider/index.php?tab=enquiries" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
          <?= csrf_field() ?><input type="hidden" name="enquiry_id" value="<?= (int)$e['id'] ?>">
          <div class="form-group" style="flex:1;min-width:240px"><label class="fl" for="reply_<?= (int)$e['id'] ?>">Your reply / quote</label><input class="form-control" id="reply_<?= (int)$e['id'] ?>" name="provider_reply" placeholder="I can come by tomorrow at 10am — quote ₦25,000…" required></div>
          <button class="btn btn-emerald" name="action" value="enquiry_reply" type="submit">Send Reply</button>
          <button class="btn btn-outline" name="action" value="enquiry_close" type="submit">Close</button>
        </form>
        <?php endif; ?>
      </div>
    <?php endforeach; endif; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
