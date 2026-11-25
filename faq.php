<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'FAQs — SabiServe';
require_once __DIR__ . '/includes/public_header.php';
$faqs = [
  ['Is SabiServe free to join?', 'Registration is free. To send or receive enquiries, customers and providers subscribe for ₦2,000 per month — no commissions on jobs, ever.'],
  ['How are providers verified?', 'Every provider passes identity and business checks by the SabiServe team before their profile goes live. Admins can suspend any account that breaches our policies.'],
  ['How much does the subscription cost?', 'Just ₦2,000 per month for both customers and providers. It covers enquiries, messaging, reviews, visibility and support. Renew anytime — even early — and your month extends automatically.'],
  ['What happens if my subscription lapses?', 'Your account stays active, but sending or receiving enquiries pauses until you renew. Your profile, reviews and history are preserved.'],
  ['Can I cancel anytime?', 'Yes. There is no lock-in. If you cancel, your access continues until the end of the paid month, then simply pauses.'],
  ['What payment methods do you accept?', 'Cards, bank transfers and USSD — all securely processed by Paystack. SabiServe never sees or stores your card details.'],
  ['I’m a provider. How do I get more enquiries?', 'Complete your profile (photo, bio, service areas), keep your subscription active, respond quickly, and deliver great work — our algorithm favours responsive, highly-rated providers.'],
  ['How do I report a problem provider?', 'Use the “Report” option in your dashboard or email support@sabiserve.com.ng. Our moderation team reviews every report within 24 hours.'],
  ['Do you operate outside Lagos?', 'Yes — SabiServe covers all states in Nigeria, with the deepest coverage in Lagos, Abuja, Port Harcourt, Ibadan and Enugu.'],
];
?>
<section class="page-hero"><div class="container"><span class="eyebrow" style="color:var(--amber-300)">FAQs</span><h1 style="margin-top:10px">Answers, before you even ask</h1></div></section>
<section class="section"><div class="container" style="max-width:880px">
  <?php foreach ($faqs as $i => [$q, $a]): ?>
    <div class="faq-item<?= $i === 0 ? ' open' : '' ?>"><button class="faq-q" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>"><?= h($q) ?><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg></button><div class="faq-a"><p><?= h($a) ?></p></div></div>
  <?php endforeach; ?>
  <p style="margin-top:18px;color:var(--muted)">Still stuck? <a href="<?= base_url('contact.php') ?>"><strong>Contact our team →</strong></a></p>
</div></section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
