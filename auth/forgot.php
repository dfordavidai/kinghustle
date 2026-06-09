<?php
/**
 * HustleKingdom — Forgot Password
 * GET  → form
 * POST → generate token, show reset link (SMTP-free for now)
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/Middleware.php';

Auth::start();

$sent   = false;
$errors = [];
$resetLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Middleware::rateLimit('forgot', 3, 300);
    $body  = Middleware::sanitize($_POST);
    $email = strtolower(trim($body['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $user = DB::one('SELECT id FROM users WHERE email = :e AND is_active = 1 LIMIT 1', [':e' => $email]);
        // Always show success (prevent email enumeration)
        if ($user) {
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            DB::run(
                'REPLACE INTO password_resets (email, token, expires_at) VALUES (:e, :t, :exp)',
                [':e' => $email, ':t' => $token, ':exp' => $expires]
            );
            // TODO: Send via email when SMTP is configured
            // For now, display the link on screen (remove in production)
            $resetLink = APP_URL . '/auth/verify?token=' . $token . '&email=' . urlencode($email);
        }
        $sent = true;
    }
}

$csrf = Auth::csrf();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Reset Password — Hustle Kingdom</title>
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
.field-err{font-size:11.5px;color:var(--red);margin-top:5px;font-weight:600;}
.btn-submit{width:100%;background:var(--green);color:#fff;border:none;border-radius:var(--r);padding:15px;font-size:14px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;}
.success-box{background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:var(--r);padding:18px;text-align:center;}
.success-box .icon{font-size:40px;margin-bottom:10px;}
.success-box h3{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;margin-bottom:8px;}
.success-box p{font-size:13px;color:var(--text2);line-height:1.6;}
.reset-link-dev{margin-top:16px;background:#fff8e8;border:1.5px dashed #e8d080;border-radius:10px;padding:12px;font-size:11px;word-break:break-all;color:#854f0b;}
.reset-link-dev strong{display:block;margin-bottom:6px;font-family:'Bricolage Grotesque',sans-serif;}
.back-link{display:block;text-align:center;margin-top:20px;color:var(--green);font-weight:700;text-decoration:none;font-size:13px;}
</style>
</head>
<body>
<div class="auth-header">
  <div class="auth-logo">👑</div>
  <div class="auth-brand">Hustle<em>Kingdom</em></div>
</div>
<div class="auth-body">
<?php if ($sent): ?>
  <div class="success-box">
    <div class="icon">📧</div>
    <h3>Check Your Email</h3>
    <p>If that email is registered, we've sent a password reset link. Check your inbox (and spam folder).</p>
    <?php if ($resetLink && APP_DEBUG): ?>
      <div class="reset-link-dev">
        <strong>⚠️ DEV ONLY — Remove in production:</strong>
        <a href="<?= htmlspecialchars($resetLink) ?>"><?= htmlspecialchars($resetLink) ?></a>
      </div>
    <?php endif; ?>
  </div>
  <a href="/auth/login" class="back-link">← Back to login</a>
<?php else: ?>
  <div class="auth-title">Reset Password 🔐</div>
  <div class="auth-sub">Enter your email address and we'll send you a link to reset your password.</div>

  <form method="POST" action="/auth/forgot">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>"/>
    <div class="field">
      <label for="email">Email Address</label>
      <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email"/>
      <?php if (!empty($errors['email'])): ?>
        <div class="field-err"><?= htmlspecialchars($errors['email']) ?></div>
      <?php endif; ?>
    </div>
    <button type="submit" class="btn-submit">Send Reset Link</button>
  </form>
  <a href="/auth/login" class="back-link">← Back to login</a>
<?php endif; ?>
</div>
</body>
</html>
