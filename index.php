<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'SabiServe — Find Trusted Service Providers in Nigeria';
$metaDesc = 'Plumbers, electricians, cleaners, tailors, tutors, caterers, photographers and more. Verified Nigerian service providers, real reviews, secure bookings. Join SabiServe today.';
require_once __DIR__ . '/includes/public_header.php';

$categories = db()->query("SELECT c.*, (SELECT COUNT(*) FROM users u WHERE u.category_id = c.id AND u.role = 'provider' AND u.status = 'active') AS provider_count FROM categories c ORDER BY c.sort_order, c.name LIMIT 24")->fetchAll();
$popularSkillSlugs = ['pipe-repairs','solar-panel-installation','dstv-installation','cctv-camera-installation','pop-ceiling-installation','borehole-drilling','generator-repairs','ac-servicing','house-wiring','floor-tiling','gas-cooker-installation','car-mechanic'];
$popularSkills = [];
if ($popularSkillSlugs) {
    $ph = implode(',', array_fill(0, count($popularSkillSlugs), '?'));
    $psStmt = db()->prepare("SELECT name, slug FROM skills WHERE slug IN ($ph)");
    $psStmt->execute($popularSkillSlugs);
    $bySlug = [];
    foreach ($psStmt->fetchAll() as $row) { $bySlug[$row['slug']] = $row; }
    foreach ($popularSkillSlugs as $slug) { if (isset($bySlug[$slug])) $popularSkills[] = $bySlug[$slug]; }
}
$featured = db()->query("SELECT u.id, u.full_name, u.business_name, u.passport, u.location, u.bio, u.category_id, c.name AS category_name, COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count FROM users u LEFT JOIN categories c ON c.id = u.category_id LEFT JOIN reviews r ON r.provider_id = u.id AND r.status = 'visible' WHERE u.role = 'provider' AND u.status = 'active' GROUP BY u.id ORDER BY avg_rating DESC, review_count DESC LIMIT 6")->fetchAll();
$stats = [
    'providers' => (int) db()->query("SELECT COUNT(*) FROM users WHERE role='provider' AND status='active'")->fetchColumn(),
    'enquiries' => (int) db()->query("SELECT COUNT(*) FROM enquiries")->fetchColumn(),
    'categories' => (int) db()->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'reviews'    => (int) db()->query("SELECT COUNT(*) FROM reviews WHERE status='visible'")->fetchColumn(),
];
function pname(array $p): string { return $p['business_name'] ?: $p['full_name']; }
?>
<!-- HERO -->
<section class="hero">
  <div class="container">
    <div>
      <span class="hero-badge"><svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.9 5.9 6.5.9-4.7 4.6 1.1 6.5L12 17.4 6.2 20l1.1-6.5L2.6 8.8l6.5-.9L12 2z"/></svg> Nigeria’s Trusted Services Marketplace</span>
      <h1>Find <span class="accent">trusted hands</span><br>for every job,<br>anywhere in Nigeria.</h1>
      <p class="lead">From leaking pipes in Surulere to last-minute caterers in Abuja — discover verified, reviewed, and reliable service providers in minutes.</p>
      <div class="hero-ctas">
        <a href="<?= base_url('browse.php') ?>" class="btn btn-amber">Find a Service <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
        <a href="<?= base_url('register.php?role=provider') ?>" class="btn btn-white">Offer a Service</a>
      </div>
      <form class="searchbar" action="<?= base_url('browse.php') ?>" method="get" role="search">
        <div class="field"><label for="q">What do you need?</label><input id="q" name="q" type="text" placeholder="e.g. plumber, tailor, tutor…" autocomplete="off"></div>
        <div class="field"><label for="category">Category</label><select id="category" name="category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= h($c['slug']) ?>"><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label for="location">Location</label><select id="location" name="location"><option value="">All Nigeria</option><?php foreach (['Lagos','Abuja (FCT)','Port Harcourt','Ibadan','Kano','Enugu','Benin City','Kaduna','Owerri','Abeokuta'] as $loc): ?><option value="<?= h($loc) ?>"><?= h($loc) ?></option><?php endforeach; ?></select></div>
        <button type="submit" aria-label="Search providers"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg> Search</button>
      </form>
      <div class="quick-tags">Popular: <?php foreach (array_slice($categories, 0, 4) as $c): ?><a href="<?= base_url('browse.php?category=' . urlencode($c['slug'])) ?>"><?= h($c['name']) ?></a><?php endforeach; ?></div>
    </div>
    <div class="hero-art">
      <img src="/assets/img/hero-illustration.jpg" alt="Illustration of Nigerian service professionals standing together" loading="eager">
      <div class="hero-float" aria-hidden="true">
        <div class="ic"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
        <div><small>Just completed</small><strong>Electrical repair · Lekki</strong></div>
      </div>
    </div>
  </div>
</section>

<!-- STATS STRIP -->
<div class="stats-strip">
  <div class="container">
    <div><div class="stat-num"><span data-count="<?= max($stats['providers'], 120) ?>">0</span>+</div><div class="stat-lbl">Verified Providers</div></div>
    <div><div class="stat-num"><span data-count="<?= max($stats['enquiries'], 480) ?>">0</span>+</div><div class="stat-lbl">Enquiries Completed</div></div>
    <div><div class="stat-num"><span data-count="<?= $stats['categories'] ?>">0</span></div><div class="stat-lbl">Service Categories</div></div>
    <div><div class="stat-num"><span data-count="<?= max($stats['reviews'], 350) ?>">0</span>+</div><div class="stat-lbl">Happy Reviews</div></div>
  </div>
