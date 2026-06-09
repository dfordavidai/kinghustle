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
    $goals = DB::query(
        'SELECT * FROM goals WHERE user_id = :uid ORDER BY is_completed ASC, created_at DESC',
        [':uid' => $userId]
    );
    Response::success('OK', $goals);
}

if ($method === 'POST') {
    $body    = Middleware::jsonBody();
    $missing = Middleware::required($body, ['title', 'target_amount']);
    if ($missing) Response::error('Missing: ' . implode(', ', $missing), 422);

    $target = (float)$body['target_amount'];
    if ($target <= 0) Response::error('Target amount must be greater than 0.', 422);

    $id = DB::insert(
        'INSERT INTO goals (user_id, title, emoji, target_amount, current_amount, deadline)
         VALUES (:uid, :title, :emoji, :target, :current, :deadline)',
        [
            ':uid'     => $userId,
            ':title'   => $body['title'],
            ':emoji'   => $body['emoji']   ?? '🎯',
            ':target'  => $target,
            ':current' => max(0, (float)($body['current_amount'] ?? 0)),
            ':deadline'=> !empty($body['deadline']) ? $body['deadline'] : null,
        ]
    );
    $goal = DB::one('SELECT * FROM goals WHERE id = :id', [':id' => $id]);
    Response::success('Goal created.', $goal, 201);
}

if ($method === 'PUT') {
    $id   = (int)($_GET['id'] ?? 0);
    if (!$id) Response::error('Missing id.', 400);

    $body = Middleware::jsonBody();
    $goal = DB::one('SELECT * FROM goals WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $userId]);
    if (!$goal) Response::error('Goal not found.', 404);

    $fields  = ['title','emoji','target_amount','current_amount','deadline','is_completed'];
    $updates = [];
    $params  = [':id' => $id];

    foreach ($fields as $f) {
        if (array_key_exists($f, $body)) {
            $updates[]   = "$f = :$f";
            $params[":$f"] = $body[$f];
        }
    }

    // Auto-set completed_at when marking complete
    if (isset($body['is_completed']) && (bool)$body['is_completed'] && !(bool)$goal['is_completed']) {
        $updates[] = 'completed_at = NOW()';
    }

    if (!$updates) Response::error('Nothing to update.', 400);

    DB::run('UPDATE goals SET ' . implode(', ', $updates) . ' WHERE id = :id', $params);
    $updated = DB::one('SELECT * FROM goals WHERE id = :id', [':id' => $id]);
    Response::success('Goal updated.', $updated);
}

if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) Response::error('Missing id.', 400);
    $deleted = DB::run('DELETE FROM goals WHERE id = :id AND user_id = :uid', [':id' => $id, ':uid' => $userId]);
    if (!$deleted) Response::error('Goal not found.', 404);
    Response::success('Goal deleted.');
}

Response::error('Method not allowed.', 405);
