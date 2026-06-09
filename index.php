<?php
/**
 * HustleKingdom — Front Controller
 */

define('HK_ROOT', __DIR__);
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Middleware.php';

Auth::start();

// ── PARSE URL ────────────────────────────────────────────────────────────────
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path       = rtrim(urldecode(parse_url($requestUri, PHP_URL_PATH) ?? '/'), '/') ?: '/';
$method     = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── DISPATCHER ───────────────────────────────────────────────────────────────
function dispatch(string $file, bool $requiresAuth = false, bool $requiresPro = false): never {
    if ($requiresAuth) Auth::guard();
    if ($requiresPro)  Auth::guardPro();
    if (!file_exists($file)) Response::notFound();
    require $file;
    exit;
}

// ── HOME ─────────────────────────────────────────────────────────────────────
if ($path === '/') {
    dispatch(HK_ROOT . '/pages/home.php');
}

// ── AUTH ─────────────────────────────────────────────────────────────────────
if ($path === '/auth/login') {
    dispatch(HK_ROOT . '/auth/login.php');
}
if ($path === '/auth/register') {
    dispatch(HK_ROOT . '/auth/register.php');
}
if ($path === '/auth/logout') {
    dispatch(HK_ROOT . '/auth/logout.php', true);
}
if ($path === '/auth/forgot') {
    dispatch(HK_ROOT . '/auth/forgot.php');
}
if ($path === '/auth/verify') {
    dispatch(HK_ROOT . '/auth/verify.php');
}

// ── FEATURE PAGES ────────────────────────────────────────────────────────────
if ($path === '/hustles') {
    dispatch(HK_ROOT . '/pages/hustles.php');
}
if ($path === '/execute') {
    dispatch(HK_ROOT . '/pages/execute.php', true);
}
if ($path === '/ai') {
    dispatch(HK_ROOT . '/pages/ai.php', true);
}
if ($path === '/ideas') {
    dispatch(HK_ROOT . '/pages/ideas.php', true);
}
if ($path === '/tools') {
    dispatch(HK_ROOT . '/pages/tools.php', true);
}

// ── PROTECTED PAGES ──────────────────────────────────────────────────────────
if ($path === '/dashboard') {
    dispatch(HK_ROOT . '/pages/dashboard.php', true);
}
if ($path === '/upgrade') {
    dispatch(HK_ROOT . '/pages/upgrade.php', true);
}
if ($path === '/payment/callback') {
    dispatch(HK_ROOT . '/pages/payment-callback.php', true);
}

// ── DYNAMIC ROUTES ───────────────────────────────────────────────────────────
if (preg_match('#^/location(?:/([^/]+))?(?:/([^/]+))?$#', $path, $m)) {
    $_GET['state'] = $m[1] ?? '';
    $_GET['city']  = $m[2] ?? '';
    dispatch(HK_ROOT . '/pages/location.php');
}

if (preg_match('#^/hustle/([a-z0-9\-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    dispatch(HK_ROOT . '/pages/hustle-detail.php');
}

// ── API ──────────────────────────────────────────────────────────────────────
if ($path === '/api/profile') {
    dispatch(HK_ROOT . '/api/profile.php', true);
}
if ($path === '/api/income') {
    dispatch(HK_ROOT . '/api/income.php', true);
}
if ($path === '/api/goals') {
    dispatch(HK_ROOT . '/api/goals.php', true);
}
if ($path === '/api/hustles') {
    dispatch(HK_ROOT . '/api/hustles.php');
}
if ($path === '/api/saved') {
    dispatch(HK_ROOT . '/api/saved.php', true);
}
if ($path === '/api/referral') {
    dispatch(HK_ROOT . '/api/referral.php', true);
}
if ($path === '/api/subscription') {
    dispatch(HK_ROOT . '/api/subscription.php', true);
}
if ($path === '/api/webhook/paystack') {
    dispatch(HK_ROOT . '/api/subscription.php');
}
if ($path === '/api/migrate') {
    dispatch(HK_ROOT . '/api/migrate.php', true);
}

// ── SEED (one-time data import — delete seed.php after use) ──────────────────
if ($path === '/seed') {
    dispatch(HK_ROOT . '/seed.php');
}

// ── 404 ──────────────────────────────────────────────────────────────────────
Response::notFound();
