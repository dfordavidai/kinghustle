<?php
/**
 * HustleKingdom — Payment Callback
 * Route: GET /payment/callback
 * Paystack redirects here after the user completes (or abandons) payment.
 * We verify the transaction server-side before activating Pro.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
Auth::start();
Auth::guard();

$user   = Auth::user();
$status = 'pending'; // pending | success | failed | already_pro

// ── Grab the reference ───────────────────────────────────────────────────────
// Paystack sends ?reference= in the query string; we also stored it in session
$ref  = $_GET['reference'] ?? $_SESSION['ps_ref'] ?? '';
$plan = $_SESSION['ps_plan'] ?? 'monthly';

if (!$ref) {
    $status = 'failed';
} else {
    // ── Verify with Paystack ──────────────────────────────────────────────────
    $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($ref));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        ],
    ]);
    $res     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = $res ? json_decode($res, true) : null;

    if ($httpCode !== 200 || empty($data['status']) || !$data['status']) {
        $status = 'failed';
    } elseif (($data['data']['status'] ?? '') !== 'success') {
        // Transaction abandoned or failed
        $status = 'failed';
    } else {
        // ── Transaction confirmed — activate Pro ──────────────────────────────
        $txMeta   = $data['data']['metadata']      ?? [];
        $txUserId = (int)($txMeta['user_id']        ?? 0);
        $txPlan   = in_array($txMeta['plan'] ?? '', ['monthly', 'annual'], true)
                    ? $txMeta['plan']
                    : $plan;
        $amount   = (int)($data['data']['amount']   ?? 0);

        // Safety: only upgrade the currently-logged-in user
        if ($txUserId && $txUserId !== $user['id']) {
            $status = 'failed';
        } else {
            $days    = ($txPlan === 'annual') ? PRO_ANNUAL_DAYS : PRO_MONTHLY_DAYS;
            $expires = date('Y-m-d H:i:s', strtotime("+$days days"));

            // Check for duplicate (webhook may have already processed this)
            $existing = DB::one(
                'SELECT id FROM subscriptions WHERE paystack_ref = :ref LIMIT 1',
                [':ref' => $ref]
            );

            if ($existing) {
                $status = 'already_pro';
            } else {
                DB::begin();
                try {
                    DB::run(
                        'UPDATE users SET is_pro = 1, pro_expires_at = :exp WHERE id = :id',
                        [':exp' => $expires, ':id' => $user['id']]
                    );
                    DB::insert(
                        'INSERT INTO subscriptions (user_id, plan, paystack_ref, amount, expires_at)
                         VALUES (:uid, :plan, :ref, :amt, :exp)',
                        [':uid' => $user['id'], ':plan' => $txPlan,
                         ':ref' => $ref, ':amt' => $amount, ':exp' => $expires]
                    );
                    DB::commit();
                    $status = 'success';
                } catch (Throwable $e) {
                    DB::rollback();
                    $status = 'failed';
                }
            }

            if ($status === 'already_pro') $status = 'success'; // idempotent — show success
        }
    }

    // Clear session refs
    unset($_SESSION['ps_ref'], $_SESSION['ps_plan']);
    Auth::refresh(); // bust cached user so isPro() reflects new state
}

$isPro = ($status === 'success') ? true : Auth::isPro();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?= $status === 'success' ? 'Welcome to Pro! — Hustle Kingdom' : 'Payment — Hustle Kingdom' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--green:#16a05a;--green2:#128f4f;--green-light:#ebf7f1;--gold:#c8960a;--gold-light:#fdf6e3;--gold-border:#e8d080;--red:#cc3333;--red-light:#fdf0f0;--r:14px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:40px 20px;}
.card{width:100%;background:var(--surface);border-radius:20px;padding:32px 24px;text-align:center;}
.icon{font-size:56px;margin-bottom:16px;display:block;}
.card-title{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;margin-bottom:10px;}
.card-body{font-size:14px;color:var(--text2);line-height:1.7;margin-bottom:24px;}
.badge-pro{display:inline-block;background:var(--gold-light);color:var(--gold);border:1.5px solid var(--gold-border);border-radius:20px;padding:4px 14px;font-size:12px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:16px;}
.btn{display:block;width:100%;padding:15px;border-radius:var(--r);font-size:14px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;text-align:center;transition:.15s;}
.btn-primary{background:var(--green);color:#fff;}
.btn-secondary{background:transparent;color:var(--green);border:1.5px solid var(--green);margin-top:10px;}
.success-card{border:2px solid #b2e0c8;}
.failed-card{border:2px solid #f5c0c0;}
</style>
</head>
<body>

<?php if ($status === 'success'): ?>
<div class="card success-card">
  <span class="icon">🎉</span>
  <div class="badge-pro">✨ PRO MEMBER</div>
  <div class="card-title">You're now Pro!</div>
  <div class="card-body">
    Welcome to Hustle Kingdom Pro. You now have unlimited access to all hustle guides, income analytics, and priority features.
  </div>
  <a href="/dashboard" class="btn btn-primary">Go to Dashboard →</a>
</div>

<?php else: ?>
<div class="card failed-card">
  <span class="icon">😔</span>
  <div class="card-title">Payment Not Completed</div>
  <div class="card-body">
    We couldn't confirm your payment. You have not been charged. If you believe this is an error, please try again or contact support.
  </div>
  <a href="/upgrade" class="btn btn-primary">Try Again</a>
  <a href="/dashboard" class="btn btn-secondary">Back to Dashboard</a>
</div>
<?php endif; ?>

</body>
</html>
