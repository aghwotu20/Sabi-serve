-- ============================================================
--  SABISERVE — MySQL database
--  Import with: phpMyAdmin → select your database → Import → choose this file
--  (Create the empty database first in cPanel → MySQL Databases)
--  ⚠ Re-importing will DROP and recreate the tables below.
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
-- CREATE DATABASE IF NOT EXISTS sabiserve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE sabiserve;

DROP TABLE IF EXISTS saved_providers;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS enquiries;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS listings;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS settings;

CREATE TABLE settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(80) NOT NULL UNIQUE,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(140) NOT NULL UNIQUE,
  icon VARCHAR(40) NOT NULL DEFAULT 'wrench',
  description VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role ENUM('user','provider','staff','admin') NOT NULL DEFAULT 'user',
  full_name VARCHAR(150) NOT NULL,
  business_name VARCHAR(150) NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) NULL,
  password_hash VARCHAR(255) NOT NULL,
  passport VARCHAR(255) NULL,
  location VARCHAR(120) NULL,
  bio TEXT NULL,
  category_id INT UNSIGNED NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  last_login_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_status (status),
  INDEX idx_users_category (category_id),
  CONSTRAINT fk_users_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE listings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  location VARCHAR(120) NULL,
  price_hint VARCHAR(120) NULL,
  status ENUM('active','paused','suspended') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_listings_provider (provider_id),
  INDEX idx_listings_category (category_id),
  INDEX idx_listings_status (status),
  FULLTEXT INDEX ft_listings (title, description),
  CONSTRAINT fk_listings_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_listings_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE enquiries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  provider_id INT UNSIGNED NOT NULL,
  listing_id INT UNSIGNED NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  provider_reply TEXT NULL,
  status ENUM('pending','responded','closed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_enquiries_user (user_id),
  INDEX idx_enquiries_provider (provider_id),
  INDEX idx_enquiries_status (status),
  CONSTRAINT fk_enquiries_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_enquiries_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_enquiries_listing FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  role ENUM('user','provider') NOT NULL,
  plan VARCHAR(40) NOT NULL DEFAULT 'monthly',
  amount DECIMAL(10,2) NOT NULL DEFAULT 2000.00,
  reference VARCHAR(80) NOT NULL UNIQUE,
  status ENUM('pending','active','expired') NOT NULL DEFAULT 'pending',
  starts_at DATETIME NULL,
  ends_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_subscriptions_user (user_id),
  INDEX idx_subscriptions_status (status),
  CONSTRAINT fk_subscriptions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT NOT NULL,
  status ENUM('visible','hidden') NOT NULL DEFAULT 'visible',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_review_pair (provider_id, user_id),
  INDEX idx_reviews_provider (provider_id),
  CONSTRAINT fk_reviews_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE saved_providers (
  user_id INT UNSIGNED NOT NULL,
  provider_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, provider_id),
  CONSTRAINT fk_saved_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_saved_provider FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contact_messages (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL,
  subject VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  status ENUM('new','read') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- default settings ----------
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'SabiServe'),
('logo', ''),
('favicon', ''),
('paystack_public_key', ''),
('paystack_secret_key', ''),
('subscription_amount', '2000'),
('maintenance_mode', '0'),
('whatsapp_number', '2348030567553'),
('facebook_url', 'https://www.facebook.com/sabiserve.ng'),
('instagram_url', 'https://www.instagram.com/sabiserve.ng'),
('office_address', 'No 8 Kess Rubber Factory Street, Ughelli, Delta State');

-- ---------- categories ----------
INSERT INTO categories (name, slug, icon, description, sort_order) VALUES
('Plumbing', 'plumbing', 'wrench', 'Repairs, installations and maintenance of water systems', 1),
('Electrical', 'electrical', 'bolt', 'Wiring, fault finding, installations and solar power', 2),
('AC & Refrigeration', 'ac-refrigeration', 'snow', 'AC servicing, repairs and cold rooms', 3),
('Cleaning & Fumigation', 'cleaning-fumigation', 'sparkles', 'Deep cleaning, fumigation and facility care', 4),
('Fashion & Tailoring', 'fashion-tailoring', 'scissors', 'Tailoring, gele, corporate uniforms and alterations', 5),
('Catering', 'catering', 'pot', 'Small chops, event buffets and private chefs', 6),
('Photography & Video', 'photography-video', 'camera', 'Weddings, portraits, products and events', 7),
('Hair & Beauty', 'hair-beauty', 'comb', 'Braiding, makeup, nails and grooming at home', 8),
('Auto Mechanics', 'auto-mechanics', 'car', 'Diagnostics, servicing and body repairs', 9),
('Home Tutoring', 'home-tutoring', 'book', 'WAEC, JAMB, primary and secondary lessons', 10),
('Carpentry & Furniture', 'carpentry-furniture', 'hammer', 'Furniture making, installation and repairs', 11),
('Painting & Decoration', 'painting-decoration', 'paint', 'Interior, exterior and decorative finishes', 12),
('Events & Decor', 'events-decor', 'party', 'Planning, decor, rentals and entertainment', 13),
('Web & Tech Services', 'web-tech', 'code', 'Websites, apps, IT support and networking', 14),
('Security Services', 'security-services', 'shield', 'Manned guarding, CCTV and access control', 15),
('Logistics & Dispatch', 'logistics-dispatch', 'truck', 'Deliveries, moving and haulage', 16);

INSERT INTO categories (name, slug, icon, description, sort_order) VALUES
('Solar & Renewable Energy', 'solar-renewable-energy', 'bolt', 'Solar panel sales, installation and inverter maintenance', 17),
('Generator & Power Solutions', 'generator-power-solutions', 'plug', 'Generator sales, repairs and servicing', 18),
('Welding & Fabrication', 'welding-fabrication', 'hammer', 'Gates, railings, tank stands and custom metal works', 19),
('Building & Construction', 'building-construction', 'home', 'Blocklaying, concrete works and building finishing', 20),
('Roofing & Waterproofing', 'roofing-waterproofing', 'hammer', 'Roof repairs, waterproofing and damp-proofing', 21),
('Tiling & Flooring', 'tiling-flooring', 'grid', 'Floor tiling, wall cladding, vinyl and epoxy finishes', 22),
('Phone & Laptop Repairs', 'phone-laptop-repairs', 'phone', 'Screen, battery, charging and motherboard repairs', 23),
('IT & Networking Services', 'it-networking', 'code', 'Networks, servers, website hosting and IT support', 24),
('CCTV & Smart Home', 'cctv-smart-home', 'camera', 'CCTV, alarms, access control and smart home setup', 25),
('Pest Control', 'pest-control', 'shield', 'Cockroaches, rodents, termites and snake removal', 26),
('Laundry & Dry Cleaning', 'laundry-dry-cleaning', 'sparkles', 'Wash, dry-clean, ironing and home pickup and delivery', 27),
('Home Nursing & Caregiving', 'home-nursing-caregiving', 'heart', 'Trained nurses and caregivers for patients and the elderly', 28),
('Childcare & Babysitting', 'childcare-babysitting', 'group', 'Trusted nannies and babysitters, daytime or live-in', 29),
('Agriculture & Livestock', 'agriculture-livestock', 'leaf', 'Poultry, fish farming, consulting and farm supplies', 30),
('Legal Services', 'legal-services', 'scale', 'Property, family, business and litigation support', 31),
('Accounting & Taxation', 'accounting-taxation', 'calculator', 'Bookkeeping, auditing, tax filing and payroll', 32),
('Entertainment & DJs', 'entertainment-djs', 'music', 'DJs, MCs, live bands, sound and event lighting', 33),
('Interior Design', 'interior-design', 'paint', 'Space planning, styling and fit-out services', 34),
('Borehole & Drilling', 'borehole-drilling', 'droplet', 'Borehole drilling, pumping and water treatment', 35),
('Refrigerator & Freezer Repair', 'refrigerator-freezer-repair', 'snow', 'Domestic and commercial cooling system repairs', 36),
('Driving Lessons', 'driving-lessons', 'car', 'Certified instructors for cars and heavy-duty vehicles', 37),
('Typing & Data Entry', 'typing-data-entry', 'briefcase', 'Typing, documentation, data entry and interpretation', 38),
('Real Estate & Property', 'real-estate-property', 'home', 'House listings, rentals, inspections and agency services', 39),
('Marketing & Branding', 'marketing-branding', 'briefcase', 'Social media, design, branding and promotion', 40),
('Shoemaking & Leatherworks', 'shoemaking-leatherworks', 'scissors', 'Shoemaking, leather products and repairs', 41),
('Career & Business Consulting', 'career-business-consulting', 'briefcase', 'CVs, business plans and professional coaching', 42),
('Barbering & Salon at Home', 'barbering-salon-at-home', 'comb', 'Home haircuts, grooming and salon services', 43),
('Swimming Pool Maintenance', 'swimming-pool-maintenance', 'droplet', 'Pool cleaning, chemical balancing and repairs', 44),
('Moving & Relocation', 'moving-relocation', 'truck', 'Local and interstate moves, packing and haulage', 45),
('Vulcanizing & Tyre Services', 'vulcanizing-tyre-services', 'car', 'Mobile vulcanizing, tyre sales and wheel balancing', 46);


-- ---------- seed accounts (all passwords documented in README) ----------
-- admin@sabiserve.com.ng  / Admin@12345
-- staff@sabiserve.com.ng  / Staff@12345
-- demo@sabiserve.com.ng   / User@12345
-- providers use           / Provider@12345
INSERT INTO users (role, full_name, business_name, email, phone, password_hash, location, bio, category_id, status) VALUES
('admin',   'SabiServe Admin',  NULL, 'admin@sabiserve.com.ng',  '08030567553', '$2y$12$GiUo4bCUvZ0A3PkfVWJzPupJLvS6P7UXvup0/Q2Y1AhcPfPjNe.p.', 'Victoria Island, Lagos', 'Platform administrator.', NULL, 'active'),
('staff',   'Yetunde Balogun',  NULL, 'staff@sabiserve.com.ng',  '08031234567', '$2y$12$Kbyzt9/qp2qu9Qkfxk0RKuULNLpPIFgzqomoANRFMXEHSl5LPRUqq', 'Ikeja, Lagos', 'Customer success and moderation team.', NULL, 'active'),
('user',    'Chinedu Okafor',   NULL, 'demo@sabiserve.com.ng',   '08059876543', '$2y$12$4fRKIY3HjRkgzYPjIbBDzO0gTnSQm1Pfi.0ZO0cO.OxbQYKdohizG', 'Surulere, Lagos', 'Product manager, first-time SabiServe customer.', NULL, 'active'),
('provider','Emeka Ikechukwu',  'Emeka Plumbing & Electrical', 'emeka@sabiserve.com.ng', '08061112233', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Ikeja, Lagos', 'Certified plumber and electrician with 8 years of experience fixing homes across Lagos. Same-day call-outs, honest quotes.', 1, 'active'),
('provider','Funke Adeleke',    'Funke Stitches Fashion House', 'funke@sabiserve.com.ng', '08062223344', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Lekki, Lagos', 'Bridal and corporate tailoring. Six years dressing Lagos brides and banks — precision cuts, premium finishes.', 5, 'active'),
('provider','Samuel Adewale',   'SparkPro Electrical Services', 'samuel@sabiserve.com.ng', '08063334455', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Abuja (FCT)', 'COREN-registered electrician: rewiring, fault finding, solar inverters and smart metering across the FCT.', 2, 'active'),
('provider','Ngozi Chukwu',     'Glam by Ngozi', 'ngozi@sabiserve.com.ng', '08064445566', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Port Harcourt', 'Mobile hair stylist and makeup artist. Bridal trials, gele, knotless braids — I come to you.', 8, 'active'),
('provider','Ibrahim Yusuf',    'CleanPro Facility Services', 'ibrahim@sabiserve.com.ng', '08065556677', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Surulere, Lagos', 'Deep cleaning, fumigation and post-construction cleanup for homes and offices. Insured and uniformed team.', 4, 'active'),
('provider','Tunde Balogun',    'BrightPath Home Tutoring', 'tunde@sabiserve.com.ng', '08066667788', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Ibadan', 'WAEC and JAMB specialist. 120+ students A1-A6 in Mathematics and Sciences since 2017.', 10, 'active'),
('provider','Chiamaka Obi',     'Chiamaka Photography Studio', 'chiamaka@sabiserve.com.ng', '08067778899', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Enugu', 'Weddings, portraits and product photography. Studio in Enugu, nationwide coverage on request.', 7, 'active'),
('provider','Maryam Bello',     'TastyTreats Catering', 'maryam@sabiserve.com.ng', '08068889900', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Abuja (FCT)', 'Small chops, shawarma, event buffets and trays. Feeding 50 to 5,000 guests — halal options available.', 6, 'active'),
('provider','Kosiso Nnamdi',    'Nnamdi Auto Clinic', 'kosiso@sabiserve.com.ng', '08069990011', '$2y$12$UNN2ak.CbHi/hDr9O9/gXO9WF0RMwK5OEoJOXG4CLpSVo92xQ9fpe', 'Yaba, Lagos', 'Computerised diagnostics, servicing and AC regas for Japanese and European cars. Free pickup within Yaba.', 9, 'active');

-- ---------- provider listings ----------
INSERT INTO listings (provider_id, category_id, title, description, location, price_hint, status) VALUES
((SELECT id FROM (SELECT id FROM users WHERE email='emeka@sabiserve.com.ng') x), 1, 'Same-day pipe leak repairs', 'Leak detection, burst pipes, tap and cistern replacements. Genuine parts, 30-day workmanship guarantee.', 'Ikeja, Lagos', 'From ₦15,000 / visit', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='emeka@sabiserve.com.ng') x), 2, 'Fault finding & meter installation', 'Tripping fuses, full or partial rewiring, prepaid meter installation and change of tariff.', 'Ikeja, Lagos', 'From ₦20,000 / job', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='funke@sabiserve.com.ng') x), 5, 'Bridal gele & gown styling', 'Beaded gowns, aso-ebi sets, gele tying and fittings — in studio or at your venue.', 'Lekki, Lagos', 'From ₦80,000 / gown', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='funke@sabiserve.com.ng') x), 5, 'Corporate uniforms (bank-grade)', 'Complete uniforms for teams of 10 to 500 — stitched, branded and delivered on schedule.', 'Lekki, Lagos', 'Quote on request', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='samuel@sabiserve.com.ng') x), 2, 'Solar inverter installation', 'Inverter sales, installation and servicing. Lithium and tubular systems, net-metering advice.', 'Abuja (FCT)', 'From ₦450,000 / 2.5kVA', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='ngozi@sabiserve.com.ng') x), 8, 'Bridal styling packages', 'Hair + makeup + gele for bride and bridesmaids. Venue service across Rivers State.', 'Port Harcourt', 'From ₦150,000 / bride', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='ibrahim@sabiserve.com.ng') x), 4, 'Deep home cleaning', 'Full-house deep clean: kitchen degreasing, descaling, wardrobe interiors and post-construction dust removal.', 'Surulere, Lagos', 'From ₦35,000 / visit', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='tunde@sabiserve.com.ng') x), 10, 'WAEC/JAMB Mathematics coaching', 'Weekly home sessions or online. Term-based packages with mock exams and parent reports.', 'Ibadan', 'From ₦12,000 / session', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='chiamaka@sabiserve.com.ng') x), 7, 'Wedding coverage (full day)', 'Two photographers, one videographer, edited highlights in 14 days, raw files included.', 'Nationwide', 'From ₦350,000 / event', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='maryam@sabiserve.com.ng') x), 6, 'Small chops & event buffets', 'Mini samosas, spring rolls, puff-puff and full event buffets with serving staff.', 'Abuja (FCT)', 'From ₦1,500 / plate', 'active'),
((SELECT id FROM (SELECT id FROM users WHERE email='kosiso@sabiserve.com.ng') x), 9, 'Computerised engine diagnostics', 'OBD scanning, fault codes, servicing and AC regas. Japanese and European models.', 'Yaba, Lagos', 'From ₦10,000 / scan', 'active');

