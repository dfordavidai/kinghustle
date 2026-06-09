<?php
/**
 * HustleKingdom — User Dashboard
 * Route: GET /dashboard
 * Requires auth. Shows income overview, goals, saved hustles, referral, streak.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();

// ── Income summary (last 30 days) ─────────────────────────────────────────────
$income30 = DB::query(
    'SELECT il.id, il.amount, il.logged_at, il.custom_name,
            COALESCE(h.name, il.custom_name) as hustle_name, h.emoji
     FROM income_log il
     LEFT JOIN hustles h ON h.id = il.hustle_id
     WHERE il.user_id = :uid AND il.logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     ORDER BY il.logged_at DESC',
    [':uid' => $user['id']]
);
$total30 = array_sum(array_column($income30, 'amount'));

// Income last 7 days for sparkline
$income7 = DB::query(
    'SELECT DATE_FORMAT(logged_at, "%a") as day_label, SUM(amount) as day_total
     FROM income_log
     WHERE user_id = :uid AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY logged_at ORDER BY logged_at ASC',
    [':uid' => $user['id']]
);

// This month vs last month
$thisMonth = (float)(DB::one(
    'SELECT COALESCE(SUM(amount),0) as t FROM income_log
     WHERE user_id = :uid AND MONTH(logged_at) = MONTH(CURDATE()) AND YEAR(logged_at) = YEAR(CURDATE())',
    [':uid' => $user['id']]
)['t'] ?? 0);

$lastMonth = (float)(DB::one(
    'SELECT COALESCE(SUM(amount),0) as t FROM income_log
     WHERE user_id = :uid AND MONTH(logged_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
       AND YEAR(logged_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))',
    [':uid' => $user['id']]
)['t'] ?? 0);

$monthChange = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100) : null;

// ── Goals ─────────────────────────────────────────────────────────────────────
$goals = DB::query(
    'SELECT * FROM goals WHERE user_id = :uid ORDER BY is_completed ASC, created_at DESC LIMIT 6',
    [':uid' => $user['id']]
);
$activeGoals    = array_filter($goals, fn($g) => !(bool)$g['is_completed']);
$completedGoals = array_filter($goals, fn($g) => (bool)$g['is_completed']);

// ── Saved Hustles ─────────────────────────────────────────────────────────────
$savedHustles = DB::query(
    'SELECT h.id, h.name, h.slug, h.emoji, h.category, h.income_min, h.income_max,
            h.income_period, h.difficulty, sh.created_at as saved_at
     FROM saved_hustles sh
     JOIN hustles h ON h.id = sh.hustle_id
     WHERE sh.user_id = :uid
     ORDER BY sh.created_at DESC LIMIT 6',
    [':uid' => $user['id']]
);

// ── Referral ──────────────────────────────────────────────────────────────────
$refCount = (int)(DB::one(
    'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :id',
    [':id' => $user['id']]
)['cnt'] ?? 0);
$shareUrl = APP_URL . '/auth/register?ref=' . urlencode($user['ref_code']);

// ── Top income sources ────────────────────────────────────────────────────────
$topSources = DB::query(
    'SELECT COALESCE(h.name, il.custom_name, "Other") as source_name,
            COALESCE(h.emoji, "💰") as emoji,
            SUM(il.amount) as total,
            COUNT(*) as entries
     FROM income_log il
     LEFT JOIN hustles h ON h.id = il.hustle_id
     WHERE il.user_id = :uid AND il.logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY source_name, emoji
     ORDER BY total DESC LIMIT 5',
    [':uid' => $user['id']]
);

// ── Recent income entries (last 5) ────────────────────────────────────────────
$recentIncome = array_slice($income30, 0, 5);

// ── Helpers ───────────────────────────────────────────────────────────────────
$diffLabel = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];
$diffClass = ['beginner' => 'badge-green', 'intermediate' => 'badge-amber', 'advanced' => 'badge-red'];

function fmt(float $n): string {
    if ($n >= 1_000_000) return '₦' . number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return '₦' . number_format($n / 1_000, 1) . 'k';
    return '₦' . number_format($n, 0);
}

$csrf = Auth::csrf();
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', $user['name'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Dashboard — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;
  --green-light:#ebf7f1;--green-border:#b2e0c8;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold-border:#e8d080;
  --red:#cc3333;--red-light:#fdf0f0;
  --amber:#e07b00;--amber-light:#fff4e5;
  --r:14px;--nav-h:56px;--bottom-h:64px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;min-height:100vh;padding-bottom:calc(var(--bottom-h) + 16px);}

/* ── NAV ── */
.nav{display:flex;align-items:center;justify-content:space-between;padding:0 20px;height:var(--nav-h);border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--bg);z-index:100;}
.nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.nav-logo-icon{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:15px;color:var(--text);}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-right{display:flex;align-items:center;gap:10px;}
.nav-avatar{width:32px;height:32px;border-radius:50%;background:var(--green-light);border:1.5px solid var(--green-border);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;text-decoration:none;}
.pro-chip{background:var(--gold-light);border:1px solid var(--gold-border);color:var(--gold);font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}

