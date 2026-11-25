<?php
require_once __DIR__ . '/db.php';

/* ---------- sessions ---------- */
function start_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('SABISERVESESS');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $secure]);
    session_start();
}
start_session();

/* ---------- output / escaping ---------- */
function h(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function redirect(string $url): void { header('Location: ' . $url); exit; }
function base_url(string $path = ''): string { return '/' . ltrim($path, '/'); }

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function flash_render(): void {
    if (empty($_SESSION['flash'])) return;
    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="flash flash-' . h($f['type']) . '"><span>' . h($f['msg']) . '</span><button type="button" class="flash-close" aria-label="Dismiss">&times;</button></div>';
    }
    unset($_SESSION['flash']);
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function verify_csrf(): void {
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
        http_response_code(400);
        flash('error', 'Security token expired. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }
}

/* ---------- settings ---------- */
function settings_all(): array {
    static $s = null;
    if ($s !== null) return $s;
    $s = [];
    try {
        foreach (db()->query('SELECT setting_key, setting_value FROM settings') as $row) $s[$row['setting_key']] = $row['setting_value'];
    } catch (Throwable $e) { $s = []; }
    return $s;
}
function setting(string $key, string $default = ''): string { $all = settings_all(); return $all[$key] ?? $default; }
function set_setting(string $key, string $value): void {
    $st = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
    $st->execute([$key, $value]);
    settings_all(); // warm
}
function save_settings(array $pairs): void {
    $st = db()->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()');
    foreach ($pairs as $k => $v) $st->execute([$k, $v]);
}

/* ---------- maintenance mode ---------- */
function maintenance_on(): bool { return setting('maintenance_mode', '0') === '1'; }
function enforce_maintenance(): void {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script === 'maintenance.php' || $script === 'login.php' || $script === 'logout.php') return;
    if (!maintenance_on()) return;
    $u = current_user();
    if ($u && $u['role'] === 'admin') return; // admins keep working
    http_response_code(503);
    require __DIR__ . '/maintenance.php';
    exit;
}

/* ---------- current user / auth ---------- */
function current_user(): ?array {
    static $u = null; static $loaded = false;
    if ($loaded) return $u;
    $loaded = true;
    $id = $_SESSION['user_id'] ?? null;
    if (!$id) return $u = null;
    $st = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $st->execute([(int)$id]);
    $u = $st->fetch() ?: null;
    if ($u) {
        if ($u['status'] === 'suspended') { session_destroy(); start_session(); $u = null; }
    }
    return $u;
}
function is_logged_in(): bool { return current_user() !== null; }
function require_login(): array {
    $u = current_user();
    if (!$u) { flash('error', 'Please log in to continue.'); redirect('/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? '/')); }
    return $u;
}
function require_role(array $roles): array {
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) { flash('error', 'You do not have permission to view that page.'); redirect(dashboard_url($u['role'])); }
    return $u;
}
function dashboard_url(string $role): string {
    return match ($role) {
        'admin' => '/admin/index.php',
        'staff' => '/staff/index.php',
        'provider' => '/provider/index.php',
        'user' => '/user/index.php',
        default => '/',
    };
}
function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?')->execute([$user['id']]);
}
function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) { $p = session_get_cookie_params(); setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']); }
    session_destroy();
}

/* ---------- subscriptions ---------- */
function subscription_amount(): int { return max(0, (int) setting('subscription_amount', '2000')); }
function active_subscription(int $userId): ?array {
    $st = db()->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active' AND ends_at > NOW() ORDER BY ends_at DESC LIMIT 1");
    $st->execute([$userId]);
    $row = $st->fetch();
    return $row ?: null;
}
function subscription_badge(int $userId): array {
    $sub = active_subscription($userId);
    if ($sub) return ['active' => true, 'label' => 'Active · renews ' . date('j M Y', strtotime($sub['ends_at']))];
    $pending = db()->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1");
    $pending->execute([$userId]);
    if ($pending->fetch()) return ['active' => false, 'label' => 'Payment pending verification'];
    return ['active' => false, 'label' => 'Not subscribed'];
}
function require_active_subscription(array $user): void {
    if (!in_array($user['role'], ['user', 'provider'], true)) return; // staff/admin exempt
    if (!active_subscription((int)$user['id'])) {
        flash('error', 'A ₦' . number_format(subscription_amount()) . ' monthly subscription is required. Please subscribe to continue.');
        redirect('/subscribe.php');
    }
}

