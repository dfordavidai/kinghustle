<?php
/**
 * HustleKingdom — Front Controller
 * Every request comes here. We resolve the route, check auth, dispatch.
 */

define('HK_ROOT', __DIR__);
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Middleware.php';

Auth::start();

// ── PARSE URL ────────────────────────────────────────────────────────────────
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath    = rtrim(dirname($scriptName), '/');
$path        = '/' . ltrim(substr(urldecode(parse_url($requestUri, PHP_URL_PATH)), strlen($basePath)), '/');
$path        = rtrim($path, '/') ?: '/';
$method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── DISPATCHER ───────────────────────────────────────────────────────────────
function dispatch(string $file, bool $requiresAuth, bool $requiresPro): never {
    if ($requiresAuth)  Auth::guard();
    if ($requiresPro)   Auth::guardPro();
    if (!file_exists($file)) Response::notFound();
    require $file;
    exit;
}

// ── DYNAMIC ROUTES (checked first, same pattern as /location) ────────────────

// /location/{state} and /location/{state}/{city}
if (preg_match('#^/location(?:/([^/]+))?(?:/([^/]+))?$#', $path, $m)) {
    $_GET['state'] = $m[1] ?? '';
    $_GET['city']  = $m[2] ?? '';
    dispatch(HK_ROOT . '/pages/location.php', false, false);
}

// /hustle/{slug}
if (preg_match('#^/hustle/([a-z0-9\-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    dispatch(HK_ROOT . '/pages/hustle-detail.php', false, false);
}

// Public pages
if ($path === '/' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/home.php', false, false);

// Auth
if ($path === '/auth/login')
    dispatch(HK_ROOT . '/auth/login.php', false, false);

if ($path === '/auth/register')
    dispatch(HK_ROOT . '/auth/register.php', false, false);

if ($path === '/auth/logout' && $method === 'GET')
    dispatch(HK_ROOT . '/auth/logout.php', true, false);

if ($path === '/auth/forgot')
    dispatch(HK_ROOT . '/auth/forgot.php', false, false);

if ($path === '/auth/verify')
    dispatch(HK_ROOT . '/auth/verify.php', false, false);

// Protected pages
if ($path === '/dashboard' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/dashboard.php', true, false);

if ($path === '/upgrade')
    dispatch(HK_ROOT . '/pages/upgrade.php', true, false);

if ($path === '/payment/callback' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/payment-callback.php', true, false);

// Feature pages — same direct dispatch pattern as /location
if ($path === '/hustles' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/hustles.php', false, false);

if ($path === '/execute' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/execute.php', true, false);

if ($path === '/ideas' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/ideas.php', false, false);

if ($path === '/tools' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/tools.php', false, false);

if ($path === '/ai' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/ai.php', true, false);

// API endpoints
if ($path === '/api/profile' && $method === 'GET')
    dispatch(HK_ROOT . '/api/profile.php', true, false);

if ($path === '/api/profile' && $method === 'PUT')
    dispatch(HK_ROOT . '/api/profile.php', true, false);

if ($path === '/api/income' && $method === 'GET')
    dispatch(HK_ROOT . '/api/income.php', true, false);

if ($path === '/api/income' && $method === 'POST')
    dispatch(HK_ROOT . '/api/income.php', true, false);

if ($path === '/api/income' && $method === 'DELETE')
    dispatch(HK_ROOT . '/api/income.php', true, false);

if ($path === '/api/goals' && $method === 'GET')
    dispatch(HK_ROOT . '/api/goals.php', true, false);

if ($path === '/api/goals' && $method === 'POST')
    dispatch(HK_ROOT . '/api/goals.php', true, false);

if ($path === '/api/goals' && $method === 'PUT')
    dispatch(HK_ROOT . '/api/goals.php', true, false);

if ($path === '/api/goals' && $method === 'DELETE')
    dispatch(HK_ROOT . '/api/goals.php', true, false);

if ($path === '/api/hustles' && $method === 'GET')
    dispatch(HK_ROOT . '/api/hustles.php', false, false);

if ($path === '/api/saved' && $method === 'GET')
    dispatch(HK_ROOT . '/api/saved.php', true, false);

if ($path === '/api/saved' && $method === 'POST')
    dispatch(HK_ROOT . '/api/saved.php', true, false);

if ($path === '/api/referral' && $method === 'GET')
    dispatch(HK_ROOT . '/api/referral.php', true, false);

if ($path === '/api/subscription' && $method === 'GET')
    dispatch(HK_ROOT . '/api/subscription.php', true, false);

if ($path === '/api/webhook/paystack' && $method === 'POST')
    dispatch(HK_ROOT . '/api/subscription.php', false, false);

if ($path === '/api/migrate' && $method === 'POST')
    dispatch(HK_ROOT . '/api/migrate.php', true, false);

// Nothing matched
Response::notFound();
