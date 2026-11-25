<?php
require_once __DIR__ . '/config.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        http_response_code(500);
        $msg = APP_DEBUG ? $e->getMessage() : 'We could not connect to the database. If you just deployed, edit includes/config.php with your cPanel MySQL details and import sabiserve.sql via phpMyAdmin.';
        die('<!doctype html><meta charset="utf-8"><title>SabiServe — Database setup</title><div style="font-family:system-ui;max-width:560px;margin:80px auto;padding:32px;border:1px solid #e5e7eb;border-radius:16px;text-align:center;color:#064e3b"><h2 style="margin:0 0 8px">Database not connected</h2><p style="color:#4b5563">' . htmlspecialchars($msg) . '</p></div>');
    }
    return $pdo;
}
