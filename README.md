# SabiServe — Connecting Nigerians to Trusted Services

A production-ready, cPanel-deployable PHP/MySQL web application: Nigeria's services
marketplace where customers find verified providers (plumbing, electrical, tailoring,
catering, tutoring, cleaning and more) and providers grow their business.

- **Public website** — homepage with smart search, 63 service categories, 665 searchable skills, featured providers, provider profiles, reviews, ratings, FAQs, About, Contact, Terms, Privacy.
- **Four account types** — Customer, Service Provider, Staff, Administrator — one login page, three portal tabs.
- **Portfolios & dashboards** — every account uploads a passport photograph and manages its own profile.
- **₦2,000/month subscription** for both customers and providers, paid via **Paystack** (test or live keys configurable in Admin → Website Settings), with automatic webhook + verify-on-return activation.
- **Admin Website Settings** — logo & favicon upload, Paystack public/secret keys, ₦ amount, maintenance-mode toggle (admins bypass), WhatsApp number, Facebook/Instagram links, office address.
- **Staff management** — admins can create staff accounts and suspend/reactivate them (and customers/providers) instantly.
- **WhatsApp floating button + Facebook/Instagram social icons** in the top bar and footer.
- **Brand system** — deep emerald + amber ("catchy and trusted"), Sora/Inter typography, mobile-first.

## Requirements

| Layer | Requirement |
|---|---|
| Hosting | cPanel shared hosting (or any Apache/Nginx with PHP) |
| PHP | 8.0+ with `pdo_mysql`, `curl`, `fileinfo`, `gd`, `mbstring`, `json`, `openssl` |
| Database | MySQL 5.7+ / MariaDB 10.3+ |

## ⚡ Deploy to cPanel in 5 steps

1. **Create the database.** cPanel → **MySQL Databases** → create a database (e.g. `youruser_sabiserve`) and a user (e.g. `youruser_dbuser`), grant **ALL PRIVILEGES**.
2. **Import the SQL.** cPanel → **phpMyAdmin** → select your new database → **Import** → choose `sql/sabiserve.sql`. (Do **not** create tables first; the file creates everything including seed data.)
3. **Edit `includes/config.php`** — set `DB_NAME`, `DB_USER`, `DB_PASS` to the values from step 1. Keep `APP_DEBUG` set to `false`.
4. **Upload the files.** File Manager → `public_html` → upload **all contents of this folder** (or the provided zip) and Extract. `index.php` must sit directly inside `public_html`. If installing in a subfolder, contact support to convert links to relative.
5. **Done.** Visit `https://yourdomain.com`. Log in at `/login.php` with the seeded accounts below, then change the passwords.

> Enable HTTPS, then uncomment the "Force HTTPS" block in `.htaccess`. Point Paystack's webhook to `https://yourdomain.com/paystack-webhook.php`.

## Seeded accounts

| Role | Email | Password |
|---|---|---|
| Administrator | admin@sabiserve.com.ng | `Admin@12345` |
| Staff | staff@sabiserve.com.ng | `Staff@12345` |
| Demo customer | demo@sabiserve.com.ng | `User@12345` |
| Demo providers | emeka@, funke@, samuel@, ngozi@, ibrahim@, tunde@, chiamaka@, maryam@, kosiso@ `@sabiserve.com.ng` | `Provider@12345` |

**Change all passwords immediately after going live.** The demo customer and 5 providers carry 30-day active subscriptions for demo purposes.

## Paystack setup

1. Get API keys from [Paystack Dashboard → Settings → API Keys & Webhooks](https://dashboard.paystack.com/#/settings/developers).
2. Log in as admin → **Website Settings → Paystack Integration** → paste Public Key and Secret Key → Save.
3. Set your monthly fee (default ₦2,000) and add the displayed **Webhook URL** in Paystack.
4. Subscriptions auto-activate on payment (verify-on-return + webhook), and extend existing active periods. Admins can also activate subscriptions manually (bank transfer/cash) under **Subscriptions → Manual activation**.

## Maintenance mode

Admin → **Website Settings → Maintenance Mode** toggles it instantly. Everyone except administrators sees the elegant 503 page; admins keep full access (including `/login.php`).

## Project structure

```
├── index.php, browse.php, provider.php     # public marketplace
├── login.php, register.php, logout.php     # unified auth (visitor / staff / admin portals)
├── subscribe.php, verify-payment.php, paystack-webhook.php
├── enquiry-submit.php, review-submit.php, save-provider.php
├── account/profile.php                     # passport photo + details for EVERY role
├── user/  provider/  staff/  admin/        # role dashboards
├── includes/ (config, db, functions, header/footer, dashboard shell, maintenance)
├── assets/ (css, js, generated illustrations, logo, favicon)
├── uploads/  (passports & branding — scripts are blocked by .htaccess)
├── sql/sabiserve.sql                       # schema + seed data
└── .htaccess                               # security headers, HTTPS hint, dir protection
```

## Skills catalogue
The marketplace ships with a full labour-market skills catalogue: **63 categories** and **665 skills** (e.g. Pipe repairs, POP ceiling installation, DSTV installation, Borehole drilling, Gas cooker installation). Providers tick the skills they offer under **Dashboard → My Skills**; customers filter and search by skill on the Find Services page, and skills appear as tags on provider cards and profiles. Admins manage the catalogue under **Admin → Skills** (add/delete skills per category).

## Security notes

- Passwords hashed with `password_hash()` (bcrypt) · sessions hardened (httponly, SameSite) · CSRF tokens on every form · prepared statements throughout · role-based access control · upload MIME sniffing + size limits · `uploads/` executes no scripts.
- Rotate the GitHub token used to publish this repository if it was shared in chat.

— Built for SabiServe Ltd · info@sabiserve.com.ng · 0803 056 7553 · November 2026
