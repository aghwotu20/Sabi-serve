<?php $siteName = $siteName ?? setting('site_name', 'SabiServe');
$wa = $wa ?? preg_replace('/\D/', '', setting('whatsapp_number', '2348030567553'));
$fb = $fb ?? setting('facebook_url', 'https://www.facebook.com/sabiserve.ng');
$ig = $ig ?? setting('instagram_url', 'https://www.instagram.com/sabiserve.ng');
$cats = [];
try { $cats = db()->query('SELECT name, slug FROM categories ORDER BY sort_order, name LIMIT 10')->fetchAll(); } catch (Throwable $e) { $cats = []; }
?>
<footer class="footer">
  <div class="container">
    <div class="footer-top">
      <div>
        <div class="foot-brand"><img src="<?= h(brand_src(setting('logo'), '/assets/img/logo-mark.svg')) ?>" alt="" width="38" height="38" style="border-radius:10px"> SabiServe<span class="dot" style="color:var(--amber-400)">.</span></div>
        <p style="font-size:.92rem;max-width:320px">Nigeria’s trusted marketplace for verified local services — from repairs and tailoring to tutoring and tech. One search. Trusted hands.</p>
        <div class="social-row">
          <a class="soc" href="<?= h($fb) ?>" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M15 3h-2.5A4.5 4.5 0 0 0 8 7.5V10H5.5v3.5H8V21h3.8v-7.5h2.6l.4-3.5h-3V7.7c0-.6.4-1 1-1H16z"/></svg></a>
          <a class="soc" href="<?= h($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24"><path d="M12 8.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4zM17.5 6.5a.9.9 0 1 0 0 1.8.9.9 0 0 0 0-1.8zM7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zM17 4.8H7A2.2 2.2 0 0 0 4.8 7v10A2.2 2.2 0 0 0 7 19.2h10a2.2 2.2 0 0 0 2.2-2.2V7A2.2 2.2 0 0 0 17 4.8z"/></svg></a>
          <a class="soc" href="https://wa.me/<?= h($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 2.1.55 4.14 1.6 5.95L2.05 22l4.29-1.12a9.87 9.87 0 0 0 5.7 1.8h.01c5.46 0 9.9-4.45 9.9-9.9 0-2.64-1.03-5.13-2.9-7A9.82 9.82 0 0 0 12.04 2zm0 17.9a8.2 8.2 0 0 1-4.13-1.12l-.3-.18-2.44.64.65-2.38-.2-.32a7.96 7.96 0 0 1-1.22-4.23c0-4.46 3.63-8.1 8.1-8.1 2.16 0 4.2.85 5.73 2.38a8.06 8.06 0 0 1 2.37 5.73c0 4.46-3.64 8.1-8.1 8.1zm4.47-6.08c-.25-.12-1.44-.71-1.66-.79-.22-.08-.38-.12-.55.12-.16.25-.63.79-.77.95-.14.16-.29.18-.54.06-.25-.12-1.03-.38-1.96-1.21-.73-.65-1.22-1.45-1.36-1.7-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.54-1.32-.75-1.8-.2-.47-.4-.41-.54-.42h-.47c-.16 0-.43.06-.66.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.4 1.02 2.57.12.16 1.75 2.67 4.25 3.74.59.26 1.06.41 1.42.53.6.19 1.14.16 1.57.1.48-.07 1.44-.59 1.64-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.47-.27z"/></svg></a>
        </div>
      </div>
      <nav aria-label="Quick links">
        <h4>Quick Links</h4>
        <ul class="foot-list">
          <li><a href="<?= base_url('browse.php') ?>">Find a Service</a></li>
          <li><a href="<?= base_url('register.php?role=provider') ?>">Offer a Service</a></li>
          <li><a href="<?= base_url('how-it-works.php') ?>">How It Works</a></li>
          <li><a href="<?= base_url('about.php') ?>">About Us</a></li>
          <li><a href="<?= base_url('faq.php') ?>">FAQs</a></li>
          <li><a href="<?= base_url('contact.php') ?>">Contact</a></li>
        </ul>
      </nav>
      <nav aria-label="Popular categories">
        <h4>Popular Categories</h4>
        <ul class="foot-list">
          <?php foreach ($cats as $c): ?>
            <li><a href="<?= base_url('browse.php?category=' . urlencode($c['slug'])) ?>"><?= h($c['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </nav>
      <div>
        <h4>Head Office</h4>
        <ul class="foot-list">
          <li>SabiServe Ltd, <?= h(setting('office_address', '17 Adeola Odeku Street, Victoria Island, Lagos')) ?></li>
          <li><a href="tel:+2348030567553">0803 056 7553</a></li>
          <li><a href="mailto:info@sabiserve.com.ng">info@sabiserve.com.ng</a></li>
          <li>Mon – Sat, 8:00am – 7:00pm WAT</li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> SabiServe Ltd. All rights reserved. RC pending.</span>
      <span><a href="<?= base_url('terms.php') ?>">Terms of Use</a> &middot; <a href="<?= base_url('privacy.php') ?>">Privacy Policy</a> &middot; <a href="<?= base_url('faq.php') ?>">Support</a></span>
    </div>
  </div>
</footer>
<a class="wa-float" href="https://wa.me/<?= h($wa) ?>?text=<?= urlencode('Hello SabiServe! I would like to know more about your services.') ?>" target="_blank" rel="noopener" aria-label="Chat with SabiServe on WhatsApp">
  <svg viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 2.1.55 4.14 1.6 5.95L2.05 22l4.29-1.12a9.87 9.87 0 0 0 5.7 1.8h.01c5.46 0 9.9-4.45 9.9-9.9 0-2.64-1.03-5.13-2.9-7A9.82 9.82 0 0 0 12.04 2zm0 17.9a8.2 8.2 0 0 1-4.13-1.12l-.3-.18-2.44.64.65-2.38-.2-.32a7.96 7.96 0 0 1-1.22-4.23c0-4.46 3.63-8.1 8.1-8.1 2.16 0 4.2.85 5.73 2.38a8.06 8.06 0 0 1 2.37 5.73c0 4.46-3.64 8.1-8.1 8.1zm4.47-6.08c-.25-.12-1.44-.71-1.66-.79-.22-.08-.38-.12-.55.12-.16.25-.63.79-.77.95-.14.16-.29.18-.54.06-.25-.12-1.03-.38-1.96-1.21-.73-.65-1.22-1.45-1.36-1.7-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.54-1.32-.75-1.8-.2-.47-.4-.41-.54-.42h-.47c-.16 0-.43.06-.66.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.4 1.02 2.57.12.16 1.75 2.67 4.25 3.74.59.26 1.06.41 1.42.53.6.19 1.14.16 1.57.1.48-.07 1.44-.59 1.64-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.47-.27z"/></svg>
  <span class="wa-tip">Need help? Chat with us</span>
</a>
<script src="/assets/js/main.js" defer></script>
</body>
</html>
