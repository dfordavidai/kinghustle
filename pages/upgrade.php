<?php
/**
 * HustleKingdom — Pro Upgrade Page
 * GET  → render plan selection
 * POST → initialise Paystack transaction → redirect to payment
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/Middleware.php';
Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plan = $_POST['plan'] ?? '';
    if (!in_array($plan, ['monthly', 'annual'], true)) {
        $error = 'Invalid plan selected.';
    } else {
        $amount = $plan === 'annual' ? PRO_ANNUAL_AMOUNT : PRO_MONTHLY_AMOUNT;

        $payload = json_encode([
            'email'    => $user['email'],
            'amount'   => $amount,
            'currency' => 'NGN',
            'metadata' => [
                'user_id' => $user['id'],
                'plan'    => $plan,
            ],
            'callback_url' => APP_URL . '/payment/callback',
        ]);

        $ch = curl_init('https://api.paystack.co/transaction/initialize');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
                'Content-Type: application/json',
            ],
        ]);
        $res  = curl_exec($ch);
        $data = json_decode($res, true);
        curl_close($ch);

        if (!empty($data['status']) && $data['status'] === true) {
            // Store reference in session for verification
            $_SESSION['ps_ref']  = $data['data']['reference'];
            $_SESSION['ps_plan'] = $plan;
            Response::redirect($data['data']['authorization_url']);
        } else {
            $error = 'Could not connect to payment processor. Please try again.';
        }
    }
}

$csrf = Auth::csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Go Pro — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--green:#16a05a;--green2:#128f4f;--green-light:#ebf7f1;--gold:#c8960a;--gold-light:#fdf6e3;--gold-border:#e8d080;--r:14px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;min-height:100vh;}
nav{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);}
.nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.nav-logo-icon{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:15px;color:var(--text);}
.nav-brand em{color:var(--green);font-style:normal;}
.back-link{font-size:13px;color:var(--green);font-weight:700;text-decoration:none;}
.hero{padding:28px 20px 20px;text-align:center;background:linear-gradient(135deg,#fdf6e3 0%,#fff 60%);}
.hero-crown{font-size:48px;margin-bottom:10px;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:26px;font-weight:800;margin-bottom:8px;}
.hero-sub{font-size:14px;color:var(--text2);line-height:1.6;}
.features{padding:20px;}
.feature-item{display:flex;gap:12px;align-items:flex-start;margin-bottom:14px;}
.feature-icon{font-size:20px;flex-shrink:0;}
.feature-text h4{font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:14px;margin-bottom:2px;}
.feature-text p{font-size:12.5px;color:var(--text2);line-height:1.5;}
.plans{padding:0 20px 24px;}
.plans-title{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;margin-bottom:14px;}
.plan-card{border:2px solid var(--border);border-radius:16px;padding:16px;margin-bottom:12px;cursor:pointer;transition:.15s;position:relative;}
.plan-card.popular{border-color:var(--gold);background:var(--gold-light);}
.popular-badge{position:absolute;top:-10px;left:50%;transform:translateX(-50%);background:var(--gold);color:#fff;font-size:10px;font-weight:800;padding:3px 12px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;}
.plan-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;}
.plan-name{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:16px;}
.plan-price{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:20px;color:var(--green);}
.plan-price span{font-size:12px;font-weight:600;color:var(--text3);}
.plan-desc{font-size:12.5px;color:var(--text2);}
.plan-save{font-size:11px;font-weight:700;color:var(--gold);margin-top:4px;}
.error-box{background:#fdf0f0;border:1px solid #f5c0c0;border-radius:10px;padding:12px;font-size:13px;color:#cc3333;margin:0 20px 16px;font-weight:600;}
.submit-btn{display:block;width:calc(100% - 40px);margin:0 auto 12px;background:var(--green);color:#fff;border:none;border-radius:var(--r);padding:15px;font-size:15px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;text-align:center;}
.secure-note{text-align:center;font-size:11.5px;color:var(--text3);padding-bottom:30px;}
.already-pro{margin:20px;background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:16px;padding:20px;text-align:center;}
.already-pro h3{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:17px;margin-bottom:6px;}
.already-pro p{font-size:13px;color:var(--text2);}
</style>
</head>
<body>

<nav>
  <a href="<?= APP_URL ?>" class="nav-logo">
    <div class="nav-logo-icon">👑</div>
    <div class="nav-brand">Hustle<em>Kingdom</em></div>
  </a>
  <a href="<?= APP_URL ?>/" class="back-link">← Back</a>
</nav>

<?php if ($isPro): ?>
<div style="padding:30px 20px;">
  <div class="already-pro">
    <h3>👑 You're Already Pro!</h3>
    <p>Your Pro access is active until <strong><?= date('F j, Y', strtotime($user['pro_expires_at'])) ?></strong>.</p>
    <br/>
    <a href="<?= APP_URL ?>/" style="color:var(--green);font-weight:700;text-decoration:none;">Go to Dashboard →</a>
  </div>
</div>
<?php else: ?>

<div class="hero">
  <div class="hero-crown">👑</div>
  <div class="hero-title">Unlock Hustle Kingdom Pro</div>
  <p class="hero-sub">Everything you need to go from side hustle to full income — unlocked.</p>
</div>

<div class="features">
  <div class="feature-item"><div class="feature-icon">📊</div><div class="feature-text"><h4>Advanced Income Analytics</h4><p>Charts, trends, and monthly income reports.</p></div></div>
  <div class="feature-item"><div class="feature-icon">💾</div><div class="feature-text"><h4>CSV Income Export</h4><p>Download your full income history any time.</p></div></div>
  <div class="feature-item"><div class="feature-icon">🤝</div><div class="feature-text"><h4>Community Access</h4><p>Join the Pro members community. Real advice, real connections.</p></div></div>
  <div class="feature-item"><div class="feature-icon">🎯</div><div class="feature-text"><h4>Unlimited Goals</h4><p>Track as many income goals as you want.</p></div></div>
  <div class="feature-item"><div class="feature-icon">🔔</div><div class="feature-text"><h4>Priority Features</h4><p>Early access to every new Hustle Kingdom feature.</p></div></div>
</div>

<?php if ($error): ?>
  <div class="error-box"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST" action="/upgrade" id="upgrade-form">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>"/>
  <input type="hidden" name="plan" id="selected-plan" value="annual"/>

  <div class="plans">
    <div class="plans-title">Choose Your Plan</div>

    <div class="plan-card popular" id="card-annual" onclick="selectPlan('annual')">
      <div class="popular-badge">🔥 Best Value</div>
      <div class="plan-header">
        <div class="plan-name">Annual Plan</div>
        <div class="plan-price">₦19,999<span>/yr</span></div>
      </div>
      <div class="plan-desc">Full Pro access for a full year.</div>
      <div class="plan-save">💰 Save ₦10,001 vs monthly</div>
    </div>

    <div class="plan-card" id="card-monthly" onclick="selectPlan('monthly')">
      <div class="plan-header">
        <div class="plan-name">Monthly Plan</div>
        <div class="plan-price">₦2,500<span>/mo</span></div>
      </div>
      <div class="plan-desc">Flexible. Cancel anytime.</div>
    </div>
  </div>

  <button type="submit" class="submit-btn" id="upgrade-btn">Pay with Paystack 🔒</button>
</form>
<p class="secure-note">🔒 Secured by Paystack · Nigerian payment gateway</p>

<script>
function selectPlan(plan) {
  document.getElementById('selected-plan').value = plan;
  document.getElementById('card-annual').className  = 'plan-card' + (plan === 'annual'  ? ' popular' : '');
  document.getElementById('card-monthly').className = 'plan-card' + (plan === 'monthly' ? ' popular' : '');
  var prices = { annual: '₦19,999', monthly: '₦2,500' };
  document.getElementById('upgrade-btn').textContent = 'Pay ' + prices[plan] + ' with Paystack 🔒';
}
document.getElementById('upgrade-form').addEventListener('submit', function(){
  var btn = document.getElementById('upgrade-btn');
  btn.textContent = 'Redirecting to Paystack…';
  btn.disabled = true;
});
</script>

<?php endif; ?>
</body>
</html>
