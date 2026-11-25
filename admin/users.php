<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (($_POST['action'] ?? '') === 'user_toggle') {
        $target = (int)$_POST['user_id'];
        $st = db()->prepare('SELECT role, full_name, status FROM users WHERE id = ?'); $st->execute([$target]); $t = $st->fetch();
        if (!$t || $t['role'] === 'admin') { flash('error', 'Administrators cannot be suspended here.'); }
        else { db()->prepare("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = ? AND role != 'admin'")->execute([$target]); flash('success', $t['full_name'] . ' is now ' . ($t['status'] === 'active' ? 'suspended' : 'active') . '.'); }
    }
    redirect('/admin/users.php' . (isset($_GET['role']) ? '?role=' . urlencode($_GET['role']) : ''));
}
$roleFilter = $_GET['role'] ?? ''; $roleFilter = in_array($roleFilter, ['user', 'provider', 'staff', 'admin'], true) ? $roleFilter : '';
$dashRole = 'admin'; $dashTitle = 'Users & Providers'; $activeTab = 'users';
require_once __DIR__ . '/../includes/dash_top.php';
$sql = "SELECT * FROM users" . ($roleFilter ? " WHERE role = ?" : "") . " ORDER BY created_at DESC LIMIT 200";
$st = db()->prepare($sql); $st->execute($roleFilter ? [$roleFilter] : []); $users = $st->fetchAll();
?>
<div class="panel">
  <div class="panel-head"><h2>All accounts</h2>
    <div class="tabs" style="border:none;margin:0"><?php foreach (['' => 'All', 'user' => 'Customers', 'provider' => 'Providers', 'staff' => 'Staff', 'admin' => 'Admins'] as $k => $lbl): ?><a class="tab<?= $roleFilter === $k ? ' active' : '' ?>" href="/admin/users.php<?= $k ? '?role=' . $k : '' ?>"><?= $lbl ?></a><?php endforeach; ?></div>
  </div>
  <div class="table-wrap"><table class="tbl"><thead><tr><th>Account</th><th>Contact</th><th>Role</th><th>Status</th><th>Action</th></tr></thead><tbody>
    <?php foreach ($users as $s): ?>
      <tr><td><div style="display:flex;gap:10px;align-items:center"><?= avatar_html($s['passport'], $s['full_name'], 44) ?><div><strong><?= h($s['full_name']) ?></strong><br><span class="hint"><?= $s['business_name'] ? h($s['business_name']) . ' · ' : '' ?>Joined <?= date('j M Y', strtotime($s['created_at'])) ?></span></div></div></td>
        <td><?= h($s['email']) ?><br><span class="hint"><?= h($s['phone'] ?: '—') ?></span></td>
        <td><span class="badge-role badge-<?= h($s['role']) ?>"><?= h($s['role']) ?></span></td>
        <td><span class="badge-role <?= $s['status'] === 'active' ? 'badge-ok' : 'badge-suspended' ?>" style="text-transform:capitalize"><?= h($s['status']) ?></span></td>
        <td><?php if ($s['role'] !== 'admin'): ?><form method="post" action="/admin/users.php<?= $roleFilter ? '?role=' . h($roleFilter) : '' ?>"><?= csrf_field() ?><input type="hidden" name="action" value="user_toggle"><input type="hidden" name="user_id" value="<?= (int)$s['id'] ?>"><button class="btn <?= $s['status'] === 'active' ? 'btn-danger' : 'btn-emerald' ?> btn-sm" type="submit" data-confirm="<?= $s['status'] === 'active' ? 'Suspend ' . h($s['full_name']) . '? They will be logged out immediately.' : 'Reactivate ' . h($s['full_name']) . '?' ?>"><?= $s['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button></form><?php else: ?><span class="hint">Protected</span><?php endif; ?></td></tr>
    <?php endforeach; ?>
  </tbody></table></div>
</div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
