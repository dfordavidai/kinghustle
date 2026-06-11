<?php
/**
 * HustleKingdom — User Profile
 * View stats, update name/phone/location/avatar, see subscription status.
 */
defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';

Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();

// ── Stats ────────────────────────────────────────────────────────────────────
$totalIncome = (float)(DB::one(
    'SELECT COALESCE(SUM(amount),0) as t FROM income_log WHERE user_id=:id',
    [':id' => $user['id']]
)['t'] ?? 0);

$incomeThisMonth = (float)(DB::one(
    'SELECT COALESCE(SUM(amount),0) as t FROM income_log
     WHERE user_id=:id AND DATE_FORMAT(logged_at,"%Y-%m")=DATE_FORMAT(NOW(),"%Y-%m")',
    [':id' => $user['id']]
)['t'] ?? 0);

$logsCount = (int)(DB::one(
    'SELECT COUNT(*) as n FROM income_log WHERE user_id=:id',
    [':id' => $user['id']]
)['n'] ?? 0);

$savedCount = (int)(DB::one(
    'SELECT COUNT(*) as n FROM saved_hustles WHERE user_id=:id',
    [':id' => $user['id']]
)['n'] ?? 0);

$goalsCount = (int)(DB::one(
    'SELECT COUNT(*) as n FROM goals WHERE user_id=:id',
    [':id' => $user['id']]
)['n'] ?? 0);

$paidRefs = (int)(DB::one(
    'SELECT COUNT(*) as n FROM referrals r
     JOIN users u ON u.id=r.referred_id
     WHERE r.referrer_id=:id AND u.is_pro=1 AND (u.pro_expires_at IS NULL OR u.pro_expires_at>NOW())',
    [':id' => $user['id']]
)['n'] ?? 0);

$refEarnings = $paidRefs * 500;

$subscription = DB::one(
    "SELECT plan, status, started_at, expires_at FROM subscriptions
     WHERE user_id=:id AND status='active' ORDER BY created_at DESC LIMIT 1",
    [':id' => $user['id']]
);

$memberSince = date('F Y', strtotime($user['created_at']));
$joinDays    = (int)floor((time() - strtotime($user['created_at'])) / 86400);

$avatarOptions = ['👑','🔥','💎','🚀','💡','🦁','🐉','⚡','🌟','🏆','💰','🎯','🦅','🌊','🎭'];

$NIGERIAN_STATES = ['Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
  'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo','Jigawa','Kaduna',
  'Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa','Niger','Ogun','Ondo','Osun',
  'Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>My Profile — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#ffffff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;--border2:#d8d8d0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --red:#cc3333;--red-light:#fdf0f0;
  --r:16px;--rs:10px;--nav:58px;--bot:64px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;}

.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-back{font-size:20px;text-decoration:none;color:var(--text2);padding:4px;}

