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
    $count  = (int)(DB::one(
        'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :id',
        [':id' => $user['id']]
    )['cnt'] ?? 0);

    $rewarded = (int)(DB::one(
        'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :id AND reward_granted = 1',
        [':id' => $user['id']]
    )['cnt'] ?? 0);

    Response::success('OK', [
        'ref_code'       => $user['ref_code'],
        'ref_count'      => $count,
        'needed'         => REFERRAL_NEEDED,
        'reward_granted' => $rewarded > 0,
        'share_url'      => APP_URL . '/auth/register?ref=' . $user['ref_code'],
    ]);
}

Response::error('Method not allowed.', 405);
