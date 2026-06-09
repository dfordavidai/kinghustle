<?php
/**
 * HustleKingdom — Discover / Browse Hustles
 * Route: GET /hustles
 * Matches the video "Discover Mode" design exactly.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
Auth::start();

$user  = Auth::isLoggedIn() ? Auth::user() : null;
$isPro = Auth::isPro();

// ── Filters ──────────────────────────────────────────────────────────────────
$cat      = $_GET['cat']    ?? '';
$filter   = $_GET['filter'] ?? '';   // no-capital | dollar | quick | passive
$income   = $_GET['income'] ?? '';   // 2k | 5k | 20k | usd (income target)
$search   = trim($_GET['q'] ?? '');
$mode     = $_GET['mode']   ?? 'browse'; // browse | swipe
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 20;
$offset   = ($page - 1) * $limit;

// ── Category metadata ────────────────────────────────────────────────────────
$categories = [
    'agro'      => ['emoji' => '🌿', 'icon' => '🌱', 'label' => 'Agro & Food',             'color' => '#2d7a3a', 'bg' => '#e8f5eb'],
    'digital'   => ['emoji' => '💻', 'icon' => '🖥️', 'label' => 'Digital & Tech',          'color' => '#2055d4', 'bg' => '#edf2fd'],
    'social'    => ['emoji' => '📱', 'icon' => '📲', 'label' => 'Social & Content',         'color' => '#7c3aed', 'bg' => '#f3eefe'],
    'ai'        => ['emoji' => '🤖', 'icon' => '🤖', 'label' => 'AI-Powered',               'color' => '#b45309', 'bg' => '#fef3c7'],
    'trade'     => ['emoji' => '🛒', 'icon' => '🛒', 'label' => 'Trading & Commerce',       'color' => '#be185d', 'bg' => '#fdf2f8'],
    'education' => ['emoji' => '🎓', 'icon' => '🎓', 'label' => 'Education & Coaching',     'color' => '#0369a1', 'bg' => '#e0f2fe'],
    'finance'   => ['emoji' => '💰', 'icon' => '💰', 'label' => 'Finance & Investment',     'color' => '#16a05a', 'bg' => '#ebf7f1'],
    'trades'    => ['emoji' => '🏗️', 'icon' => '🏗️', 'label' => 'Trades & Labour',         'color' => '#c2410c', 'bg' => '#fff7ed'],
    'creative'  => ['emoji' => '🎨', 'icon' => '🎨', 'label' => 'Creative & Media',         'color' => '#9d174d', 'bg' => '#fdf2f8'],
    'realestate'=> ['emoji' => '🏠', 'icon' => '🏠', 'label' => 'Real Estate',              'color' => '#075985', 'bg' => '#e0f2fe'],
    'transport' => ['emoji' => '🚗', 'icon' => '🚗', 'label' => 'Transport & Logistics',    'color' => '#92400e', 'bg' => '#fffbeb'],
    'health'    => ['emoji' => '💆', 'icon' => '💆', 'label' => 'Health & Beauty',           'color' => '#6b21a8', 'bg' => '#f5f3ff'],
];

// ── DB Counts per category ────────────────────────────────────────────────────
$catCounts = [];
try {
    $rows = DB::query("SELECT category, COUNT(*) as cnt FROM hustles WHERE is_active=1 GROUP BY category", []);
    foreach ($rows as $r) $catCounts[$r['category']] = (int)$r['cnt'];
} catch (\Exception $e) { $catCounts = []; }

// ── Total / stats ─────────────────────────────────────────────────────────────
$totalHustles = array_sum($catCounts) ?: 739;
$totalCats    = count($catCounts) ?: 12;

// ── Build WHERE for hustle list ──────────────────────────────────────────────
$where  = ['is_active = 1'];
$params = [];

if ($cat)    { $where[] = 'category = :cat'; $params[':cat'] = $cat; }
if ($search) { $where[] = '(name LIKE :q OR description LIKE :q2)'; $params[':q'] = "%$search%"; $params[':q2'] = "%$search%"; }

// Quick-filter mappings
if ($filter === 'no-capital') { $where[] = 'capital_needed = 0'; }
if ($filter === 'dollar')     { $where[] = "income_period = 'month' AND income_max >= 100000"; } // proxy for dollar income
if ($filter === 'quick')      { $where[] = "difficulty = 'beginner'"; }
if ($filter === 'passive')    { $where[] = "category IN ('digital','social','ai')"; }

// Income targets
if ($income === '2k')   { $where[] = 'income_min <= 60000'; }
if ($income === '5k')   { $where[] = 'income_min <= 150000 AND income_max >= 100000'; }
if ($income === '20k')  { $where[] = 'income_max >= 300000'; }
if ($income === 'usd')  { $where[] = 'income_max >= 500000'; }

$whereStr = implode(' AND ', $where);

// ── Fetch hustles for swipe/browse ───────────────────────────────────────────
$hustles = [];
try {
    $hustles = DB::query(
        "SELECT id, name, slug, emoji, category, income_min, income_max, income_period,
                difficulty, capital_needed, description, is_featured, save_count
         FROM hustles WHERE $whereStr
         ORDER BY is_featured DESC, save_count DESC, id ASC
         LIMIT $limit OFFSET $offset",
        $params
    );
} catch (\Exception $e) { $hustles = []; }

$total = 0;
try {
    $total = (int)(DB::one("SELECT COUNT(*) as cnt FROM hustles WHERE $whereStr", $params)['cnt'] ?? 0);
} catch (\Exception $e) { $total = 0; }
$pages = max(1, ceil($total / $limit));

// ── Trending category (most saves) ───────────────────────────────────────────
$trendingCat = 'AI-Powered';
$trendingCount = 22;
try {
    $tr = DB::one("SELECT category, COUNT(*) as cnt FROM hustles WHERE is_active=1 GROUP BY category ORDER BY cnt DESC LIMIT 1", []);
    if ($tr) { $trendingCat = $categories[$tr['category']]['label'] ?? ucfirst($tr['category']); $trendingCount = $tr['cnt']; }
} catch (\Exception $e) {}

function fmtM(float $n): string {
    if ($n >= 1_000_000) return '₦' . number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return '₦' . number_format($n / 1_000, 0) . 'k';
    return '₦' . number_format($n, 0);
}

function filterUrl(array $overrides = []): string {
    $p = array_merge(
        array_filter(['cat' => $_GET['cat'] ?? '', 'filter' => $_GET['filter'] ?? '', 'income' => $_GET['income'] ?? '', 'q' => trim($_GET['q'] ?? ''), 'mode' => $_GET['mode'] ?? '']),
        $overrides
    );
    $qs = http_build_query(array_filter($p));
    return APP_URL . '/hustles' . ($qs ? "?$qs" : '');
}

$savedSlugs = [];
if ($user) {
    try {
        $rows = DB::query('SELECT h.slug FROM saved_hustles sh JOIN hustles h ON h.id=sh.hustle_id WHERE sh.user_id=:uid', [':uid'=>$user['id']]);
        $savedSlugs = array_column($rows, 'slug');
    } catch (\Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Discover Hustles — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--text4:#c8c8c0;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --orange:#d45820;--orange-light:#fdf0eb;
  --red:#cc3333;--red-light:#fdf0f0;
  --purple:#6b35c8;--purple-light:#f3eefe;
  --blue:#2055d4;--blue-light:#edf2fd;
  --r:16px;--rs:10px;--nav:58px;--bot:64px;
  --sh:0 2px 16px rgba(0,0,0,.06);--sh2:0 8px 32px rgba(0,0,0,.12);
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;height:0;}

/* ── TOP NAV ── */
.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;text-decoration:none;}
.nav-icon-btn{width:34px;height:34px;border-radius:50%;background:transparent;border:none;display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;text-decoration:none;color:var(--text2);}

