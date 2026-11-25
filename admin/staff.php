<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'staff_create':
            $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
            $phone = trim($_POST['phone'] ?? ''); $pw = $_POST['password'] ?? '';
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pw) < 8) {
                flash('error', 'Provide a name, valid email and a password of at least 8 characters.'); break;
            }
            $exists = db()->prepare('SELECT id FROM users WHERE email = ?'); $exists->execute([$email]);
            if ($exists->fetchColumn()) { flash('error', 'That email is already registered.'); break; }
            db()->prepare('INSERT INTO users (role, full_name, email, phone, password_hash, status) VALUES (\'staff\', ?, ?, ?, ?, \'active\')')
                ->execute([mb_substr($name, 0, 150), $email, mb_substr($phone, 0, 30), password_hash($pw, PASSWORD_DEFAULT)]);
            flash('success', 'Staff account created for ' . $name . '. They can log in immediately.');
            break;
        case 'staff_toggle':
            $target = (int)($_POST['staff_id'] ?? 0);
            if ($target === (int)$u['id']) { flash('error', 'You cannot suspend your own account.'); break; }
            $st = db()->prepare("SELECT role, full_name, status FROM users WHERE id = ?"); $st->execute([$target]); $t = $st->fetch();
            if (!$t || $t['role'] !== 'staff') { flash('error', 'That is not a staff account.'); break; }
            db()->prepare("UPDATE users SET status = IF(status = 'active', 'suspended', 'active') WHERE id = ? AND role = 'staff'")->execute([$target]);
            flash('success', $t['full_name'] . ($t['status'] === 'active' ? ' has been suspended. They can no longer log in.' : ' has been reactivated.'));
            break;
        case 'staff_delete':
            $target = (int)($_POST['staff_id'] ?? 0);
            if ($target === (int)$u['id']) { flash('error', 'You cannot delete your own account.'); break; }
            db()->prepare("DELETE FROM users WHERE id = ? AND role = 'staff'")->execute([$target]);
            flash('info', 'Staff account deleted.');
            break;
    }
    redirect('/admin/staff.php');
}

$dashRole = 'admin'; $dashTitle = 'Staff Management'; $activeTab = 'staff';
require_once __DIR__ . '/../includes/dash_top.php';
$staff = db()->query("SELECT * FROM users WHERE role = 'staff' ORDER BY created_at DESC")->fetchAll();
$admins = db()->query("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC")->fetchAll();
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>Staff accounts (<?= count($staff) ?>)</h2><span class="hint">Suspend instantly — access is revoked on their next request</span></div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Staff member</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead><tbody>
      <?php if (!$staff): ?><tr><td colspan="4"><div class="empty">No staff yet — create the first one on the right.</div></td></tr><?php endif; ?>
      <?php foreach ($staff as $s): ?>
        <tr><td><div style="display:flex;gap:10px;align-items:center"><?= avatar_html($s['passport'], $s['full_name'], 44) ?><div><strong><?= h($s['full_name']) ?></strong><br><span class="hint"><?= h($s['email']) ?><?= $s['phone'] ? ' · ' . h($s['phone']) : '' ?></span></div></div></td>
          <td><?= date('j M Y', strtotime($s['created_at'])) ?></td>
          <td><span class="badge-role <?= $s['status'] === 'active' ? 'badge-ok' : 'badge-suspended' ?>" style="text-transform:capitalize"><?= h($s['status']) ?></span></td>
          <td><div class="action-row">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="staff_toggle"><input type="hidden" name="staff_id" value="<?= (int)$s['id'] ?>"><button class="btn <?= $s['status'] === 'active' ? 'btn-danger' : 'btn-emerald' ?> btn-sm" type="submit" data-confirm="<?= $s['status'] === 'active' ? 'Suspend ' . h($s['full_name']) . '? They will be logged out within seconds.' : 'Reactivate ' . h($s['full_name']) . '?' ?>"><?= $s['status'] === 'active' ? 'Suspend' : 'Reactivate' ?></button></form>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="staff_delete"><input type="hidden" name="staff_id" value="<?= (int)$s['id'] ?>"><button class="btn btn-outline btn-sm" type="submit" data-confirm="Permanently delete this staff account?">Delete</button></form>
          </div></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
    <p class="hint" style="margin-top:12px">Administrators: <?= h(implode(', ', array_column($admins, 'full_name'))) ?></p>
  </div>
  <form class="panel" method="post" action="/admin/staff.php">
    <?= csrf_field() ?><input type="hidden" name="action" value="staff_create">
    <div class="panel-head"><h2>➕ Add staff member</h2></div>
    <div class="form-group" style="margin-bottom:12px"><label class="fl" for="full_name">Full name *</label><input class="form-control" id="full_name" name="full_name" required placeholder="Yetunde Balogun"></div>
    <div class="form-group" style="margin-bottom:12px"><label class="fl" for="email">Work email *</label><input class="form-control" id="email" name="email" type="email" required placeholder="yetunde@sabiserve.com.ng"></div>
    <div class="form-group" style="margin-bottom:12px"><label class="fl" for="phone">Phone / WhatsApp</label><input class="form-control" id="phone" name="phone" placeholder="0803 000 0000"></div>
    <div class="form-group" style="margin-bottom:16px"><label class="fl" for="password">Temporary password * <span class="hint">(min 8 chars — ask them to change it)</span></label><input class="form-control" id="password" name="password" type="text" required minlength="8" placeholder="e.g. Welcome2026!"></div>
    <button class="btn btn-amber btn-block" type="submit">Create Staff Account</button>
    <p class="hint" style="margin-top:12px">Staff can moderate listings, reviews and messages — but cannot change settings, suspend staff, or see payment keys.</p>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
