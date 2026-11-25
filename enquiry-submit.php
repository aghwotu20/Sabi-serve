<?php
require_once __DIR__ . '/includes/functions.php';
verify_csrf();
$me = require_login();
if ($me['role'] !== 'user') { flash('error', 'Only customer accounts can send enquiries.'); redirect($_POST['back'] ?? '/'); }
require_active_subscription($me);
$providerId = (int)($_POST['provider_id'] ?? 0);
$subject = trim($_POST['subject'] ?? ''); $message = trim($_POST['message'] ?? '');
if ($providerId <= 0 || $subject === '' || $message === '') { flash('error', 'Please fill in the subject and details.'); redirect($_POST['back'] ?? '/'); }
$st = db()->prepare("SELECT id FROM users WHERE id = ? AND role = 'provider' AND status = 'active'"); $st->execute([$providerId]);
if (!$st->fetchColumn()) { flash('error', 'That provider is unavailable.'); redirect('browse.php'); }
$st = db()->prepare('INSERT INTO enquiries (user_id, provider_id, subject, message, status) VALUES (?, ?, ?, ?, \'pending\')');
$st->execute([$me['id'], $providerId, mb_substr($subject, 0, 180), $message]);
flash('success', 'Enquiry sent! The provider will respond shortly. Track it in your dashboard.');
redirect('/user/index.php?tab=enquiries');