-- ---------- active subscriptions (30-day demo windows) ----------
INSERT INTO subscriptions (user_id, role, plan, amount, reference, status, starts_at, ends_at) VALUES
((SELECT id FROM (SELECT id FROM users WHERE email='demo@sabiserve.com.ng') x), 'user', 'monthly', 2000.00, 'SEED-DEMO-001', 'active', NOW() - INTERVAL 5 DAY, NOW() + INTERVAL 25 DAY),
((SELECT id FROM (SELECT id FROM users WHERE email='emeka@sabiserve.com.ng') x), 'provider', 'monthly', 2000.00, 'SEED-DEMO-002', 'active', NOW() - INTERVAL 20 DAY, NOW() + INTERVAL 10 DAY),
((SELECT id FROM (SELECT id FROM users WHERE email='funke@sabiserve.com.ng') x), 'provider', 'monthly', 2000.00, 'SEED-DEMO-003', 'active', NOW() - INTERVAL 70 DAY, NOW() + INTERVAL 60 DAY),
((SELECT id FROM (SELECT id FROM users WHERE email='samuel@sabiserve.com.ng') x), 'provider', 'monthly', 2000.00, 'SEED-DEMO-004', 'active', NOW() - INTERVAL 12 DAY, NOW() + INTERVAL 18 DAY),
((SELECT id FROM (SELECT id FROM users WHERE email='ngozi@sabiserve.com.ng') x), 'provider', 'monthly', 2000.00, 'SEED-DEMO-005', 'active', NOW() - INTERVAL 8 DAY, NOW() + INTERVAL 22 DAY),
((SELECT id FROM (SELECT id FROM users WHERE email='maryam@sabiserve.com.ng') x), 'provider', 'monthly', 2000.00, 'SEED-DEMO-006', 'active', NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 28 DAY);

