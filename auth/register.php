<?php
/**
 * HustleKingdom — Register
 * GET  → render registration form
 * POST → validate, create user, credit referrer, login, redirect
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/Middleware.php';

Auth::start();

if (Auth::isLoggedIn()) {
    Response::redirect(APP_URL . '/');
}

$errors    = [];
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
           || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

// Nigerian states list
$NG_STATES = [
    'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
    'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo',
    'Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa',
    'Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::rateLimit('register', 5, 300);

    $body  = $wantsJson ? Middleware::jsonBody() : Middleware::sanitize($_POST);
    $name  = $body['name']     ?? '';
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password'] ?? '';
    $phone = $body['phone']    ?? '';
    $city  = $body['city']     ?? '';
    $state = $body['state']    ?? '';
    $ref   = strtoupper(trim($body['ref'] ?? $_GET['ref'] ?? ''));

    // ── Validation ───────────────────────────────────────────────────────────
    if (strlen($name) < 2)                       $errors['name']     = 'Enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email']  = 'Enter a valid email address.';
    if (strlen($pass) < PASSWORD_MIN_LEN)        $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LEN . ' characters.';
    if (!in_array($state, $NG_STATES, true))     $errors['state']    = 'Select your state.';
    if (strlen($city) < 2)                       $errors['city']     = 'Enter your city.';

    // Check email uniqueness
    if (empty($errors['email'])) {
        $exists = DB::one('SELECT id FROM users WHERE email = :e LIMIT 1', [':e' => $email]);
        if ($exists) $errors['email'] = 'This email is already registered. Log in instead.';
    }

    if (empty($errors)) {
        // Generate unique ref code
        do {
            $refCode = 'HK-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $taken   = DB::one('SELECT id FROM users WHERE ref_code = :r LIMIT 1', [':r' => $refCode]);
        } while ($taken);

        // Resolve referrer
        $referrerId = null;
        if ($ref) {
            $referrer = DB::one('SELECT id FROM users WHERE ref_code = :r LIMIT 1', [':r' => $ref]);
            if ($referrer) $referrerId = (int)$referrer['id'];
        }

        DB::begin();
        try {
            $userId = DB::insert(
                'INSERT INTO users (name, email, password_hash, phone, city, state, ref_code, referred_by)
                 VALUES (:name, :email, :hash, :phone, :city, :state, :ref, :referrer)',
                [
                    ':name'     => $name,
                    ':email'    => $email,
                    ':hash'     => password_hash($pass, PASSWORD_BCRYPT, ['cost' => 12]),
                    ':phone'    => $phone,
                    ':city'     => $city,
                    ':state'    => $state,
                    ':ref'      => $refCode,
                    ':referrer' => $referrerId,
                ]
            );

            // Record referral relationship
            if ($referrerId) {
                DB::insert(
                    'INSERT INTO referrals (referrer_id, referred_id) VALUES (:r, :n)',
                    [':r' => $referrerId, ':n' => $userId]
                );

                // Check if referrer hit their reward threshold
                $refCount = DB::one(
                    'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :r',
                    [':r' => $referrerId]
                )['cnt'] ?? 0;

                if ((int)$refCount >= REFERRAL_NEEDED) {
                    // Check reward not already granted
                    $alreadyGranted = DB::one(
                        'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :r AND reward_granted = 1',
                        [':r' => $referrerId]
                    )['cnt'] ?? 0;

                    if ((int)$alreadyGranted === 0) {
                        $expires = date('Y-m-d H:i:s', strtotime('+' . REFERRAL_REWARD_DAYS . ' days'));
                        DB::run(
                            'UPDATE users SET is_pro = 1, pro_expires_at = :exp WHERE id = :id',
                            [':exp' => $expires, ':id' => $referrerId]
                        );
                        DB::run(
                            'INSERT INTO subscriptions (user_id, plan, amount, expires_at)
                             VALUES (:uid, "referral", 0, :exp)',
                            [':uid' => $referrerId, ':exp' => $expires]
                        );
                        DB::run(
                            'UPDATE referrals SET reward_granted = 1 WHERE referrer_id = :r',
                            [':r' => $referrerId]
                        );
                    }
                }
            }

            DB::commit();

            Auth::login($userId);

            if ($wantsJson) {
                Response::success('Account created!', ['redirect' => '/']);
            }
            Response::redirect(APP_URL . '/');

        } catch (Throwable $e) {
            DB::rollback();
            if (APP_DEBUG) throw $e;
            $errors['general'] = 'Registration failed. Please try again.';
        }
    }

    if ($wantsJson && !empty($errors)) {
        Response::error('Registration failed.', 422, $errors);
    }
}

$csrf    = Auth::csrf();
$refCode = htmlspecialchars(strtoupper($_GET['ref'] ?? ''));
$NG_STATES_OPTS = $NG_STATES;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Create Account — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;--red:#cc3333;--red-light:#fdf0f0;--gold:#c8960a;--gold-light:#fdf6e3;--r:14px;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;}
.auth-header{padding:20px 20px 0;display:flex;align-items:center;gap:10px;}
.auth-logo{width:36px;height:36px;background:var(--green);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.auth-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;}
.auth-brand em{color:var(--green);font-style:normal;}
.auth-body{padding:28px 20px 60px;}
.auth-title{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;margin-bottom:5px;}
.auth-sub{font-size:13px;color:var(--text2);margin-bottom:24px;line-height:1.6;}
.ref-banner{background:var(--gold-light);border:1.5px solid #e8d080;border-radius:12px;padding:11px 14px;margin-bottom:20px;font-size:12.5px;font-weight:700;color:var(--gold);display:flex;gap:8px;align-items:center;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:12px;font-weight:700;color:var(--text2);margin-bottom:5px;font-family:'Bricolage Grotesque',sans-serif;}
.field input,.field select{width:100%;padding:13px 14px;border:1.5px solid var(--border);border-radius:var(--r);font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);background:#fff;outline:none;transition:.2s;}
.field input:focus,.field select:focus{border-color:var(--green);}
.field input.err,.field select.err{border-color:var(--red);}
.field-err{font-size:11.5px;color:var(--red);margin-top:5px;font-weight:600;}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.alert-err{background:var(--red-light);border:1px solid #f5c0c0;border-radius:var(--r);padding:12px 14px;font-size:13px;color:var(--red);margin-bottom:18px;font-weight:600;}
.btn-submit{width:100%;background:var(--green);color:#fff;border:none;border-radius:var(--r);padding:15px;font-size:14px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;margin-top:8px;}
.btn-submit:active{background:var(--green2);}
.auth-foot{text-align:center;margin-top:20px;font-size:13px;color:var(--text2);}
.auth-foot a{color:var(--green);font-weight:700;text-decoration:none;}
.terms{font-size:11px;color:var(--text3);text-align:center;margin-top:14px;line-height:1.6;}
</style>
</head>
<body>

<div class="auth-header">
  <div class="auth-logo">👑</div>
  <div class="auth-brand">Hustle<em>Kingdom</em></div>
</div>

<div class="auth-body">
  <div class="auth-title">Join Hustle Kingdom 🚀</div>
  <div class="auth-sub">Discover your perfect side hustle and start earning more today.</div>

  <?php if ($refCode): ?>
    <div class="ref-banner">🎁 Referral code <strong><?= $refCode ?></strong> applied — you've been invited!</div>
  <?php endif; ?>

  <?php if (!empty($errors['general'])): ?>
    <div class="alert-err"><?= htmlspecialchars($errors['general']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/auth/register<?= $refCode ? '?ref=' . urlencode($refCode) : '' ?>" id="reg-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>"/>
    <?php if ($refCode): ?>
      <input type="hidden" name="ref" value="<?= htmlspecialchars($refCode) ?>"/>
    <?php endif; ?>

    <div class="field">
      <label for="name">Full Name</label>
      <input type="text" id="name" name="name" placeholder="John Chukwu"
             value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" autocomplete="name"
             class="<?= isset($errors['name']) ? 'err' : '' ?>"/>
      <?php if (!empty($errors['name'])): ?>
        <div class="field-err"><?= htmlspecialchars($errors['name']) ?></div>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email"
             value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
             class="<?= isset($errors['email']) ? 'err' : '' ?>"/>
      <?php if (!empty($errors['email'])): ?>
        <div class="field-err"><?= htmlspecialchars($errors['email']) ?></div>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Min. 8 characters" autocomplete="new-password"
             class="<?= isset($errors['password']) ? 'err' : '' ?>"/>
      <?php if (!empty($errors['password'])): ?>
        <div class="field-err"><?= htmlspecialchars($errors['password']) ?></div>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="phone">Phone Number <span style="color:var(--text3);font-weight:500;">(optional)</span></label>
      <input type="tel" id="phone" name="phone" placeholder="08012345678" autocomplete="tel"
             value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"/>
    </div>

    <div class="field-row">
      <div class="field">
        <label for="state">State</label>
        <select id="state" name="state" class="<?= isset($errors['state']) ? 'err' : '' ?>">
          <option value="">Select state</option>
          <?php foreach ($NG_STATES_OPTS as $s): ?>
            <option value="<?= htmlspecialchars($s) ?>"
              <?= (($_POST['state'] ?? '') === $s) ? 'selected' : '' ?>>
              <?= htmlspecialchars($s) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if (!empty($errors['state'])): ?>
          <div class="field-err"><?= htmlspecialchars($errors['state']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label for="city">City</label>
        <input type="text" id="city" name="city" placeholder="Lagos Island"
               value="<?= htmlspecialchars($_POST['city'] ?? '') ?>"
               class="<?= isset($errors['city']) ? 'err' : '' ?>"/>
        <?php if (!empty($errors['city'])): ?>
          <div class="field-err"><?= htmlspecialchars($errors['city']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <button type="submit" class="btn-submit" id="btn-submit">Create My Account →</button>
  </form>

  <div class="auth-foot">Already have an account? <a href="/auth/login">Log in</a></div>
  <div class="terms">By signing up, you agree to our Terms of Service and Privacy Policy.</div>
</div>

<script>
document.getElementById('reg-form').addEventListener('submit', function(){
  var btn = document.getElementById('btn-submit');
  btn.textContent = 'Creating account…';
  btn.disabled = true;
});
</script>
</body>
</html>