</div>

<!-- CATEGORIES -->
<section class="section" id="categories">
  <div class="container">
    <div class="section-head">
      <div><span class="eyebrow">Browse by Category</span><h2 class="section-title">Every service, one search away</h2></div>
      <a class="btn btn-outline btn-sm" href="<?= base_url('browse.php') ?>">View all services <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M9 6l6 6-6 6"/></svg></a>
    </div>
    <div class="cat-grid">
      <?php foreach ($categories as $c): ?>
        <a class="cat-chip" href="<?= base_url('browse.php?category=' . urlencode($c['slug'])) ?>"><span class="ic"><?= category_icon($c['icon']) ?></span><span class="t"><?= h($c['name']) ?></span></a>
      <?php endforeach; ?>
    </div>
    <?php if ($popularSkills): ?>
    <div style="margin-top:26px">
      <div class="eyebrow" style="margin-bottom:10px">Popular skills</div>
      <div class="skill-chip-row">
        <?php foreach ($popularSkills as $sk): ?><a class="skill-chip" href="<?= base_url('browse.php?skill=' . urlencode($sk['slug'])) ?>"><?= h($sk['name']) ?></a><?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- FEATURED PROVIDERS -->
<section class="section" style="background:#fff;border-top:1px solid var(--line);border-bottom:1px solid var(--line)">
  <div class="container">
    <div class="section-head">
      <div><span class="eyebrow">Featured Providers</span><h2 class="section-title">Top-rated hands, ready to work</h2><p class="section-sub">Real reviews from real customers. Every provider is verified by the SabiServe team before going live.</p></div>
      <a class="btn btn-emerald btn-sm" href="<?= base_url('browse.php') ?>">See all providers</a>
    </div>
    <div class="p-grid">
      <?php foreach ($featured as $p): ?>
        <article class="p-card">
          <div class="p-card-top">
            <?= avatar_html($p['passport'], pname($p), 76, 'p-avatar') ?>
            <div>
              <div class="p-name"><?= h(pname($p)) ?></div>
              <div class="p-cat"><?= h($p['category_name'] ?? 'General Services') ?></div>
              <div class="p-loc"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/></svg><?= h($p['location'] ?: 'Nigeria') ?></div>
            </div>
          </div>
          <div class="p-body"><p class="p-bio"><?= h($p['bio'] ?: 'Verified SabiServe provider delivering quality work with a smile — book me today.') ?></p></div>
          <div class="p-foot">
            <div class="p-rating"><span class="stars"><?= star_rating((float)$p['avg_rating']) ?></span><strong style="color:var(--green-950)"><?= number_format((float)$p['avg_rating'], 1) ?></strong>(<?= (int)$p['review_count'] ?>)</div>
            <a class="btn btn-ghost btn-sm" href="<?= base_url('provider.php?id=' . (int)$p['id']) ?>">View</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="section">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">How It Works</span><h2 class="section-title">Three steps to sorted</h2></div><a class="btn btn-outline btn-sm" href="<?= base_url('how-it-works.php') ?>">Learn more</a></div>
    <div class="steps">
      <div class="step"><h3>Search &amp; compare</h3><p>Tell us what you need and where. Filter by category, rating and location to find the right professional fast.</p></div>
      <div class="step"><h3>Send an enquiry</h3><p>Open the provider’s profile, describe the job, and send a booking request — all inside SabiServe.</p></div>
      <div class="step"><h3>Get it done &amp; review</h3><p>Agree on scope, get the work done, then leave a rating to help the next customer choose well.</p></div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS -->
<section class="section" style="padding-top:0">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">Loved Across Nigeria</span><h2 class="section-title">Stories from our community</h2></div></div>
    <div class="testi-grid">
      <figure class="testi"><p>My AC broke down at 9pm in Ikeja and a SabiServe technician was at my door the next morning. Professional, honest pricing, zero stress.</p><figcaption class="testi-who"><span class="avatar avatar-initials" style="width:44px;height:44px;font-size:17px">AO</span><span><strong>Adaeze Okafor</strong><small>Product Manager · Lagos</small></span></figcaption></figure>
      <figure class="testi"><p>As a tailor, 80% of my new customers now find me on SabiServe. The enquiries come straight to my dashboard — it has genuinely changed my business.</p><figcaption class="testi-who"><span class="avatar avatar-initials" style="width:44px;height:44px;font-size:17px">BM</span><span><strong>Bello Musa</strong><small>Fashion Designer · Abuja</small></span></figcaption></figure>
      <figure class="testi"><p>Vetted providers, real reviews, and WhatsApp support that actually responds. SabiServe is the first marketplace I trust for home services.</p><figcaption class="testi-who"><span class="avatar avatar-initials" style="width:44px;height:44px;font-size:17px">CE</span><span><strong>Chiamaka Eze</strong><small>Small Business Owner · Port Harcourt</small></span></figcaption></figure>
    </div>
  </div>
</section>

<!-- CTA BAND -->
<section class="cta-band">
  <div class="container">
    <div><h2>Are you a service provider?</h2><p>Join SabiServe for just ₦2,000 a month. Showcase your work, receive verified enquiries, and grow your reputation across Nigeria.</p></div>
    <a href="<?= base_url('register.php?role=provider') ?>" class="btn btn-emerald" style="font-size:1.05rem;padding:16px 30px">Join as a Provider <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
  </div>
</section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