/* ── HERO BANNER ── */
.hero{background:linear-gradient(135deg,#0d6e3c 0%,#16a05a 55%,#1ec870 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);}
.hero::after{content:'';position:absolute;right:40px;bottom:-60px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.04);}
.hero-mode-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:26px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;position:relative;z-index:1;}
.hero-title span{color:#a3f0c8;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.75);line-height:1.55;margin-bottom:18px;position:relative;z-index:1;}
.hero-stats{display:flex;gap:8px;position:relative;z-index:1;}
.hstat{flex:1;background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:10px 8px;text-align:center;}
.hstat-n{font-family:'Bricolage Grotesque',sans-serif;font-size:18px;font-weight:800;color:#fff;}
.hstat-l{font-size:10px;color:rgba(255,255,255,.7);margin-top:2px;}

/* ── FILTER PILLS ── */
.filter-section{padding:14px 16px 0;}
.filter-label{font-size:10.5px;font-weight:800;color:var(--text3);letter-spacing:.06em;margin-bottom:8px;font-family:'Bricolage Grotesque',sans-serif;}
.filter-scroll{display:flex;gap:7px;overflow-x:auto;scrollbar-width:none;padding-bottom:2px;}
.filter-scroll::-webkit-scrollbar{display:none;}
.fpill{flex-shrink:0;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;white-space:nowrap;cursor:pointer;}
.fpill.active{background:var(--green);border-color:var(--green);color:#fff;}
.fpill:active{opacity:.75;}

/* ── CATEGORIES SECTION ── */
.section-head{padding:18px 16px 10px;display:flex;align-items:center;justify-content:space-between;}
.section-label{font-size:10.5px;font-weight:800;color:var(--text3);letter-spacing:.06em;font-family:'Bricolage Grotesque',sans-serif;}
.section-link{font-size:12px;font-weight:700;color:var(--green);text-decoration:none;}

.cat-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;padding:0 16px;}
.cat-card{border:1.5px solid var(--border);border-radius:14px;padding:14px 10px 12px;text-align:center;cursor:pointer;text-decoration:none;transition:.15s;display:flex;flex-direction:column;align-items:center;gap:6px;}
.cat-card:active{transform:scale(.97);}
.cat-card.active{border-color:var(--green);background:var(--green-light);}
.cat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;margin:0 auto;}
.cat-name{font-family:'Bricolage Grotesque',sans-serif;font-size:11.5px;font-weight:800;color:var(--text);line-height:1.3;}
.cat-count{font-size:10px;color:var(--text3);font-weight:600;}

/* ── TRENDING BANNER ── */
.trending-banner{margin:14px 16px 0;background:#0d0d12;border-radius:var(--r);padding:14px 16px;display:flex;align-items:center;justify-content:space-between;text-decoration:none;}
.tb-left{}
.tb-chip{display:inline-flex;align-items:center;gap:5px;background:rgba(22,160,90,.25);border:1px solid rgba(22,160,90,.4);border-radius:20px;padding:3px 9px;font-size:10px;font-weight:800;color:#4ade80;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:5px;}
.tb-title{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:#fff;margin-bottom:3px;}
.tb-sub{font-size:11.5px;color:rgba(255,255,255,.55);}
.tb-arrow{width:36px;height:36px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;flex-shrink:0;}

/* ── INCOME TARGET ── */
.income-section{padding:16px 16px 0;}
.income-scroll{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;}
.income-scroll::-webkit-scrollbar{display:none;}
.income-pill{flex-shrink:0;padding:8px 16px;border-radius:20px;font-size:12px;font-weight:800;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;}
.income-pill.active{background:var(--text);border-color:var(--text);color:#fff;}

/* ── BROWSE / HUSTLE LIST ── */
.browse-section{padding:16px 16px 0;}
.search-box{display:flex;gap:8px;align-items:center;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:0 13px;height:46px;margin-bottom:12px;}
.search-box:focus-within{border-color:var(--green);}
.search-box input{flex:1;background:none;border:none;outline:none;font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);}
.search-box input::placeholder{color:var(--text3);}
.search-box span{font-size:16px;color:var(--text3);}

.hustle-list{display:flex;flex-direction:column;gap:10px;}
.hcard{background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:14px;text-decoration:none;color:var(--text);display:flex;gap:12px;align-items:flex-start;transition:.15s;}
.hcard:active{background:var(--surface);}
.hc-icon{width:46px;height:46px;border-radius:12px;background:var(--surface2);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.hc-body{flex:1;min-width:0;}
.hc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;margin-bottom:3px;}
.hc-desc{font-size:11.5px;color:var(--text3);line-height:1.5;margin-bottom:8px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.hc-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.hc-income{font-size:11.5px;font-weight:800;color:var(--green3);font-family:'Bricolage Grotesque',sans-serif;}
.tag{font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.to{background:var(--orange-light);color:var(--orange);}
.tr{background:var(--red-light);color:var(--red);}
.tp{background:var(--purple-light);color:var(--purple);}
.tgd{background:var(--gold-light);color:var(--gold2);}
.hc-save{width:28px;height:28px;border-radius:50%;border:1.5px solid var(--border);background:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;cursor:pointer;flex-shrink:0;align-self:flex-start;margin-top:2px;}
.hc-save.saved{background:var(--green-light);border-color:var(--green);}

/* ── SWIPE CARD VIEW — FULL SCREEN OVERLAY ── */
.swipe-wrap{display:none;}
.swipe-wrap.active{display:block;}
.browse-wrap{display:block;}
.browse-wrap.hidden{display:none;}

#swipe-view{
  position:fixed;top:0;left:0;right:0;bottom:0;
  background:#fff;z-index:300;
  display:flex;flex-direction:column;
  max-width:480px;margin:0 auto;
}

.swipe-header{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);flex-shrink:0;background:#fff;}
.swipe-back{width:34px;height:34px;border-radius:50%;background:var(--surface);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;text-decoration:none;flex-shrink:0;}
.swipe-cat-info{flex:1;}
.swipe-cat-icon{font-size:16px;display:inline-block;margin-right:6px;}
.swipe-cat-name{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;}
.swipe-cat-sub{font-size:11px;color:var(--text3);}
.swipe-dots{display:flex;gap:4px;align-items:center;}
.sdot{width:20px;height:5px;border-radius:3px;background:var(--border);}
.sdot.active{background:var(--green);width:28px;}

.swipe-stage{position:relative;padding:12px 16px;flex:1;display:flex;align-items:center;justify-content:center;}
.swipe-card{width:100%;border-radius:20px;overflow:hidden;background:linear-gradient(160deg,#b5410a 0%,#8b2500 100%);position:relative;touch-action:none;user-select:none;}
.swipe-card-inner{padding:18px 18px 16px;}
.sc-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(0,0,0,.3);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:rgba(255,255,255,.9);font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;}
.sc-emoji{font-size:52px;margin-bottom:10px;display:block;}
.sc-title{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;color:#fff;margin-bottom:10px;line-height:1.2;}
.sc-desc{font-size:13px;color:rgba(255,255,255,.8);line-height:1.6;margin-bottom:14px;}
.sc-hint{background:rgba(0,0,0,.4);border-radius:20px;padding:7px 14px;text-align:center;font-size:12px;color:rgba(255,255,255,.8);margin-bottom:0;}
.sc-income-row{display:flex;gap:0;overflow:hidden;border-radius:0 0 20px 20px;}
.sc-income-item{flex:1;padding:10px 8px;text-align:center;background:rgba(0,0,0,.25);border-right:1px solid rgba(255,255,255,.1);}
.sc-income-item:last-child{border-right:none;}
.sc-income-label{font-size:9px;color:rgba(255,255,255,.5);font-weight:700;letter-spacing:.05em;margin-bottom:2px;}
.sc-income-val{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:#fff;}

.swipe-actions{display:flex;align-items:center;justify-content:space-around;padding:8px 16px;flex-shrink:0;padding-bottom:calc(var(--bot) + 8px);background:#fff;}
.sa-btn{width:56px;height:56px;border-radius:50%;border:none;display:flex;align-items:center;justify-content:center;font-size:22px;cursor:pointer;box-shadow:var(--sh2);}
.sa-skip{background:#fff;border:2px solid #ffcccc;}
.sa-detail{background:#fff;border:2px solid var(--border);}
.sa-save{background:var(--green);}
.swipe-toast{position:fixed;bottom:calc(var(--bot)+20px);left:50%;transform:translateX(-50%);background:#0d0d0c;color:#fff;font-size:13px;font-weight:700;padding:10px 20px;border-radius:30px;z-index:400;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap;font-family:'Bricolage Grotesque',sans-serif;}
.swipe-toast.show{opacity:1;}

/* ── EMPTY ── */
.empty{text-align:center;padding:40px 24px;color:var(--text3);}
.empty .ei{font-size:40px;margin-bottom:12px;}
.empty h3{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:var(--text2);margin-bottom:6px;}

/* ── PAGINATION ── */
.pagination{display:flex;gap:8px;justify-content:center;padding:20px 16px;flex-wrap:wrap;}
.ppage{padding:8px 14px;border-radius:10px;font-size:13px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;}
.ppage.active{background:var(--green);border-color:var(--green);color:#fff;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;cursor:pointer;border:none;background:transparent;color:var(--text3);text-decoration:none;transition:.2s;}
.bnav.active{color:var(--green);}
.bni{font-size:20px;}.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;cursor:pointer;border:none;background:transparent;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}
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
    <?php if ($user): ?>
      <?php if ($isPro): ?>
        <span class="pro-chip">⭐ PRO</span>
      <?php else: ?>
        <a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/dashboard" class="nav-avatar" title="Dashboard"><?= htmlspecialchars($user['avatar_emoji'] ?? '👤') ?></a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/auth/login" class="pro-chip">Login →</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div class="hero-mode-chip">💡 DISCOVER MODE</div>
  <div class="hero-title">Find your next <span>hustle</span></div>
  <div class="hero-sub">Browse categories or swipe to explore. No pressure — just ideas.</div>
  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-n"><?= $totalHustles ?>+</div>
      <div class="hstat-l">Hustles</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?= $totalCats ?></div>
      <div class="hstat-l">Categories</div>
    </div>
    <div class="hstat">
      <div class="hstat-n">50+</div>
      <div class="hstat-l">Countries</div>
    </div>
  </div>
</div>

<!-- FILTER BY -->
<div class="filter-section">
  <div class="filter-label">FILTER BY</div>
  <div class="filter-scroll">
    <a href="<?= filterUrl(['filter'=>'','income'=>'','cat'=>'','page'=>'']) ?>" class="fpill <?= !$filter && !$income && !$cat ? 'active' : '' ?>">All</a>
    <a href="<?= filterUrl(['filter'=>'no-capital','page'=>'']) ?>" class="fpill <?= $filter==='no-capital' ? 'active' : '' ?>">No Capital</a>
    <a href="<?= filterUrl(['filter'=>'dollar','page'=>'']) ?>" class="fpill <?= $filter==='dollar' ? 'active' : '' ?>">Dollar Income</a>
    <a href="<?= filterUrl(['filter'=>'quick','page'=>'']) ?>" class="fpill <?= $filter==='quick' ? 'active' : '' ?>">Quick Start</a>
    <a href="<?= filterUrl(['filter'=>'passive','page'=>'']) ?>" class="fpill <?= $filter==='passive' ? 'active' : '' ?>">Passive Income</a>
  </div>
</div>

<!-- CATEGORIES GRID -->
<div class="section-head">
  <div class="section-label">CATEGORIES</div>
</div>
<div class="cat-grid">
  <?php foreach ($categories as $slug => $info):
    $count = $catCounts[$slug] ?? 0;
  ?>
    <a href="<?= filterUrl(['cat' => $slug, 'page' => '', 'mode' => 'swipe']) ?>"
       class="cat-card <?= $cat === $slug ? 'active' : '' ?>"
       style="background:<?= htmlspecialchars($info['bg']) ?>;">
      <div class="cat-icon" style="background:<?= htmlspecialchars($info['bg']) ?>;"><?= $info['icon'] ?></div>
      <div class="cat-name"><?= htmlspecialchars($info['label']) ?></div>
      <div class="cat-count"><?= $count ?> hustles</div>
    </a>
  <?php endforeach; ?>
</div>

<!-- TRENDING BANNER -->
<a href="<?= filterUrl(['filter'=>'','cat'=>'ai','mode'=>'browse','page'=>'']) ?>" class="trending-banner">
  <div class="tb-left">
    <div class="tb-chip">🔥 TRENDING</div>
    <div class="tb-title"><?= htmlspecialchars($trendingCat) ?> Hustles</div>
    <div class="tb-sub"><?= $trendingCount ?> ideas · earn ₦50k–₦500k/month</div>
  </div>
  <div class="tb-arrow">→</div>
</a>

<!-- INCOME TARGET -->
<div class="income-section">
  <div class="filter-label" style="margin-bottom:8px;">OR BROWSE BY INCOME TARGET</div>
  <div class="income-scroll">
    <a href="<?= filterUrl(['income'=>'2k','filter'=>'','page'=>'']) ?>" class="income-pill <?= $income==='2k' ? 'active' : '' ?>">₦2k<span style="font-size:10px;opacity:.6;">/day</span></a>
    <a href="<?= filterUrl(['income'=>'5k','filter'=>'','page'=>'']) ?>" class="income-pill <?= $income==='5k' ? 'active' : '' ?>">₦5k<span style="font-size:10px;opacity:.6;">/day</span></a>
    <a href="<?= filterUrl(['income'=>'20k','filter'=>'','page'=>'']) ?>" class="income-pill <?= $income==='20k' ? 'active' : '' ?>">₦20k<span style="font-size:10px;opacity:.6;">/day</span></a>
    <a href="<?= filterUrl(['income'=>'usd','filter'=>'','page'=>'']) ?>" class="income-pill <?= $income==='usd' ? 'active' : '' ?>" style="background:#0d0d0c;color:#fff;border-color:#0d0d0c;">$USD</a>
  </div>
</div>

<!-- SWIPE CARD VIEW (when category selected + mode=swipe) -->
<?php if ($cat && $mode === 'swipe' && !empty($hustles)): ?>
<script>document.body.style.overflow='hidden';</script>
<div id="swipe-view">
  <div class="swipe-header">
    <a href="<?= filterUrl(['mode'=>'browse','cat'=>'','page'=>'']) ?>" class="swipe-back">←</a>
    <div class="swipe-cat-info">
      <div><span class="swipe-cat-icon"><?= $categories[$cat]['icon'] ?? '📦' ?></span><span class="swipe-cat-name"><?= htmlspecialchars($categories[$cat]['label'] ?? ucfirst($cat)) ?> hustles</span></div>
      <div class="swipe-cat-sub">0 of <?= $total ?> ideas</div>
    </div>
    <div class="swipe-dots">
      <?php for ($i = 0; $i < min(10, $total); $i++): ?>
        <div class="sdot <?= $i === 0 ? 'active' : '' ?>"></div>
      <?php endfor; ?>
    </div>
  </div>

  <div class="swipe-stage" id="swipe-stage">
    <?php foreach ($hustles as $idx => $h):
      $catInfo = $categories[$h['category']] ?? ['color'=>'#8b2500','bg'=>'#f5e6e0'];
      $gradColors = [
        'agro'      => 'linear-gradient(160deg,#1a6b2f 0%,#0d4a1f 100%)',
        'digital'   => 'linear-gradient(160deg,#1a3c8b 0%,#0d2660 100%)',
        'social'    => 'linear-gradient(160deg,#5b1fa0 0%,#3a0d6e 100%)',
        'ai'        => 'linear-gradient(160deg,#b5410a 0%,#8b2500 100%)',
        'trade'     => 'linear-gradient(160deg,#8b1a56 0%,#5a0d35 100%)',
        'education' => 'linear-gradient(160deg,#0a5a8b 0%,#053a60 100%)',
        'finance'   => 'linear-gradient(160deg,#0d6e3c 0%,#084d2a 100%)',
        'trades'    => 'linear-gradient(160deg,#8b3c0a 0%,#602200 100%)',
        'creative'  => 'linear-gradient(160deg,#8b1a3c 0%,#5a0d25 100%)',
        'realestate'=> 'linear-gradient(160deg,#0a4a6e 0%,#053060 100%)',
        'transport' => 'linear-gradient(160deg,#7a4a0a 0%,#5a3000 100%)',
        'health'    => 'linear-gradient(160deg,#5a1a8b 0%,#3a0d6e 100%)',
      ];
      $grad = $gradColors[$h['category']] ?? 'linear-gradient(160deg,#333 0%,#111 100%)';
      $diffMap = ['beginner'=>'BEGINNER','intermediate'=>'REGULAR','advanced'=>'ADVANCED'];
    ?>
      <div class="swipe-card <?= $idx === 0 ? 'current' : '' ?>"
           id="sc-<?= $idx ?>"
           data-slug="<?= htmlspecialchars($h['slug']) ?>"
           data-name="<?= htmlspecialchars($h['name']) ?>"
           style="<?= $idx > 0 ? 'display:none;' : '' ?>background:<?= $grad ?>;">
        <div class="swipe-card-inner">
          <div class="sc-badge">🏢 <?= ucfirst($h['category']) ?> Business · <?= $diffMap[$h['difficulty']] ?? 'medium' ?></div>
          <div class="sc-emoji"><?= htmlspecialchars($h['emoji']) ?></div>
          <div class="sc-title"><?= htmlspecialchars($h['name']) ?></div>
          <div class="sc-desc"><?= htmlspecialchars(mb_substr($h['description'] ?? '', 0, 200)) ?></div>
          <div class="sc-hint">👆 Swipe up for next · left to skip · right to save</div>
        </div>
        <div class="sc-income-row">
          <div class="sc-income-item">
            <div class="sc-income-label">BEGINNER</div>
            <div class="sc-income-val"><?= fmtM((float)$h['income_min']) ?>/month</div>
          </div>
          <div class="sc-income-item">
            <div class="sc-income-label">REGULAR</div>
            <div class="sc-income-val"><?= fmtM(((float)$h['income_min']+(float)$h['income_max'])/2) ?>/month</div>
          </div>
          <div class="sc-income-item">
            <div class="sc-income-label">ADVANCED</div>
            <div class="sc-income-val"><?= fmtM((float)$h['income_max']) ?>/month</div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="swipe-actions">
    <button class="sa-btn sa-skip" id="btn-skip" onclick="swipeAction('skip')">✕</button>
    <a href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($hustles[0]['slug'] ?? '') ?>" class="sa-btn sa-detail" id="btn-detail" style="font-size:18px;color:var(--text2);">📋</a>
    <button class="sa-btn sa-save" id="btn-save" onclick="swipeAction('save')">✏️</button>
  </div>
</div>

<?php elseif ($cat && $mode === 'swipe' && empty($hustles)): ?>
<script>document.body.style.overflow='hidden';</script>
<div id="swipe-view">
  <div class="swipe-header">
    <a href="<?= filterUrl(['mode'=>'browse','cat'=>'','page'=>'']) ?>" class="swipe-back">←</a>
    <div class="swipe-cat-info">
      <div><span class="swipe-cat-icon"><?= $categories[$cat]['icon'] ?? '📦' ?></span><span class="swipe-cat-name"><?= htmlspecialchars($categories[$cat]['label'] ?? ucfirst($cat)) ?> hustles</span></div>
      <div class="swipe-cat-sub">0 ideas</div>
    </div>
  </div>
  <div class="swipe-stage">
    <div style="text-align:center;padding:40px 20px;">
      <div style="font-size:48px;margin-bottom:12px">🎉</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:18px;font-weight:800;color:var(--text2);margin-bottom:8px">You've seen them all!</div>
      <p style="font-size:13px;color:var(--text3);margin-bottom:20px">Browse other categories or search the full list.</p>
      <a href="<?= APP_URL ?>/hustles" style="display:inline-block;background:var(--green);color:#fff;border-radius:12px;padding:12px 24px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;">Browse All →</a>
    </div>
  </div>
</div>

<?php else: ?>
<!-- BROWSE LIST VIEW -->
<div class="browse-section" style="margin-top:16px;">
  <?php if ($cat || $search || $filter || $income): ?>
  <form method="GET" action="<?= APP_URL ?>/hustles" style="margin-bottom:12px;">
    <?php if ($cat):    ?><input type="hidden" name="cat"    value="<?= htmlspecialchars($cat)    ?>"/><?php endif; ?>
    <?php if ($filter): ?><input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"/><?php endif; ?>
    <?php if ($income): ?><input type="hidden" name="income" value="<?= htmlspecialchars($income) ?>"/><?php endif; ?>
    <div class="search-box">
      <span>🔍</span>
      <input type="text" name="q" placeholder="Search by name, keyword, tag…" value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
    </div>
  </form>
  <?php endif; ?>

  <?php if (empty($hustles) && ($cat || $search || $filter || $income)): ?>
    <div class="empty">
      <div class="ei">🔍</div>
      <h3>No hustles found</h3>
      <p>Try different filters or search terms.</p>
    </div>
  <?php elseif (!empty($hustles)): ?>
    <div class="hustle-list">
      <?php foreach ($hustles as $h):
        $isSaved = in_array($h['slug'], $savedSlugs);
        $diffClass = ['beginner'=>'tg','intermediate'=>'to','advanced'=>'tr'][$h['difficulty']] ?? 'tg';
        $diffLabel = ucfirst($h['difficulty']);
      ?>
        <div style="position:relative;">
          <a class="hcard" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
            <div class="hc-icon"><?= htmlspecialchars($h['emoji']) ?></div>
            <div class="hc-body">
              <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
              <div class="hc-desc"><?= htmlspecialchars($h['description'] ?? '') ?></div>
              <div class="hc-meta">
                <span class="hc-income"><?= fmtM((float)$h['income_min']) ?>–<?= fmtM((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></span>
                <span class="tag <?= $diffClass ?>"><?= $diffLabel ?></span>
                <?php if ($h['capital_needed'] == 0): ?><span class="tag tg">₦0 start</span><?php endif; ?>
              </div>
            </div>
            <div class="hc-save <?= $isSaved ? 'saved' : '' ?>" onclick="event.preventDefault();toggleSave(this,'<?= htmlspecialchars($h['slug']) ?>')"><?= $isSaved ? '✓' : '🔖' ?></div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
      <div class="pagination">
        <?php foreach (range(max(1,$page-2), min($pages,$page+2)) as $p): ?>
          <a href="<?= filterUrl(['page'=>$p]) ?>" class="ppage <?= $p==$page?'active':'' ?>"><?= $p ?></a>
        <?php endforeach; ?>
        <?php if ($page < $pages): ?><a href="<?= filterUrl(['page'=>$page+1]) ?>" class="ppage">Next →</a><?php endif; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="swipe-toast" id="swipe-toast"></div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav active"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
// ── Swipe cards ──────────────────────────────────────────────────────────────
const cards = <?= json_encode(array_values($hustles)) ?>;
let currentIdx = 0;

function getCard(idx) {
  return document.getElementById('sc-' + idx);
}

function updateSwipeState() {
  const card = getCard(currentIdx);
  if (!card) return;
  const slug = card.dataset.slug;
  const detail = document.getElementById('btn-detail');
  if (detail) detail.href = '<?= APP_URL ?>/hustle/' + slug;

  // Update progress indicator
  const sub = document.querySelector('.swipe-cat-sub');
  if (sub) sub.textContent = currentIdx + ' of <?= $total ?> ideas';
  const dots = document.querySelectorAll('.sdot');
  dots.forEach((d, i) => {
    d.classList.toggle('active', i === currentIdx);
  });
}

function swipeAction(action) {
  const card = getCard(currentIdx);
  if (!card) return;

  if (action === 'save') {
    const name = card.dataset.name;
    const slug = card.dataset.slug;
    showToast('✅ Saved: ' + name);
    // API save
    fetch('<?= APP_URL ?>/api/saved', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({slug: slug}),
      credentials:'same-origin'
    }).catch(()=>{});
  } else if (action === 'skip') {
    showToast('⏭ Skipped');
  }

  // Animate out
  card.style.transition = 'transform .3s, opacity .3s';
  const tx = action === 'save' ? '60%' : action === 'skip' ? '-60%' : '0';
  const ty = action === 'next' ? '-60%' : '0';
  card.style.transform = `translate(${tx}, ${ty}) rotate(${action==='save'?8:action==='skip'?-8:0}deg)`;
  card.style.opacity = '0';

  setTimeout(() => {
    card.style.display = 'none';
    currentIdx++;
    const next = getCard(currentIdx);
    if (next) {
      next.style.display = 'block';
      next.style.transform = 'translateY(30px)';
      next.style.opacity = '0';
      next.style.transition = 'transform .3s, opacity .3s';
      requestAnimationFrame(() => {
        next.style.transform = 'none';
        next.style.opacity = '1';
      });
      updateSwipeState();
    } else {
      document.getElementById('swipe-stage').innerHTML = '<div style="text-align:center;padding:40px 20px;color:var(--text3)"><div style="font-size:40px;margin-bottom:12px">🎉</div><div style="font-family:\'Bricolage Grotesque\',sans-serif;font-size:18px;font-weight:800;color:var(--text2);margin-bottom:8px">You\'ve seen them all!</div><p style="font-size:13px">Browse other categories or search the full list.</p><a href="<?= APP_URL ?>/hustles" style="display:inline-block;margin-top:16px;background:var(--green);color:#fff;border-radius:12px;padding:12px 24px;font-family:\'Bricolage Grotesque\',sans-serif;font-size:13px;font-weight:800;text-decoration:none;">Browse All →</a></div>';
    }
  }, 300);
}

// Touch/swipe support for swipe cards
let ts = null, tx0 = null, ty0 = null;
document.addEventListener('touchstart', e => {
  const card = document.querySelector('.swipe-card:not([style*="display:none"])');
  if (!card) return;
  ts = Date.now(); tx0 = e.touches[0].clientX; ty0 = e.touches[0].clientY;
}, {passive:true});

document.addEventListener('touchend', e => {
  const card = document.querySelector('.swipe-card:not([style*="display:none"])');
  if (!card || ts === null) return;
  const dx = e.changedTouches[0].clientX - tx0;
  const dy = e.changedTouches[0].clientY - ty0;
  const dt = Date.now() - ts;
  if (Math.abs(dx) > 60 && Math.abs(dx) > Math.abs(dy)) {
    swipeAction(dx > 0 ? 'save' : 'skip');
  } else if (dy < -60 && Math.abs(dy) > Math.abs(dx)) {
    swipeAction('next');
  }
  ts = tx0 = ty0 = null;
}, {passive:true});

// ── Save toggle (browse view) ────────────────────────────────────────────────
function toggleSave(btn, slug) {
  const saved = btn.classList.toggle('saved');
  btn.textContent = saved ? '✓' : '🔖';
  fetch('<?= APP_URL ?>/api/saved', {
    method: saved ? 'POST' : 'DELETE',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify({slug}),
    credentials: 'same-origin'
  }).catch(() => {});
  showToast(saved ? '✅ Saved!' : '🗑 Removed');
}

function showToast(msg) {
  const t = document.getElementById('swipe-toast');
  if (!t) return;
  t.textContent = msg; t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2200);
}
</script>
</body>
</html>
