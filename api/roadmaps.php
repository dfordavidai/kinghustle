<?php
/**
 * HustleKingdom — Roadmaps API
 * GET /api/roadmaps.php            → list all roadmaps (id, hustle_id, name, emoji, summary, duration)
 * GET /api/roadmaps.php?id=slug    → full roadmap with phases for a given hustle slug
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';

header('Content-Type: application/json');
Auth::start();

if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'GET') {
    Response::error('Method not allowed.', 405);
}

// Single roadmap by hustle_id/slug
if (!empty($_GET['id'])) {
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['id']));
    $row  = DB::one(
        'SELECT * FROM roadmaps WHERE hustle_id = :id',
        [':id' => $slug]
    );
    if (!$row) Response::error('Roadmap not found.', 404);

    $row['phases'] = json_decode($row['phases'] ?? '[]', true);
    Response::success('OK', $row);
}

// List all roadmaps (lightweight — no phases)
$rows = DB::query(
    'SELECT id, hustle_id, name, emoji, summary, duration FROM roadmaps ORDER BY id ASC'
);
Response::success('OK', ['roadmaps' => $rows, 'total' => count($rows)]);
