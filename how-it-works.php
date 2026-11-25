<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'How It Works — SabiServe';
require_once __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow" style="color:var(--amber-300)">How It Works</span><h1 style="margin-top:10px">From search to sorted in three steps</h1></div></section>
<section class="section"><div class="container">
  <span class="eyebrow">For Customers</span><h2 class="section-title" style="margin-bottom:26px">Find help in minutes</h2>
  <div class="steps" style="margin-bottom:56px">
    <div class="step"><h3>Create your account</h3><p>Sign up as a customer in under two minutes and subscribe for ₦2,000/month to unlock bookings.</p></div>
    <div class="step"><h3>Search &amp; compare</h3><p>Filter by category, location and rating. Read real reviews from Nigerians like you.</p></div>
    <div class="step"><h3>Send an enquiry &amp; book</h3><p>Describe your job, agree on scope and price, then relax while the pro gets it done.</p></div>
  </div>
  <span class="eyebrow">For Providers</span><h2 class="section-title" style="margin-bottom:26px">Grow with SabiServe</h2>
  <div class="steps">
    <div class="step"><h3>Register &amp; subscribe</h3><p>Create your provider profile, add your passport photo, services and service areas, then subscribe for ₦2,000/month.</p></div>
    <div class="step"><h3>Receive enquiries</h3><p>Qualified customers send job requests straight to your dashboard and WhatsApp.</p></div>
    <div class="step"><h3>Deliver &amp; get reviewed</h3><p>Great work earns 5-star reviews — and reviews bring the next customer. Compound your reputation.</p></div>
  </div>
  <div class="panel" style="margin-top:44px;text-align:center"><h2 style="margin-bottom:8px">Questions? We answer fast.</h2><p style="color:var(--muted);margin-bottom:16px">Our Lagos team responds on WhatsApp within business hours.</p><a class="btn btn-amber" href="https://wa.me/<?= h(preg_replace('/\D/', '', setting('whatsapp_number', '2348030567553'))) ?>" target="_blank" rel="noopener">Chat with us on WhatsApp</a></div>
</div></section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
