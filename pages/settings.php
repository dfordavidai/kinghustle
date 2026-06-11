<?php
/**
 * HustleKingdom — Settings
 * Change password, notification prefs, danger zone (delete account).
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

// ── Handle POST actions (non-AJAX) ───────────────────────────────────────────
$pageAlert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    Middleware::verifyCsrf();

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $row = DB::one('SELECT password_hash FROM users WHERE id=:id', [':id' => $user['id']]);
        if (!password_verify($current, $row['password_hash'])) {
            $pageAlert = ['type'=>'err','msg'=>'Current password is incorrect.'];
        } elseif (strlen($new) < PASSWORD_MIN_LEN) {
            $pageAlert = ['type'=>'err','msg'=>'New password must be at least '.PASSWORD_MIN_LEN.' characters.'];
        } elseif ($new !== $confirm) {
            $pageAlert = ['type'=>'err','msg'=>'New passwords do not match.'];
        } else {
            DB::run('UPDATE users SET password_hash=:h WHERE id=:id', [
                ':h'  => password_hash($new, PASSWORD_DEFAULT),
                ':id' => $user['id'],
            ]);
            $pageAlert = ['type'=>'ok','msg'=>'Password updated successfully.'];
        }
    }

    if ($action === 'delete_account') {
        $confirm = trim($_POST['confirm_word'] ?? '');
        if (strtolower($confirm) !== 'delete') {
            $pageAlert = ['type'=>'err','msg'=>'Type "delete" exactly to confirm.'];
        } else {
            // Soft delete — mark inactive, anonymize PII, expire session
            DB::run(
                "UPDATE users SET is_active=0, email=CONCAT('deleted_',id,'@removed.hk'),
                 name='Deleted User', phone=NULL, reset_token=NULL, verify_token=NULL
                 WHERE id=:id",
                [':id' => $user['id']]
            );
            Auth::logout();
            header('Location: ' . APP_URL . '/?account=deleted');
            exit;
        }
    }
}

$csrf = Auth::csrf();
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Settings — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#ffffff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;--border2:#d8d8d0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --red:#cc3333;--red-light:#fdf0f0;
  --r:16px;--rs:10px;--nav:58px;--bot:64px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;}

.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:17px;}
.nav-back{font-size:20px;text-decoration:none;color:var(--text2);}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}

.page-alert{margin:14px 16px 0;padding:12px 14px;border-radius:var(--rs);font-size:13px;font-weight:600;}
.page-alert.ok{background:var(--green-light);border:1px solid #b2e0c8;color:var(--green3);}
.page-alert.err{background:var(--red-light);border:1px solid #f0c0c0;color:var(--red);}

.section{padding:16px 16px 0;}
.sec-label{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;}

/* menu rows */
.menu-row{display:flex;align-items:center;justify-content:space-between;padding:14px;border-bottom:1px solid var(--border);cursor:pointer;text-decoration:none;color:inherit;}
.menu-row:last-child{border-bottom:none;}
.menu-left{display:flex;align-items:center;gap:12px;}
.menu-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.menu-text{display:flex;flex-direction:column;gap:1px;}
.menu-title{font-size:14px;font-weight:700;}
.menu-sub{font-size:12px;color:var(--text3);}
.menu-chevron{font-size:16px;color:var(--text3);}

/* form fields */
.field-row{display:flex;flex-direction:column;padding:14px;border-bottom:1px solid var(--border);}
.field-row:last-child{border-bottom:none;}
.field-label{font-size:11px;font-weight:700;color:var(--text3);margin-bottom:5px;}
.field-input{border:1.5px solid var(--border2);border-radius:var(--rs);padding:10px 12px;font-family:'Instrument Sans',sans-serif;font-size:14px;color:var(--text);background:#fff;outline:none;width:100%;}
.field-input:focus{border-color:var(--green);}

.submit-btn{width:100%;border:none;border-radius:var(--rs);padding:13px;font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;cursor:pointer;margin-top:12px;}
.submit-btn.green{background:var(--green);color:#fff;}
.submit-btn.red{background:var(--red);color:#fff;}
.submit-btn:disabled{opacity:.6;}

/* collapsible section */
.collapsible{display:none;}
.collapsible.open{display:block;}

/* toggle */
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px;border-bottom:1px solid var(--border);}
.toggle-row:last-child{border-bottom:none;}
.toggle-label{font-size:14px;font-weight:600;}
.toggle-sub{font-size:11px;color:var(--text3);margin-top:1px;}
.toggle-switch{position:relative;width:44px;height:24px;flex-shrink:0;}
.toggle-switch input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;background:var(--border2);border-radius:20px;cursor:pointer;transition:.2s;}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.2s;}
.toggle-switch input:checked + .toggle-slider{background:var(--green);}
.toggle-switch input:checked + .toggle-slider::before{transform:translateX(20px);}

