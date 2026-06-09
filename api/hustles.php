<?php
defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';

header('Content-Type: application/json');
Auth::start();

$method = strtoupper($_SERVER['REQUEST_METHOD']);
if ($method !== 'GET') Response::error('Method not allowed.', 405);

// ── Single hustle by slug ────────────────────────────────────────────────────
if (!empty($_GET['slug'])) {
    $slug   = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug']));
    $hustle = DB::one(
        'SELECT * FROM hustles WHERE slug = :slug AND is_active = 1',
        [':slug' => $slug]
    );
    if (!$hustle) Response::error('Hustle not found.', 404);

    // Decode JSON fields
    $hustle['skills_needed']  = json_decode($hustle['skills_needed']  ?? '[]', true);
    $hustle['location_tags']  = json_decode($hustle['location_tags']  ?? '[]', true);
    $hustle['steps']          = json_decode($hustle['steps']          ?? '[]', true);

    // Increment view count async-ish (fire and forget in same request)
    DB::run('UPDATE hustles SET view_count = view_count + 1 WHERE id = :id', [':id' => $hustle['id']]);

    Response::success('OK', $hustle);
}

// ── List hustles with filters ─────────────────────────────────────────────────
$where  = ['h.is_active = 1'];
$params = [];

if (!empty($_GET['category'])) {
    $where[]            = 'h.category = :cat';
    $params[':cat']     = $_GET['category'];
}

if (!empty($_GET['difficulty'])) {
    $where[]            = 'h.difficulty = :diff';
    $params[':diff']    = $_GET['difficulty'];
}

if (!empty($_GET['featured'])) {
    $where[]            = 'h.is_featured = 1';
}

if (!empty($_GET['city']) || !empty($_GET['state'])) {
    $loc            = $_GET['city'] ?? $_GET['state'];
    $where[]        = '(JSON_CONTAINS(h.location_tags, :loc) OR JSON_CONTAINS(h.location_tags, \'"Online"\'))';
    $params[':loc'] = json_encode($loc);
}

if (!empty($_GET['search'])) {
    $where[]           = 'MATCH(h.name, h.description) AGAINST (:q IN BOOLEAN MODE)';
    $params[':q']      = $_GET['search'] . '*';
}

$limit  = max(1, min(50, (int)($_GET['limit']  ?? 20)));
$offset = max(0, (int)($_GET['offset'] ?? 0));

$sql = 'SELECT h.id, h.name, h.slug, h.emoji, h.category, h.description,
               h.income_min, h.income_max, h.income_period, h.difficulty,
               h.capital_needed, h.skills_needed, h.location_tags,
               h.is_featured, h.save_count
        FROM hustles h
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY h.is_featured DESC, h.save_count DESC, h.id ASC
        LIMIT ' . $limit . ' OFFSET ' . $offset;

$hustles = DB::query($sql, $params);

// Decode JSON fields
foreach ($hustles as &$h) {
    $h['skills_needed'] = json_decode($h['skills_needed'] ?? '[]', true);
    $h['location_tags'] = json_decode($h['location_tags'] ?? '[]', true);
}

// Get total count for pagination
$countSql = 'SELECT COUNT(*) as cnt FROM hustles h WHERE ' . implode(' AND ', $where);
$total    = (int)(DB::one($countSql, $params)['cnt'] ?? 0);

Response::success('OK', [
    'hustles' => $hustles,
    'total'   => $total,
    'limit'   => $limit,
    'offset'  => $offset,
]);
