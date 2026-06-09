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

// ── ROUTE TABLE ──────────────────────────────────────────────────────────────
// Format: 'METHOD /path' => [file, requiresAuth, requiresPro]
$routes = [

    // ── Public pages ─────────────────────────────────────────────────────────
    'GET /'                    => [HK_ROOT . '/pages/dashboard.php',    true,  false],

    // ── Auth ─────────────────────────────────────────────────────────────────
    'GET /auth/login'          => [HK_ROOT . '/auth/login.php',         false, false],
    'POST /auth/login'         => [HK_ROOT . '/auth/login.php',         false, false],
    'GET /auth/register'       => [HK_ROOT . '/auth/register.php',      false, false],
    'POST /auth/register'      => [HK_ROOT . '/auth/register.php',      false, false],
    'GET /auth/logout'         => [HK_ROOT . '/auth/logout.php',        true,  false],
    'GET /auth/forgot'         => [HK_ROOT . '/auth/forgot.php',        false, false],
    'POST /auth/forgot'        => [HK_ROOT . '/auth/forgot.php',        false, false],
    'GET /auth/verify'         => [HK_ROOT . '/auth/verify.php',        false, false],
    'POST /auth/verify'        => [HK_ROOT . '/auth/verify.php',        false, false],

    // ── Protected pages ───────────────────────────────────────────────────────
    'GET /dashboard'           => [HK_ROOT . '/pages/dashboard.php',    true,  false],
    'GET /upgrade'             => [HK_ROOT . '/pages/upgrade.php',      true,  false],
    'POST /upgrade'            => [HK_ROOT . '/pages/upgrade.php',      true,  false],
    'GET /payment/callback'    => [HK_ROOT . '/pages/payment-callback.php', true, false],

    // ── API endpoints ────────────────────────────────────────────────────────
    'GET /api/profile'         => [HK_ROOT . '/api/profile.php',        true,  false],
    'PUT /api/profile'         => [HK_ROOT . '/api/profile.php',        true,  false],
    'GET /api/income'          => [HK_ROOT . '/api/income.php',         true,  false],
    'POST /api/income'         => [HK_ROOT . '/api/income.php',         true,  false],
    'DELETE /api/income'       => [HK_ROOT . '/api/income.php',         true,  false],
    'GET /api/goals'           => [HK_ROOT . '/api/goals.php',          true,  false],
    'POST /api/goals'          => [HK_ROOT . '/api/goals.php',          true,  false],
    'PUT /api/goals'           => [HK_ROOT . '/api/goals.php',          true,  false],
    'DELETE /api/goals'        => [HK_ROOT . '/api/goals.php',          true,  false],
    'GET /api/hustles'         => [HK_ROOT . '/api/hustles.php',        false, false],
    'GET /api/saved'           => [HK_ROOT . '/api/saved.php',          true,  false],
    'POST /api/saved'          => [HK_ROOT . '/api/saved.php',          true,  false],
    'GET /api/referral'        => [HK_ROOT . '/api/referral.php',       true,  false],
    'GET /api/subscription'    => [HK_ROOT . '/api/subscription.php',   true,  false],
    'POST /api/webhook/paystack' => [HK_ROOT . '/api/subscription.php', false, false],
    'POST /api/migrate'        => [HK_ROOT . '/api/migrate.php',        true,  false],

];

// ── DYNAMIC ROUTES ───────────────────────────────────────────────────────────
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

// ── RESOLVE ROUTE ────────────────────────────────────────────────────────────
$routeKey = "$method $path";
if (isset($routes[$routeKey])) {
    [$file, $auth, $pro] = $routes[$routeKey];
    dispatch($file, $auth, $pro);
}

// Try without trailing slash variant
$altKey = "$method " . rtrim($path, '/');
if (isset($routes[$altKey])) {
    [$file, $auth, $pro] = $routes[$altKey];
    dispatch($file, $auth, $pro);
}

// Nothing matched
Response::notFound();

// ── DISPATCHER ───────────────────────────────────────────────────────────────
function dispatch(string $file, bool $requiresAuth, bool $requiresPro): never {
    if ($requiresAuth)  Auth::guard();
    if ($requiresPro)   Auth::guardPro();
    if (!file_exists($file)) Response::notFound();
    require $file;
    exit;
}
