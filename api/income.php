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

// ── GET ──────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $days   = max(1, min(365, (int)($_GET['days'] ?? 90)));
    $rows   = DB::query(
        'SELECT il.id, il.hustle_id, il.custom_name, il.amount, il.note, il.logged_at,
                COALESCE(h.name, il.custom_name) as hustle_name, h.emoji
         FROM income_log il
         LEFT JOIN hustles h ON h.id = il.hustle_id
         WHERE il.user_id = :uid
           AND il.logged_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
         ORDER BY il.logged_at DESC, il.id DESC',
        [':uid' => $userId, ':days' => $days]
    );

    $total = array_sum(array_column($rows, 'amount'));
    Response::success('OK', ['entries' => $rows, 'total' => $total, 'days' => $days]);
}

// ── POST ─────────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body   = Middleware::jsonBody();
    $missing = Middleware::required($body, ['amount']);
    if ($missing) Response::error('Missing: ' . implode(', ', $missing), 422);

    $amount = (float)$body['amount'];
    if ($amount <= 0) Response::error('Amount must be greater than 0.', 422);
    if ($amount > 100_000_000) Response::error('Amount too large.', 422);

    $hustleId   = !empty($body['hustle_id']) ? (int)$body['hustle_id'] : null;
    $customName = $hustleId ? null : ($body['custom_name'] ?? 'Custom Income');
    $note       = $body['note'] ?? null;
    $loggedAt   = $body['logged_at'] ?? date('Y-m-d');

    // Validate date
    $d = DateTime::createFromFormat('Y-m-d', $loggedAt);
    if (!$d || $d->format('Y-m-d') !== $loggedAt) $loggedAt = date('Y-m-d');

    $id = DB::insert(
        'INSERT INTO income_log (user_id, hustle_id, custom_name, amount, note, logged_at)
         VALUES (:uid, :hid, :cname, :amt, :note, :dat)',
        [':uid' => $userId, ':hid' => $hustleId, ':cname' => $customName,
         ':amt' => $amount, ':note' => $note, ':dat' => $loggedAt]
    );

    Response::success('Income logged.', ['id' => $id], 201);
}

// ── DELETE ───────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) Response::error('Missing id.', 400);

    $deleted = DB::run(
        'DELETE FROM income_log WHERE id = :id AND user_id = :uid',
        [':id' => $id, ':uid' => $userId]
    );

    if (!$deleted) Response::error('Entry not found.', 404);
    Response::success('Deleted.');
}

Response::error('Method not allowed.', 405);