.profile-hero{background:linear-gradient(140deg,#0d6e3c 0%,#16a05a 55%,#20c870 100%);padding:28px 20px 24px;text-align:center;position:relative;overflow:hidden;}
.profile-hero::before{content:'';position:absolute;right:-40px;top:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.06);}
.avatar-ring{width:72px;height:72px;background:rgba(255,255,255,.2);border:3px solid rgba(255,255,255,.5);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:34px;margin:0 auto 12px;cursor:pointer;position:relative;}
.avatar-edit-hint{position:absolute;bottom:-2px;right:-2px;width:22px;height:22px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;border:2px solid #fff;}
.profile-name{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;color:#fff;margin-bottom:3px;}
.profile-email{font-size:12px;color:rgba(255,255,255,.7);margin-bottom:10px;}
.profile-meta{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;}
.meta-pill{background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);color:#fff;font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;}

.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;padding:16px 16px 0;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px 10px;text-align:center;}
.stat-n{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;color:var(--green);}
.stat-l{font-size:10px;color:var(--text3);font-weight:600;margin-top:2px;line-height:1.3;}

.section{padding:16px 16px 0;}
.sec-label{font-size:11px;font-weight:700;color:var(--text3);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px;}
.card{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;}
.field-row{display:flex;flex-direction:column;padding:14px;border-bottom:1px solid var(--border);}
.field-row:last-child{border-bottom:none;}
.field-label{font-size:11px;font-weight:700;color:var(--text3);margin-bottom:5px;}
.field-input{border:1.5px solid var(--border2);border-radius:var(--rs);padding:10px 12px;font-family:'Instrument Sans',sans-serif;font-size:14px;color:var(--text);background:#fff;outline:none;width:100%;}
.field-input:focus{border-color:var(--green);}
.field-input[readonly]{background:var(--surface2);color:var(--text3);cursor:default;}
select.field-input{appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%239a9a92'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 12px center;}

.save-btn{width:100%;background:var(--green);color:#fff;border:none;border-radius:var(--rs);padding:13px;font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;cursor:pointer;margin-top:12px;}
.save-btn:disabled{opacity:.6;cursor:default;}

.sub-badge{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:20px;font-size:12px;font-weight:700;}
.sub-badge.pro{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);}
.sub-badge.free{background:var(--surface2);border:1px solid var(--border2);color:var(--text3);}

.info-row{display:flex;justify-content:space-between;align-items:center;padding:12px 14px;border-bottom:1px solid var(--border);}
.info-row:last-child{border-bottom:none;}
.info-key{font-size:13px;color:var(--text2);}
.info-val{font-size:13px;font-weight:700;text-align:right;}

.alert{padding:11px 14px;border-radius:var(--rs);font-size:13px;font-weight:600;margin-top:12px;display:none;}
.alert.ok{background:var(--green-light);border:1px solid #b2e0c8;color:var(--green3);}
.alert.err{background:var(--red-light);border:1px solid #f0c0c0;color:var(--red);}

.avatar-picker{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:500;align-items:flex-end;justify-content:center;}
.avatar-picker.open{display:flex;}
.avatar-sheet{background:#fff;width:100%;max-width:430px;border-radius:20px 20px 0 0;padding:24px 20px 32px;}
.avatar-sheet h3{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;margin-bottom:16px;}
.avatar-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;}
.av-btn{width:100%;aspect-ratio:1;font-size:26px;border-radius:12px;border:2px solid var(--border);background:var(--surface);cursor:pointer;display:flex;align-items:center;justify-content:center;}
.av-btn.selected{border-color:var(--green);background:var(--green-light);}

.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;cursor:pointer;border:none;background:transparent;color:var(--text3);text-decoration:none;}
.bnav.active .bnl,.bnav.active .bni{color:var(--green);}
.bni{font-size:18px;}
.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:4px 0;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}
</style>
</head>
<body>

<nav class="topnav">
  <a href="<?= APP_URL ?>/dashboard" class="nav-back" title="Back">←</a>
  <a href="<?= APP_URL ?>/" class="nav-brand" style="font-size:15px;">
    Hustle<em>Kingdom</em>
  </a>
  <div class="nav-r">
    <?php if ($isPro): ?>
      <span class="pro-chip">⭐ PRO</span>
    <?php else: ?>
      <a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/settings" style="font-size:20px;text-decoration:none;" title="Settings">⚙️</a>
  </div>
</nav>

<!-- ── HERO ── -->
<div class="profile-hero">
  <div class="avatar-ring" onclick="openAvatarPicker()" id="avatar-display">
    <?= htmlspecialchars($user['avatar_emoji']) ?>
    <span class="avatar-edit-hint">✏️</span>
  </div>
  <div class="profile-name" id="display-name"><?= htmlspecialchars($user['name']) ?></div>
  <div class="profile-email"><?= htmlspecialchars($user['email']) ?></div>
  <div class="profile-meta">
    <?php if ($isPro): ?>
      <span class="meta-pill">⭐ Pro Member</span>
    <?php endif; ?>
    <span class="meta-pill">📅 Since <?= $memberSince ?></span>
    <span class="meta-pill">🔥 <?= $user['streak_count'] ?> day streak</span>
  </div>
</div>

<!-- ── STATS ── -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-n">₦<?= $totalIncome >= 1000000 ? round($totalIncome/1000000,1).'M' : ($totalIncome >= 1000 ? round($totalIncome/1000,0).'k' : number_format($totalIncome,0)) ?></div>
    <div class="stat-l">Total Income Logged</div>
  </div>
  <div class="stat-card">
    <div class="stat-n"><?= $savedCount ?></div>
    <div class="stat-l">Saved Hustles</div>
  </div>
  <div class="stat-card">
    <div class="stat-n"><?= $paidRefs ?></div>
    <div class="stat-l">Paid Referrals</div>
  </div>
</div>

<!-- ── SUBSCRIPTION ── -->
<div class="section">
  <div class="sec-label">Subscription</div>
  <div class="card">
    <div class="info-row">
      <span class="info-key">Plan</span>
      <span class="info-val">
        <?php if ($isPro && $subscription): ?>
          <span class="sub-badge pro">⭐ Pro · <?= ucfirst($subscription['plan']) ?></span>
        <?php else: ?>
          <span class="sub-badge free">Free</span>
        <?php endif; ?>
      </span>
    </div>
    <?php if ($isPro && $subscription): ?>
    <div class="info-row">
      <span class="info-key">Expires</span>
      <span class="info-val"><?= date('d M Y', strtotime($user['pro_expires_at'])) ?></span>
    </div>
    <div class="info-row">
      <span class="info-key">Started</span>
      <span class="info-val"><?= date('d M Y', strtotime($subscription['started_at'])) ?></span>
    </div>
    <?php else: ?>
    <div class="info-row">
      <span class="info-key" style="color:var(--text2);">Unlock Pro features</span>
      <a href="<?= APP_URL ?>/upgrade" style="font-size:12px;font-weight:800;color:var(--gold2);">Upgrade →</a>
    </div>
    <?php endif; ?>
    <div class="info-row">
      <span class="info-key">Member since</span>
      <span class="info-val"><?= $memberSince ?> (<?= $joinDays ?> days)</span>
    </div>
  </div>
</div>

<!-- ── REFERRAL EARNINGS ── -->
<?php if ($paidRefs > 0): ?>
<div class="section">
  <div class="sec-label">Referral Earnings</div>
  <div class="card">
    <div class="info-row">
      <span class="info-key">Paid referrals</span>
      <span class="info-val"><?= $paidRefs ?></span>
    </div>
    <div class="info-row">
      <span class="info-key">Total earned</span>
      <span class="info-val" style="color:var(--green);">₦<?= number_format($refEarnings) ?></span>
    </div>
    <div class="info-row" style="border-bottom:none;">
      <span class="info-key" style="color:var(--text2);">Withdraw via dashboard</span>
      <a href="<?= APP_URL ?>/dashboard" style="font-size:12px;font-weight:800;color:var(--green);">Go →</a>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── EDIT PROFILE ── -->
<div class="section">
  <div class="sec-label">Edit Profile</div>
  <div class="card">
    <div class="field-row">
      <div class="field-label">Full Name</div>
      <input class="field-input" id="f-name" type="text" value="<?= htmlspecialchars($user['name']) ?>" maxlength="100"/>
    </div>
    <div class="field-row">
      <div class="field-label">Email</div>
      <input class="field-input" type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly/>
    </div>
    <div class="field-row">
      <div class="field-label">Phone</div>
      <input class="field-input" id="f-phone" type="tel" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" maxlength="20" placeholder="+234 80X XXX XXXX"/>
    </div>
    <div class="field-row">
      <div class="field-label">State</div>
      <select class="field-input" id="f-state">
        <option value="">— Select state —</option>
        <?php foreach ($NIGERIAN_STATES as $st): ?>
          <option value="<?= htmlspecialchars($st) ?>" <?= ($user['state']??'')===$st?'selected':'' ?>><?= htmlspecialchars($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="field-row" style="border-bottom:none;">
      <div class="field-label">City / Area</div>
      <input class="field-input" id="f-city" type="text" value="<?= htmlspecialchars($user['city'] ?? '') ?>" maxlength="100" placeholder="e.g. Ikeja, Wuse II"/>
    </div>
  </div>

  <div id="profile-alert" class="alert"></div>
  <button class="save-btn" id="save-btn" onclick="saveProfile()">Save Changes</button>
</div>

<div style="height:8px;"></div>

<!-- ── AVATAR PICKER ── -->
<div class="avatar-picker" id="avatar-picker" onclick="if(event.target===this)closeAvatarPicker()">
  <div class="avatar-sheet">
    <h3>Choose Your Avatar</h3>
    <div class="avatar-grid" id="avatar-grid">
      <?php foreach ($avatarOptions as $em): ?>
        <button class="av-btn <?= $user['avatar_emoji']===$em?'selected':'' ?>"
          onclick="selectAvatar('<?= $em ?>')"><?= $em ?></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- ── BOTTOM NAV ── -->
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
  <a href="<?= APP_URL ?>/profile" class="bnav active">
    <div class="bni">👤</div><div class="bnl">Profile</div>
  </a>
</nav>

<script>
let currentAvatar = <?= json_encode($user['avatar_emoji']) ?>;

function openAvatarPicker() {
  document.getElementById('avatar-picker').classList.add('open');
}
function closeAvatarPicker() {
  document.getElementById('avatar-picker').classList.remove('open');
}
function selectAvatar(em) {
  currentAvatar = em;
  document.getElementById('avatar-display').childNodes[0].textContent = em;
  document.querySelectorAll('.av-btn').forEach(b => b.classList.toggle('selected', b.textContent.trim() === em));
  closeAvatarPicker();
  saveProfile(true); // auto-save avatar immediately
}

async function saveProfile(avatarOnly = false) {
  const btn   = document.getElementById('save-btn');
  const alert = document.getElementById('profile-alert');
  alert.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'Saving…';

  const body = { avatar_emoji: currentAvatar };
  if (!avatarOnly) {
    body.name  = document.getElementById('f-name').value.trim();
    body.phone = document.getElementById('f-phone').value.trim();
    body.state = document.getElementById('f-state').value;
    body.city  = document.getElementById('f-city').value.trim();
    if (!body.name) { showAlert('Name cannot be empty.', false); btn.disabled=false; btn.textContent='Save Changes'; return; }
  }

  try {
    const res  = await fetch('/api/profile', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
    const data = await res.json();
    if (data.ok) {
      if (!avatarOnly) {
        document.getElementById('display-name').textContent = body.name || data.data?.name;
        showAlert('✅ Profile saved!', true);
      }
    } else {
      showAlert(data.error || 'Update failed.', false);
    }
  } catch (e) {
    showAlert('Network error. Try again.', false);
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save Changes';
  }
}

function showAlert(msg, ok) {
  const el = document.getElementById('profile-alert');
  el.textContent = msg;
  el.className = 'alert ' + (ok ? 'ok' : 'err');
  el.style.display = 'block';
  if (ok) setTimeout(() => el.style.display='none', 3000);
}
</script>
</body>
</html>
