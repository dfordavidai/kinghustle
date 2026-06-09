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
$requestUri  = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName  = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$basePath    = rtrim(dirname($scriptName), '/');
$path        = '/' . ltrim(substr(urldecode(parse_url($requestUri, PHP_URL_PATH)), strlen($basePath)), '/');
$path        = rtrim($path, '/') ?: '/';
$method      = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// ── DEBUG: dump all server vars so we can see what Railway passes ─────────────
if (isset($_GET['_debug'])) {
    header('Content-Type: text/plain');
    echo "REQUEST_URI  = " . ($_SERVER['REQUEST_URI']   ?? 'N/A') . "\n";
    echo "SCRIPT_NAME  = " . ($_SERVER['SCRIPT_NAME']   ?? 'N/A') . "\n";
    echo "PATH_INFO    = " . ($_SERVER['PATH_INFO']      ?? 'N/A') . "\n";
    echo "QUERY_STRING = " . ($_SERVER['QUERY_STRING']   ?? 'N/A') . "\n";
    echo "basePath     = $basePath\n";
    echo "path         = $path\n";
    echo "method       = $method\n";
    echo "\n--- All SERVER vars ---\n";
    foreach ($_SERVER as $k => $v) echo "$k = $v\n";
    exit;
}

// ── DISPATCHER ───────────────────────────────────────────────────────────────
function dispatch(string $file, bool $requiresAuth, bool $requiresPro): never {
    if ($requiresAuth)  Auth::guard();
    if ($requiresPro)   Auth::guardPro();
    if (!file_exists($file)) Response::notFound();
    require $file;
    exit;
}

// ── DYNAMIC ROUTES ───────────────────────────────────────────────────────────
if (preg_match('#^/location(?:/([^/]+))?(?:/([^/]+))?$#', $path, $m)) {
    $_GET['state'] = $m[1] ?? '';
    $_GET['city']  = $m[2] ?? '';
    dispatch(HK_ROOT . '/pages/location.php', false, false);
}

if (preg_match('#^/hustle/([a-z0-9\-]+)$#', $path, $m)) {
    $_GET['slug'] = $m[1];
    dispatch(HK_ROOT . '/pages/hustle-detail.php', false, false);
}

// ── ALL ROUTES AS DIRECT DISPATCH (same pattern as /location) ────────────────
if ($path === '/' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/home.php', false, false);

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

if ($path === '/dashboard' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/dashboard.php', true, false);

if ($path === '/upgrade')
    dispatch(HK_ROOT . '/pages/upgrade.php', true, false);

if ($path === '/payment/callback' && $method === 'GET')
    dispatch(HK_ROOT . '/pages/payment-callback.php', true, false);

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

if ($path === '/api/profile')
    dispatch(HK_ROOT . '/api/profile.php', true, false);

if ($path === '/api/income')
    dispatch(HK_ROOT . '/api/income.php', true, false);

if ($path === '/api/goals')
    dispatch(HK_ROOT . '/api/goals.php', true, false);

if ($path === '/api/hustles' && $method === 'GET')
    dispatch(HK_ROOT . '/api/hustles.php', false, false);

if ($path === '/api/saved')
    dispatch(HK_ROOT . '/api/saved.php', true, false);

if ($path === '/api/referral' && $method === 'GET')
    dispatch(HK_ROOT . '/api/referral.php', true, false);

if ($path === '/api/subscription')
    dispatch(HK_ROOT . '/api/subscription.php', true, false);

if ($path === '/api/webhook/paystack' && $method === 'POST')
    dispatch(HK_ROOT . '/api/subscription.php', false, false);

if ($path === '/api/migrate' && $method === 'POST')
    dispatch(HK_ROOT . '/api/migrate.php', true, false);

// Nothing matched — show debug info instead of plain 404
http_response_code(404);
header('Content-Type: text/plain');
echo "404 — No route matched\n\n";
echo "REQUEST_URI = " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "SCRIPT_NAME = " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "basePath    = $basePath\n";
echo "path        = $path\n";
echo "method      = $method\n";
