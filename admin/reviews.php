<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['review_id'] ?? 0); $action = $_POST['action'] ?? '';
    if ($action === 'toggle') db()->prepare("UPDATE reviews SET status = IF(status = 'visible', 'hidden', 'visible') WHERE id = ?")->execute([$id]);
    elseif ($action === 'delete') db()->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
    flash('info', 'Review updated.'); redirect('/admin/reviews.php');
}
$dashRole = 'admin'; $dashTitle = 'Reviews Moderation'; $activeTab = 'reviews';
require_once __DIR__ . '/../includes/dash_top.php';
$rows = db()->query("SELECT r.*, u.full_name AS reviewer, p.full_name AS provider, p.business_name FROM reviews r JOIN users u ON u.id = r.user_id JOIN users p ON p.id = r.provider_id ORDER BY r.created_at DESC LIMIT 100")->fetchAll();
?>
<div class="panel"><div class="panel-head"><h2>All reviews (<?= count($rows) ?>)</h2></div>
<?php foreach ($rows as $r): $pn = $r['business_name'] ?: $r['provider']; ?>
  <div class="review-item"><div style="display:flex;justify-content:space-between;gap:14px;flex-wrap:wrap"><div><strong><?= h($pn) ?></strong> <span class="hint">reviewed by <?= h($r['reviewer']) ?> · <?= time_ago($r['created_at']) ?></span><br><span class="stars" style="margin:6px 0"><?= star_rating((float)$r['rating']) ?></span><p style="color:var(--muted);max-width:640px"><?= nl2br(h($r['comment'])) ?></p><span class="pill <?= $r['status'] === 'visible' ? 'pill-green' : 'pill-red' ?>"><?= h(ucfirst($r['status'])) ?></span></div>
    <div class="action-row" style="align-self:center">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>"><button class="btn <?= $r['status'] === 'visible' ? 'btn-outline' : 'btn-emerald' ?> btn-sm" type="submit"><?= $r['status'] === 'visible' ? 'Hide' : 'Restore' ?></button></form>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="review_id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete this review permanently?">Delete</button></form>
    </div></div></div>
<?php endforeach; ?></div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
