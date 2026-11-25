<?php
/**
 * SabiServe :: configuration
 * ---------------------------
 * 1. For cPanel: create a MySQL database + user in cPanel → MySQL Databases,
 *    import sabiserve.sql via phpMyAdmin, then edit the 4 values below.
 * 2. APP_DEBUG should stay false on a live site.
 */
define('APP_NAME', 'SabiServe');
define('APP_DEBUG', false);
define('APP_ENV', 'production');

// --- MySQL database (edit these for your cPanel hosting) ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'sabiserve');
define('DB_USER', 'sabiserve');
define('DB_PASS', 'sabiserve_dev_pass');
define('DB_CHARSET', 'utf8mb4');

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');
define('MAX_PASSPORT_BYTES', 2 * 1024 * 1024);   // 2 MB
define('MAX_BRAND_BYTES', 2 * 1024 * 1024);      // 2 MB (logo / favicon)

date_default_timezone_set('Africa/Lagos');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
