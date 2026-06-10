<?php
/**
 * HustleKingdom — User Dashboard (Revamped)
 * Route: GET /dashboard
 * Requires auth. Full-featured: income, goals, saved hustles, categories, tools, referral, streak.
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
// 7-day sparkline
$income7 = DB::query(
    'SELECT DATE(logged_at) as day_date, DATE_FORMAT(MIN(logged_at), "%a") as day_label, SUM(amount) as day_total
     FROM income_log
     WHERE user_id = :uid AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(logged_at) ORDER BY DATE(logged_at) ASC',
    [':uid' => $user['id']]
);

// Rolling 30-day total
$total30 = (float)(DB::one(
    'SELECT COALESCE(SUM(amount),0) as t FROM income_log
     WHERE user_id = :uid AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
    [':uid' => $user['id']]
)['t'] ?? 0);

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

// ── Referral (only subscribed referred users count) ───────────────────────────
$refCount = (int)(DB::one(
    'SELECT COUNT(*) as cnt FROM referrals r
     JOIN users u ON u.id = r.referred_id
     WHERE r.referrer_id = :id AND u.is_pro = 1 AND (u.pro_expires_at IS NULL OR u.pro_expires_at > NOW())',
    [':id' => $user['id']]
)['cnt'] ?? 0);
$refEarnings = $refCount * 500; // ₦500 per subscribed referral
$shareUrl = APP_URL . '/auth/register?ref=' . urlencode($user['ref_code']);

// ── All hustles for browsing (limited) ────────────────────────────────────────
$allHustles = DB::query(
    'SELECT id, name, slug, emoji, category, income_min, income_max, income_period, difficulty
     FROM hustles WHERE is_active = 1 ORDER BY RAND() LIMIT 50',
    []
);

// ── Helpers ───────────────────────────────────────────────────────────────────
$diffLabel = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];
$diffClass = ['beginner' => 'tg', 'intermediate' => 'to', 'advanced' => 'tr'];

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
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#ffffff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;--border2:#d8d8d0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--text4:#c8c8c0;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;--green-glow:rgba(22,160,90,0.15);
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --orange:#d45820;--orange-light:#fdf0eb;
  --red:#cc3333;--red-light:#fdf0f0;
  --purple:#6b35c8;--purple-light:#f3eefe;
  --teal:#0e9488;--teal-light:#ebfaf8;
  --r:16px;--rs:10px;--rx:22px;
  --nav:58px;--bot:64px;
  --sh:0 2px 16px rgba(0,0,0,0.06);--sh2:0 8px 40px rgba(0,0,0,0.10);
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;padding-bottom:calc(var(--bot) + 16px);}
::-webkit-scrollbar{width:0;height:0;}

/* ── TOP NAV ── */
.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;text-decoration:none;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;cursor:pointer;border:none;background:transparent;color:var(--text3);text-decoration:none;transition:.2s;}
.bnav.active{color:var(--green);}
.bni{font-size:18px;}
.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;cursor:pointer;border:none;background:transparent;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}