/* danger zone */
.danger-card{background:var(--red-light);border:1px solid #f0c0c0;border-radius:var(--r);padding:16px;}
.danger-title{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;color:var(--red);margin-bottom:6px;}
.danger-body{font-size:13px;color:var(--text2);line-height:1.5;margin-bottom:12px;}

.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;cursor:pointer;border:none;background:transparent;color:var(--text3);text-decoration:none;}
.bni{font-size:18px;}
.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:4px 0;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}
</style>
</head>
<body>

<nav class="topnav">
  <a href="<?= APP_URL ?>/profile" class="nav-back" title="Back to Profile">←</a>
  <div class="nav-title">Settings</div>
  <div class="nav-r">
    <?php if ($isPro): ?>
      <span class="pro-chip">⭐ PRO</span>
    <?php else: ?>
      <a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a>
    <?php endif; ?>
  </div>
</nav>

<?php if ($pageAlert): ?>
  <div class="page-alert <?= $pageAlert['type'] ?>"><?= htmlspecialchars($pageAlert['msg']) ?></div>
<?php endif; ?>

<!-- ── ACCOUNT ── -->
<div class="section">
  <div class="sec-label">Account</div>
  <div class="card">
    <a href="<?= APP_URL ?>/profile" class="menu-row">
      <div class="menu-left">
        <div class="menu-icon" style="background:var(--green-light);">👤</div>
        <div class="menu-text">
          <div class="menu-title"><?= htmlspecialchars($user['name']) ?></div>
          <div class="menu-sub"><?= htmlspecialchars($user['email']) ?></div>
        </div>
      </div>
      <div class="menu-chevron">›</div>
    </a>
    <div class="menu-row" onclick="toggleSection('password-section')">
      <div class="menu-left">
        <div class="menu-icon" style="background:#f3eefe;">🔑</div>
        <div class="menu-text">
          <div class="menu-title">Change Password</div>
          <div class="menu-sub">Update your login password</div>
        </div>
      </div>
      <div class="menu-chevron" id="pw-chevron">›</div>
    </div>
    <div class="collapsible" id="password-section" style="border-top:1px solid var(--border);">
      <form method="POST" action="/settings">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>"/>
        <input type="hidden" name="action" value="change_password"/>
        <div class="field-row">
          <div class="field-label">Current Password</div>
          <input class="field-input" type="password" name="current_password" required autocomplete="current-password"/>
        </div>
        <div class="field-row">
          <div class="field-label">New Password</div>
          <input class="field-input" type="password" name="new_password" required minlength="<?= PASSWORD_MIN_LEN ?>" autocomplete="new-password"/>
        </div>
        <div class="field-row" style="border-bottom:none;">
          <div class="field-label">Confirm New Password</div>
          <input class="field-input" type="password" name="confirm_password" required autocomplete="new-password"/>
        </div>
        <div style="padding:0 14px 14px;">
          <button class="submit-btn green" type="submit">Update Password</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── SUBSCRIPTION ── -->
<div class="section">
  <div class="sec-label">Subscription</div>
  <div class="card">
    <?php if ($isPro): ?>
    <div class="menu-row" style="cursor:default;">
      <div class="menu-left">
        <div class="menu-icon" style="background:var(--gold-light);">⭐</div>
        <div class="menu-text">
          <div class="menu-title">Pro Member</div>
          <div class="menu-sub">Expires <?= date('d M Y', strtotime($user['pro_expires_at'])) ?></div>
        </div>
      </div>
      <span style="font-size:12px;font-weight:700;color:var(--green);">Active</span>
    </div>
    <?php else: ?>
    <a href="<?= APP_URL ?>/upgrade" class="menu-row">
      <div class="menu-left">
        <div class="menu-icon" style="background:var(--gold-light);">⭐</div>
        <div class="menu-text">
          <div class="menu-title">Upgrade to Pro</div>
          <div class="menu-sub">₦2,500/mo · Unlock all features</div>
        </div>
      </div>
      <div class="menu-chevron">›</div>
    </a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/dashboard" class="menu-row" style="border-bottom:none;">
      <div class="menu-left">
        <div class="menu-icon" style="background:var(--green-light);">💸</div>
        <div class="menu-text">
          <div class="menu-title">Referral Earnings</div>
          <div class="menu-sub">Withdraw your ₦500/referral earnings</div>
        </div>
      </div>
      <div class="menu-chevron">›</div>
    </a>
  </div>
</div>

<!-- ── NOTIFICATIONS ── -->
<div class="section">
  <div class="sec-label">Notifications</div>
  <div class="card">
    <div class="toggle-row">
      <div>
        <div class="toggle-label">Income Reminders</div>
        <div class="toggle-sub">Remind me to log daily income</div>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" id="pref-income-reminder" checked onchange="savePref('income_reminder', this.checked)"/>
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="toggle-row">
      <div>
        <div class="toggle-label">Referral Alerts</div>
        <div class="toggle-sub">Notify when someone uses my referral link</div>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" id="pref-referral-alert" checked onchange="savePref('referral_alert', this.checked)"/>
        <span class="toggle-slider"></span>
      </label>
    </div>
    <div class="toggle-row" style="border-bottom:none;">
      <div>
        <div class="toggle-label">Goal Milestones</div>
        <div class="toggle-sub">Celebrate when I hit a goal milestone</div>
      </div>
      <label class="toggle-switch">
        <input type="checkbox" id="pref-goal-alert" checked onchange="savePref('goal_alert', this.checked)"/>
        <span class="toggle-slider"></span>
      </label>
    </div>
  </div>
  <p style="font-size:11px;color:var(--text3);padding:8px 4px 0;">Push notifications will be available in the upcoming mobile app. These preferences are saved for when that launches.</p>
