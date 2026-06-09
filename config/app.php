<?php
/**
 * HustleKingdom — Application Configuration
 * Reads from $_ENV (set in .env via cPanel or server config).
 * Falls back to defaults for local dev.
 */

// Load .env file if it exists (simple parser — no composer needed)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!array_key_exists($key, $_ENV) && !array_key_exists($key, $_SERVER)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// ── APP ──────────────────────────────────────────────────────────────────────
define('APP_NAME',    $_ENV['APP_NAME']    ?? 'Hustle Kingdom');
define('APP_URL',     rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'));
define('APP_ENV',     $_ENV['APP_ENV']     ?? 'production'); // production | development
define('APP_DEBUG',   ($_ENV['APP_DEBUG']  ?? 'false') === 'true');

// ── DATABASE ─────────────────────────────────────────────────────────────────
// Railway MySQL plugin injects MYSQL_URL = mysql://user:pass@host:port/dbname
// Fall back to individual DB_* vars for other hosts / local dev
if (!empty($_ENV['MYSQL_URL'])) {
    $dsn = parse_url($_ENV['MYSQL_URL']);
    define('DB_HOST', $dsn['host']                         ?? 'localhost');
    define('DB_PORT', (int)($dsn['port']                   ?? 3306));
    define('DB_NAME', ltrim($dsn['path'] ?? 'hustlekingdom', '/'));
    define('DB_USER', $dsn['user']                         ?? 'root');
    define('DB_PASS', isset($dsn['pass']) ? urldecode($dsn['pass']) : '');
} else {
    define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
    define('DB_PORT', (int)($_ENV['DB_PORT'] ?? 3306));
    define('DB_NAME', $_ENV['DB_NAME'] ?? 'hustlekingdom');
    define('DB_USER', $_ENV['DB_USER'] ?? 'root');
    define('DB_PASS', $_ENV['DB_PASS'] ?? '');
}
define('DB_CHARSET',  'utf8mb4');

// ── SESSION ──────────────────────────────────────────────────────────────────
define('SESSION_NAME',     'hk_session');
define('SESSION_LIFETIME', 60 * 60 * 24 * 30); // 30 days

// ── SECURITY ─────────────────────────────────────────────────────────────────
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LEN',  8);

// ── PAYSTACK ─────────────────────────────────────────────────────────────────
define('PAYSTACK_SECRET_KEY',  $_ENV['PAYSTACK_SECRET_KEY']  ?? '');
define('PAYSTACK_PUBLIC_KEY',  $_ENV['PAYSTACK_PUBLIC_KEY']  ?? '');
define('PAYSTACK_WEBHOOK_SECRET', $_ENV['PAYSTACK_WEBHOOK_SECRET'] ?? '');

// ── PLANS ────────────────────────────────────────────────────────────────────
define('PRO_MONTHLY_AMOUNT',  250000); // Paystack uses kobo — ₦2,500
define('PRO_ANNUAL_AMOUNT',  1999900); // ₦19,999
define('PRO_MONTHLY_DAYS',   30);
define('PRO_ANNUAL_DAYS',    365);
define('REFERRAL_NEEDED',    2);       // referrals needed for free Pro week
define('REFERRAL_REWARD_DAYS', 7);

// ── PATHS ────────────────────────────────────────────────────────────────────
define('ROOT_DIR',   dirname(__DIR__));
define('PUBLIC_DIR', ROOT_DIR);
define('CORE_DIR',   ROOT_DIR . '/core');
define('PAGES_DIR',  ROOT_DIR . '/pages');
define('AUTH_DIR',   ROOT_DIR . '/auth');

// ── ERROR HANDLING ───────────────────────────────────────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT_DIR . '/logs/error.log');
}
