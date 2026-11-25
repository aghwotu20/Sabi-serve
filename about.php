<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'About SabiServe — Nigeria’s Trusted Services Marketplace';
require_once __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow" style="color:var(--amber-300)">About Us</span><h1 style="margin-top:10px">We’re building Nigeria’s most trusted services marketplace</h1><p style="color:#A7F3D0;margin-top:12px;max-width:640px;font-size:1.05rem">SabiServe connects homes and businesses with verified local professionals — making it easy to find, book and review trusted hands for every job.</p></div></section>
<section class="section"><div class="container grid-2" style="grid-template-columns:1fr 1.1fr;align-items:center">
  <img src="/assets/img/about-illustration.jpg" alt="Nigerian customer shaking hands with a verified SabiServe provider" style="border-radius:var(--radius-lg);box-shadow:var(--shadow-md)">
  <div class="prose" style="max-width:none">
    <h2 style="margin-top:0">Why we exist</h2>
    <p>Too many Nigerians rely on word-of-mouth to find plumbers, electricians, tailors and tutors — and still get disappointed. SabiServe replaces guesswork with verified profiles, transparent reviews and secure enquiries.</p>
    <h2>What makes us different</h2>
    <ul><li><strong>Verified providers</strong> — identity and business checks before a profile goes live.</li><li><strong>Real reviews</strong> — only customers who sent an enquiry can review, and every review is moderated.</li><li><strong>Nationwide reach</strong> — every state, from Lagos to Kano to Port Harcourt.</li><li><strong>Fair pricing</strong> — no commission on jobs; one simple ₦2,000 monthly subscription per account.</li></ul>
    <h2>Our ambition</h2>
    <p>To become the default way Nigerians discover and book local services — and to help a million skilled artisans and professionals build bankable reputations.</p>
  </div>
</div></section>
<section class="cta-band"><div class="container"><div><h2>Join the SabiServe movement</h2><p>Whether you need a service or you offer one, we’d love you here.</p></div><div style="display:flex;gap:12px;flex-wrap:wrap"><a class="btn btn-emerald" href="<?= base_url('register.php?role=user') ?>">I need a service</a><a class="btn btn-emerald" style="background:var(--green-950)" href="<?= base_url('register.php?role=provider') ?>">I offer a service</a></div></div></section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
