<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['message_id'] ?? 0); $action = $_POST['action'] ?? '';
    if ($action === 'read') db()->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?")->execute([$id]);
    elseif ($action === 'delete') db()->prepare('DELETE FROM contact_messages WHERE id = ?')->execute([$id]);
    flash('info', 'Message updated.'); redirect('/admin/messages.php');
}
$dashRole = 'admin'; $dashTitle = 'Contact Messages'; $activeTab = 'messages';
require_once __DIR__ . '/../includes/dash_top.php';
$rows = db()->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 100")->fetchAll();
?>
<div class="panel"><div class="panel-head"><h2>Inbox (<?= count($rows) ?>)</h2></div>
<?php if (!$rows): ?><div class="empty">No messages yet.</div><?php endif; ?>
<?php foreach ($rows as $m): ?>
  <div class="review-item"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><div><strong><?= h($m['subject']) ?></strong> <span class="pill <?= $m['status'] === 'new' ? 'pill-amber' : 'pill-grey' ?>"><?= h(ucfirst($m['status'])) ?></span><br><span class="hint"><?= h($m['name']) ?> · <a href="mailto:<?= h($m['email']) ?>"><?= h($m['email']) ?></a> · <?= time_ago($m['created_at']) ?></span></div>
    <div class="action-row" style="align-self:center">
      <?php if ($m['status'] === 'new'): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="read"><input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-outline btn-sm" type="submit">Mark read</button></form><?php endif; ?>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete this message?">Delete</button></form>
    </div></div><p style="margin-top:8px;color:var(--muted)"><?= nl2br(h($m['message'])) ?></p></div>
<?php endforeach; ?></div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
