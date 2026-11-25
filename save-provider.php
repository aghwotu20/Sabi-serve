<?php
require_once __DIR__ . '/includes/functions.php';
verify_csrf();
$me = require_login();
if ($me['role'] !== 'user') { flash('error', 'Only customer accounts can save providers.'); redirect('/'); }
$providerId = (int)($_POST['provider_id'] ?? 0);
$exists = db()->prepare('SELECT 1 FROM saved_providers WHERE user_id = ? AND provider_id = ?'); $exists->execute([$me['id'], $providerId]);
if ($exists->fetchColumn()) { db()->prepare('DELETE FROM saved_providers WHERE user_id = ? AND provider_id = ?')->execute([$me['id'], $providerId]); flash('info', 'Removed from your saved providers.'); }
else { db()->prepare('INSERT IGNORE INTO saved_providers (user_id, provider_id) VALUES (?, ?)')->execute([$me['id'], $providerId]); flash('success', 'Saved to your providers list.'); }
redirect($_POST['back'] ?? '/');
