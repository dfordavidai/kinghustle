<?php
defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/Middleware.php';

header('Content-Type: application/json');
Auth::start();
Auth::guard();

$method = strtoupper($_SERVER['REQUEST_METHOD']);
$userId = Auth::id();

if ($method === 'GET') {
    $saved = DB::query(
        'SELECT sh.hustle_id, h.name, h.slug, h.emoji, h.category,
                h.income_min, h.income_max, h.income_period, h.difficulty, sh.created_at
         FROM saved_hustles sh
         JOIN hustles h ON h.id = sh.hustle_id
         WHERE sh.user_id = :uid
         ORDER BY sh.created_at DESC',
        [':uid' => $userId]
    );
    Response::success('OK', $saved);
}

if ($method === 'POST') {
    $body     = Middleware::jsonBody();
    $hustleId = (int)($body['hustle_id'] ?? 0);
    if (!$hustleId) Response::error('Missing hustle_id.', 400);

    // Verify hustle exists
    $hustle = DB::one('SELECT id FROM hustles WHERE id = :id AND is_active = 1', [':id' => $hustleId]);
    if (!$hustle) Response::error('Hustle not found.', 404);

    // Toggle: if exists, delete; if not, insert
    $existing = DB::one(
        'SELECT id FROM saved_hustles WHERE user_id = :uid AND hustle_id = :hid',
        [':uid' => $userId, ':hid' => $hustleId]
    );

    if ($existing) {
        DB::run('DELETE FROM saved_hustles WHERE user_id = :uid AND hustle_id = :hid',
            [':uid' => $userId, ':hid' => $hustleId]);
        DB::run('UPDATE hustles SET save_count = GREATEST(0, save_count - 1) WHERE id = :id', [':id' => $hustleId]);
        Response::success('Removed from saved.', ['saved' => false, 'hustle_id' => $hustleId]);
    } else {
        DB::insert('INSERT INTO saved_hustles (user_id, hustle_id) VALUES (:uid, :hid)',
            [':uid' => $userId, ':hid' => $hustleId]);
        DB::run('UPDATE hustles SET save_count = save_count + 1 WHERE id = :id', [':id' => $hustleId]);
        Response::success('Saved!', ['saved' => true, 'hustle_id' => $hustleId], 201);
    }
}

Response::error('Method not allowed.', 405);
