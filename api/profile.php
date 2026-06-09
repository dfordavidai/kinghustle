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
$user   = Auth::user();

if ($method === 'GET') {
    Response::success('OK', $user);
}

if ($method === 'PUT') {
    $body = Middleware::jsonBody();

    $allowed = ['name','phone','city','state','avatar_emoji'];
    $updates = [];
    $params  = [];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $body)) {
            $updates[] = "$field = :$field";
            $params[":$field"] = $body[$field];
        }
    }

    if (empty($updates)) Response::error('Nothing to update.', 400);

    // Validate name if provided
    if (isset($params[':name']) && strlen($params[':name']) < 2) {
        Response::error('Name must be at least 2 characters.', 422);
    }

    $params[':id'] = $user['id'];
    DB::run('UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id', $params);
    Auth::refresh();
    Response::success('Profile updated.', Auth::user());
}

Response::error('Method not allowed.', 405);
