<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['listing_id'] ?? 0); $action = $_POST['action'] ?? '';
    if ($action === 'toggle') db()->prepare("UPDATE listings SET status = IF(status = 'suspended', 'active', 'suspended') WHERE id = ?")->execute([$id]);
    elseif ($action === 'delete') db()->prepare('DELETE FROM listings WHERE id = ?')->execute([$id]);
    flash('info', 'Listing updated.'); redirect('/admin/listings.php');
}
$dashRole = 'admin'; $dashTitle = 'Listings Moderation'; $activeTab = 'listings';
require_once __DIR__ . '/../includes/dash_top.php';
$rows = db()->query("SELECT l.*, u.full_name AS provider, u.business_name, c.name AS category_name FROM listings l JOIN users u ON u.id = l.provider_id LEFT JOIN categories c ON c.id = l.category_id ORDER BY l.created_at DESC LIMIT 150")->fetchAll();
?>
<div class="panel"><div class="panel-head"><h2>All listings (<?= count($rows) ?>)</h2></div>
<div class="table-wrap"><table class="tbl"><thead><tr><th>Service</th><th>Provider</th><th>Category</th><th>Status</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($rows as $l): $pn = $l['business_name'] ?: $l['provider']; ?>
    <tr><td><strong><?= h($l['title']) ?></strong><br><span class="hint"><?= h(mb_strimwidth((string)$l['description'], 0, 70, '…')) ?></span></td><td><?= h($pn) ?></td><td><?= h($l['category_name'] ?? '—') ?></td>
      <td><span class="pill <?= $l['status'] === 'active' ? 'pill-green' : ($l['status'] === 'suspended' ? 'pill-red' : 'pill-grey') ?>"><?= h(ucfirst($l['status'])) ?></span></td>
      <td><div class="action-row">
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>"><button class="btn <?= $l['status'] === 'suspended' ? 'btn-emerald' : 'btn-outline' ?> btn-sm" type="submit"><?= $l['status'] === 'suspended' ? 'Restore' : 'Suspend' ?></button></form>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="listing_id" value="<?= (int)$l['id'] ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete this listing permanently?">Delete</button></form>
      </div></td></tr>
  <?php endforeach; ?>
</tbody></table></div></div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
