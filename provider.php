<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$st = db()->prepare("SELECT u.*, c.name AS category_name, c.slug AS category_slug FROM users u LEFT JOIN categories c ON c.id = u.category_id WHERE u.id = ? AND u.role = 'provider'");
$st->execute([$id]); $p = $st->fetch();
if (!$p || $p['status'] !== 'active') { http_response_code(404); $pageTitle = 'Provider not found — SabiServe'; require_once __DIR__ . '/includes/public_header.php'; echo '<section class="section"><div class="container"><div class="empty"><h2>Provider not found</h2><p>This provider may have paused their profile. <a href="' . base_url('browse.php') . '"><strong>Browse other providers →</strong></a></p></div></div></section>'; require_once __DIR__ . '/includes/public_footer.php'; exit; }

$pname = $p['business_name'] ?: $p['full_name'];
$pageTitle = h($pname) . ' — Trusted Provider on SabiServe';
require_once __DIR__ . '/includes/public_header.php';

$listings = db()->prepare("SELECT l.*, c.name AS category_name FROM listings l LEFT JOIN categories c ON c.id = l.category_id WHERE l.provider_id = ? AND l.status = 'active' ORDER BY l.created_at DESC");
$listings->execute([$id]); $listings = $listings->fetchAll();
$skillsStmt = db()->prepare("SELECT s.name, c.name AS cat_name FROM provider_skills ps JOIN skills s ON s.id = ps.skill_id JOIN categories c ON c.id = s.category_id WHERE ps.provider_id = ? ORDER BY c.sort_order, c.name, s.sort_order, s.name");
$skillsStmt->execute([$id]); $providerSkills = $skillsStmt->fetchAll();
$reviews = db()->prepare("SELECT r.*, u.full_name AS reviewer FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.provider_id = ? AND r.status = 'visible' ORDER BY r.created_at DESC");
$reviews->execute([$id]); $reviews = $reviews->fetchAll();
$avg = $reviews ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
$me = current_user();
$isSaved = false;
if ($me) { $sv = db()->prepare('SELECT 1 FROM saved_providers WHERE user_id = ? AND provider_id = ?'); $sv->execute([$me['id'], $id]); $isSaved = (bool)$sv->fetchColumn(); }
$alreadyReviewed = false;
if ($me) { $rv = db()->prepare('SELECT 1 FROM reviews WHERE user_id = ? AND provider_id = ?'); $rv->execute([$me['id'], $id]); $alreadyReviewed = (bool)$rv->fetchColumn(); }
$waLink = 'https://wa.me/' . preg_replace('/\D/', '', $p['phone'] ?? '') . '?text=' . urlencode('Hello ' . $pname . ', I found you on SabiServe and would like a quote.');
?>
<section class="cat-hero" style="padding:40px 0">
  <div class="container">
    <nav class="breadcrumb"><a href="<?= base_url('browse.php') ?>">Find Services</a> / <?= h($p['category_name'] ?? 'Provider') ?></nav>
    <div style="display:flex;gap:20px;align-items:center;flex-wrap:wrap">
      <?= avatar_html($p['passport'], $pname, 96) ?>
      <div>
        <h1 style="font-size:1.9rem"><?= h($pname) ?></h1>
        <div style="display:flex;gap:10px;align-items:center;margin-top:8px;flex-wrap:wrap">
          <span class="badge-verified"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg> Verified Provider</span>
          <span style="color:#A7F3D0"><?= h($p['category_name'] ?? 'General Services') ?> · <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-1px"><path d="M12 21s7-5.5 7-11a7 7 0 1 0-14 0c0 5.5 7 11 7 11z"/></svg> <?= h($p['location'] ?: 'Nigeria') ?></span>
        </div>
        <div class="p-rating" style="margin-top:8px;color:#FDE68A"><span class="stars"><?= star_rating((float)$avg) ?></span><strong style="color:#fff"><?= number_format($avg, 1) ?></strong><span style="color:#A7F3D0">· <?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?></span></div>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top:36px">
  <div class="container grid-2">
    <div>
      <div class="panel">
        <div class="panel-head"><h2>About</h2>
          <?php if ($me && $me['role'] === 'user'): ?>
            <form method="post" action="<?= base_url('save-provider.php') ?>"><?= csrf_field() ?><input type="hidden" name="provider_id" value="<?= (int)$p['id'] ?>"><input type="hidden" name="back" value="provider.php?id=<?= (int)$p['id'] ?>"><button class="btn btn-outline btn-sm" type="submit"><?= $isSaved ? '★ Saved' : '☆ Save provider' ?></button></form>
          <?php endif; ?>
        </div>
        <p style="color:var(--muted)"><?= nl2br(h($p['bio'] ?: 'This provider has not added a bio yet. Send an enquiry to discuss your job.')) ?></p>
      </div>

      <?php if ($listings): ?>
      <div class="panel">
        <div class="panel-head"><h2>Services offered</h2><span class="pill pill-green"><?= count($listings) ?> active</span></div>
        <div class="table-wrap"><table class="tbl"><thead><tr><th>Service</th><th>Price guide</th><th>Area</th></tr></thead><tbody>
          <?php foreach ($listings as $l): ?><tr><td><strong><?= h($l['title']) ?></strong><br><span class="hint"><?= h(mb_strimwidth((string)$l['description'], 0, 90, '…')) ?></span></td><td><?= h($l['price_hint'] ?: 'On request') ?></td><td><?= h($l['location'] ?: $p['location'] ?: '—') ?></td></tr><?php endforeach; ?>
        </tbody></table></div>
      </div>
      <?php endif; ?>

      <?php if ($providerSkills): ?>
      <div class="panel">
        <div class="panel-head"><h2>Skills & expertise</h2><span class="pill pill-green"><?= count($providerSkills) ?> skills</span></div>
        <?php $lastCat = null; foreach ($providerSkills as $sk): if ($sk['cat_name'] !== $lastCat): $lastCat = $sk['cat_name']; ?><div class="skill-cat-label"><?= h($lastCat) ?></div><?php endif; ?><span class="skill-tag"><?= h($sk['name']) ?></span> <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-head"><h2>Reviews (<?= count($reviews) ?>)</h2><span class="stars"><?= star_rating((float)$avg) ?></span></div>
        <?php if (!$reviews): ?><p class="hint">No reviews yet — be the first to share your experience.</p><?php endif; ?>
        <?php foreach ($reviews as $r): ?>
          <div class="review-item"><div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap"><strong><?= h($r['reviewer']) ?></strong><span class="hint"><?= time_ago($r['created_at']) ?></span></div><span class="stars" style="margin:6px 0"><?= star_rating((float)$r['rating']) ?></span><p style="color:var(--muted);font-size:.92rem"><?= nl2br(h($r['comment'])) ?></p></div>
        <?php endforeach; ?>
        <?php if ($me && $me['role'] === 'user' && !$alreadyReviewed): ?>
          <form method="post" action="<?= base_url('review-submit.php') ?>" style="margin-top:16px;border-top:1px solid var(--line);padding-top:16px">
            <?= csrf_field() ?><input type="hidden" name="provider_id" value="<?= (int)$p['id'] ?>">
            <div class="form-grid">
              <div class="form-group"><label class="fl" for="rating">Your rating</label><select class="form-control" id="rating" name="rating" required><option value="5">★★★★★ Excellent</option><option value="4">★★★★ Very good</option><option value="3">★★★ Good</option><option value="2">★★ Fair</option><option value="1">★ Poor</option></select></div>
              <div class="form-group full" style="grid-column:1/-1"><label class="fl" for="comment">Your review</label><textarea class="form-control" id="comment" name="comment" required maxlength="600" placeholder="How was the job? Communication, quality, pricing…"></textarea></div>
            </div>
            <button class="btn btn-emerald btn-sm" style="margin-top:12px" type="submit">Submit review</button>
          </form>
        <?php elseif ($alreadyReviewed): ?><p class="hint" style="margin-top:12px">You have already reviewed this provider. Thank you!</p><?php endif; ?>
      </div>
    </div>

    <aside>
      <div class="form-card" style="position:sticky;top:96px">
        <h2 style="font-size:1.15rem;margin-bottom:4px">Request this provider</h2>
        <p class="hint" style="margin-bottom:16px">Average response time: <strong>under 2 hours</strong></p>
        <?php if (!$me): ?>
          <p style="margin-bottom:14px;color:var(--muted);font-size:.92rem">Please <a href="<?= base_url('login.php?next=' . urlencode('provider.php?id=' . $id)) ?>"><strong>log in</strong></a> or <a href="<?= base_url('register.php?role=user') ?>"><strong>create an account</strong></a> to send an enquiry.</p>
          <a class="btn btn-amber btn-block" href="<?= base_url('login.php?next=' . urlencode('provider.php?id=' . $id)) ?>">Log in to enquire</a>
        <?php elseif ($me['role'] !== 'user'): ?>
          <p class="hint">Only customer accounts can send enquiries. You are logged in as <?= h(ucfirst($me['role'])) ?>.</p>
        <?php else: ?>
          <form method="post" action="<?= base_url('enquiry-submit.php') ?>">
            <?= csrf_field() ?><input type="hidden" name="provider_id" value="<?= (int)$p['id'] ?>">
            <input type="hidden" name="back" value="provider.php?id=<?= (int)$p['id'] ?>">
            <div class="form-group" style="margin-bottom:12px"><label class="fl" for="subject">What do you need?</label><input class="form-control" id="subject" name="subject" required maxlength="180" placeholder="e.g. Fix leaking kitchen pipe"></div>
            <div class="form-group" style="margin-bottom:12px"><label class="fl" for="message">Details</label><textarea class="form-control" id="message" name="message" required maxlength="1200" placeholder="Describe the job, your location and preferred timing…"></textarea></div>
            <button class="btn btn-amber btn-block" type="submit">Send Enquiry <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 2 11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg></button>
          </form>
        <?php endif; ?>
        <?php if (!empty($p['phone']) && preg_replace('/\D/', '', $p['phone']) !== ''): ?>
          <a class="btn btn-outline btn-block" style="margin-top:10px" href="<?= h($waLink) ?>" target="_blank" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 2.1.55 4.14 1.6 5.95L2.05 22l4.29-1.12a9.87 9.87 0 0 0 5.7 1.8h.01c5.46 0 9.9-4.45 9.9-9.9 0-2.64-1.03-5.13-2.9-7A9.82 9.82 0 0 0 12.04 2z"/></svg> Chat on WhatsApp</a>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