/* ── BOTTOM NAV ── */
.bottom-nav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;background:var(--bg);border-top:1px solid var(--border);display:flex;height:var(--bottom-h);z-index:100;}
.bnav-item{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:3px;text-decoration:none;color:var(--text3);font-size:10px;font-weight:600;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;}
.bnav-item.active{color:var(--green);}
.bnav-icon{font-size:20px;line-height:1;}

/* ── LAYOUT ── */
.page{padding:0 20px;}
.section{margin-top:24px;}
.section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
.section-title{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;}
.section-link{font-size:12.5px;color:var(--green);font-weight:700;text-decoration:none;}

/* ── GREETING ── */
.greeting{padding:20px 20px 0;}
.greeting-sub{font-size:13px;color:var(--text2);margin-top:2px;}
.greeting-name{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;}
.streak-pill{display:inline-flex;align-items:center;gap:5px;background:var(--amber-light);border:1px solid #f5d9a0;border-radius:20px;padding:4px 10px;font-size:12px;font-weight:700;color:var(--amber);margin-top:8px;}

/* ── INCOME HERO CARD ── */
.income-hero{background:var(--green);border-radius:20px;padding:20px;margin:16px 20px 0;color:#fff;position:relative;overflow:hidden;}
.income-hero::before{content:'';position:absolute;top:-30px;right:-30px;width:130px;height:130px;background:rgba(255,255,255,.08);border-radius:50%;}
.income-hero::after{content:'';position:absolute;bottom:-20px;right:30px;width:80px;height:80px;background:rgba(255,255,255,.05);border-radius:50%;}
.ih-label{font-size:12px;font-weight:600;opacity:.8;margin-bottom:4px;}
.ih-amount{font-family:'Bricolage Grotesque',sans-serif;font-size:32px;font-weight:800;line-height:1.1;margin-bottom:4px;}
.ih-period{font-size:12px;opacity:.75;}
.ih-row{display:flex;align-items:center;justify-content:space-between;margin-top:16px;position:relative;z-index:1;}
.ih-stat{text-align:center;}
.ih-stat-val{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;}
.ih-stat-label{font-size:10.5px;opacity:.7;margin-top:1px;}
.ih-divider{width:1px;height:28px;background:rgba(255,255,255,.2);}
.change-up{color:#7ffab0;font-size:11px;font-weight:700;}
.change-down{color:#ffb3b3;font-size:11px;font-weight:700;}

/* ── QUICK ACTIONS ── */
.quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-top:16px;}
.qa-btn{display:flex;flex-direction:column;align-items:center;gap:6px;background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:12px 6px;text-decoration:none;color:var(--text);transition:.15s;}
.qa-btn:active{background:var(--border);}
.qa-icon{font-size:20px;line-height:1;}
.qa-label{font-size:10.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;text-align:center;color:var(--text2);}

/* ── STAT CARDS ── */
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.stat-card{background:var(--surface);border-radius:var(--r);padding:14px;}
.sc-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;}
.sc-val{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;}
.sc-sub{font-size:11px;color:var(--text3);margin-top:2px;}

/* ── GOALS ── */
.goal-card{border:1.5px solid var(--border);border-radius:var(--r);padding:14px;margin-bottom:10px;}
.goal-card.completed{opacity:.65;background:var(--surface);}
.goal-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;}
.goal-title-row{display:flex;align-items:center;gap:8px;}
.goal-emoji{font-size:22px;}
.goal-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:14px;line-height:1.2;}
.goal-deadline{font-size:11px;color:var(--text3);margin-top:2px;}
.goal-pct{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--green);}
.goal-pct.done{color:var(--text3);}
.goal-bar-bg{height:6px;background:var(--border);border-radius:4px;overflow:hidden;}
.goal-bar-fill{height:100%;background:var(--green);border-radius:4px;transition:.4s;}
.goal-bar-fill.done{background:var(--text3);}
.goal-amounts{display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--text3);}
.goal-amounts strong{color:var(--text2);font-weight:700;}
.empty-state{text-align:center;padding:24px 0;color:var(--text3);font-size:13px;}
.empty-state .es-icon{font-size:32px;margin-bottom:8px;}
.empty-state .es-text{color:var(--text3);}
.empty-state a{color:var(--green);font-weight:700;text-decoration:none;}

