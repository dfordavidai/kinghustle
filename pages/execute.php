<?php
/**
 * HustleKingdom — Execute: Execution Hub
 * Route: GET /execute
 * Requires auth. Matches video "Execution Hub" design exactly.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();

// ── All active hustles (the Execute hub shows ALL hustles, not just saved) ───
$search  = trim($_GET['q']      ?? '');
$sort    = $_GET['sort']        ?? 'default';
$efilter = $_GET['efilter']     ?? 'all';    // all | progress | zero-capital | beginner
$view    = $_GET['view']        ?? 'list';   // list | grid

$where  = ['is_active = 1'];
$params = [];

if ($search) {
    $where[] = '(name LIKE :q OR description LIKE :q2)';
    $params[':q']  = "%$search%";
    $params[':q2'] = "%$search%";
}
if ($efilter === 'zero-capital') { $where[] = 'capital_needed = 0'; }
if ($efilter === 'beginner')     { $where[] = "difficulty = 'beginner'"; }

$orderBy = match($sort) {
    'income-high' => 'income_max DESC',
    'income-low'  => 'income_min ASC',
    'easiest'     => "FIELD(difficulty,'beginner','intermediate','advanced')",
    'fastest'     => 'capital_needed ASC',
    default       => 'is_featured DESC, save_count DESC',
};

$whereStr = implode(' AND ', $where);

$hustles = [];
try {
    $hustles = DB::query(
        "SELECT id, name, slug, emoji, category, income_min, income_max, income_period,
                difficulty, capital_needed, description, steps, skills_needed, is_featured
         FROM hustles WHERE $whereStr ORDER BY $orderBy LIMIT 50",
        $params
    );
} catch (\Exception $e) { $hustles = []; }

// ── Execution progress ────────────────────────────────────────────────────────
$progress = [];
try {
    $rawP = DB::one('SELECT meta_value FROM user_meta WHERE user_id=:uid AND meta_key="exec_progress"', [':uid'=>$user['id']]);
    $progress = $rawP ? (json_decode($rawP['meta_value'], true) ?: []) : [];
} catch (\Exception $e) {}

// ── Saved hustles (for "In Progress" filter) ──────────────────────────────────
$savedSlugs = [];
try {
    $rows = DB::query('SELECT h.slug FROM saved_hustles sh JOIN hustles h ON h.id=sh.hustle_id WHERE sh.user_id=:uid', [':uid'=>$user['id']]);
    $savedSlugs = array_column($rows, 'slug');
} catch (\Exception $e) {}

if ($efilter === 'progress') {
    $hustles = array_filter($hustles, fn($h) => in_array($h['slug'], $savedSlugs));
}

// ── Stats ─────────────────────────────────────────────────────────────────────
$totalHustles   = count($hustles);
$withRoadmap    = 0;
foreach ($hustles as $h) {
    $steps = json_decode($h['steps'] ?? '[]', true);
    if (!empty($steps)) $withRoadmap++;
}

// Income logged today
$loggedToday = 0;
try {
    $lt = DB::one('SELECT COUNT(*) as cnt FROM income_log WHERE user_id=:uid AND DATE(logged_at)=CURDATE()', [':uid'=>$user['id']]);
    $loggedToday = (int)($lt['cnt'] ?? 0);
} catch (\Exception $e) {}

function fmtE(float $n): string {
    if ($n >= 1_000_000) return '₦' . number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return '₦' . number_format($n / 1_000, 0) . 'k';
    return '₦' . number_format($n, 0);
}
function fmtDay(float $n): string {
    // Convert monthly to daily approximation
    $daily = $n / 30;
    if ($daily >= 1_000_000) return '₦' . number_format($daily / 1_000_000, 1) . 'M';
    if ($daily >= 1_000)     return '₦' . number_format($daily / 1_000, 0) . 'k';
    return '₦' . number_format($daily, 0);
}

$diffColors = ['beginner' => 'tg', 'intermediate' => 'to', 'advanced' => 'tr'];
$diffLabels = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];

function filterExUrl(array $overrides = []): string {
    $p = array_merge(
        array_filter(['q' => trim($_GET['q'] ?? ''), 'sort' => $_GET['sort'] ?? '', 'efilter' => $_GET['efilter'] ?? '', 'view' => $_GET['view'] ?? '']),
        $overrides
    );
    $qs = http_build_query(array_filter($p));
    return APP_URL . '/execute' . ($qs ? "?$qs" : '');
}

// ── Active hustle detail panel ─────────────────────────────────────────────────
$activeSlug = $_GET['hustle'] ?? '';
$activeHustle = null;
if ($activeSlug) {
    foreach ($hustles as $h) {
        if ($h['slug'] === $activeSlug) { $activeHustle = $h; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Execute — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --orange:#d45820;--orange-light:#fdf0eb;
  --red:#cc3333;--red-light:#fdf0f0;
  --purple:#6b35c8;--purple-light:#f3eefe;
  --r:16px;--nav:58px;--bot:64px;
  --sh:0 2px 16px rgba(0,0,0,.06);
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;overflow-x:hidden;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;height:0;}

/* ── TOP NAV ── */
.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-bell{width:34px;height:34px;border-radius:50%;background:transparent;border:none;display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;position:relative;}
.nav-bell-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--red);border-radius:50%;border:2px solid #fff;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,#071a0d 0%,#0d3a1a 50%,#16a05a22 100%);padding:22px 18px 22px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(22,160,90,.08);}
.hero-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(22,160,90,.2);border:1px solid rgba(22,160,90,.35);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#4ade80;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:28px;font-weight:800;color:#fff;line-height:1.1;margin-bottom:4px;}
.hero-title span{color:#4ade80;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.6);line-height:1.55;margin-bottom:18px;}
.hero-stats{display:flex;gap:8px;}
.hstat{flex:1;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:10px 8px;text-align:center;}
.hstat-n{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;color:#fff;}
.hstat-l{font-size:10px;color:rgba(255,255,255,.55);margin-top:2px;}
.hstat.gold .hstat-n{color:#fbbf24;}

/* ── INCOME TRACKER CTA ── */
.income-cta{display:flex;align-items:center;justify-content:center;gap:8px;background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:12px;margin:14px 16px 0;padding:13px;text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;color:var(--green3);}

/* ── SEARCH + CONTROLS ── */
.controls{padding:14px 16px 0;}
.search-box{display:flex;gap:8px;align-items:center;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:0 13px;height:46px;margin-bottom:10px;}
.search-box:focus-within{border-color:var(--green);}
.search-box input{flex:1;background:none;border:none;outline:none;font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);}
.search-box input::placeholder{color:var(--text3);}
.controls-row{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;}
.sort-btn{display:flex;align-items:center;gap:6px;background:var(--surface);border:1.5px solid var(--border);border-radius:10px;padding:8px 12px;font-size:12px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text2);cursor:pointer;position:relative;}
.sort-btn select{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%;}
.view-toggle{display:flex;gap:4px;}
.vt-btn{width:34px;height:34px;border-radius:8px;border:1.5px solid var(--border);background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;text-decoration:none;}
.vt-btn.active{background:var(--green);border-color:var(--green);}

.filter-scroll{display:flex;gap:7px;overflow-x:auto;scrollbar-width:none;padding-bottom:2px;}
.filter-scroll::-webkit-scrollbar{display:none;}
.fpill{flex-shrink:0;padding:7px 13px;border-radius:20px;font-size:11.5px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;cursor:pointer;}
.fpill.active{background:var(--green);border-color:var(--green);color:#fff;}

/* ── HUSTLE LIST CARDS ── */
.hustle-list{padding:12px 16px 0;display:flex;flex-direction:column;gap:10px;}
.hcard{background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:14px 14px 12px;transition:.15s;}
.hcard:active{background:var(--surface);}
.hc-top{display:flex;align-items:flex-start;gap:12px;margin-bottom:10px;}
.hc-icon{width:46px;height:46px;border-radius:12px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.hc-info{flex:1;min-width:0;}
.hc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;margin-bottom:3px;}
.hc-desc{font-size:11.5px;color:var(--text3);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.hc-edit{width:28px;height:28px;border-radius:50%;border:1.5px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;cursor:pointer;flex-shrink:0;text-decoration:none;color:var(--text3);}

/* Income gradient bar */
.income-bar-wrap{margin-bottom:8px;}
.income-bar-labels{display:flex;justify-content:space-between;font-size:10px;font-weight:700;color:var(--text4);margin-bottom:4px;}
.income-bar{height:6px;background:linear-gradient(90deg,var(--green) 0%,#f59e0b 50%,var(--red) 100%);border-radius:3px;}
.income-bar-vals{display:flex;justify-content:space-between;font-size:11.5px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;margin-top:4px;}
.income-bar-vals .low{color:var(--text2);}
.income-bar-vals .high{color:var(--red);}

/* Tags row */
.hc-tags{display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin-bottom:10px;}
.tag{font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.to{background:var(--orange-light);color:var(--orange);}
.tr{background:var(--red-light);color:var(--red);}
.tp{background:var(--purple-light);color:var(--purple);}
.tgd{background:var(--gold-light);color:var(--gold2);}
.tag-capital{background:#fff3e0;color:#e65100;}
.tag-time{color:var(--text3);font-size:11px;display:flex;align-items:center;gap:4px;}

/* Progress dots */
.hc-bottom{display:flex;align-items:center;justify-content:space-between;}
.prog-dots{display:flex;gap:5px;align-items:center;}
.pdot{width:10px;height:10px;border-radius:50%;background:var(--border);}
.pdot.done{background:var(--green);}
.hc-actions{display:flex;gap:8px;}
.btn-commit{display:flex;align-items:center;gap:5px;padding:8px 13px;border-radius:10px;border:1.5px solid var(--border);background:#fff;font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--text2);cursor:pointer;text-decoration:none;}
.btn-start{display:flex;align-items:center;gap:5px;padding:8px 13px;border-radius:10px;border:none;background:var(--green);font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:#fff;cursor:pointer;text-decoration:none;}

/* ── DETAIL PANEL (bottom sheet) ── */
.detail-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:300;opacity:0;pointer-events:none;transition:.3s;}
.detail-overlay.open{opacity:1;pointer-events:all;}
.detail-sheet{position:fixed;bottom:0;left:50%;transform:translateX(-50%) translateY(100%);width:100%;max-width:430px;background:#fff;border-radius:24px 24px 0 0;max-height:90vh;overflow-y:auto;z-index:301;transition:.3s;}
.detail-sheet.open{transform:translateX(-50%) translateY(0);}
.ds-handle{width:40px;height:4px;background:var(--border);border-radius:2px;margin:12px auto 16px;}
.ds-header{padding:0 18px 16px;border-bottom:1px solid var(--border);}
.ds-emoji{font-size:36px;margin-bottom:8px;}
.ds-name{font-family:'Bricolage Grotesque',sans-serif;font-size:18px;font-weight:800;margin-bottom:4px;}
.ds-income{font-size:12px;color:var(--text3);font-weight:600;}
.ds-section{padding:16px 18px;border-bottom:1px solid var(--border);}
.ds-sec-title{font-size:10px;font-weight:800;color:var(--text3);letter-spacing:.06em;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:10px;}
.income-levels{display:flex;flex-direction:column;gap:8px;}
.income-level{display:flex;align-items:center;justify-content:space-between;font-size:13px;}
.il-dot{width:12px;height:12px;border-radius:50%;flex-shrink:0;}
.il-label{flex:1;margin-left:8px;color:var(--text2);font-weight:600;}
.il-val{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;}
.prog-section{padding:16px 18px;}
.prog-bar-wrap{margin-bottom:14px;}
.prog-bar-labels{display:flex;justify-content:space-between;font-size:11.5px;font-weight:700;color:var(--text3);margin-bottom:6px;}
.prog-bar-labels span:last-child{color:var(--green);}
.prog-bar-track{height:8px;background:var(--border);border-radius:4px;overflow:hidden;}
.prog-bar-fill{height:100%;background:linear-gradient(90deg,var(--green),#2ae87a);border-radius:4px;transition:.4s;}
.steps-list{display:flex;flex-direction:column;gap:0;}
.step-item{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:1px solid var(--border);cursor:pointer;}
.step-item:last-child{border-bottom:none;}
.step-circle{width:26px;height:26px;border-radius:50%;background:var(--green);display:flex;align-items:center;justify-content:center;color:#fff;font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;flex-shrink:0;}
.step-circle.locked{background:var(--border);color:var(--text3);}
.step-circle.locked-icon{font-size:13px;}
.step-text{font-size:13px;line-height:1.55;flex:1;color:var(--text2);}
.lock-banner{background:#0d1f14;border-radius:12px;padding:16px;text-align:center;margin-top:4px;}
.lb-lock{font-size:28px;margin-bottom:8px;}
.lb-title{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;color:#fff;margin-bottom:6px;}
.lb-sub{font-size:12px;color:rgba(255,255,255,.55);margin-bottom:14px;line-height:1.5;}
.lb-btn{display:block;background:var(--gold);color:#fff;border-radius:10px;padding:12px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;text-align:center;}
.ds-close{position:absolute;top:16px;right:16px;width:32px;height:32px;border-radius:50%;background:var(--surface);border:none;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
.bnav.active{color:var(--green);}
.bni{font-size:20px;}.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}

/* Toast */
.toast{position:fixed;bottom:calc(var(--bot)+16px);left:50%;transform:translateX(-50%);background:#0d0d0c;color:#fff;font-size:13px;font-weight:700;padding:10px 20px;border-radius:30px;z-index:500;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap;font-family:'Bricolage Grotesque',sans-serif;}
.toast.show{opacity:1;}
</style>
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
  <a href="<?= APP_URL ?>/" class="nav-brand">
    <div class="nav-logo-box">👑</div>
    Hustle<em>Kingdom</em>
  </a>
  <div class="nav-r">
    <?php if ($isPro): ?><span class="pro-chip">⭐ PRO</span><?php else: ?><a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a><?php endif; ?>
    <button class="nav-bell">🔔<span class="nav-bell-dot"></span></button>
    <a href="<?= APP_URL ?>/dashboard" class="nav-avatar"><?= htmlspecialchars($user['avatar_emoji'] ?? '👤') ?></a>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div class="hero-chip">▶ EXECUTION HUB</div>
  <div class="hero-title">Pick One.<br/><span>Start Today.</span></div>
  <div class="hero-sub">Search, sort, commit. Every hustle links to a step-by-step plan and income tracker.</div>
  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-n"><?= number_format($totalHustles) ?></div>
      <div class="hstat-l">Hustles</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?= $withRoadmap ?></div>
      <div class="hstat-l">With roadmap</div>
    </div>
    <div class="hstat gold">
      <div class="hstat-n"><?= $loggedToday > 0 ? '₦' . number_format($loggedToday) : 'N0' ?></div>
      <div class="hstat-l">Logged Today</div>
    </div>
  </div>
</div>

<!-- Income Tracker CTA -->
<a href="<?= APP_URL ?>/dashboard" class="income-cta">
  💰 Open Income Tracker
</a>

<!-- SEARCH + CONTROLS -->
<div class="controls">
  <form method="GET" action="<?= APP_URL ?>/execute">
    <?php if ($sort):    ?><input type="hidden" name="sort"    value="<?= htmlspecialchars($sort) ?>"/><?php endif; ?>
    <?php if ($efilter): ?><input type="hidden" name="efilter" value="<?= htmlspecialchars($efilter) ?>"/><?php endif; ?>
    <?php if ($view):    ?><input type="hidden" name="view"    value="<?= htmlspecialchars($view) ?>"/><?php endif; ?>
    <div class="search-box">
      <span style="font-size:16px;color:var(--text3);">🔍</span>
      <input type="text" name="q" placeholder="Search by name, keyword, tag…" value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
    </div>
  </form>
  <div class="controls-row">
    <div class="sort-btn">
      ↕ Sort: <?= match($sort) { 'income-high' => 'Income ↓', 'income-low' => 'Income ↑', 'easiest' => 'Easiest', 'fastest' => 'Fastest', default => 'Default' } ?>
      <select onchange="window.location=this.value">
        <option value="<?= filterExUrl(['sort'=>'default']) ?>" <?= $sort==='default'?'selected':'' ?>>Default</option>
        <option value="<?= filterExUrl(['sort'=>'income-high']) ?>" <?= $sort==='income-high'?'selected':'' ?>>Income High → Low</option>
        <option value="<?= filterExUrl(['sort'=>'income-low']) ?>" <?= $sort==='income-low'?'selected':'' ?>>Income Low → High</option>
        <option value="<?= filterExUrl(['sort'=>'easiest']) ?>" <?= $sort==='easiest'?'selected':'' ?>>Easiest First</option>
        <option value="<?= filterExUrl(['sort'=>'fastest']) ?>" <?= $sort==='fastest'?'selected':'' ?>>Fastest Start</option>
      </select>
    </div>
    <div class="view-toggle">
      <a href="<?= filterExUrl(['view'=>'list']) ?>" class="vt-btn <?= $view==='list'?'active':'' ?>" style="<?= $view==='list'?'filter:invert(1);':'' ?>">☰</a>
      <a href="<?= filterExUrl(['view'=>'grid']) ?>" class="vt-btn <?= $view==='grid'?'active':'' ?>" style="<?= $view==='grid'?'filter:invert(1);':'' ?>">⊞</a>
    </div>
  </div>
  <div class="filter-scroll">
    <a href="<?= filterExUrl(['efilter'=>'all']) ?>" class="fpill <?= $efilter==='all'?'active':'' ?>">🔥 All</a>
    <a href="<?= filterExUrl(['efilter'=>'progress']) ?>" class="fpill <?= $efilter==='progress'?'active':'' ?>">☑ In Progress</a>
    <a href="<?= filterExUrl(['efilter'=>'zero-capital']) ?>" class="fpill <?= $efilter==='zero-capital'?'active':'' ?>">💰 Zero Capital</a>
    <a href="<?= filterExUrl(['efilter'=>'beginner']) ?>" class="fpill <?= $efilter==='beginner'?'active':'' ?>">🟢 Beginner</a>
  </div>
</div>

<!-- HUSTLE LIST -->
<div class="hustle-list">
<?php if (empty($hustles)): ?>
  <div style="text-align:center;padding:40px 16px;color:var(--text3);">
    <div style="font-size:40px;margin-bottom:12px;">📋</div>
    <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:var(--text2);margin-bottom:8px;">No hustles found</div>
    <p style="font-size:13px;">Try different filters or <a href="<?= APP_URL ?>/execute" style="color:var(--green);font-weight:700;">clear all</a>.</p>
  </div>
<?php else: ?>
  <?php foreach ($hustles as $h):
    $steps     = json_decode($h['steps'] ?? '[]', true) ?: [];
    $hKey      = 'hustle_' . $h['id'];
    $done      = $progress[$hKey] ?? [];
    $doneCount = count($done);
    $stepTotal = max(1, count($steps));
    $pct       = $stepTotal > 0 ? round(($doneCount / $stepTotal) * 100) : 0;
    $diffClass = $diffColors[$h['difficulty']] ?? 'tg';
    $diffLabel = $diffLabels[$h['difficulty']] ?? '';
    $isSaved   = in_array($h['slug'], $savedSlugs);

    // Progress dots (show up to 5)
    $dotCount = min(5, $stepTotal);
    $doneDots = $stepTotal > 0 ? round(($doneCount / $stepTotal) * $dotCount) : 0;

    // Time-to-first estimate from difficulty
    $timeMap = ['beginner'=>'First client in 2–3 days','intermediate'=>'First profit in 1–2 weeks','advanced'=>'First sale in 1–2 months'];
    $timeEst = $timeMap[$h['difficulty']] ?? '';
  ?>
    <div class="hcard">
      <div class="hc-top">
        <div class="hc-icon"><?= htmlspecialchars($h['emoji']) ?></div>
        <div class="hc-info">
          <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
          <div class="hc-desc"><?= htmlspecialchars($h['description'] ?? '') ?></div>
        </div>
        <a href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>" class="hc-edit">✏️</a>
      </div>

      <!-- Income gradient bar -->
      <div class="income-bar-wrap">
        <div class="income-bar-labels">
          <span>BEGINNER</span><span>ADVANCED</span>
        </div>
        <div class="income-bar"></div>
        <div class="income-bar-vals">
          <span class="low"><?= fmtDay((float)$h['income_min']) ?>/day</span>
          <span class="high"><?= fmtDay((float)$h['income_max']) ?>/day</span>
        </div>
      </div>

      <!-- Tags -->
      <div class="hc-tags">
        <?php if ($h['capital_needed'] == 0): ?>
          <span class="tag tgd">💰 N0</span>
        <?php else: ?>
          <span class="tag tag-capital">💰 <?= fmtE((float)$h['capital_needed']) ?></span>
        <?php endif; ?>
        <?php if ($timeEst): ?><span class="tag-time">⏱ <?= htmlspecialchars($timeEst) ?></span><?php endif; ?>
        <span class="tag <?= $diffClass ?>"><?= $diffLabel ?></span>
        <?php if (!empty($steps)): ?><span class="tag tg" style="cursor:pointer;" onclick="openDetail('<?= addslashes(htmlspecialchars($h['slug'])) ?>')">⚡ Start now</span><?php endif; ?>
      </div>

      <!-- Bottom: progress + actions -->
      <div class="hc-bottom">
        <div class="prog-dots">
          <?php for ($d = 0; $d < $dotCount; $d++): ?>
            <div class="pdot <?= $d < $doneDots ? 'done' : '' ?>"></div>
          <?php endfor; ?>
        </div>
        <div class="hc-actions">
          <button class="btn-commit" onclick="commitHustle('<?= htmlspecialchars($h['slug']) ?>','<?= addslashes(htmlspecialchars($h['name'])) ?>')">
            🎯 Commit
          </button>
          <button class="btn-start" onclick="openDetail('<?= htmlspecialchars($h['slug']) ?>')">
            ▶ Start
          </button>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- DETAIL PANEL -->
<div class="detail-overlay" id="detail-overlay" onclick="closeDetail()"></div>
<div class="detail-sheet" id="detail-sheet">
  <button class="ds-close" onclick="closeDetail()">✕</button>
  <div class="ds-handle"></div>
  <div id="detail-content"><!-- filled by JS --></div>
</div>

<div class="toast" id="toast"></div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--green);">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
// All hustles data for the detail panel
const hustlesData = <?= json_encode(array_values($hustles)) ?>;
const progress    = <?= json_encode($progress) ?>;
const isPro       = <?= json_encode($isPro) ?>;
const APP_URL     = '<?= APP_URL ?>';

function findHustle(slug) {
  return hustlesData.find(h => h.slug === slug);
}

function fmtMoney(n) {
  n = parseFloat(n);
  if (n >= 1e6) return '₦' + (n/1e6).toFixed(1) + 'M';
  if (n >= 1e3) return '₦' + Math.round(n/1e3) + 'k';
  return '₦' + Math.round(n);
}

function openDetail(slug) {
  const h = findHustle(slug);
  if (!h) { window.location = APP_URL + '/hustle/' + slug; return; }

  const steps  = JSON.parse(h.steps  || '[]');
  const hKey   = 'hustle_' + h.id;
  const done   = progress[hKey] || [];
  const pct    = steps.length > 0 ? Math.round((done.length / steps.length) * 100) : 0;

  let stepsHtml = '';
  steps.slice(0, isPro ? steps.length : 2).forEach((s, i) => {
    stepsHtml += `<div class="step-item">
      <div class="step-circle">${i+1}</div>
      <div class="step-text">${s}</div>
    </div>`;
  });

  const lockedCount = steps.length - (isPro ? steps.length : 2);
  const lockHtml = (!isPro && lockedCount > 0) ? `
    <div class="lock-banner">
      <div class="lb-lock">🔒</div>
      <div class="lb-title">${lockedCount} more step${lockedCount>1?'s':''} locked</div>
      <div class="lb-sub">The most actionable part. Upgrade to Pro to unlock the full roadmap.</div>
      <a href="${APP_URL}/upgrade" class="lb-btn">⭐ Upgrade to Pro</a>
    </div>` : '';

  const content = `
    <div class="ds-header">
      <div class="ds-emoji">${h.emoji}</div>
      <div class="ds-name">${h.name}</div>
      <div class="ds-income">${fmtMoney(h.income_min)}–${fmtMoney(h.income_max)}/${h.income_period}</div>
    </div>
    <div class="ds-section">
      <div class="ds-sec-title">INCOME LEVELS</div>
      <div class="income-levels">
        <div class="income-level"><div class="il-dot" style="background:#16a05a;"></div><div class="il-label">Beginner</div><div class="il-val">${fmtMoney(h.income_min)}/month</div></div>
        <div class="income-level"><div class="il-dot" style="background:#f59e0b;"></div><div class="il-label">Regular</div><div class="il-val">${fmtMoney((parseFloat(h.income_min)+parseFloat(h.income_max))/2)}/month</div></div>
        <div class="income-level"><div class="il-dot" style="background:#cc3333;"></div><div class="il-label">Advanced</div><div class="il-val">${fmtMoney(h.income_max)}/month</div></div>
      </div>
    </div>
    <div class="prog-section">
      <div class="ds-sec-title">MY PROGRESS</div>
      <div class="prog-bar-wrap">
        <div class="prog-bar-labels"><span>Step ${done.length} of ${steps.length}</span><span>${pct}%</span></div>
        <div class="prog-bar-track"><div class="prog-bar-fill" style="width:${pct}%;"></div></div>
      </div>
      <div class="ds-sec-title">HOW TO START (${steps.length} STEPS)</div>
      <div class="steps-list">${stepsHtml}</div>
      ${lockHtml}
      ${steps.length === 0 ? '<p style="font-size:13px;color:var(--text3);text-align:center;padding:12px 0;">No steps yet. View the full guide.</p>' : ''}
      <a href="${APP_URL}/hustle/${h.slug}" style="display:flex;align-items:center;justify-content:center;gap:6px;background:var(--green);color:#fff;border-radius:12px;padding:14px;margin-top:16px;text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;">📖 Full Hustle Guide →</a>
    </div>`;

  document.getElementById('detail-content').innerHTML = content;
  document.getElementById('detail-overlay').classList.add('open');
  document.getElementById('detail-sheet').classList.add('open');
}

function closeDetail() {
  document.getElementById('detail-overlay').classList.remove('open');
  document.getElementById('detail-sheet').classList.remove('open');
}

function commitHustle(slug, name) {
  // Save to saved_hustles via API
  fetch(APP_URL + '/api/saved', {
    method: 'POST',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({slug}),
    credentials: 'same-origin'
  }).then(r => r.json()).then(d => {
    showToast('✅ Committed to: ' + name);
  }).catch(() => showToast('✅ Committed!'));
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2400);
}

// Open detail if hustle param in URL
<?php if ($activeHustle): ?>
setTimeout(() => openDetail('<?= htmlspecialchars($activeHustle['slug']) ?>'), 100);
<?php endif; ?>
</script>
</body>
</html>
