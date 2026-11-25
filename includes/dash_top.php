<?php
require_once __DIR__ . '/functions.php';
$dashRole = $dashRole ?? 'user';
$dashTitle = $dashTitle ?? 'Dashboard';
$activeTab = $activeTab ?? 'overview';
$u = require_role(match ($dashRole) { 'admin' => ['admin'], 'staff' => ['staff', 'admin'], 'provider' => ['provider'], default => ['user'] });
// staff block also allowed into admin views intentionally only where coded; role gate kept strict here.
$siteName = setting('site_name', 'SabiServe');
$logoSrc = brand_src(setting('logo'), '/assets/img/logo-mark.svg');
$subInfo = in_array($u['role'], ['user', 'provider'], true) ? subscription_badge((int)$u['id']) : null;
$navs = [
  'user' => [
    'overview' => ['label' => 'Overview', 'href' => '/user/index.php', 'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    'enquiries' => ['label' => 'My Enquiries', 'href' => '/user/index.php?tab=enquiries', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
    'saved' => ['label' => 'Saved Providers', 'href' => '/user/index.php?tab=saved', 'icon' => 'M12 3l2.7 5.6 6 .8-4.4 4.2 1.1 6L12 17.4 6.6 19.6l1.1-6-4.4-4.2 6-.8L12 3z'],
    'profile' => ['label' => 'My Profile', 'href' => '/account/profile.php', 'icon' => 'M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm-7.5 9a7.5 7.5 0 0 1 15 0'],
    'subscribe' => ['label' => 'Subscription', 'href' => '/subscribe.php', 'icon' => 'M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6l8-3z'],
  ],
  'provider' => [
    'overview' => ['label' => 'Overview', 'href' => '/provider/index.php', 'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    'listings' => ['label' => 'My Listings', 'href' => '/provider/index.php?tab=listings', 'icon' => 'M4 7h16M4 12h16M4 17h10'],
    'skills' => ['label' => 'My Skills', 'href' => '/provider/index.php?tab=skills', 'icon' => 'M9 12l2 2 4-4M12 3l7 3v5c0 5-3 8.5-7 10-4-1.5-7-5-7-10V6l7-3z'],
    'enquiries' => ['label' => 'Enquiries', 'href' => '/provider/index.php?tab=enquiries', 'icon' => 'M4 6h16M4 12h16M4 18h10'],
    'profile' => ['label' => 'My Profile', 'href' => '/account/profile.php', 'icon' => 'M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm-7.5 9a7.5 7.5 0 0 1 15 0'],
    'subscribe' => ['label' => 'Subscription', 'href' => '/subscribe.php', 'icon' => 'M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6l8-3z'],
  ],
  'staff' => [
    'overview' => ['label' => 'Overview', 'href' => '/staff/index.php', 'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    'listings' => ['label' => 'Listings', 'href' => '/staff/index.php?tab=listings', 'icon' => 'M4 7h16M4 12h16M4 17h10'],
    'reviews' => ['label' => 'Reviews', 'href' => '/staff/index.php?tab=reviews', 'icon' => 'M12 3l2.7 5.6 6 .8-4.4 4.2 1.1 6L12 17.4 6.6 19.6l1.1-6-4.4-4.2 6-.8L12 3z'],
    'messages' => ['label' => 'Messages', 'href' => '/staff/index.php?tab=messages', 'icon' => 'M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8z'],
    'profile' => ['label' => 'My Profile', 'href' => '/account/profile.php', 'icon' => 'M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm-7.5 9a7.5 7.5 0 0 1 15 0'],
  ],
  'admin' => [
    'overview' => ['label' => 'Overview', 'href' => '/admin/index.php', 'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    'staff' => ['label' => 'Staff Management', 'href' => '/admin/staff.php', 'icon' => 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75'],
    'users' => ['label' => 'Users & Providers', 'href' => '/admin/users.php', 'icon' => 'M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
    'categories' => ['label' => 'Categories', 'href' => '/admin/categories.php', 'icon' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z'],
    'skills' => ['label' => 'Skills', 'href' => '/admin/skills.php', 'icon' => 'M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01'],
    'listings' => ['label' => 'Listings', 'href' => '/admin/listings.php', 'icon' => 'M4 7h16M4 12h16M4 17h10'],
    'reviews' => ['label' => 'Reviews', 'href' => '/admin/reviews.php', 'icon' => 'M12 3l2.7 5.6 6 .8-4.4 4.2 1.1 6L12 17.4 6.6 19.6l1.1-6-4.4-4.2 6-.8L12 3z'],
    'subscriptions' => ['label' => 'Subscriptions', 'href' => '/admin/subscriptions.php', 'icon' => 'M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
    'messages' => ['label' => 'Messages', 'href' => '/admin/messages.php', 'icon' => 'M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8z'],
    'settings' => ['label' => 'Website Settings', 'href' => '/admin/settings.php', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6z'],
    'profile' => ['label' => 'My Profile', 'href' => '/account/profile.php', 'icon' => 'M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9zm-7.5 9a7.5 7.5 0 0 1 15 0'],
  ],
];
// settings icon fix: use sliders icon
$navs['admin']['settings']['icon'] = 'M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3M1 14h6M9 8h6M17 16h6';
$nav = $navs[$dashRole];
$roleLabel = ['user' => 'Customer', 'provider' => 'Service Provider', 'staff' => 'Staff', 'admin' => 'Administrator'][$dashRole];
$faviconSrc = brand_src(setting('favicon'), '/assets/img/favicon.svg');
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($dashTitle) ?> — <?= h($siteName) ?> Dashboard</title>
<meta name="robots" content="noindex">
<link rel="icon" href="<?= h($faviconSrc) ?>" sizes="any">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="dash-body">
<div class="dash-shell">
  <aside class="sidebar" aria-label="Dashboard navigation">
    <a class="brand" href="/" aria-label="Back to website"><img class="mark" src="<?= h($logoSrc) ?>" alt="" width="38" height="38"><span><?= h($siteName) ?><span class="dot" style="color:var(--amber-400)">.</span></span></a>
    <div class="side-user">
      <?= avatar_html($u['passport'], $u['full_name'], 52) ?>
      <div><div class="nm"><?= h($u['full_name']) ?></div><span class="rl"><?= h($roleLabel) ?></span></div>
    </div>
    <nav class="side-nav">
      <?php foreach ($nav as $key => $item): ?>
        <a class="side-link<?= $activeTab === $key ? ' active' : '' ?>" href="<?= h($item['href']) ?>"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="<?= h($item['icon']) ?>"/></svg><?= h($item['label']) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <a class="side-link" href="/"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>View website</a>
      <a class="side-link" href="/logout.php" style="color:#FCA5A5"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>Log out</a>
    </div>
  </aside>
  <main class="dash-main">
    <div class="dash-topbar">
      <h1><?= h($dashTitle) ?></h1>
      <?php if ($subInfo): ?><span class="pill <?= $subInfo['active'] ? 'pill-green' : 'pill-amber' ?>"><?= $subInfo['active'] ? '● Subscription Active' : '○ ' . h($subInfo['label']) ?></span><?php else: ?><a class="btn btn-ghost btn-sm" href="/">sabiserve.com.ng</a><?php endif; ?>
    </div>
    <div class="flash-wrap" style="position:static;transform:none;width:100%;padding:14px 28px 0"><?php flash_render(); ?></div>
    <div class="dash-content">
