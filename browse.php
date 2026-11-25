<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Browse Service Providers in Nigeria — SabiServe';
require_once __DIR__ . '/includes/public_header.php';

$q = trim($_GET['q'] ?? '');
$category = trim($_GET['category'] ?? '');
$location = trim($_GET['location'] ?? '');
$sort = $_GET['sort'] ?? 'rating';

$where = ["u.role = 'provider'", "u.status = 'active'"];
$params = [];
if ($q !== '') { $where[] = '(u.full_name LIKE ? OR u.business_name LIKE ? OR u.bio LIKE ? OR EXISTS (SELECT 1 FROM listings l WHERE l.provider_id = u.id AND (l.title LIKE ? OR l.description LIKE ?)))'; $like = '%' . $q . '%'; array_push($params, $like, $like, $like, $like, $like); }
if ($category !== '') { $where[] = 'c.slug = ?'; $params[] = $category; }
if ($location !== '') { $where[] = 'u.location LIKE ?'; $params[] = '%' . $location . '%'; }
$orderBy = match ($sort) { 'newest' => 'u.created_at DESC', 'name' => 'COALESCE(u.business_name, u.full_name) ASC', default => 'avg_rating DESC, review_count DESC' };

$sql = "SELECT u.id, u.full_name, u.business_name, u.passport, u.location, u.bio, u.created_at, c.name AS category_name, c.slug AS category_slug, COALESCE(AVG(r.rating),0) AS avg_rating, COUNT(r.id) AS review_count FROM users u LEFT JOIN categories c ON c.id = u.category_id LEFT JOIN reviews r ON r.provider_id = u.id AND r.status = 'visible' WHERE " . implode(' AND ', $where) . " GROUP BY u.id ORDER BY {$orderBy} LIMIT 60";
$st = db()->prepare($sql); $st->execute($params); $providers = $st->fetchAll();
$categories = db()->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();
$locations = ['Lagos','Abuja (FCT)','Port Harcourt','Ibadan','Kano','Enugu','Benin City','Kaduna','Owerri','Abeokuta'];
?>
<section class="cat-hero">
  <div class="container">
    <nav class="breadcrumb" aria-label="Breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / Find Services</nav>
    <h1>Explore verified service providers</h1>
    <p style="color:#A7F3D0;margin-top:8px;max-width:560px">Search real profiles, compare ratings, and send an enquiry in minutes — all trusted, all Nigerian.</p>
  </div>
</section>
<section class="section" style="padding-top:34px">
  <div class="container">
    <form class="toolbar" method="get" action="<?= base_url('browse.php') ?>" role="search">
      <input class="form-control" type="text" name="q" placeholder="Keyword e.g. plumber…" value="<?= h($q) ?>" aria-label="Keyword">
      <select class="form-control" name="category" aria-label="Category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= h($c['slug']) ?>" <?= $category === $c['slug'] ? 'selected' : '' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select>
      <select class="form-control" name="location" aria-label="Location"><option value="">All Nigeria</option><?php foreach ($locations as $loc): ?><option value="<?= h($loc) ?>" <?= $location === $loc ? 'selected' : '' ?>><?= h($loc) ?></option><?php endforeach; ?></select>
      <select class="form-control" name="sort" aria-label="Sort"><option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Sort: Top rated</option><option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort: Newest</option><option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Sort: Name A–Z</option></select>
      <button class="btn btn-emerald" type="submit"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg> Search</button>
      <?php if ($q !== '' || $category !== '' || $location !== ''): ?><a class="btn btn-outline" href="<?= base_url('browse.php') ?>">Clear</a><?php endif; ?>
    </form>

    <div class="search-results-bar"><span><strong style="color:var(--green-950)"><?= count($providers) ?></strong> provider<?= count($providers) === 1 ? '' : 's' ?> found<?= $category ? ' in “' . h($category) . '”' : '' ?><?= $location ? ' · ' . h($location) : '' ?></span><span>Showing active, verified listings</span></div>

    <?php if (!$providers): ?>
      <div class="empty"><h3 style="margin-bottom:8px">No providers match that yet</h3><p>Try a different keyword or location — or <a href="<?= base_url('register.php?role=provider') ?>"><strong>become the first provider</strong></a> in this niche.</p></div>
    <?php else: ?>
      <div class="p-grid">
        <?php foreach ($providers as $p): $pname = $p['business_name'] ?: $p['full_name']; ?>
          <article class="p-card">
            <div class="p-card-top"><?= avatar_html($p['passport'], $pname, 76, 'p-avatar') ?>
              <div><div class="p-name"><?= h($pname) ?></div><div class="p-cat"><?= h($p['category_name'] ?? 'General Services') ?></div><div class="p-loc"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg><?= h($p['location'] ?: 'Nigeria') ?></div></div>
            </div>
            <div class="p-body"><p class="p-bio"><?= h($p['bio'] ?: 'Verified SabiServe provider. Send an enquiry for quotes, availability and pricing.') ?></p></div>
            <div class="p-foot"><div class="p-rating"><span class="stars"><?= star_rating((float)$p['avg_rating']) ?></span><strong style="color:var(--green-950)"><?= number_format((float)$p['avg_rating'], 1) ?></strong>(<?= (int)$p['review_count'] ?>)</div><a class="btn btn-ghost btn-sm" href="<?= base_url('provider.php?id=' . (int)$p['id']) ?>">View Profile</a></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