/* ── HERO ── */
.hero{background:linear-gradient(140deg,#0d6e3c 0%,#16a05a 55%,#20c870 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.06);}
.hero::after{content:'';position:absolute;left:-20px;bottom:-30px;width:120px;height:120px;border-radius:50%;background:rgba(0,0,0,0.06);}
.hero-greeting{font-size:12.5px;color:rgba(255,255,255,.7);font-weight:600;margin-bottom:4px;}
.hero-name{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;}
.hero-name span{color:#a3f0c8;}
.streak-pill{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:20px;padding:4px 10px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:14px;cursor:pointer;}
.hero-stats{display:flex;gap:8px;position:relative;z-index:1;}
.hstat{flex:1;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:10px;text-align:center;}
.hstat-n{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;color:#fff;}
.hstat-l{font-size:10px;color:rgba(255,255,255,.65);margin-top:2px;}

/* ── SPARKLINE ── */
.sparkline-wrap{display:flex;align-items:flex-end;gap:4px;height:28px;margin:10px 0 14px;position:relative;z-index:1;}
.spark-bar{flex:1;background:rgba(255,255,255,.3);border-radius:3px 3px 0 0;min-height:3px;}
.change-up{color:#7ffab0;font-size:11px;font-weight:700;}
.change-down{color:#ffb3b3;font-size:11px;font-weight:700;}

/* ── SHARED ── */
.sec-head{display:flex;align-items:center;justify-content:space-between;padding:0 16px;margin:20px 0 12px;}
.sec-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;display:flex;align-items:center;gap:7px;}
.sec-all{font-size:12px;font-weight:700;color:var(--green);background:none;border:none;cursor:pointer;text-decoration:none;}
.page-inner{padding:0;}

/* ── QUICK ACTIONS ── */
.qa-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:16px 16px 0;}
.qa-btn{display:flex;flex-direction:column;align-items:center;gap:6px;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:13px 6px;text-decoration:none;color:var(--text);cursor:pointer;transition:.15s;touch-action:manipulation;}
.qa-btn:active{background:var(--surface2);}
.qa-icon{font-size:20px;line-height:1;}
.qa-label{font-size:10px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;text-align:center;color:var(--text2);}

/* ── INCOME CARD ── */
.income-month-card{margin:16px 16px 0;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:16px;display:flex;gap:14px;align-items:center;}
.imc-amount{font-family:'Bricolage Grotesque',sans-serif;font-size:28px;font-weight:800;color:var(--green3);line-height:1;}
.imc-label{font-size:11px;color:var(--text3);margin-bottom:3px;font-weight:600;}
.imc-change{font-size:11.5px;font-weight:700;}
.imc-right{flex:1;min-width:0;}

/* ── STAT GRID ── */
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;padding:0 16px;}
.stat-card{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:14px;}
.sc-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;}
.sc-val{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;}
.sc-sub{font-size:11px;color:var(--text3);margin-top:2px;}

/* ── GOAL CARDS ── */
.goal-card{border:1.5px solid var(--border);border-radius:var(--r);padding:14px;margin-bottom:10px;}
.goal-card.done{opacity:.6;background:var(--surface);}
.goal-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;}
.goal-title-row{display:flex;align-items:center;gap:8px;}
.goal-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:13.5px;line-height:1.2;}
.goal-deadline{font-size:11px;color:var(--text3);margin-top:1px;}
.goal-pct{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:var(--green);}
.goal-pct.done{color:var(--text3);}
.goal-bar-bg{height:6px;background:var(--border);border-radius:4px;overflow:hidden;}
.goal-bar-fill{height:100%;background:var(--green);border-radius:4px;}
.goal-bar-fill.done{background:var(--text3);}
.goal-amounts{display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--text3);}
.goal-amounts strong{color:var(--text2);font-weight:700;}
.goal-actions{display:flex;gap:7px;margin-top:10px;}
.goal-actions button{flex:1;border-radius:10px;padding:7px 8px;font-size:11.5px;font-weight:700;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;border:none;}

/* ── INCOME LIST ── */
.income-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);}
.income-row:last-child{border-bottom:none;}
.income-emoji{width:36px;height:36px;background:var(--green-light);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
.income-info{flex:1;min-width:0;}
.income-name{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.income-date{font-size:11px;color:var(--text3);}
.income-amount{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--green);}

/* ── HUSTLE CARDS ── */
.hustle-scroll{display:flex;gap:10px;overflow-x:auto;padding:0 16px 8px;scrollbar-width:none;-webkit-overflow-scrolling:touch;}
.hustle-card{flex-shrink:0;width:160px;background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:13px;cursor:pointer;text-decoration:none;color:var(--text);transition:.15s;touch-action:manipulation;}
.hustle-card:active{background:var(--surface);}
.hc-emoji{font-size:26px;margin-bottom:7px;display:block;}
.hc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:12.5px;font-weight:800;margin-bottom:4px;line-height:1.3;}
.hc-income{font-size:11px;color:var(--green3);font-weight:700;margin-bottom:6px;}
.hc-tags{display:flex;gap:5px;flex-wrap:wrap;}

