<?php
/**
 * HustleKingdom — Login
 * GET  → render login form
 * POST → validate credentials, create session, redirect or return JSON
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/Middleware.php';

Auth::start();

// Already logged in — redirect home
if (Auth::isLoggedIn()) {
    Response::redirect(APP_URL . '/');
}

$errors  = [];
$success = false;
$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
           || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    Middleware::rateLimit('login', 10, 60);

    // Parse body (form or JSON)
    $body  = $wantsJson ? Middleware::jsonBody() : Middleware::sanitize($_POST);
    $email = strtolower(trim($body['email'] ?? ''));
    $pass  = $body['password'] ?? '';
    $next  = $body['next'] ?? $_GET['next'] ?? '/';

    // ── Validate ─────────────────────────────────────────────────────────────
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (strlen($pass) < 1) {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        $user = DB::one(
            'SELECT id, name, password_hash, is_active FROM users WHERE email = :email LIMIT 1',
            [':email' => $email]
        );

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $errors['general'] = 'Incorrect email or password.';
        } elseif (!(bool)$user['is_active']) {
            $errors['general'] = 'Your account has been suspended. Contact support.';
        } else {
            // ── Success ───────────────────────────────────────────────────────
            Auth::login((int)$user['id']);

            // Update last_seen
            DB::run('UPDATE users SET last_seen_at = NOW() WHERE id = :id', [':id' => $user['id']]);

            if ($wantsJson) {
                Response::success('Login successful.', ['redirect' => $next]);
            }
            Response::redirect(APP_URL . (str_starts_with($next, '/') ? $next : '/'));
        }
    }

    if ($wantsJson && !empty($errors)) {
        Response::error('Login failed.', 422, $errors);
    }
}

$next = htmlspecialchars($_GET['next'] ?? '/', ENT_QUOTES);
$csrf = Auth::csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Login — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;--red:#cc3333;--red-light:#fdf0f0;--r:14px;}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;display:flex;flex-direction:column;}
.auth-header{padding:20px 20px 0;display:flex;align-items:center;gap:10px;}
.auth-logo{width:36px;height:36px;background:var(--green);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.auth-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;}
.auth-brand em{color:var(--green);font-style:normal;}
.auth-body{flex:1;padding:32px 20px 40px;}
.auth-title{font-family:'Bricolage Grotesque',sans-serif;font-size:26px;font-weight:800;margin-bottom:6px;}
.auth-sub{font-size:13px;color:var(--text2);margin-bottom:28px;line-height:1.6;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:12.5px;font-weight:700;color:var(--text2);margin-bottom:6px;font-family:'Bricolage Grotesque',sans-serif;}
.field input{width:100%;padding:13px 14px;border:1.5px solid var(--border);border-radius:var(--r);font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);background:#fff;outline:none;transition:.2s;}
.field input:focus{border-color:var(--green);}
.field input.err{border-color:var(--red);}
.field-err{font-size:11.5px;color:var(--red);margin-top:5px;font-weight:600;}
.alert-err{background:var(--red-light);border:1px solid #f5c0c0;border-radius:var(--r);padding:12px 14px;font-size:13px;color:var(--red);margin-bottom:18px;font-weight:600;}
.btn-submit{width:100%;background:var(--green);color:#fff;border:none;border-radius:var(--r);padding:15px;font-size:14px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;margin-top:4px;}
.btn-submit:active{background:var(--green2);}
.auth-foot{text-align:center;margin-top:22px;font-size:13px;color:var(--text2);}
.auth-foot a{color:var(--green);font-weight:700;text-decoration:none;}
.forgot{display:block;text-align:right;font-size:12px;color:var(--green);font-weight:700;text-decoration:none;margin-top:-8px;margin-bottom:18px;}
</style>
</head>
<body>

<div class="auth-header">
  <div class="auth-logo">👑</div>
  <div class="auth-brand">Hustle<em>Kingdom</em></div>
</div>

<div class="auth-body">
  <div class="auth-title">Welcome back 👋</div>
  <div class="auth-sub">Log in to access your hustles, income tracker and goals.</div>

  <?php if (!empty($errors['general'])): ?>
    <div class="alert-err"><?= htmlspecialchars($errors['general']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/auth/login?next=<?= urlencode($next) ?>" id="login-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>"/>

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
      <input type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password"
             class="<?= isset($errors['password']) ? 'err' : '' ?>"/>
      <?php if (!empty($errors['password'])): ?>
        <div class="field-err"><?= htmlspecialchars($errors['password']) ?></div>
      <?php endif; ?>
    </div>

    <a href="/auth/forgot" class="forgot">Forgot password?</a>

    <button type="submit" class="btn-submit" id="btn-submit">Log In →</button>
  </form>

  <div class="auth-foot">
    Don't have an account? <a href="/auth/register">Sign up free</a>
  </div>
</div>

<script>
document.getElementById('login-form').addEventListener('submit', function(){
  var btn = document.getElementById('btn-submit');
  btn.textContent = 'Logging in…';
  btn.disabled = true;
});
</script>
</body>
</html>
