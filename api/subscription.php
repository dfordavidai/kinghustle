<?php
defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';

header('Content-Type: application/json');
Auth::start();

$method = strtoupper($_SERVER['REQUEST_METHOD']);
$path   = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// ── Paystack Webhook ─────────────────────────────────────────────────────────
// POST /api/webhook/paystack  (no auth required — validated by HMAC)
if ($method === 'POST' && str_contains($path, 'webhook')) {

    $rawBody  = file_get_contents('php://input');
    $sig      = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';
    $expected = hash_hmac('sha512', $rawBody, PAYSTACK_WEBHOOK_SECRET);

    if (!hash_equals($expected, $sig)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }

    $event = json_decode($rawBody, true);
    $type  = $event['event'] ?? '';
    $data  = $event['data']  ?? [];

    switch ($type) {
        case 'charge.success':
            $ref      = $data['reference'] ?? '';
            $amount   = (int)($data['amount'] ?? 0); // kobo
            $meta     = $data['metadata']    ?? [];
            $userId   = (int)($meta['user_id'] ?? 0);
            $plan     = in_array($meta['plan'] ?? '', ['monthly', 'annual'], true)
                        ? $meta['plan']
                        : 'monthly';

            if (!$userId || !$ref) break;

            $days    = ($plan === 'annual') ? PRO_ANNUAL_DAYS : PRO_MONTHLY_DAYS;
            $expires = date('Y-m-d H:i:s', strtotime("+$days days"));

            DB::begin();
            try {
                DB::run('UPDATE users SET is_pro = 1, pro_expires_at = :exp WHERE id = :id',
                    [':exp' => $expires, ':id' => $userId]);

                DB::insert(
                    'INSERT INTO subscriptions (user_id, plan, paystack_ref, amount, expires_at)
                     VALUES (:uid, :plan, :ref, :amt, :exp)
                     ON DUPLICATE KEY UPDATE status = "active", expires_at = :exp',
                    [':uid' => $userId, ':plan' => $plan, ':ref' => $ref,
                     ':amt' => $amount, ':exp' => $expires]
                );
                DB::commit();
            } catch (Throwable $e) {
                DB::rollback();
            }
            break;

        case 'subscription.disable':
        case 'invoice.payment_failed':
            $subCode = $data['subscription_code'] ?? '';
            if ($subCode) {
                DB::run(
                    'UPDATE subscriptions SET status = "cancelled" WHERE paystack_sub_code = :code',
                    [':code' => $subCode]
                );
                // Revoke Pro if expired
                $sub = DB::one('SELECT user_id FROM subscriptions WHERE paystack_sub_code = :c', [':c' => $subCode]);
                if ($sub) {
                    DB::run('UPDATE users SET is_pro = 0 WHERE id = :id AND (pro_expires_at IS NULL OR pro_expires_at <= NOW())',
                        [':id' => $sub['user_id']]);
                }
            }
            break;
    }

    http_response_code(200);
    echo json_encode(['received' => true]);
    exit;
}

// ── Subscription status (authenticated) ─────────────────────────────────────
// GET /api/subscription
if ($method === 'GET') {
    Auth::guard();
    $user = Auth::user();

    $sub = DB::one(
        'SELECT plan, status, started_at, expires_at FROM subscriptions
         WHERE user_id = :uid AND status = "active"
         ORDER BY expires_at DESC LIMIT 1',
        [':uid' => $user['id']]
    );

    Response::success('OK', [
        'is_pro'     => Auth::isPro(),
        'expires_at' => $user['pro_expires_at'],
        'plan'       => $sub['plan'] ?? null,
        'status'     => $sub['status'] ?? 'none',
    ]);
}

Response::error('Method not allowed.', 405);