/* ── TAGS ── */
.tag{font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.tb{background:var(--blue-light);color:var(--blue);}
.to{background:var(--orange-light);color:var(--orange);}
.tp{background:var(--purple-light);color:var(--purple);}
.tgd{background:var(--gold-light);color:var(--gold2);}
.tt{background:var(--teal-light);color:var(--teal);}
.tr{background:var(--red-light);color:var(--red);}



/* ── TOOLS SECTION ── */
.tools-scroll{display:flex;gap:10px;overflow-x:auto;padding:0 16px 8px;scrollbar-width:none;-webkit-overflow-scrolling:touch;}
.tool-card{flex-shrink:0;width:130px;background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:13px 11px;cursor:pointer;text-decoration:none;transition:.15s;touch-action:manipulation;}
.tool-card:active{background:var(--surface);}
.tc-emoji{font-size:24px;margin-bottom:6px;display:block;}
.tc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--text);margin-bottom:3px;}
.tc-badge{font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}

/* ── PRO BANNERS ── */
.pro-banner{border-radius:var(--r);padding:16px;display:flex;align-items:center;gap:13px;cursor:pointer;touch-action:manipulation;}
.pro-banner h3{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:14px;margin-bottom:3px;}
.pro-banner p{font-size:11.5px;opacity:.85;line-height:1.5;}
.pb-btn{font-size:11px;font-weight:800;padding:7px 12px;border-radius:10px;text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;flex-shrink:0;border:none;cursor:pointer;}

