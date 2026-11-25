<?php $wa = function_exists('setting') ? preg_replace('/\D/', '', setting('whatsapp_number', '2348030567553')) : '2348030567553'; ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SabiServe — We’ll be right back</title>
<meta name="robots" content="noindex">
<style>
  :root{--emerald:#065f46;--emerald-deep:#043f2e;--amber:#fbbf24;--ink:#0b2e24}
  *{box-sizing:border-box;margin:0;padding:0}
  body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'Segoe UI',system-ui,sans-serif;background:radial-gradient(1200px 700px at 50% -10%,#0b7a55 0%,var(--emerald) 45%,var(--emerald-deep) 100%);color:#ecfdf5;text-align:center;padding:24px}
  .pulse{animation:float 2.6s ease-in-out infinite}
  @keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-14px)}}
  .badge{display:inline-flex;gap:10px;align-items:center;background:rgba(251,191,36,.15);border:1px solid rgba(251,191,36,.4);color:var(--amber);padding:8px 18px;border-radius:999px;font-size:13px;letter-spacing:.12em;text-transform:uppercase;font-weight:700;margin-bottom:26px}
  h1{font-size:clamp(2rem,6vw,3.4rem);line-height:1.12;margin-bottom:16px;font-weight:800;letter-spacing:-.02em}
  p{max-width:520px;margin:0 auto 28px;color:#bbf7d0;font-size:1.05rem;line-height:1.6}
  a.btn{display:inline-flex;align-items:center;gap:10px;background:var(--amber);color:#1f2937;font-weight:800;padding:14px 26px;border-radius:14px;text-decoration:none;box-shadow:0 10px 30px rgba(0,0,0,.35)}
  .foot{margin-top:36px;font-size:.85rem;color:#6ee7b7a0}
</style>
</head>
<body>
  <main>
    <div class="pulse"><svg width="96" height="96" viewBox="0 0 96 96" fill="none" role="img" aria-label="SabiServe under maintenance"><rect x="6" y="6" width="84" height="84" rx="22" fill="#FBBF24"/><path d="M48 22c-9.4 0-17 7.6-17 17 0 12.8 17 28 17 28s17-15.2 17-28c0-9.4-7.6-17-17-17z" fill="#065F46"/><path d="M48 31.5a7.5 7.5 0 1 0 0 15 7.5 7.5 0 0 0 0-15z" fill="#FBBF24"/></svg></div>
    <div style="height:22px"></div>
    <span class="badge">Maintenance Mode</span>
    <h1>SabiServe is getting a quick tune-up</h1>
    <p>We’re polishing things to serve you better. The platform will be back online shortly — thank you for your patience.</p>
    <a class="btn" href="https://wa.me/<?= h($wa) ?>" target="_blank" rel="noopener">Chat with us on WhatsApp</a>
    <div class="foot">&copy; <?= date('Y') ?> SabiServe &middot; sabiserve.com.ng</div>
  </main>
</body>
</html>