/* ---------- uploads ---------- */
function handle_upload(string $field, array $allowedMimes, int $maxBytes, string $prefix): ?string {
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (code ' . $file['error'] . ').');
    if ($file['size'] > $maxBytes) throw new RuntimeException('File is too large. Maximum allowed is ' . round($maxBytes / 1048576) . ' MB.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']) ?: '';
    $ext = match ($mime) {
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif', 'image/x-icon', 'image/vnd.microsoft.icon' => 'ico', default => null,
    };
    if ($ext === null || !in_array($mime, $allowedMimes, true)) throw new RuntimeException('Unsupported file type. Allowed: JPG, PNG, WEBP' . (in_array('image/x-icon', $allowedMimes, true) ? ', ICO' : '') . '.');
    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
    $name = $prefix . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) throw new RuntimeException('Could not save the uploaded file.');
    return $name;
}
function passport_src(?string $passport): string {
    if ($passport && file_exists(UPLOAD_DIR . $passport)) return UPLOAD_URL . $passport;
    return '/assets/img/avatar-placeholder.svg';
}
function brand_src(?string $file, string $fallback): string {
    return ($file && file_exists(UPLOAD_DIR . $file)) ? UPLOAD_URL . $file : $fallback;
}

/* ---------- misc helpers ---------- */
function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name)); $a = strtoupper(substr($parts[0] ?? 'S', 0, 1)); $b = strtoupper(substr($parts[count($parts) - 1] ?? '', 0, 1));
    return $a . ($b !== $a ? $b : '');
}
function time_ago(string $datetime): string {
    $ts = strtotime($datetime); if (!$ts) return '';
    $diff = time() - $ts;
    return match (true) {
        $diff < 60 => 'just now',
        $diff < 3600 => floor($diff / 60) . ' min ago',
        $diff < 86400 => floor($diff / 3600) . ' hr ago',
        $diff < 2592000 => floor($diff / 86400) . ' days ago',
        default => date('j M Y', $ts),
    };
}
function star_rating(float $avg): string {
    $full = (int) floor($avg); $half = ($avg - $full) >= 0.4; $out = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) $out .= '<svg viewBox="0 0 24 24" class="star star-full"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        elseif ($i === $full + 1 && $half) $out .= '<svg viewBox="0 0 24 24" class="star star-half"><defs><linearGradient id="half' . $i . md5((string)$avg) . '"><stop offset="50%" stop-color="#FBBF24"/><stop offset="50%" stop-color="#E5E7EB"/></linearGradient></defs><path fill="url(#half' . $i . md5((string)$avg) . ')" d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
        else $out .= '<svg viewBox="0 0 24 24" class="star star-empty"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.77 5.82 21 7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
    }
    return $out;
}
function category_icon(string $key, int $size = 28): string {
    $icons = [
        'wrench' => '<path d="M14.7 6.3a4.5 4.5 0 0 0-5.94 5.6L3 18.66A2 2 0 1 0 5.34 21l6.76-5.76a4.5 4.5 0 0 0 5.6-5.94l-2.83 2.83-2.45-.71-.71-2.45 3.99-3.67z"/>',
        'bolt' => '<path d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/>',
        'sparkles' => '<path d="M12 3l1.9 4.6L18.5 9l-4.6 1.9L12 15.5l-1.9-4.6L5.5 9l4.6-1.4L12 3z"/><path d="M19 14l.9 2.1 2.1.9-2.1.9L19 20l-.9-2.1-2.1-.9 2.1-.9L19 14z"/>',
        'hammer' => '<path d="M14 4l6 6-2.3 2.3-6-6L14 4z"/><path d="M11.7 8.3 3 17l4 4 8.7-8.7" stroke-linejoin="round"/>',
        'paint' => '<path d="M4 5h16v6H4z"/><path d="M6 11v3a2 2 0 0 0 2 2h4v5h2v-5h2a2 2 0 0 0 2-2v-3"/>',
        'snow' => '<path d="M12 2v20M4 6l16 12M20 6L4 18M8 3l4 3 4-3M8 21l4-3 4 3" stroke-linecap="round"/>',
        'scissors' => '<circle cx="6" cy="6" r="2.4"/><circle cx="6" cy="18" r="2.4"/><path d="M8 7.5 20 19M8 16.5 20 5"/>',
        'pot' => '<path d="M4 10h16M6 10v6a4 4 0 0 0 4 4h4a4 4 0 0 0 4-4v-6M9 6c0-1.1.9-2 2-2M13 6c0-1.1.9-2 2-2" stroke-linecap="round"/>',
        'camera' => '<path d="M4 8h3l2-3h6l2 3h3v11H4z" stroke-linejoin="round"/><circle cx="12" cy="13" r="3.4"/>',
        'comb' => '<path d="M7 3v8a2 2 0 0 0 2 2 2 2 0 0 0 2-2V3M9 13v8M14 3v18M17 3v6a2 2 0 0 1-2 2" stroke-linecap="round"/>',
        'car' => '<path d="M4 15l1.5-5A2 2 0 0 1 7.4 8.5h9.2A2 2 0 0 1 18.5 10L20 15v4h-2.5v-1.5h-11V19H4v-4z" stroke-linejoin="round"/><path d="M7 12h10"/>',
        'book' => '<path d="M5 4h11a3 3 0 0 1 3 3v13H8a3 3 0 0 1-3-3V4z" stroke-linejoin="round"/><path d="M5 17a3 3 0 0 1 3-3h11"/>',
        'party' => '<path d="M4 20l4-11 7 7-11 4z" stroke-linejoin="round"/><path d="M13 5l1.5 1.5M18 9l1.5 1.5M15 3l.8 2M20 6l2 .8" stroke-linecap="round"/>',
        'code' => '<path d="M8 8l-5 4 5 4M16 8l5 4-5 4M13 6l-2 12" stroke-linecap="round" stroke-linejoin="round"/>',
        'shield' => '<path d="M12 3l8 3v6c0 4.5-3.2 7.8-8 9-4.8-1.2-8-4.5-8-9V6l8-3z" stroke-linejoin="round"/>',
        'truck' => '<path d="M3 7h11v10H3zM14 10h4l3 3v4h-7" stroke-linejoin="round"/><circle cx="7" cy="17" r="1.4"/><circle cx="17" cy="17" r="1.4"/>',
        'plug' => '<path d="M9 3v4M15 3v4M7 7h10v3a5 5 0 0 1-5 5 5 5 0 0 1-5-5V7z" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 15v6" stroke-linecap="round"/>',
        'home' => '<path d="M4 11.5 12 4l8 7.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.5 10v9h11v-9" stroke-linejoin="round"/><path d="M10.5 19v-4.5h3V19" stroke-linejoin="round"/>',
        'grid' => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
        'phone' => '<rect x="7" y="2" width="10" height="20" rx="2.5"/><path d="M10.5 18.5h3" stroke-linecap="round"/>',
        'heart' => '<path d="M12 20.3S4.4 15.6 4.4 9.4A4.5 4.5 0 0 1 12 6.2a4.5 4.5 0 0 1 7.6 3.2c0 6.2-7.6 10.9-7.6 10.9z" stroke-linejoin="round"/>',
        'group' => '<circle cx="9" cy="8" r="3.2"/><circle cx="16.6" cy="10" r="2.5"/><path d="M3.8 20a5.3 5.3 0 0 1 10.4 0M13.4 15.6a4.4 4.4 0 0 1 7 4.4" stroke-linecap="round"/>',
        'leaf' => '<path d="M5 19C5 9.5 11.5 5 21 5c0 10-5.5 14-16 14z" stroke-linejoin="round"/><path d="M5 19c4-6.5 8.5-9.5 12.5-11.5" stroke-linecap="round"/>',
        'scale' => '<path d="M12 4v16M8.5 20h7M5 7h14" stroke-linecap="round"/><path d="M7 7l-2.3 6a3.1 3.1 0 0 0 4.6 0L7 7zM17 7l-2.3 6a3.1 3.1 0 0 0 4.6 0L17 7z" stroke-linejoin="round"/>',
        'calculator' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8.5 7h7M8.5 12h.01M12 12h.01M15.5 12h.01M8.5 16h.01M12 16h.01M15.5 16h.01" stroke-linecap="round"/>',
        'music' => '<path d="M9 18V5l10-2v12.5" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="16.5" cy="15.5" r="2.5"/>',
        'droplet' => '<path d="M12 3s6.5 7.1 6.5 11.8a6.5 6.5 0 0 1-13 0C5.5 10.1 12 3 12 3z" stroke-linejoin="round"/>',
        'briefcase' => '<rect x="3" y="8" width="18" height="12" rx="2"/><path d="M9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2M3 13.5h18" stroke-linecap="round"/>',
    ];
    $p = $icons[$key] ?? $icons['wrench'];
    return '<svg width="' . (int)$size . '" height="' . (int)$size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}
function avatar_html(?string $passport, string $name, int $size = 96, string $class = ''): string {
    if ($passport && file_exists(UPLOAD_DIR . $passport)) {
        return '<img class="avatar ' . h($class) . '" src="' . h(UPLOAD_URL . $passport) . '" alt="' . h($name) . '" width="' . $size . '" height="' . $size . '" loading="lazy">';
    }
    return '<span class="avatar avatar-initials ' . h($class) . '" style="width:' . $size . 'px;height:' . $size . 'px;font-size:' . round($size * 0.38) . 'px" aria-hidden="true">' . h(initials($name)) . '</span>';
}
function client_ip(): string { return $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown'; }