-- ---------- sample reviews ----------
INSERT INTO reviews (provider_id, user_id, rating, comment, status) VALUES
((SELECT id FROM (SELECT id FROM users WHERE email='emeka@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='demo@sabiserve.com.ng') x), 5, 'Emeka fixed a burst pipe at 11pm the same day I booked. Professional, neat and transparent pricing.', 'visible'),
((SELECT id FROM (SELECT id FROM users WHERE email='emeka@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='staff@sabiserve.com.ng') x), 5, 'Excellent workmanship on our office rewiring. Highly recommended.', 'visible'),
((SELECT id FROM (SELECT id FROM users WHERE email='funke@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='demo@sabiserve.com.ng') x), 5, 'My aso-ebi set was the talk of the wedding. Funke is a magician with fabric.', 'visible'),
((SELECT id FROM (SELECT id FROM users WHERE email='samuel@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='demo@sabiserve.com.ng') x), 4, 'Solid solar installation. Took a day longer than promised but the quality is outstanding.', 'visible'),
((SELECT id FROM (SELECT id FROM users WHERE email='ngozi@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='staff@sabiserve.com.ng') x), 5, 'Ngozi did our bridal party hair and makeup — everyone cried (happy tears!).', 'visible');

-- ---------- sample enquiry ----------
INSERT INTO enquiries (user_id, provider_id, subject, message, provider_reply, status) VALUES
((SELECT id FROM (SELECT id FROM users WHERE email='demo@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='emeka@sabiserve.com.ng') x), 'Kitchen pipe leak', 'Good morning Emeka, water is dripping under the sink and pooling inside the cabinet. Can you come today?', 'Good morning! Yes — I can be there by 4pm today. Please clear under the sink before I arrive. Quote after inspection.', 'responded'),
((SELECT id FROM (SELECT id FROM users WHERE email='demo@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='samuel@sabiserve.com.ng') x), 'Solar for 3-bed flat', 'I want to convert my 3-bed flat in Abuja to solar. Ballpark budget and timeline?', NULL, 'pending');

-- ---------- sample saved provider + message ----------
INSERT INTO saved_providers (user_id, provider_id) VALUES
((SELECT id FROM (SELECT id FROM users WHERE email='demo@sabiserve.com.ng') x), (SELECT id FROM (SELECT id FROM users WHERE email='emeka@sabiserve.com.ng') x));
INSERT INTO contact_messages (name, email, subject, message, status) VALUES
('Obinna Eze', 'obinna@example.com', 'Partnership enquiry', 'We run a facility management firm in Lagos and would like to list as a provider. Who should we talk to?', 'new');

SET FOREIGN_KEY_CHECKS = 1;