/* ── INCOME LIST ── */
.income-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);}
.income-row:last-child{border-bottom:none;}
.income-emoji{width:36px;height:36px;background:var(--green-light);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.income-info{flex:1;min-width:0;}
.income-name{font-size:13.5px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.income-date{font-size:11.5px;color:var(--text3);}
.income-amount{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--green);white-space:nowrap;}

/* ── SAVED HUSTLES ── */
.hustle-row{display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);}
.hustle-row:last-child{border-bottom:none;}
.hustle-icon{width:40px;height:40px;background:var(--surface);border:1px solid var(--border);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.hustle-info{flex:1;min-width:0;}
.hustle-name{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.hustle-income{font-size:11.5px;color:var(--text3);}
.hustle-arrow{color:var(--text3);font-size:16px;}

/* ── BADGES ── */
.badge{display:inline-block;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;}
.badge-green{background:var(--green-light);color:var(--green3);}
.badge-amber{background:var(--amber-light);color:var(--amber);}
.badge-red{background:var(--red-light);color:var(--red);}

/* ── REFERRAL CARD ── */
.ref-card{background:var(--gold-light);border:1.5px solid var(--gold-border);border-radius:16px;padding:16px;}
.ref-title{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;margin-bottom:4px;}
.ref-sub{font-size:12.5px;color:var(--text2);line-height:1.5;margin-bottom:14px;}
.ref-progress{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.ref-dots{display:flex;gap:6px;}
.ref-dot{width:28px;height:28px;border-radius:50%;border:2px solid var(--gold-border);display:flex;align-items:center;justify-content:center;font-size:13px;}
.ref-dot.filled{background:var(--gold);border-color:var(--gold);color:#fff;}
.ref-label{font-size:12px;color:var(--text2);}
.ref-copy-row{display:flex;gap:8px;}
.ref-code-box{flex:1;background:#fff;border:1.5px solid var(--gold-border);border-radius:10px;padding:10px 12px;font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;color:var(--text);letter-spacing:.5px;}
.ref-copy-btn{background:var(--gold);color:#fff;border:none;border-radius:10px;padding:10px 14px;font-size:12px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;}

/* ── PRO BANNER ── */
.pro-banner{background:linear-gradient(135deg,#1a7a45 0%,#0f5530 100%);border-radius:16px;padding:16px;color:#fff;display:flex;align-items:center;gap:14px;}
.pb-icon{font-size:32px;flex-shrink:0;}
.pb-text h3{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:15px;margin-bottom:3px;}
.pb-text p{font-size:12px;opacity:.85;line-height:1.5;}
.pb-btn{background:#fff;color:var(--green3);font-size:11px;font-weight:800;padding:7px 12px;border-radius:10px;text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;flex-shrink:0;margin-top:2px;}

/* ── SPARKLINE ── */
.sparkline-wrap{display:flex;align-items:flex-end;gap:4px;height:32px;margin-top:10px;}
.spark-bar{flex:1;background:rgba(255,255,255,.3);border-radius:3px 3px 0 0;min-height:4px;transition:.3s;}

/* ── LOG INCOME MODAL ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:200;align-items:flex-end;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg);border-radius:20px 20px 0 0;padding:20px 20px 40px;width:100%;max-width:430px;animation:slideUp .25s ease;}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.modal-handle{width:40px;height:4px;background:var(--border);border-radius:2px;margin:0 auto 18px;}
.modal-title{font-family:'Bricolage Grotesque',sans-serif;font-size:18px;font-weight:800;margin-bottom:18px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:12px;font-weight:700;color:var(--text2);margin-bottom:6px;font-family:'Bricolage Grotesque',sans-serif;}
.field input,.field select,.field textarea{width:100%;padding:12px 14px;border:1.5px solid var(--border);border-radius:var(--r);font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);background:#fff;outline:none;transition:.2s;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--green);}
.field textarea{resize:none;height:72px;}
.modal-submit{width:100%;background:var(--green);color:#fff;border:none;border-radius:var(--r);padding:14px;font-size:14px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;margin-top:4px;}
.modal-submit:active{background:var(--green2);}

/* ── TOAST ── */
.toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#0d0d0c;color:#fff;font-size:13px;font-weight:600;padding:10px 18px;border-radius:30px;z-index:300;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap;}
.toast.show{opacity:1;}

/* ── GOAL ADD FORM ── */
.goal-modal .modal-title{margin-bottom:14px;}
</style>
</head>
<body>

<!-- ── NAV ─────────────────────────────────────────────────────────────────── -->
<nav class="nav">
  <a href="<?= APP_URL ?>/" class="nav-logo">
    <div class="nav-logo-icon">👑</div>
    <div class="nav-brand">Hustle<em>Kingdom</em></div>
  </a>
  <div class="nav-right">
    <?php if ($isPro): ?>
      <span class="pro-chip">⭐ PRO</span>
    <?php else: ?>
      <a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/auth/logout" class="nav-avatar" title="Logout"><?= htmlspecialchars($user['avatar_emoji']) ?></a>
  </div>
</nav>

<!-- ── GREETING ────────────────────────────────────────────────────────────── -->
<div class="greeting">
  <div class="greeting-name"><?= htmlspecialchars($greeting) ?>, <?= htmlspecialchars($firstName) ?> <?= htmlspecialchars($user['avatar_emoji']) ?></div>
  <div class="greeting-sub">Here's your hustle overview for <?= date('F Y') ?>.</div>
  <?php if ($user['streak_count'] > 0): ?>
    <div class="streak-pill">🔥 <?= (int)$user['streak_count'] ?> day streak — keep it going!</div>
  <?php endif; ?>
</div>

<!-- ── INCOME HERO ─────────────────────────────────────────────────────────── -->
<div class="income-hero" style="position:relative;">
  <div class="ih-label">Income this month</div>
  <div class="ih-amount"><?= fmt($thisMonth) ?></div>
  <div class="ih-period">
    <?php if ($monthChange !== null): ?>
      <?php if ($monthChange >= 0): ?>
        <span class="change-up">↑ <?= abs($monthChange) ?>% vs last month</span>
      <?php else: ?>
        <span class="change-down">↓ <?= abs($monthChange) ?>% vs last month</span>
      <?php endif; ?>
    <?php else: ?>
      No data from last month
    <?php endif; ?>
  </div>

  <!-- Sparkline (last 7 days) -->
  <?php if (!empty($income7)): ?>
    <?php $maxDay = max(array_column($income7, 'day_total')) ?: 1; ?>
    <div class="sparkline-wrap" id="sparkline">
      <?php foreach ($income7 as $day): ?>
        <?php $h = max(8, round(($day['day_total'] / $maxDay) * 32)); ?>
        <div class="spark-bar" style="height:<?= $h ?>px;" title="<?= htmlspecialchars($day['day_label']) ?>: <?= fmt((float)$day['day_total']) ?>"></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="ih-row">
    <div class="ih-stat">
      <div class="ih-stat-val"><?= fmt($total30) ?></div>
      <div class="ih-stat-label">Last 30 days</div>
    </div>
    <div class="ih-divider"></div>
    <div class="ih-stat">
      <div class="ih-stat-val"><?= count($income30) ?></div>
      <div class="ih-stat-label">Entries</div>
    </div>
    <div class="ih-divider"></div>
    <div class="ih-stat">
      <div class="ih-stat-val"><?= count($activeGoals) ?></div>
      <div class="ih-stat-label">Active goals</div>
    </div>
  </div>
</div>

<!-- ── QUICK ACTIONS ───────────────────────────────────────────────────────── -->
<div class="page">
  <div class="quick-actions">
    <a href="#" class="qa-btn" onclick="openLogModal(event)">
      <div class="qa-icon">💰</div>
      <div class="qa-label">Log Income</div>
    </a>
    <a href="#" class="qa-btn" onclick="openGoalModal(event)">
      <div class="qa-icon">🎯</div>
      <div class="qa-label">Add Goal</div>
    </a>
    <a href="<?= APP_URL ?>/" class="qa-btn">
      <div class="qa-icon">🔍</div>
      <div class="qa-label">Find Hustles</div>
    </a>
    <a href="<?= APP_URL ?>/upgrade" class="qa-btn">
      <div class="qa-icon">⭐</div>
      <div class="qa-label"><?= $isPro ? 'Pro Active' : 'Go Pro' ?></div>
    </a>
  </div>

  <!-- ── GOALS ─────────────────────────────────────────────────────────────── -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">🎯 My Goals</div>
      <a href="#" class="section-link" onclick="openGoalModal(event)">+ Add goal</a>
    </div>

    <?php if (empty($goals)): ?>
      <div class="empty-state">
        <div class="es-icon">🎯</div>
        <div class="es-text">No goals yet. <a href="#" onclick="openGoalModal(event)">Set your first goal →</a></div>
      </div>
    <?php else: ?>
      <?php foreach ($activeGoals as $goal):
        $pct = $goal['target_amount'] > 0 ? min(100, round(($goal['current_amount'] / $goal['target_amount']) * 100)) : 0;
      ?>
        <div class="goal-card" id="goal-<?= (int)$goal['id'] ?>">
          <div class="goal-header">
            <div class="goal-title-row">
              <span class="goal-emoji"><?= htmlspecialchars($goal['emoji']) ?></span>
              <div>
                <div class="goal-title"><?= htmlspecialchars($goal['title']) ?></div>
                <?php if ($goal['deadline']): ?>
                  <div class="goal-deadline">📅 <?= date('d M Y', strtotime($goal['deadline'])) ?></div>
                <?php endif; ?>
              </div>
            </div>
            <div class="goal-pct"><?= $pct ?>%</div>
          </div>
          <div class="goal-bar-bg"><div class="goal-bar-fill" style="width:<?= $pct ?>%"></div></div>
          <div class="goal-amounts">
            <span>Saved: <strong><?= fmt((float)$goal['current_amount']) ?></strong></span>
            <span>Target: <strong><?= fmt((float)$goal['target_amount']) ?></strong></span>
          </div>
          <div style="display:flex;gap:8px;margin-top:10px;">
            <button onclick="addToGoal(<?= (int)$goal['id'] ?>, '<?= htmlspecialchars(addslashes($goal['title'])) ?>')"
              style="flex:1;background:var(--green-light);border:1px solid var(--green-border);border-radius:10px;padding:8px;font-size:12px;font-weight:700;color:var(--green3);cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;">
              + Add funds
            </button>
            <button onclick="markGoalDone(<?= (int)$goal['id'] ?>)"
              style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;color:var(--text3);cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;">
              ✓ Done
            </button>
            <button onclick="deleteGoal(<?= (int)$goal['id'] ?>)"
              style="background:var(--red-light);border:1px solid #f5c0c0;border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;color:var(--red);cursor:pointer;">
              🗑
            </button>
          </div>
        </div>
      <?php endforeach; ?>

      <?php foreach ($completedGoals as $goal): ?>
        <div class="goal-card completed">
          <div class="goal-header">
            <div class="goal-title-row">
              <span class="goal-emoji">✅</span>
              <div>
                <div class="goal-title"><?= htmlspecialchars($goal['title']) ?></div>
                <div class="goal-deadline">Completed <?= date('d M Y', strtotime($goal['completed_at'])) ?></div>
              </div>
            </div>
            <div class="goal-pct done">100%</div>
          </div>
          <div class="goal-bar-bg"><div class="goal-bar-fill done" style="width:100%"></div></div>
          <div class="goal-amounts">
            <span>Target: <strong><?= fmt((float)$goal['target_amount']) ?></strong></span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── RECENT INCOME ──────────────────────────────────────────────────────── -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">💸 Recent Income</div>
      <a href="#" class="section-link" onclick="openLogModal(event)">+ Log</a>
    </div>

    <?php if (empty($recentIncome)): ?>
      <div class="empty-state">
        <div class="es-icon">💸</div>
        <div class="es-text">No income logged yet. <a href="#" onclick="openLogModal(event)">Log your first ₦ →</a></div>
      </div>
    <?php else: ?>
      <div id="income-list">
        <?php foreach ($recentIncome as $entry): ?>
          <div class="income-row" id="income-<?= (int)$entry['id'] ?>">
            <div class="income-emoji"><?= htmlspecialchars($entry['emoji'] ?? '💰') ?></div>
            <div class="income-info">
              <div class="income-name"><?= htmlspecialchars($entry['hustle_name'] ?? 'Custom Income') ?></div>
              <div class="income-date"><?= date('d M Y', strtotime($entry['logged_at'])) ?></div>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
              <div class="income-amount">+<?= fmt((float)$entry['amount']) ?></div>
              <button onclick="deleteEntry(<?= (int)$entry['id'] ?>)"
                style="background:none;border:none;font-size:14px;color:var(--text3);cursor:pointer;padding:4px;">🗑</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- ── TOP INCOME SOURCES ─────────────────────────────────────────────────── -->
  <?php if (!empty($topSources)): ?>
    <div class="section">
      <div class="section-header">
        <div class="section-title">📊 Top Sources (30d)</div>
      </div>
      <div class="stat-grid">
        <?php foreach (array_slice($topSources, 0, 4) as $src): ?>
          <div class="stat-card">
            <div class="sc-label"><?= htmlspecialchars($src['emoji']) ?> <?= htmlspecialchars($src['source_name']) ?></div>
            <div class="sc-val"><?= fmt((float)$src['total']) ?></div>
            <div class="sc-sub"><?= (int)$src['entries'] ?> entr<?= $src['entries'] == 1 ? 'y' : 'ies' ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <!-- ── SAVED HUSTLES ──────────────────────────────────────────────────────── -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">🔖 Saved Hustles</div>
      <a href="<?= APP_URL ?>/" class="section-link">Browse all →</a>
    </div>
    <?php if (empty($savedHustles)): ?>
      <div class="empty-state">
        <div class="es-icon">🔖</div>
        <div class="es-text">No saved hustles. <a href="<?= APP_URL ?>/">Explore hustles →</a></div>
      </div>
    <?php else: ?>
      <?php foreach ($savedHustles as $h): ?>
        <a class="hustle-row" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
          <div class="hustle-icon"><?= htmlspecialchars($h['emoji']) ?></div>
          <div class="hustle-info">
            <div class="hustle-name"><?= htmlspecialchars($h['name']) ?></div>
            <div class="hustle-income">
              <?= fmt((float)$h['income_min']) ?>–<?= fmt((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?>
              · <span class="badge <?= htmlspecialchars($diffClass[$h['difficulty']] ?? 'badge-green') ?>"><?= htmlspecialchars($diffLabel[$h['difficulty']] ?? '') ?></span>
            </div>
          </div>
          <div class="hustle-arrow">›</div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ── REFERRAL ───────────────────────────────────────────────────────────── -->
  <div class="section">
    <div class="section-header">
      <div class="section-title">🤝 Refer & Earn</div>
    </div>
    <div class="ref-card">
      <div class="ref-title">Invite friends, get Pro free 🎁</div>
      <div class="ref-sub">Refer <?= REFERRAL_NEEDED ?> friends and get <?= REFERRAL_REWARD_DAYS ?> days of Pro for free. You've referred <strong><?= $refCount ?></strong> so far.</div>
      <div class="ref-progress">
        <div class="ref-dots">
          <?php for ($i = 0; $i < REFERRAL_NEEDED; $i++): ?>
            <div class="ref-dot <?= $i < $refCount ? 'filled' : '' ?>"><?= $i < $refCount ? '✓' : ($i + 1) ?></div>
          <?php endfor; ?>
        </div>
        <div class="ref-label"><?= max(0, REFERRAL_NEEDED - $refCount) ?> more to unlock free Pro week</div>
      </div>
      <div class="ref-copy-row">
        <div class="ref-code-box" id="ref-code"><?= htmlspecialchars($user['ref_code']) ?></div>
        <button class="ref-copy-btn" onclick="copyRef()">Copy link</button>
      </div>
    </div>
  </div>

  <!-- ── PRO UPSELL (only for free users) ──────────────────────────────────── -->
  <?php if (!$isPro): ?>
    <div class="section" style="margin-bottom:8px;">
      <div class="pro-banner">
        <div class="pb-icon">👑</div>
        <div class="pb-text" style="flex:1;">
          <h3>Unlock Pro</h3>
          <p>Unlimited income history, advanced analytics, and priority support.</p>
        </div>
        <a href="<?= APP_URL ?>/upgrade" class="pb-btn">Upgrade</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- ACCOUNT INFO -->
  <div class="section" style="padding-bottom:8px;">
    <div class="section-header">
      <div class="section-title">👤 Account</div>
      <a href="<?= APP_URL ?>/auth/logout" class="section-link" style="color:var(--red);">Log out</a>
    </div>
    <div class="stat-grid">
      <div class="stat-card">
        <div class="sc-label">Name</div>
        <div class="sc-val" style="font-size:15px;"><?= htmlspecialchars($user['name']) ?></div>
        <div class="sc-sub"><?= htmlspecialchars($user['email']) ?></div>
      </div>
      <div class="stat-card">
        <div class="sc-label">Plan</div>
        <div class="sc-val" style="font-size:15px;color:<?= $isPro ? 'var(--gold)' : 'var(--text2)' ?>;">
          <?= $isPro ? '⭐ Pro' : 'Free' ?>
        </div>
        <div class="sc-sub">
          <?= $isPro ? 'Expires ' . date('d M Y', strtotime($user['pro_expires_at'])) : 'Limited access' ?>
        </div>
      </div>
    </div>
  </div>

</div><!-- /page -->

<!-- ── BOTTOM NAV ─────────────────────────────────────────────────────────── -->
<nav class="bottom-nav">
  <a href="<?= APP_URL ?>/" class="bnav-item">
    <div class="bnav-icon">🏠</div>
    <div>Home</div>
  </a>
  <a href="<?= APP_URL ?>/dashboard" class="bnav-item active">
    <div class="bnav-icon">📊</div>
    <div>Dashboard</div>
  </a>
  <a href="#" class="bnav-item" onclick="openLogModal(event)">
    <div class="bnav-icon" style="width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;margin-top:-8px;">+</div>
    <div>Log</div>
  </a>
  <a href="<?= APP_URL ?>/" class="bnav-item">
    <div class="bnav-icon">🔍</div>
    <div>Hustles</div>
  </a>
  <a href="<?= APP_URL ?>/upgrade" class="bnav-item">
    <div class="bnav-icon">👑</div>
    <div>Pro</div>
  </a>
</nav>

<!-- ── LOG INCOME MODAL ─────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="log-modal" onclick="closeModalOnBg(event,'log-modal')">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-title">💰 Log Income</div>
    <div class="field">
      <label>Amount (₦)</label>
      <input type="number" id="log-amount" placeholder="e.g. 15000" min="1" inputmode="numeric"/>
    </div>
    <div class="field">
      <label>Source</label>
      <input type="text" id="log-source" placeholder="e.g. Freelance Design, VTU, Catering…"/>
    </div>
    <div class="field">
      <label>Date</label>
      <input type="date" id="log-date" value="<?= date('Y-m-d') ?>"/>
    </div>
    <div class="field">
      <label>Note (optional)</label>
      <textarea id="log-note" placeholder="Any extra details…"></textarea>
    </div>
    <button class="modal-submit" onclick="submitIncome()">Save Income →</button>
  </div>
</div>

<!-- ── ADD GOAL MODAL ─────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="goal-modal" onclick="closeModalOnBg(event,'goal-modal')">
  <div class="modal goal-modal">
    <div class="modal-handle"></div>
    <div class="modal-title">🎯 New Goal</div>
    <div class="field">
      <label>Goal Title</label>
      <input type="text" id="goal-title" placeholder="e.g. Buy a laptop, Emergency fund…"/>
    </div>
    <div class="field">
      <label>Emoji</label>
      <input type="text" id="goal-emoji" placeholder="🎯" maxlength="2" value="🎯" style="max-width:80px;"/>
    </div>
    <div class="field">
      <label>Target Amount (₦)</label>
      <input type="number" id="goal-target" placeholder="e.g. 200000" min="1" inputmode="numeric"/>
    </div>
    <div class="field">
      <label>Deadline (optional)</label>
      <input type="date" id="goal-deadline"/>
    </div>
    <button class="modal-submit" onclick="submitGoal()">Create Goal →</button>
  </div>
</div>

<!-- ── ADD FUNDS MODAL ────────────────────────────────────────────────────── -->
<div class="modal-overlay" id="funds-modal" onclick="closeModalOnBg(event,'funds-modal')">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-title" id="funds-modal-title">Add Funds to Goal</div>
    <input type="hidden" id="funds-goal-id"/>
    <div class="field">
      <label>Amount to Add (₦)</label>
      <input type="number" id="funds-amount" placeholder="e.g. 5000" min="1" inputmode="numeric"/>
    </div>
    <button class="modal-submit" onclick="submitFunds()">Add Funds →</button>
  </div>
</div>

<!-- ── TOAST ──────────────────────────────────────────────────────────────── -->
<div class="toast" id="toast"></div>

<script>
const CSRF = <?= json_encode($csrf) ?>;

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, duration = 2500) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), duration);
}

// ── Modal helpers ─────────────────────────────────────────────────────────────
function openLogModal(e) { e && e.preventDefault(); document.getElementById('log-modal').classList.add('open'); }
function openGoalModal(e){ e && e.preventDefault(); document.getElementById('goal-modal').classList.add('open'); }
function closeModal(id)  { document.getElementById(id).classList.remove('open'); }
function closeModalOnBg(e, id) { if (e.target === document.getElementById(id)) closeModal(id); }

function addToGoal(id, title) {
  document.getElementById('funds-goal-id').value = id;
  document.getElementById('funds-modal-title').textContent = '+ Add Funds to: ' + title;
  document.getElementById('funds-amount').value = '';
  document.getElementById('funds-modal').classList.add('open');
}

// ── API helper ────────────────────────────────────────────────────────────────
async function api(method, url, body = null) {
  const opts = {
    method,
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    credentials: 'same-origin',
  };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  return res.json();
}

// ── Log Income ────────────────────────────────────────────────────────────────
async function submitIncome() {
  const amount = parseFloat(document.getElementById('log-amount').value);
  const source = document.getElementById('log-source').value.trim();
  const date   = document.getElementById('log-date').value;
  const note   = document.getElementById('log-note').value.trim();

  if (!amount || amount <= 0) { showToast('⚠️ Enter a valid amount'); return; }
  if (!source) { showToast('⚠️ Enter a source name'); return; }

  const r = await api('POST', '/api/income', {
    amount, custom_name: source, logged_at: date, note: note || null
  });

  if (r.ok) {
    showToast('✅ Income logged!');
    closeModal('log-modal');
    document.getElementById('log-amount').value = '';
    document.getElementById('log-source').value = '';
    document.getElementById('log-note').value   = '';
    // Inject new row at top of list
    const list = document.getElementById('income-list');
    if (list) {
      const fmtAmt = amount >= 1000000 ? '₦' + (amount/1000000).toFixed(1) + 'M'
                   : amount >= 1000    ? '₦' + (amount/1000).toFixed(1) + 'k'
                   :                    '₦' + amount.toLocaleString();
      const row = document.createElement('div');
      row.className = 'income-row';
      row.id = 'income-' + (r.data?.id || Date.now());
      row.innerHTML = `
        <div class="income-emoji">💰</div>
        <div class="income-info">
          <div class="income-name">${source}</div>
          <div class="income-date">${new Date(date).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="income-amount">+${fmtAmt}</div>
          <button onclick="deleteEntry(${r.data?.id})"
            style="background:none;border:none;font-size:14px;color:var(--text3);cursor:pointer;padding:4px;">🗑</button>
        </div>`;
      list.prepend(row);
    }
  } else {
    showToast('❌ ' + (r.error || 'Failed to log income'));
  }
}

// ── Delete Income ─────────────────────────────────────────────────────────────
async function deleteEntry(id) {
  if (!confirm('Delete this income entry?')) return;
  const r = await api('DELETE', '/api/income?id=' + id);
  if (r.ok) {
    const el = document.getElementById('income-' + id);
    if (el) el.remove();
    showToast('🗑 Entry deleted');
  } else {
    showToast('❌ Could not delete');
  }
}

// ── Add Goal ──────────────────────────────────────────────────────────────────
async function submitGoal() {
  const title    = document.getElementById('goal-title').value.trim();
  const emoji    = document.getElementById('goal-emoji').value.trim() || '🎯';
  const target   = parseFloat(document.getElementById('goal-target').value);
  const deadline = document.getElementById('goal-deadline').value || null;

  if (!title)             { showToast('⚠️ Enter a goal title'); return; }
  if (!target || target <= 0) { showToast('⚠️ Enter a valid target amount'); return; }

  const r = await api('POST', '/api/goals', { title, emoji, target_amount: target, deadline });

  if (r.ok) {
    showToast('✅ Goal created!');
    closeModal('goal-modal');
    // Reload to show new goal
    location.reload();
  } else {
    showToast('❌ ' + (r.error || 'Failed to create goal'));
  }
}

// ── Mark Goal Done ────────────────────────────────────────────────────────────
async function markGoalDone(id) {
  if (!confirm('Mark this goal as completed? 🎉')) return;
  const r = await api('PUT', '/api/goals?id=' + id, { is_completed: 1 });
  if (r.ok) {
    showToast('🎉 Goal completed!');
    location.reload();
  } else {
    showToast('❌ ' + (r.error || 'Failed'));
  }
}

// ── Delete Goal ───────────────────────────────────────────────────────────────
async function deleteGoal(id) {
  if (!confirm('Delete this goal?')) return;
  const r = await api('DELETE', '/api/goals?id=' + id);
  if (r.ok) {
    const el = document.getElementById('goal-' + id);
    if (el) el.remove();
    showToast('🗑 Goal deleted');
  } else {
    showToast('❌ ' + (r.error || 'Failed'));
  }
}

// ── Add Funds to Goal ─────────────────────────────────────────────────────────
async function submitFunds() {
  const id     = parseInt(document.getElementById('funds-goal-id').value);
  const amount = parseFloat(document.getElementById('funds-amount').value);
  if (!amount || amount <= 0) { showToast('⚠️ Enter a valid amount'); return; }

  // Get current goal data first
  const goals = await api('GET', '/api/goals');
  const goal  = goals.data?.find(g => g.id === id);
  if (!goal) { showToast('❌ Goal not found'); return; }

  const newAmount = parseFloat(goal.current_amount) + amount;
  const isComplete = newAmount >= parseFloat(goal.target_amount) ? 1 : 0;

  const r = await api('PUT', '/api/goals?id=' + id, {
    current_amount: newAmount,
    is_completed: isComplete,
  });

  if (r.ok) {
    showToast(isComplete ? '🎉 Goal reached!' : '✅ Funds added!');
    closeModal('funds-modal');
    location.reload();
  } else {
    showToast('❌ ' + (r.error || 'Failed'));
  }
}

// ── Copy Referral Link ────────────────────────────────────────────────────────
function copyRef() {
  const url = <?= json_encode($shareUrl) ?>;
  if (navigator.clipboard) {
    navigator.clipboard.writeText(url).then(() => showToast('🔗 Link copied!'));
  } else {
    const ta = document.createElement('textarea');
    ta.value = url;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('🔗 Link copied!');
  }
}
</script>
</body>
</html>
