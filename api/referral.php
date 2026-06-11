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

// ── GET — fetch referral stats + withdrawal history ──────────────────────────
if ($method === 'GET') {
    $count = (int)(DB::one(
        'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :id',
        [':id' => $user['id']]
    )['cnt'] ?? 0);

    $rewarded = (int)(DB::one(
        'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :id AND reward_granted = 1',
        [':id' => $user['id']]
    )['cnt'] ?? 0);

    // Paid referrals (subscribed) drive cash earnings
    $paidCount = (int)(DB::one(
        'SELECT COUNT(*) as cnt FROM referrals r
         JOIN users u ON u.id = r.referred_id
         WHERE r.referrer_id = :id AND u.is_pro = 1 AND (u.pro_expires_at IS NULL OR u.pro_expires_at > NOW())',
        [':id' => $user['id']]
    )['cnt'] ?? 0);

    $totalEarned = $paidCount * 500;

    // Amount already paid out or pending
    $withdrawnRow = DB::one(
        "SELECT COALESCE(SUM(amount),0) as total FROM withdrawal_requests
         WHERE user_id = :id AND status IN ('pending','processing','paid')",
        [':id' => $user['id']]
    );
    $withdrawn = (int)($withdrawnRow['total'] ?? 0);

    $available = max(0, $totalEarned - $withdrawn);

    $history = DB::query(
        'SELECT id, amount, bank_name, account_name, account_number, status, admin_note, requested_at, resolved_at
         FROM withdrawal_requests WHERE user_id = :id ORDER BY requested_at DESC LIMIT 10',
        [':id' => $user['id']]
    );

    Response::success('OK', [
        'ref_code'        => $user['ref_code'],
        'ref_count'       => $count,
        'paid_ref_count'  => $paidCount,
        'needed'          => REFERRAL_NEEDED,
        'reward_granted'  => $rewarded > 0,
        'total_earned'    => $totalEarned,
        'withdrawn'       => $withdrawn,
        'available'       => $available,
        'share_url'       => APP_URL . '/auth/register?ref=' . $user['ref_code'],
        'withdrawals'     => $history,
    ]);
}

// ── POST — submit a withdrawal request ──────────────────────────────────────
if ($method === 'POST') {
    Middleware::rateLimit('withdrawal', 5, 3600); // 5 requests/hour

    $body = Middleware::jsonBody();
    $action = $body['action'] ?? '';

    if ($action !== 'withdraw') {
        Response::error('Unknown action.', 400);
    }

    $bankName      = trim($body['bank_name']      ?? '');
    $accountName   = trim($body['account_name']   ?? '');
    $accountNumber = trim($body['account_number'] ?? '');
    $amount        = (int)($body['amount']         ?? 0);

    // Validate
    $errors = [];
    if (empty($bankName))                                  $errors[] = 'Bank name is required.';
    if (empty($accountName))                               $errors[] = 'Account name is required.';
    if (!preg_match('/^\d{10}$/', $accountNumber))         $errors[] = 'Account number must be exactly 10 digits.';
    if ($amount < 500)                                     $errors[] = 'Minimum withdrawal is ₦500.';

    if (!empty($errors)) {
        Response::error($errors[0], 422, $errors);
    }

    // Re-calculate available balance inside a transaction to prevent race conditions
    DB::begin();
    try {
        $paidCount = (int)(DB::one(
            'SELECT COUNT(*) as cnt FROM referrals r
             JOIN users u ON u.id = r.referred_id
             WHERE r.referrer_id = :id AND u.is_pro = 1 AND (u.pro_expires_at IS NULL OR u.pro_expires_at > NOW())
             FOR UPDATE',
            [':id' => $user['id']]
        )['cnt'] ?? 0);

        $totalEarned = $paidCount * 500;

        $withdrawnRow = DB::one(
            "SELECT COALESCE(SUM(amount),0) as total FROM withdrawal_requests
             WHERE user_id = :id AND status IN ('pending','processing','paid')
             FOR UPDATE",
            [':id' => $user['id']]
        );
        $withdrawn = (int)($withdrawnRow['total'] ?? 0);
        $available = max(0, $totalEarned - $withdrawn);

        if ($amount > $available) {
            DB::rollback();
            Response::error(
                'Insufficient balance. Available: ₦' . number_format($available) . '.',
                422
            );
        }

        $id = DB::insert(
            'INSERT INTO withdrawal_requests (user_id, amount, bank_name, account_name, account_number)
             VALUES (:uid, :amt, :bank, :aname, :anum)',
            [
                ':uid'   => $user['id'],
                ':amt'   => $amount,
                ':bank'  => $bankName,
                ':aname' => $accountName,
                ':anum'  => $accountNumber,
            ]
        );

        DB::commit();

        Response::success('Withdrawal request submitted! We process payments within 3–5 business days.', [
            'request_id' => $id,
            'amount'     => $amount,
            'status'     => 'pending',
        ], 201);

    } catch (Throwable $e) {
        DB::rollback();
        if (APP_DEBUG) throw $e;
        Response::error('Could not submit request. Please try again.', 500);
    }
}

Response::error('Method not allowed.', 405);