</div>

<!-- ── APP ── -->
<div class="section">
  <div class="sec-label">App</div>
  <div class="card">
    <a href="<?= APP_URL ?>/glossary" class="menu-row">
      <div class="menu-left">
        <div class="menu-icon" style="background:#ebf3fe;">📚</div>
        <div class="menu-text">
          <div class="menu-title">Hustle Glossary</div>
          <div class="menu-sub">Business terms explained simply</div>
        </div>
      </div>
      <div class="menu-chevron">›</div>
    </a>
    <a href="mailto:support@hustlekingdom.ng" class="menu-row">
      <div class="menu-left">
        <div class="menu-icon" style="background:#ebf7f1;">💬</div>
        <div class="menu-text">
          <div class="menu-title">Contact Support</div>
          <div class="menu-sub">support@hustlekingdom.ng</div>
        </div>
      </div>
      <div class="menu-chevron">›</div>
    </a>
    <a href="<?= APP_URL ?>/auth/logout" class="menu-row" style="border-bottom:none;">
      <div class="menu-left">
        <div class="menu-icon" style="background:var(--red-light);">🚪</div>
        <div class="menu-text">
          <div class="menu-title" style="color:var(--red);">Log Out</div>
          <div class="menu-sub">Sign out of this device</div>
        </div>
      </div>
      <div class="menu-chevron" style="color:var(--red);">›</div>
    </a>
  </div>
</div>

<!-- ── DANGER ZONE ── -->
<div class="section">
  <div class="sec-label">Danger Zone</div>
  <div class="danger-card">
    <div class="danger-title">⚠️ Delete Account</div>
    <div class="danger-body">
      This permanently deletes your account, income logs, goals, and all data.
      This action <strong>cannot be undone</strong>. Your Pro subscription will not be refunded.
    </div>
    <button class="submit-btn red" onclick="toggleSection('delete-section')">Delete My Account</button>
    <div class="collapsible" id="delete-section" style="margin-top:14px;">
      <form method="POST" action="/settings" onsubmit="return confirmDelete(this)">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>"/>
        <input type="hidden" name="action" value="delete_account"/>
        <div style="margin-bottom:10px;">
          <label class="field-label" style="font-size:12px;color:var(--red);font-weight:700;display:block;margin-bottom:6px;">
            Type <strong>delete</strong> to confirm:
          </label>
          <input class="field-input" type="text" name="confirm_word" placeholder="delete" autocomplete="off"
            style="border-color:#f0c0c0;background:rgba(255,255,255,.8);"/>
        </div>
        <button class="submit-btn red" type="submit">Permanently Delete Account</button>
      </form>
    </div>
  </div>
</div>

<div style="padding:16px 16px 0;text-align:center;font-size:11px;color:var(--text3);">
  Hustle Kingdom · v1.0 · Made with 💚 in Nigeria
</div>
<div style="height:16px;"></div>

<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav">
    <div class="bni">🏠</div><div class="bnl">Home</div>
  </a>
  <a href="<?= APP_URL ?>/hustles" class="bnav">
    <div class="bni">💡</div><div class="bnl">Discover</div>
  </a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav">
    <div class="bni">📍</div><div class="bnl">Location</div>
  </a>
  <a href="<?= APP_URL ?>/profile" class="bnav">
    <div class="bni">👤</div><div class="bnl">Profile</div>
  </a>
</nav>

<script>
function toggleSection(id) {
  const el = document.getElementById(id);
  el.classList.toggle('open');
  if (id === 'password-section') {
    document.getElementById('pw-chevron').textContent = el.classList.contains('open') ? '⌄' : '›';
  }
}

function confirmDelete(form) {
  return confirm('Are you absolutely sure? This cannot be undone.');
}

// Preferences stored in localStorage (server-side prefs coming in a future release)
function savePref(key, val) {
  try { localStorage.setItem('hk_pref_'+key, val ? '1' : '0'); } catch(e){}
}
function loadPrefs() {
  ['income_reminder','referral_alert','goal_alert'].forEach(k => {
    const stored = localStorage.getItem('hk_pref_'+k);
    if (stored === '0') {
      const el = document.getElementById('pref-'+k.replace('_','-'));
      if (el) el.checked = false;
    }
  });
}
loadPrefs();

// Open password section if redirected with alert (from password change)
<?php if ($pageAlert && str_contains($pageAlert['msg'] ?? '', 'assword')): ?>
document.getElementById('password-section').classList.add('open');
document.getElementById('pw-chevron').textContent = '⌄';
<?php endif; ?>
</script>
</body>
</html>
