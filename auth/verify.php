<?php
/**
 * HustleKingdom — Verify Reset Token & Set New Password
 * GET  → validate token, show new-password form
 * POST → set new password, invalidate token, redirect to login
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/Middleware.php';

Auth::start();

// Already logged in
if (Auth::isLoggedIn()) {
    Response::redirect(APP_URL . '/dashboard');
}

$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));
$errors = [];
$tokenValid = false;
$success = false;

// ── Validate token on every request ──────────────────────────────────────────
if ($token && $email) {
    $reset = DB::one(
        'SELECT email, token, expires_at FROM password_resets
         WHERE email = :e AND token = :t LIMIT 1',
        [':e' => $email, ':t' => $token]
    );

    if ($reset && strtotime($reset['expires_at']) > time()) {
        $tokenValid = true;
    }
}

if (!$tokenValid && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Show invalid/expired token page
    $tokenValid = false;
}

// ── Handle POST — set new password ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    Middleware::verifyCsrf($_POST['_csrf'] ?? '');

    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    if (strlen($password) < PASSWORD_MIN_LEN) {
        $errors['password'] = 'Password must be at least ' . PASSWORD_MIN_LEN . ' characters.';
    } elseif ($password !== $password2) {
        $errors['password2'] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $user = DB::one('SELECT id FROM users WHERE email = :e AND is_active = 1 LIMIT 1', [':e' => $email]);

        if ($user) {
            DB::begin();
            try {
                DB::run(
                    'UPDATE users SET password_hash = :h WHERE id = :id',
                    [':h' => $hash, ':id' => $user['id']]
                );
                // Invalidate the reset token
                DB::run('DELETE FROM password_resets WHERE email = :e', [':e' => $email]);
                DB::commit();
                $success = true;
            } catch (Throwable $e) {
                DB::rollback();
                $errors['general'] = 'Something went wrong. Please try again.';
            }
        } else {
            $errors['general'] = 'Account not found.';
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
<title>Set New Password — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--green:#16a05a;--green2:#128f4f;--green-light:#ebf7f1;--red:#cc3333;--red-light:#fdf0f0;--r:14px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;}
.auth-header{padding:20px 20px 0;display:flex;align-items:center;gap:10px;}
.auth-logo{width:36px;height:36px;background:var(--green);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;}
.auth-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;}
.auth-brand em{color:var(--green);font-style:normal;}
.auth-body{padding:32px 20px 60px;}
.auth-title{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;margin-bottom:6px;}
.auth-sub{font-size:13px;color:var(--text2);margin-bottom:28px;line-height:1.6;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:12px;font-weight:700;color:var(--text2);margin-bottom:5px;font-family:'Bricolage Grotesque',sans-serif;}
.field input{width:100%;padding:13px 14px;border:1.5px solid var(--border);border-radius:var(--r);font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);background:#fff;outline:none;transition:.2s;}
.field input:focus{border-color:var(--green);}
.field input.err{border-color:var(--red);}
.field-err{font-size:11.5px;color:var(--red);margin-top:5px;font-weight:600;}
.alert-err{background:var(--red-light);border:1px solid #f5c0c0;border-radius:var(--r);padding:12px 14px;font-size:13px;color:var(--red);margin-bottom:18px;font-weight:600;}
.btn-submit{width:100%;background:var(--green);color:#fff;border:none;border-radius:var(--r);padding:15px;font-size:14px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;}
.success-box{background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:var(--r);padding:18px;text-align:center;}
.success-box .icon{font-size:40px;margin-bottom:10px;}
.success-box h3{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;margin-bottom:8px;}
.success-box p{font-size:13px;color:var(--text2);line-height:1.6;}
.expired-box{background:var(--red-light);border:1.5px solid #f5c0c0;border-radius:var(--r);padding:20px;text-align:center;}
.expired-box .icon{font-size:40px;margin-bottom:10px;}
.expired-box h3{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;margin-bottom:8px;color:var(--red);}
.expired-box p{font-size:13px;color:var(--text2);line-height:1.6;}
.back-link{display:block;text-align:center;margin-top:20px;color:var(--green);font-weight:700;text-decoration:none;font-size:13px;}
</style>
</head>
<body>
<div class="auth-header">
  <div class="auth-logo">👑</div>
  <div class="auth-brand">Hustle<em>Kingdom</em></div>
</div>
<div class="auth-body">

<?php if ($success): ?>
  <div class="success-box">
    <div class="icon">🔓</div>
    <h3>Password Updated!</h3>
    <p>Your password has been changed successfully. You can now log in with your new password.</p>
  </div>
  <a href="/auth/login" class="back-link">Log in →</a>

<?php elseif (!$tokenValid): ?>
  <div class="expired-box">
    <div class="icon">⏰</div>
    <h3>Link Expired or Invalid</h3>
    <p>This password reset link has expired or already been used. Reset links are valid for 1 hour.</p>
  </div>
  <a href="/auth/forgot" class="back-link">Request a new link →</a>

<?php else: ?>
  <div class="auth-title">New Password 🔐</div>
  <div class="auth-sub">Choose a strong password for your account.</div>

  <?php if (!empty($errors['general'])): ?>
    <div class="alert-err"><?= htmlspecialchars($errors['general']) ?></div>
  <?php endif; ?>

  <form method="POST" action="/auth/verify?token=<?= urlencode($token) ?>&email=<?= urlencode($email) ?>">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>"/>

    <div class="field">
      <label for="password">New Password</label>
      <input type="password" id="password" name="password" placeholder="Min <?= PASSWORD_MIN_LEN ?> characters"
             autocomplete="new-password" class="<?= isset($errors['password']) ? 'err' : '' ?>"/>
      <?php if (!empty($errors['password'])): ?>
        <div class="field-err"><?= htmlspecialchars($errors['password']) ?></div>
      <?php endif; ?>
    </div>

    <div class="field">
      <label for="password2">Confirm New Password</label>
      <input type="password" id="password2" name="password2" placeholder="Repeat your password"
             autocomplete="new-password" class="<?= isset($errors['password2']) ? 'err' : '' ?>"/>
      <?php if (!empty($errors['password2'])): ?>
        <div class="field-err"><?= htmlspecialchars($errors['password2']) ?></div>
      <?php endif; ?>
    </div>

    <button type="submit" class="btn-submit">Set New Password →</button>
  </form>
  <a href="/auth/forgot" class="back-link">← Request a different link</a>

<?php endif; ?>
</div>
</body>
</html>
