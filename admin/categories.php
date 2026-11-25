<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);

function slugify(string $s): string { $s = strtolower(trim($s)); $s = preg_replace('/[^a-z0-9]+/', '-', $s); return trim($s, '-') ?: 'category'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? ''); $icon = trim($_POST['icon'] ?? 'wrench'); $desc = trim($_POST['description'] ?? '');
            if ($name === '') { flash('error', 'Category name is required.'); break; }
            $slug = slugify($name); $base = $slug; $i = 2;
            $chk = db()->prepare('SELECT COUNT(*) FROM categories WHERE slug = ?');
            while (true) { $chk->execute([$slug]); if ((int)$chk->fetchColumn() === 0) break; $slug = $base . '-' . $i++; }
            db()->prepare('INSERT INTO categories (name, slug, icon, description, sort_order) VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order),0) + 1 FROM categories c2))')->execute([mb_substr($name, 0, 120), $slug, mb_substr($icon, 0, 40), mb_substr($desc, 0, 255)]);
            flash('success', 'Category “' . $name . '” created.');
            break;
        case 'update':
            db()->prepare('UPDATE categories SET name = ?, icon = ?, description = ? WHERE id = ?')->execute([mb_substr(trim($_POST['name'] ?? ''), 0, 120), mb_substr(trim($_POST['icon'] ?? 'wrench'), 0, 40), mb_substr(trim($_POST['description'] ?? ''), 0, 255), (int)$_POST['category_id']]);
            flash('success', 'Category updated.');
            break;
        case 'delete':
            $id = (int)$_POST['category_id'];
            $cntStmt = db()->prepare('SELECT COUNT(*) FROM users WHERE category_id = ?'); $cntStmt->execute([$id]); $cnt = (int)$cntStmt->fetchColumn();
            if ($cnt > 0) { flash('error', 'Cannot delete — ' . $cnt . ' provider(s) are in this category. Reassign them first.'); break; }
            db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('info', 'Category deleted.');
            break;
    }
    redirect('/admin/categories.php');
}
$dashRole = 'admin'; $dashTitle = 'Service Categories'; $activeTab = 'categories';
require_once __DIR__ . '/../includes/dash_top.php';
$categories = db()->query("SELECT c.*, (SELECT COUNT(*) FROM users u WHERE u.category_id = c.id AND u.role = 'provider') AS providers FROM categories c ORDER BY c.sort_order, c.name")->fetchAll();
$iconOptions = ['wrench','bolt','sparkles','hammer','paint','snow','scissors','pot','camera','comb','car','book','party','code','shield','truck'];
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>Categories (<?= count($categories) ?>)</h2></div>
    <div class="table-wrap"><table class="tbl"><thead><tr><th>Icon</th><th>Category</th><th>Providers</th><th></th></tr></thead><tbody>
      <?php foreach ($categories as $c): ?>
        <tr><td style="color:var(--green-800)"><?= category_icon($c['icon'], 26) ?></td>
          <td><strong><?= h($c['name']) ?></strong><br><span class="hint"><?= h($c['slug']) ?></span></td>
          <td><?= (int)$c['providers'] ?></td>
          <td><div class="action-row">
            <button class="btn btn-outline btn-sm" type="button" onclick="var f=document.getElementById('edit-<?= (int)$c['id'] ?>');f.style.display=f.style.display==='none'?'table-row':'none'">Edit</button>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete “<?= h($c['name']) ?>”?">Delete</button></form>
          </div></td></tr>
        <tr id="edit-<?= (int)$c['id'] ?>" style="display:none"><td colspan="4"><form method="post" style="display:flex;gap:10px;flex-wrap:wrap;align-items:end;padding:10px;background:var(--green-50);border-radius:12px"><?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="category_id" value="<?= (int)$c['id'] ?>"><div class="form-group"><label class="fl">Name</label><input class="form-control" name="name" value="<?= h($c['name']) ?>" required></div><div class="form-group"><label class="fl">Icon</label><select class="form-control" name="icon"><?php foreach ($iconOptions as $ic): ?><option value="<?= $ic ?>" <?= $c['icon'] === $ic ? 'selected' : '' ?>><?= $ic ?></option><?php endforeach; ?></select></div><div class="form-group" style="flex:1;min-width:200px"><label class="fl">Description</label><input class="form-control" name="description" value="<?= h($c['description']) ?>"></div><button class="btn btn-emerald btn-sm" type="submit">Save</button></form></td></tr>
      <?php endforeach; ?>
    </tbody></table></div>
  </div>
  <form class="panel" method="post" action="/admin/categories.php">
    <?= csrf_field() ?><input type="hidden" name="action" value="create">
    <div class="panel-head"><h2>➕ New category</h2></div>
    <div class="form-group" style="margin-bottom:12px"><label class="fl" for="name">Name *</label><input class="form-control" id="name" name="name" required placeholder="e.g. Solar &amp; Renewable Energy"></div>
    <div class="form-group" style="margin-bottom:12px"><label class="fl" for="icon">Icon</label><select class="form-control" id="icon" name="icon"><?php foreach ($iconOptions as $ic): ?><option value="<?= $ic ?>"><?= ucfirst($ic) ?></option><?php endforeach; ?></select></div>
    <div class="form-group" style="margin-bottom:16px"><label class="fl" for="description">Short description</label><textarea class="form-control" id="description" name="description" maxlength="255" placeholder="Installation, maintenance and sales of solar systems…"></textarea></div>
    <button class="btn btn-amber btn-block" type="submit">Add Category</button>
  </form>
</div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
