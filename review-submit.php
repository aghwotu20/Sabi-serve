<?php
require_once __DIR__ . '/includes/functions.php';
verify_csrf();
$me = require_login();
if ($me['role'] !== 'user') { flash('error', 'Only customer accounts can leave reviews.'); redirect('browse.php'); }
require_active_subscription($me);
$providerId = (int)($_POST['provider_id'] ?? 0);
$rating = max(1, min(5, (int)($_POST['rating'] ?? 0))); $comment = trim($_POST['comment'] ?? '');
if ($providerId <= 0 || $comment === '') { flash('error', 'Please complete the review form.'); redirect('provider.php?id=' . $providerId); }
$dup = db()->prepare('SELECT id FROM reviews WHERE user_id = ? AND provider_id = ?'); $dup->execute([$me['id'], $providerId]);
if ($dup->fetchColumn()) { flash('error', 'You have already reviewed this provider.'); redirect('provider.php?id=' . $providerId); }
$st = db()->prepare('INSERT INTO reviews (provider_id, user_id, rating, comment, status) VALUES (?, ?, ?, ?, \'visible\')');
$st->execute([$providerId, $me['id'], $rating, mb_substr($comment, 0, 600)]);
flash('success', 'Thank you! Your review has been published.');
redirect('provider.php?id=' . $providerId);
