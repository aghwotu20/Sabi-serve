<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Contact SabiServe — We’d Love to Hear From You';
$sent = false; $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? ''); $email = trim($_POST['email'] ?? ''); $subject = trim($_POST['subject'] ?? ''); $message = trim($_POST['message'] ?? '');
    if ($name === '' || $subject === '' || $message === '') $errors[] = 'Please fill in your name, subject and message.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email address.';
    if (!$errors) {
        db()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)')->execute([mb_substr($name,0,150), mb_substr($email,0,150), mb_substr($subject,0,180), $message]);
        $sent = true;
    }
}
require_once __DIR__ . '/includes/public_header.php';
?>
<section class="page-hero"><div class="container"><span class="eyebrow" style="color:var(--amber-300)">Contact Us</span><h1 style="margin-top:10px">We’re here to help</h1><p style="color:#A7F3D0;margin-top:10px;max-width:560px">Questions about subscriptions, verification, partnerships or your account — reach us and we’ll get back within one business day.</p></div></section>
<section class="section"><div class="container grid-2">
  <div class="form-card">
    <?php if ($sent): ?><div class="flash flash-success" style="position:static;transform:none;margin-bottom:16px"><span>Message received! Our team will reach out within one business day.</span></div><?php endif; ?>
    <?php foreach ($errors as $e): ?><div class="flash flash-error" style="position:static;transform:none;margin-bottom:12px"><span><?= h($e) ?></span></div><?php endforeach; ?>
    <form method="post" action="<?= base_url('contact.php') ?>"><?= csrf_field() ?>
      <div class="form-grid">
        <div class="form-group"><label class="fl" for="name">Full name</label><input class="form-control" id="name" name="name" required value="<?= h($_POST['name'] ?? '') ?>"></div>
        <div class="form-group"><label class="fl" for="email">Email</label><input class="form-control" id="email" name="email" type="email" required value="<?= h($_POST['email'] ?? '') ?>"></div>
        <div class="form-group full"><label class="fl" for="subject">Subject</label><input class="form-control" id="subject" name="subject" required value="<?= h($_POST['subject'] ?? '') ?>"></div>
        <div class="form-group full"><label class="fl" for="message">Message</label><textarea class="form-control" id="message" name="message" required><?= h($_POST['message'] ?? '') ?></textarea></div>
      </div>
      <button class="btn btn-emerald btn-block" style="margin-top:16px" type="submit">Send message</button>
    </form>
  </div>
  <aside>
    <div class="panel"><h2 style="font-size:1.1rem;margin-bottom:14px">Reach us directly</h2>
      <ul class="foot-list" style="color:var(--muted)"><li><strong style="color:var(--green-950)">Head Office</strong><br>No 8 Kess Rubber Factory Street, Ughelli, Delta State</li><li><strong style="color:var(--green-950)">Phone / WhatsApp</strong><br><a href="tel:+2348030567553">0803 056 7553</a></li><li><strong style="color:var(--green-950)">Email</strong><br><a href="mailto:info@sabiserve.com.ng">info@sabiserve.com.ng</a><br><a href="mailto:support@sabiserve.com.ng">support@sabiserve.com.ng</a></li><li><strong style="color:var(--green-950)">Hours</strong><br>Mon – Sat, 8:00am – 7:00pm WAT</li></ul>
    </div>
    <a class="btn btn-amber btn-block" href="https://wa.me/<?= h(preg_replace('/\D/', '', setting('whatsapp_number', '2348030567553'))) ?>" target="_blank" rel="noopener" style="font-size:1rem;padding:16px"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 2.1.55 4.14 1.6 5.95L2.05 22l4.29-1.12a9.87 9.87 0 0 0 5.7 1.8h.01c5.46 0 9.9-4.45 9.9-9.9 0-2.64-1.03-5.13-2.9-7A9.82 9.82 0 0 0 12.04 2z"/></svg> Chat on WhatsApp now</a>
  </aside>
</div></section>
<?php require_once __DIR__ . '/includes/public_footer.php'; ?>