/* ── REFERRAL CARD ── */
.ref-card{background:var(--gold-light);border:1.5px solid #e8d080;border-radius:var(--r);padding:16px;}
.ref-title{font-family:'Bricolage Grotesque',sans-serif;font-size:14.5px;font-weight:800;margin-bottom:4px;}
.ref-sub{font-size:12px;color:var(--text2);line-height:1.55;margin-bottom:12px;}
.ref-dots{display:flex;gap:6px;margin-bottom:12px;}
.ref-dot{width:26px;height:26px;border-radius:50%;border:2px solid #e8d080;display:flex;align-items:center;justify-content:center;font-size:12px;}
.ref-dot.filled{background:var(--gold);border-color:var(--gold);color:#fff;}
.ref-copy-row{display:flex;gap:8px;}
.ref-code-box{flex:1;background:#fff;border:1.5px solid #e8d080;border-radius:10px;padding:10px 12px;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--text);}
.ref-copy-btn{background:var(--gold);color:#fff;border:none;border-radius:10px;padding:10px 14px;font-size:12px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:24px 16px;color:var(--text3);font-size:13px;}
.empty-state .ei{font-size:32px;margin-bottom:8px;}
.empty-state a{color:var(--green);font-weight:700;text-decoration:none;}

/* ── ACCOUNT ── */
.account-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;padding:0 16px;}
.acc-card{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:13px;}
.acc-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;}
.acc-val{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;line-height:1.2;}
.acc-sub{font-size:11px;color:var(--text3);margin-top:2px;}

/* ── TOAST ── */
.toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#0d0d0c;color:#fff;font-size:13px;font-weight:600;padding:10px 18px;border-radius:30px;z-index:400;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap;}
.toast.show{opacity:1;}

/* ── DIVIDER ── */
.divider{height:1px;background:var(--border);margin:0 16px;}
.section-gap{height:24px;}
</style>
</head>
<body>

<!-- ── TOP NAV ── -->
<nav class="topnav">
  <a href="<?= APP_URL ?>/" class="nav-brand">
    <div class="nav-logo-box">👑</div>
    Hustle<em>Kingdom</em>
  </a>
  <div class="nav-r">
    <?php if ($isPro): ?>
      <span class="pro-chip">⭐ PRO</span>
    <?php else: ?>
      <a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/auth/logout" class="nav-avatar" title="Log out"><?= htmlspecialchars($user['avatar_emoji']) ?></a>
  </div>
</nav>

<!-- ═══════════════════════════════════
     HERO
═══════════════════════════════════ -->
<div class="hero">
  <div class="hero-greeting"><?= htmlspecialchars($greeting) ?></div>
  <div class="hero-name"><?= htmlspecialchars($firstName) ?> <span><?= htmlspecialchars($user['avatar_emoji']) ?></span></div>

  <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;flex-wrap:wrap;">
    <span style="font-size:11px;color:rgba(255,255,255,.65);"><?= htmlspecialchars($user['email']) ?></span>
    <?php if ($isPro): ?>
      <span style="background:rgba(200,150,10,.25);border:1px solid rgba(200,150,10,.5);color:#ffe27a;font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;">⭐ PRO</span>
    <?php else: ?>
      <a href="<?= APP_URL ?>/upgrade" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;font-size:10px;font-weight:800;padding:3px 8px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;">Upgrade →</a>
    <?php endif; ?>
    <a href="<?= APP_URL ?>/auth/logout" style="background:rgba(255,80,80,.2);border:1px solid rgba(255,100,100,.35);color:#ffb3b3;font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;">Log out</a>
  </div>

  <?php if ($user['streak_count'] > 0): ?>
    <div class="streak-pill">🔥 <?= (int)$user['streak_count'] ?> day streak — keep it up!</div>
  <?php endif; ?>

  <?php if (!empty($income7)): ?>
    <?php $maxDay = max(array_column($income7, 'day_total')) ?: 1; ?>
    <div class="sparkline-wrap">
      <?php foreach ($income7 as $day): ?>
        <?php $h = max(3, round(($day['day_total'] / $maxDay) * 28)); ?>
        <div class="spark-bar" style="height:<?= $h ?>px;" title="<?= htmlspecialchars($day['day_label']) ?>: <?= fmt((float)$day['day_total']) ?>"></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="height:14px;"></div>
  <?php endif; ?>

  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-n"><?= fmt($thisMonth) ?></div>
      <div class="hstat-l">This month</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?= fmt($total30) ?></div>
      <div class="hstat-l">Last 30 days</div>
    </div>
    <div class="hstat">
      <div class="hstat-n">₦<?= $refEarnings > 0 ? number_format($refEarnings) : '0' ?></div>
      <div class="hstat-l">Ref Earnings</div>
    </div>
  </div>

  <?php if ($monthChange !== null): ?>
    <div style="margin-top:10px;font-size:11.5px;position:relative;z-index:1;">
      <?php if ($monthChange >= 0): ?>
        <span class="change-up">↑ <?= abs($monthChange) ?>% vs last month</span>
      <?php else: ?>
        <span class="change-down">↓ <?= abs($monthChange) ?>% vs last month</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════
     QUICK ACTIONS
═══════════════════════════════════ -->
<div class="qa-grid">
  <a href="<?= APP_URL ?>/hustles" class="qa-btn">
    <div class="qa-icon">🔥</div>
    <div class="qa-label">Browse Hustles</div>
  </a>
  <a href="<?= APP_URL ?>/hustles/saved" class="qa-btn">
    <div class="qa-icon">🔖</div>
    <div class="qa-label">Saved Hustles</div>
  </a>
  <a href="<?= APP_URL ?>/execute" class="qa-btn">
    <div class="qa-icon">⚡</div>
    <div class="qa-label">Executed Hustles</div>
  </a>
  <a href="<?= APP_URL ?>/socialmoney" class="qa-btn">
    <div class="qa-icon">📱</div>
    <div class="qa-label">Platforms</div>
  </a>
  <a href="<?= APP_URL ?>/glossary" class="qa-btn">
    <div class="qa-icon">📖</div>
    <div class="qa-label">Glossary</div>
  </a>
  <a href="<?= APP_URL ?>/tools" class="qa-btn">
    <div class="qa-icon">🛠</div>
    <div class="qa-label">Tools</div>
  </a>
  <a href="<?= APP_URL ?>/ai" class="qa-btn">
    <div class="qa-icon">🤖</div>
    <div class="qa-label">AI Advisor</div>
  </a>
  <a href="<?= APP_URL ?>/upgrade" class="qa-btn" style="<?= $isPro ? 'opacity:.5;pointer-events:none;' : '' ?>">
    <div class="qa-icon">⭐</div>
    <div class="qa-label"><?= $isPro ? 'Pro Active' : 'Go Pro' ?></div>
  </a>
</div>

<!-- ═══════════════════════════════════
     DISCOVER HUSTLES — ONLINE
═══════════════════════════════════ -->
<?php
// Separate allHustles into online and offline by category
$onlineCategories  = ['digital','social','ai','creative','education','finance'];
$offlineCategories = ['agro','trade','trades','health','realestate','transport'];
$onlineHustles  = array_values(array_filter($allHustles, fn($h) => in_array($h['category'], $onlineCategories)));
$offlineHustles = array_values(array_filter($allHustles, fn($h) => in_array($h['category'], $offlineCategories)));
?>
<div class="sec-head">
  <div class="sec-title">🔥 Discover Hustles</div>
  <a href="<?= APP_URL ?>/hustles" class="sec-all">See all →</a>
</div>

<!-- Online Hustles sub-label -->
<div style="padding:0 16px 8px;display:flex;align-items:center;gap:7px;">
  <span style="font-size:11px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;color:var(--blue);background:var(--blue-light);border:1px solid #b5d4f4;border-radius:20px;padding:4px 10px;">🌐 Online</span>
</div>
<div class="hustle-scroll">
  <?php foreach (array_slice($onlineHustles, 0, 10) as $h): ?>
    <a class="hustle-card" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
      <span class="hc-emoji"><?= htmlspecialchars($h['emoji']) ?></span>
      <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
      <div class="hc-income"><?= fmt((float)$h['income_min']) ?>–<?= fmt((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
      <div class="hc-tags">
        <span class="tag <?= htmlspecialchars($diffClass[$h['difficulty']] ?? 'tg') ?>"><?= htmlspecialchars($diffLabel[$h['difficulty']] ?? '') ?></span>
      </div>
    </a>
  <?php endforeach; ?>
  <?php if (empty($onlineHustles)): ?>
    <?php foreach (array_slice($allHustles, 0, 8) as $h): ?>
      <a class="hustle-card" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
        <span class="hc-emoji"><?= htmlspecialchars($h['emoji']) ?></span>
        <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
        <div class="hc-income"><?= fmt((float)$h['income_min']) ?>–<?= fmt((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
        <div class="hc-tags">
          <span class="tag <?= htmlspecialchars($diffClass[$h['difficulty']] ?? 'tg') ?>"><?= htmlspecialchars($diffLabel[$h['difficulty']] ?? '') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Offline Hustles sub-label -->
<div style="padding:8px 16px 8px;display:flex;align-items:center;gap:7px;">
  <span style="font-size:11px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;color:var(--orange);background:var(--orange-light);border:1px solid #f5c4b3;border-radius:20px;padding:4px 10px;">🏙️ Offline</span>
</div>
<div class="hustle-scroll">
  <?php foreach (array_slice($offlineHustles, 0, 10) as $h): ?>
    <a class="hustle-card" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
      <span class="hc-emoji"><?= htmlspecialchars($h['emoji']) ?></span>
      <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
      <div class="hc-income"><?= fmt((float)$h['income_min']) ?>–<?= fmt((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
      <div class="hc-tags">
        <span class="tag <?= htmlspecialchars($diffClass[$h['difficulty']] ?? 'tg') ?>"><?= htmlspecialchars($diffLabel[$h['difficulty']] ?? '') ?></span>
      </div>
    </a>
  <?php endforeach; ?>
  <?php if (empty($offlineHustles)): ?>
    <?php foreach (array_slice($allHustles, 8, 8) as $h): ?>
      <a class="hustle-card" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
        <span class="hc-emoji"><?= htmlspecialchars($h['emoji']) ?></span>
        <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
        <div class="hc-income"><?= fmt((float)$h['income_min']) ?>–<?= fmt((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
        <div class="hc-tags">
          <span class="tag <?= htmlspecialchars($diffClass[$h['difficulty']] ?? 'tg') ?>"><?= htmlspecialchars($diffLabel[$h['difficulty']] ?? '') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════
     SOCIAL MONEY
═══════════════════════════════════ -->
<?php
// Social Money platforms data (mirrored from socialmoney.php)
$dashSocialPlatforms = [
  ['icon'=>'🎵','name'=>'TikTok',      'earn'=>'₦1k–₦50k/day',    'id'=>'tiktok',    'bg'=>'#e8f0fe','border'=>'#c5d8fc'],
  ['icon'=>'📸','name'=>'Instagram',   'earn'=>'₦2k–₦100k/day',   'id'=>'instagram', 'bg'=>'#fce8f3','border'=>'#f5c5e3'],
  ['icon'=>'▶️','name'=>'YouTube',     'earn'=>'$100–$10k/month',  'id'=>'youtube',   'bg'=>'#fce8e8','border'=>'#f5c5c5'],
  ['icon'=>'💬','name'=>'WhatsApp',    'earn'=>'₦500–₦20k/day',   'id'=>'whatsapp',  'bg'=>'#e8fce8','border'=>'#c5f0c5'],
  ['icon'=>'✈️','name'=>'Telegram',    'earn'=>'₦1k–₦50k/day',    'id'=>'telegram',  'bg'=>'#e8f4fc','border'=>'#c5e4f5'],
];
?>
<div class="sec-head" style="margin-top:8px;">
  <div class="sec-title">📱 Social Money</div>
  <a href="<?= APP_URL ?>/socialmoney" class="sec-all">See all →</a>
</div>
<div class="hustle-scroll">
  <?php foreach ($dashSocialPlatforms as $sp): ?>
    <a class="hustle-card" href="<?= APP_URL ?>/socialmoney?p=<?= $sp['id'] ?>" style="background:<?= htmlspecialchars($sp['bg']) ?>;border-color:<?= htmlspecialchars($sp['border']) ?>;">
      <span class="hc-emoji" style="font-size:28px;"><?= $sp['icon'] ?></span>
      <div class="hc-name"><?= htmlspecialchars($sp['name']) ?></div>
      <div class="hc-income"><?= htmlspecialchars($sp['earn']) ?></div>
      <div class="hc-tags">
        <span class="tag tb">Social</span>
      </div>
    </a>
  <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════
     OFFLINE MONEY-MAKING TOOLS
═══════════════════════════════════ -->
<?php
// Offline tools data (mirrored from tools.php)
$dashOfflineTools = [
  ['emoji'=>'🚗','name'=>'Ride-Hailing Car','earn'=>'₦15k–₦40k/day','badge'=>'Ride-Hailing'],
  ['emoji'=>'⚡','name'=>'Industrial Generator','earn'=>'₦20k–₦80k/day','badge'=>'Power'],
  ['emoji'=>'📸','name'=>'Professional Camera','earn'=>'₦20k–₦150k/event','badge'=>'Photography'],
  ['emoji'=>'🎤','name'=>'PA Sound System','earn'=>'₦30k–₦200k/event','badge'=>'Events'],
  ['emoji'=>'🏠','name'=>'Short-Let Apartment','earn'=>'₦20k–₦80k/night','badge'=>'Real Estate'],
];
?>
<div class="sec-head" style="margin-top:8px;">
  <div class="sec-title">🏗️ Offline Money-Making Tools</div>
  <a href="<?= APP_URL ?>/tools" class="sec-all">See all →</a>
</div>
<div class="tools-scroll">
  <?php foreach ($dashOfflineTools as $t): ?>
    <a class="tool-card" href="<?= APP_URL ?>/tools">
      <span class="tc-emoji"><?= $t['emoji'] ?></span>
      <div class="tc-name"><?= htmlspecialchars($t['name']) ?></div>
      <div style="font-size:11px;font-weight:700;color:var(--green3);margin:3px 0 5px;"><?= htmlspecialchars($t['earn']) ?></div>
      <span class="tc-badge to"><?= htmlspecialchars($t['badge']) ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- ═══════════════════════════════════
     PRO UPSELL (free users only)
═══════════════════════════════════ -->
<?php if (!$isPro): ?>
<div style="padding:0 16px;margin-top:6px;">
  <!-- AI Advisor promo -->
  <a href="<?= APP_URL ?>/upgrade" style="display:block;background:linear-gradient(140deg,#060d1f 0%,#0f2040 55%,#0a1628 100%);border-radius:var(--r);padding:16px;text-decoration:none;margin-bottom:10px;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-20px;top:-20px;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(99,179,237,0.15),transparent);"></div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:44px;height:44px;border-radius:12px;background:rgba(99,179,237,0.15);border:1px solid rgba(99,179,237,0.3);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">✨</div>
      <div style="flex:1;">
        <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;color:#fff;margin-bottom:3px;">Unlimited AI Hustle Advisor</div>
        <div style="font-size:11.5px;color:rgba(255,255,255,.5);line-height:1.5;">Free users get 3 questions. Pro: unlimited personalized hustle plans anytime.</div>
      </div>
    </div>
    <div style="margin-top:12px;background:linear-gradient(135deg,#c8960a,#f0b820);border-radius:10px;padding:10px 14px;text-align:center;">
      <span style="font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:#fff;">👑 Unlock Pro — ₦2,500/month</span>
    </div>
  </a>

  <!-- Pro features grid -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:10px;">
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#fdf6e3,#fffdf5);border:1.5px solid #e8d080;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">🔒</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--gold2);margin-bottom:3px;">30 Exclusive Hustles</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Premium high-income ideas for Pro members only.</div>
    </a>
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#eeedfe,#f5f0ff);border:1.5px solid #c4a8f4;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">🗺️</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--purple);margin-bottom:3px;">Full Roadmaps</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Step-by-step plans with tools & income milestones.</div>
    </a>
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#ebf7f1,#f0fdf6);border:1.5px solid #b2e0c8;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">📊</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--green3);margin-bottom:3px;">Income Analytics</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Charts, top earners, monthly breakdown & CSV export.</div>
    </a>
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#e6f1fb,#edf5ff);border:1.5px solid #b5d4f4;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">🌍</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--blue);margin-bottom:3px;">Country Hustle Maps</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Curated hustles for 50+ countries worldwide.</div>
    </a>
  </div>
</div>
<?php endif; ?>



<!-- ═══════════════════════════════════
     REFERRAL
═══════════════════════════════════ -->
<div class="sec-head">
  <div class="sec-title">🤝 Refer & Earn</div>
</div>
<div style="padding:0 16px;">
  <div class="ref-card">
    <div class="ref-title">Earn ₦500 per referral 💸</div>
    <div class="ref-sub">You earn <strong>₦500</strong> for every friend who signs up with your link <em>and subscribes to Pro</em>. Free sign-ups don't count — only paying subscribers.</div>

    <div style="display:flex;gap:10px;margin-bottom:14px;">
      <div style="flex:1;background:#fff;border:1.5px solid #e8d080;border-radius:12px;padding:12px;text-align:center;">
        <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;color:var(--gold2);"><?= $refCount ?></div>
        <div style="font-size:10.5px;color:var(--text3);font-weight:600;margin-top:2px;">Paid Referrals</div>
      </div>
      <div style="flex:1;background:#fff;border:1.5px solid #e8d080;border-radius:12px;padding:12px;text-align:center;">
        <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;color:var(--green3);">₦<?= number_format($refEarnings) ?></div>
        <div style="font-size:10.5px;color:var(--text3);font-weight:600;margin-top:2px;">Total Earned</div>
      </div>
      <div style="flex:1;background:#fff;border:1.5px solid #e8d080;border-radius:12px;padding:12px;text-align:center;">
        <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;color:var(--blue);">₦500</div>
        <div style="font-size:10.5px;color:var(--text3);font-weight:600;margin-top:2px;">Per Subscriber</div>
      </div>
    </div>

    <div style="background:#fffbe8;border:1px solid #f0dc80;border-radius:10px;padding:9px 12px;font-size:11px;color:var(--text2);margin-bottom:12px;line-height:1.55;">
      ⚠️ Only referred users who <strong>subscribe to Pro</strong> count toward your earnings. Free accounts do not qualify.
    </div>

    <div class="ref-copy-row">
      <div class="ref-code-box" id="ref-code"><?= htmlspecialchars($user['ref_code']) ?></div>
      <button class="ref-copy-btn" onclick="copyRef()">Copy link</button>
    </div>
  </div>
</div>

<div style="height:20px;"></div>

<!-- ═══════════════════════════════════
     BOTTOM NAV
═══════════════════════════════════ -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav">
    <div class="bni">🏠</div>
    <div class="bnl">Home</div>
  </a>
  <a href="<?= APP_URL ?>/hustles" class="bnav">
    <div class="bni">💡</div>
    <div class="bnl">Discover</div>
  </a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav">
    <div class="bni">📍</div>
    <div class="bnl">Location</div>
  </a>
  <a href="<?= APP_URL ?>/ai" class="bnav active">
    <div class="bni">🤖</div>
    <div class="bnl">AI</div>
  </a>
</nav>







<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<script>
const CSRF = <?= json_encode($csrf) ?>;

function showToast(msg, ms = 2500) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), ms);
}

function copyRef() {
  const url = <?= json_encode($shareUrl) ?>;
  if (navigator.clipboard) { navigator.clipboard.writeText(url).then(() => showToast('🔗 Link copied!')); }
  else {
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
