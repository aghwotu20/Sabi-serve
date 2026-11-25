<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_role(['admin']);

function slugify_skill(string $s): string { $s = strtolower(trim($s)); $s = preg_replace('/[^a-z0-9]+/', '-', $s); return trim($s, '-') ?: 'skill'; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    switch ($action) {
        case 'create':
            $name = trim($_POST['name'] ?? ''); $catId = (int)($_POST['category_id'] ?? 0);
            if ($name === '' || $catId <= 0) { flash('error', 'Skill name and category are required.'); break; }
            $slug = slugify_skill($name); $base = $slug; $i = 2;
            $chk = db()->prepare('SELECT COUNT(*) FROM skills WHERE category_id = ? AND slug = ?');
            while (true) { $chk->execute([$catId, $slug]); if ((int)$chk->fetchColumn() === 0) break; $slug = $base . '-' . $i++; }
            db()->prepare('INSERT INTO skills (category_id, name, slug, sort_order) VALUES (?, ?, ?, (SELECT COALESCE(MAX(sort_order),0) + 1 FROM skills s2 WHERE s2.category_id = ?))')->execute([$catId, mb_substr($name, 0, 150), $slug, $catId]);
            flash('success', 'Skill “' . $name . '” added.');
            break;
        case 'delete':
            db()->prepare('DELETE FROM skills WHERE id = ?')->execute([(int)$_POST['skill_id']]);
            flash('info', 'Skill deleted. Providers who offered it will no longer show it.');
            break;
    }
    redirect('/admin/skills.php' . (isset($_POST['category_id']) ? '?category=' . (int)$_POST['category_id'] : ''));
}
$dashRole = 'admin'; $dashTitle = 'Skills Catalogue'; $activeTab = 'skills';
require_once __DIR__ . '/../includes/dash_top.php';
$categories = db()->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
$counts = db()->query('SELECT c.id, COUNT(s.id) AS n FROM categories c LEFT JOIN skills s ON s.category_id = c.id GROUP BY c.id')->fetchAll();
$countMap = [];
foreach ($counts as $c) { $countMap[(int)$c['id']] = (int)$c['n']; }
$totalSkills = (int) db()->query('SELECT COUNT(*) FROM skills')->fetchColumn();
$selCat = (int)($_GET['category'] ?? 0);
$skills = [];
$selCatName = '';
if ($selCat > 0) {
    $st = db()->prepare('SELECT s.*, (SELECT COUNT(*) FROM provider_skills ps WHERE ps.skill_id = s.id) AS providers FROM skills s WHERE s.category_id = ? ORDER BY s.sort_order, s.name');
    $st->execute([$selCat]); $skills = $st->fetchAll();
    $cn = db()->prepare('SELECT name FROM categories WHERE id = ?'); $cn->execute([$selCat]); $selCatName = (string)$cn->fetchColumn();
}
?>
<div class="grid-2">
  <div class="panel">
    <div class="panel-head"><h2>Categories (<?= $totalSkills ?> skills total)</h2></div>
    <div style="display:flex;flex-direction:column;gap:4px">
      <?php foreach ($categories as $c): ?>
        <a href="/admin/skills.php?category=<?= (int)$c['id'] ?>" style="display:flex;justify-content:space-between;align-items:center;padding:9px 12px;border-radius:10px;<?= $selCat === (int)$c['id'] ? 'background:var(--green-50);border:1px solid var(--green-700);font-weight:700;color:var(--green-950)' : 'border:1px solid transparent;color:var(--ink)' ?>">
          <span><?= h($c['name']) ?></span><span class="pill <?= ($countMap[(int)$c['id']] ?? 0) > 0 ? 'pill-green' : 'pill-grey' ?>"><?= $countMap[(int)$c['id']] ?? 0 ?> skills</span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <div>
    <form class="panel" method="post" action="/admin/skills.php" style="margin-bottom:22px">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <div class="panel-head"><h2>➕ Add a skill</h2></div>
      <div class="form-group" style="margin-bottom:12px"><label class="fl" for="category_id">Category *</label>
        <select class="form-control" id="category_id" name="category_id" required><option value="">Select category…</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= $selCat === (int)$c['id'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group" style="margin-bottom:16px"><label class="fl" for="name">Skill name *</label><input class="form-control" id="name" name="name" required maxlength="150" placeholder="e.g. Solar panel installation"></div>
      <button class="btn btn-amber btn-block" type="submit">Add Skill</button>
    </form>
    <div class="panel">
      <div class="panel-head"><h2><?= $selCat ? h($selCatName) : 'Select a category' ?></h2><?php if ($selCat): ?><span class="pill pill-green"><?= count($skills) ?> skills</span><?php endif; ?></div>
      <?php if (!$selCat): ?>
        <div class="empty">Pick a category on the left to view and manage its skills.</div>
      <?php elseif (!$skills): ?>
        <div class="empty">No skills in this category yet — add the first one above.</div>
      <?php else: ?>
        <div class="table-wrap"><table class="tbl"><thead><tr><th>Skill</th><th>Providers</th><th></th></tr></thead><tbody>
          <?php foreach ($skills as $sk): ?>
            <tr><td><strong><?= h($sk['name']) ?></strong><br><span class="hint"><?= h($sk['slug']) ?></span></td>
              <td><?= (int)$sk['providers'] ?></td>
              <td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="skill_id" value="<?= (int)$sk['id'] ?>"><input type="hidden" name="category_id" value="<?= $selCat ?>"><button class="btn btn-danger btn-sm" type="submit" data-confirm="Delete skill “<?= h($sk['name']) ?>”?">Delete</button></form></td></tr>
          <?php endforeach; ?>
        </tbody></table></div>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/dash_bottom.php'; ?>
