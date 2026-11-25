<?php
require_once __DIR__ . '/functions.php';
enforce_maintenance();
$currentUser = current_user();
$pageTitle = $pageTitle ?? 'SabiServe — Connecting Nigerians to Trusted Services';
$metaDesc = $metaDesc ?? 'Find verified plumbers, electricians, cleaners, tutors, caterers, fashion designers and more across Nigeria. Book trusted local service providers in minutes on SabiServe.';
$siteName = setting('site_name', 'SabiServe');
$logoFile = setting('logo'); $faviconFile = setting('favicon');
$logoSrc = brand_src($logoFile, '/assets/img/logo-mark.svg');
$faviconSrc = brand_src($faviconFile, '/assets/img/favicon.svg');
$wa = preg_replace('/\D/', '', setting('whatsapp_number', '2348030567553'));
$fb = setting('facebook_url', 'https://www.facebook.com/sabiserve.ng');
$ig = setting('instagram_url', 'https://www.instagram.com/sabiserve.ng');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($metaDesc) ?>">
<meta name="theme-color" content="#065F46">
<link rel="icon" href="<?= h($faviconSrc) ?>" sizes="any">
<link rel="apple-touch-icon" href="<?= h($faviconSrc) ?>">
<meta property="og:title" content="<?= h($pageTitle) ?>">
<meta property="og:description" content="<?= h($metaDesc) ?>">
<meta property="og:type" content="website">
<meta property="og:image" content="/assets/img/og-cover.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<div class="topbar">
  <div class="container">
    <div class="topbar-left">
      <a href="tel:+2348030567553"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 11.91a16 16 0 0 0 6 6l3.27-1.27a2 2 0 0 1 2.11.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg><span class="hide-sm">0803 056 7553</span></a>
      <a href="mailto:info@sabiserve.com.ng"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg><span class="hide-sm">info@sabiserve.com.ng</span></a>
    </div>
    <div class="topbar-social" aria-label="SabiServe on social media">
      <a class="soc" href="<?= h($fb) ?>" target="_blank" rel="noopener" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M15 3h-2.5A4.5 4.5 0 0 0 8 7.5V10H5.5v3.5H8V21h3.8v-7.5h2.6l.4-3.5h-3V7.7c0-.6.4-1 1-1H16z"/></svg></a>
      <a class="soc" href="<?= h($ig) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24"><path d="M12 8.8a3.2 3.2 0 1 0 0 6.4 3.2 3.2 0 0 0 0-6.4zM17.5 6.5a.9.9 0 1 0 0 1.8.9.9 0 0 0 0-1.8zM7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 1.8H7A2.2 2.2 0 0 0 4.8 7v10A2.2 2.2 0 0 0 7 19.2h10a2.2 2.2 0 0 0 2.2-2.2V7A2.2 2.2 0 0 0 17 4.8z"/></svg></a>
      <a class="soc" href="https://wa.me/<?= h($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><svg viewBox="0 0 24 24"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 2.1.55 4.14 1.6 5.95L2.05 22l4.29-1.12a9.87 9.87 0 0 0 5.7 1.8h.01c5.46 0 9.9-4.45 9.9-9.9 0-2.64-1.03-5.13-2.9-7A9.82 9.82 0 0 0 12.04 2zm0 17.9a8.2 8.2 0 0 1-4.13-1.12l-.3-.18-2.44.64.65-2.38-.2-.32a7.96 7.96 0 0 1-1.22-4.23c0-4.46 3.63-8.1 8.1-8.1 2.16 0 4.2.85 5.73 2.38a8.06 8.06 0 0 1 2.37 5.73c0 4.46-3.64 8.1-8.1 8.1zm4.47-6.08c-.25-.12-1.44-.71-1.66-.79-.22-.08-.38-.12-.55.12-.16.25-.63.79-.77.95-.14.16-.29.18-.54.06-.25-.12-1.03-.38-1.96-1.21-.73-.65-1.22-1.45-1.36-1.7-.14-.25-.02-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.54-1.32-.75-1.8-.2-.47-.4-.41-.54-.42h-.47c-.16 0-.43.06-.66.31-.22.25-.87.85-.87 2.07 0 1.22.89 2.4 1.02 2.57.12.16 1.75 2.67 4.25 3.74.59.26 1.06.41 1.42.53.6.19 1.14.16 1.57.1.48-.07 1.44-.59 1.64-1.16.2-.57.2-1.06.14-1.16-.06-.1-.22-.16-.47-.27z"/></svg></a>
    </div>
  </div>
</div>

<header class="navbar">
  <div class="container">
    <a class="brand" href="<?= base_url('/') ?>" aria-label="<?= h($siteName) ?> — home">
      <img class="mark" src="<?= h($logoSrc) ?>" alt="<?= h($siteName) ?> logo" width="42" height="42">
      <span><?= h($siteName) ?><span class="dot">.</span></span>
    </a>
    <nav aria-label="Primary navigation">
      <ul class="nav-links">
        <li><a href="<?= base_url('browse.php') ?>">Find Services</a></li>
        <li><a href="<?= base_url('how-it-works.php') ?>">How It Works</a></li>
        <li><a href="<?= base_url('about.php') ?>">About</a></li>
        <li><a href="<?= base_url('faq.php') ?>">FAQs</a></li>
        <li><a href="<?= base_url('contact.php') ?>">Contact</a></li>
      </ul>
    </nav>
    <div class="nav-cta">
      <?php if ($currentUser): ?>
        <a class="btn btn-emerald btn-sm" href="<?= h(dashboard_url($currentUser['role'])) ?>">My Dashboard
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>
      <?php else: ?>
        <a class="btn btn-outline btn-sm" href="<?= base_url('login.php') ?>">Log In</a>
        <a class="btn btn-amber btn-sm" href="<?= base_url('register.php') ?>">Get Started</a>
      <?php endif; ?>
    </div>
    <button class="hamburger" data-hamburger aria-label="Toggle menu"><svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
  </div>
  <div class="mobile-menu" id="mobileMenu">
    <a href="<?= base_url('browse.php') ?>">Find Services</a>
    <a href="<?= base_url('how-it-works.php') ?>">How It Works</a>
    <a href="<?= base_url('about.php') ?>">About</a>
    <a href="<?= base_url('faq.php') ?>">FAQs</a>
    <a href="<?= base_url('contact.php') ?>">Contact</a>
    <?php if ($currentUser): ?>
      <a href="<?= h(dashboard_url($currentUser['role'])) ?>">My Dashboard</a>
    <?php else: ?>
      <a href="<?= base_url('login.php') ?>">Log In</a>
      <a class="btn btn-amber" href="<?= base_url('register.php') ?>">Get Started Free</a>
    <?php endif; ?>
  </div>
</header>
<div class="flash-wrap"><?php flash_render(); ?></div>
