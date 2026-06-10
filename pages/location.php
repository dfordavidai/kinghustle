<?php
/**
 * HustleKingdom — Location Hub
 * Matches the HustleKingdom v18 design exactly as seen in video.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/Response.php';
Auth::start();

$user  = Auth::isLoggedIn() ? Auth::user() : null;
$isPro = Auth::isPro();

$tab    = $_GET['tab']  ?? 'nigeria';
$zone   = $_GET['zone'] ?? 'all';
$search = trim($_GET['q'] ?? '');

function stateSlug(string $name): string {
    return strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $name));
}

$pageTitle = 'Find Hustles by Location | Hustle Kingdom';
$metaDesc  = 'Find the best side hustles in your Nigerian state or country. 700+ hustle ideas with income data.';
$canonical = APP_URL . '/location';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>"/>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;
  --blue:#2055d4;
  --r:16px;--nav:58px;--bot:64px;--sh:0 2px 16px rgba(0,0,0,.06);
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
.nav-brand .hk-tag{font-size:11px;font-weight:800;color:var(--text3);font-family:'Bricolage Grotesque',sans-serif;border:1.5px solid var(--border);border-radius:6px;padding:1px 6px;margin-left:2px;}
.nav-r{display:flex;gap:8px;align-items:center;}
.nav-icon-btn{width:34px;height:34px;border-radius:50%;background:transparent;border:none;display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;position:relative;}
.nav-bell-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:#cc3333;border-radius:50%;border:2px solid #fff;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,#0d4a6e 0%,#0f6e9e 55%,#1a9ecf 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06);}
.hero-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:26px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;position:relative;z-index:1;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.72);line-height:1.55;position:relative;z-index:1;}

/* ── TABS ── */
.tab-row{display:flex;gap:8px;padding:14px 16px;}
.tab-btn{flex:1;padding:11px;border-radius:12px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;border:none;cursor:pointer;text-align:center;transition:.2s;text-decoration:none;display:block;}
.tab-btn.active{background:var(--green);color:#fff;}
.tab-btn:not(.active){background:var(--surface);color:var(--text2);border:1.5px solid var(--border);}

/* ── SEARCH ── */
.search-wrap{padding:0 16px 12px;}
.search-box{display:flex;gap:8px;align-items:center;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:0 13px;height:46px;}
.search-box:focus-within{border-color:var(--green);}
.search-box input{flex:1;background:none;border:none;outline:none;font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);}
.search-box input::placeholder{color:var(--text3);}
.search-icon{font-size:16px;color:var(--text3);}

/* ── ZONE PILLS ── */
.zone-scroll{display:flex;gap:7px;overflow-x:auto;scrollbar-width:none;padding:0 16px 12px;}
.zone-scroll::-webkit-scrollbar{display:none;}
.zpill{flex-shrink:0;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;cursor:pointer;}
.zpill.active{background:var(--green);border-color:var(--green);color:#fff;}

/* ── STATE GRID ── */
.state-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 16px;}
.scard{background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:13px 14px;display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);cursor:pointer;transition:.15s;}
.scard:active{background:var(--surface);}
.scard.hot{border-color:#b2e0c8;background:var(--green-light);}
.sc-icon{width:40px;height:40px;border-radius:10px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.scard.hot .sc-icon{background:rgba(255,255,255,.7);}
.sc-info{flex:1;min-width:0;}
.sc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;display:flex;align-items:center;gap:5px;flex-wrap:wrap;}
.hot-badge{font-size:9px;font-weight:800;background:var(--green);color:#fff;padding:2px 6px;border-radius:5px;font-family:'Bricolage Grotesque',sans-serif;flex-shrink:0;}
.sc-count{font-size:11px;color:var(--green3);font-weight:700;margin-top:2px;}

/* ── STATE DETAIL PANEL ── */
.state-panel{display:none;flex-direction:column;}
.state-panel.active{display:flex;}
.state-back{display:flex;align-items:center;gap:8px;padding:14px 16px 10px;font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;cursor:pointer;color:var(--text);border:none;background:transparent;text-align:left;}
.state-back-arrow{font-size:18px;color:var(--text3);}

/* State hero */
.state-hero{background:linear-gradient(135deg,#0d6e3c 0%,#16a05a 55%,#1ec870 100%);padding:20px 16px 18px;position:relative;overflow:hidden;}
.state-hero::before{content:'';position:absolute;right:-30px;top:-30px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.07);}
.state-hero-top{display:flex;align-items:center;gap:14px;margin-bottom:12px;position:relative;z-index:1;}
.state-hero-icon{width:54px;height:54px;border-radius:14px;background:rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0;}
.state-hero-text h2{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;color:#fff;margin-bottom:3px;}
.state-hero-text p{font-size:12px;color:rgba(255,255,255,.75);line-height:1.4;}
.state-stats{display:flex;gap:6px;position:relative;z-index:1;}
.stat-box{flex:1;background:rgba(255,255,255,.16);border:1px solid rgba(255,255,255,.22);border-radius:10px;padding:8px 6px;text-align:center;}
.stat-val{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:#fff;}
.stat-lbl{font-size:9px;color:rgba(255,255,255,.7);margin-top:1px;text-transform:uppercase;letter-spacing:.03em;}

/* Market Intelligence */
.market-intel{margin:14px 16px 0;background:var(--gold-light);border:1.5px solid #e8d080;border-radius:var(--r);padding:16px;}
.market-intel-hd{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:var(--gold);display:flex;align-items:center;gap:6px;margin-bottom:8px;}
.market-intel p{font-size:12.5px;color:var(--text2);line-height:1.6;}

/* Best Zones */
.zones-block{margin:14px 16px 0;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:14px;}
.zones-hd{font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);display:flex;align-items:center;gap:6px;margin-bottom:8px;}
.zones-block p{font-size:12px;color:var(--text2);line-height:1.7;}
.zones-block p strong,.zones-block p b{color:var(--text);font-weight:700;}

/* Hustles section */
.hustles-hd{padding:16px 16px 10px;display:flex;align-items:center;gap:6px;}
.hustles-hd-icon{font-size:14px;}
.hustles-hd-text{font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);}
.hustles-hd-sub{font-size:11.5px;color:var(--text3);margin-left:auto;}
.hustles-tap-hint{font-size:11.5px;color:var(--text3);padding:0 16px 10px;}

/* Hustle cards */
.hustle-list{padding:0 16px;display:flex;flex-direction:column;gap:10px;}
.hcard{background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:14px;display:flex;gap:12px;cursor:pointer;transition:.15s;box-shadow:var(--sh);}
.hcard:active{background:var(--surface);}
.hcard-icon{width:46px;height:46px;border-radius:12px;background:var(--green-light);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.hcard-body{flex:1;min-width:0;}
.hcard-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;margin-bottom:4px;}
.hcard-why{font-size:11.5px;color:var(--text3);line-height:1.5;margin-bottom:7px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.hcard-earn{font-size:11.5px;font-weight:800;color:var(--green3);font-family:'Bricolage Grotesque',sans-serif;display:flex;align-items:center;gap:5px;}
.hcard-earn-icon{font-size:12px;}
.hcard-arr{font-size:20px;color:var(--text3);flex-shrink:0;align-self:center;}

/* ── HUSTLE DETAIL SHEET ── */
.sheet-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:400;opacity:0;pointer-events:none;transition:.25s;}
.sheet-overlay.open{opacity:1;pointer-events:all;}
.sheet{position:fixed;bottom:0;left:50%;transform:translateX(-50%) translateY(100%);width:100%;max-width:430px;background:#fff;border-radius:22px 22px 0 0;z-index:401;transition:transform .3s cubic-bezier(.32,0,.15,1);max-height:88vh;overflow-y:auto;padding-bottom:32px;}
.sheet.open{transform:translateX(-50%) translateY(0);}
.sheet-handle{width:36px;height:4px;background:var(--border);border-radius:2px;margin:12px auto 0;}
.sheet-inner{padding:16px 20px 0;}
.sheet-emoji{font-size:44px;margin-bottom:10px;}
.sheet-title{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;margin-bottom:6px;}
.sheet-tags{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;}
.stag{font-size:11px;font-weight:700;padding:4px 10px;border-radius:8px;font-family:'Bricolage Grotesque',sans-serif;}
.stag-level{background:var(--surface);color:var(--text2);border:1px solid var(--border);text-transform:uppercase;}
.stag-level.beginner{background:#ebf7f1;color:var(--green3);border-color:#b2e0c8;}
.stag-level.medium{background:#fdf6e3;color:var(--gold);border-color:#e8d080;}
.stag-level.advanced{background:#fdf0f0;color:#cc3333;border-color:#f5b8b8;}
.stag-match{background:var(--green-light);color:var(--green3);border:1px solid #b2e0c8;}
.sheet-desc{font-size:13px;color:var(--text2);line-height:1.65;margin-bottom:16px;}

/* Quick Facts */
.qf-title{font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:8px;}
.qf-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;}
.qf-box{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:11px 12px;}
.qf-lbl{font-size:9.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--text3);font-weight:700;margin-bottom:3px;}
.qf-val{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:var(--text);}

/* Income potential */
.income-box{background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:var(--r);padding:14px;margin-bottom:16px;}
.income-row{display:flex;align-items:center;justify-content:space-between;padding:5px 0;}
.income-row+.income-row{border-top:1px solid rgba(22,160,90,.12);}
.income-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;}
.income-label{font-size:12.5px;color:var(--text2);flex:1;margin-left:8px;}
.income-val{font-family:'Bricolage Grotesque',sans-serif;font-size:12.5px;font-weight:800;color:var(--green3);}

/* Why this state */
.why-box{background:var(--surface);border:1px solid var(--border);border-radius:var(--r);padding:14px;margin-bottom:16px;}
.why-title{font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.06em;color:var(--text3);margin-bottom:7px;}
.why-text{font-size:12.5px;color:var(--text2);line-height:1.65;}
.why-earn{font-size:12px;font-weight:800;color:var(--green3);margin-top:7px;font-family:'Bricolage Grotesque',sans-serif;}

/* Steps */
.steps-list{list-style:none;display:flex;flex-direction:column;gap:9px;margin-bottom:16px;}
.step-item{display:flex;align-items:flex-start;gap:10px;font-size:12.5px;color:var(--text2);line-height:1.5;}
.step-num{width:22px;height:22px;border-radius:50%;background:var(--green);color:#fff;font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;}

/* CTA */
.sheet-cta{display:block;margin:0 20px 0;text-align:center;background:var(--green);color:#fff;text-decoration:none;border-radius:14px;padding:15px;font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;cursor:pointer;border:none;width:calc(100% - 40px);}

/* ── COUNTRIES COMING SOON ── */
.coming-soon{padding:48px 24px;text-align:center;color:var(--text3);}
.coming-soon-icon{font-size:52px;margin-bottom:14px;}
.coming-soon h3{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;color:var(--text2);margin-bottom:8px;}
.coming-soon p{font-size:13px;line-height:1.6;margin-bottom:20px;}
.btn-green{display:inline-block;background:var(--green);color:#fff;border-radius:12px;padding:12px 24px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
.bnav.active{color:var(--green);}
.bni{font-size:20px;}
.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}
.bnav-ctr .bnl{color:var(--text3);}

/* empty state */
.empty-state{padding:40px 16px;text-align:center;color:var(--text3);}
.empty-state-icon{font-size:42px;margin-bottom:12px;}
.empty-state h3{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:var(--text2);margin-bottom:6px;}
.empty-state p{font-size:13px;line-height:1.6;}
</style>
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
  <a href="<?= APP_URL ?>/" class="nav-brand">
    <div class="nav-logo-box">👑</div>
    Hustle<em>Kingdom</em>
    <span class="hk-tag">HK</span>
  </a>
  <div class="nav-r">
    <?php if ($user): ?>
      <?php if ($isPro): ?><span class="pro-chip">⭐ PRO</span><?php else: ?><a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a><?php endif; ?>
      <button class="nav-icon-btn">📖</button>
      <button class="nav-icon-btn">🖊️<span class="nav-bell-dot"></span></button>
    <?php else: ?>
      <a href="<?= APP_URL ?>/auth/register" class="pro-chip">Get Started →</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HUB PAGE wrapper -->
<div id="hub-page">

  <!-- Hero -->
  <div class="hero">
    <div class="hero-chip">📍 LOCATION HUSTLES</div>
    <div class="hero-title">Find Hustles Near You</div>
    <div class="hero-sub">Browse by Nigerian state or explore 50+ countries worldwide.</div>
  </div>

  <!-- Tabs -->
  <div class="tab-row">
    <button class="tab-btn <?= $tab==='nigeria'?'active':'' ?>" onclick="switchTab('nigeria')">🇳🇬 Nigerian States</button>
    <button class="tab-btn <?= $tab==='countries'?'active':'' ?>" onclick="switchTab('countries')">🌍 Countries</button>
  </div>

  <!-- Nigerian States panel -->
  <div id="tab-nigeria" style="<?= $tab!=='nigeria'?'display:none':'' ?>">
    <!-- Search -->
    <div class="search-wrap">
      <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="state-search" placeholder="Search state…" autocomplete="off" oninput="filterStates()"/>
      </div>
    </div>

    <!-- Zone pills -->
    <div class="zone-scroll" id="zone-pills"></div>

    <!-- State grid -->
    <div class="state-grid" id="state-grid"></div>

    <!-- Empty state -->
    <div id="no-states" class="empty-state" style="display:none">
      <div class="empty-state-icon">🔍</div>
      <h3>No states found</h3>
      <p>Try a different search term or zone.</p>
    </div>
  </div>

  <!-- Countries panel -->
  <div id="tab-countries" style="<?= $tab!=='countries'?'display:none':'' ?>">
    <div class="coming-soon">
      <div class="coming-soon-icon">🌍</div>
      <h3>50+ Countries Coming Soon</h3>
      <p>We're mapping hustle opportunities worldwide. Start with Nigerian states for now.</p>
      <button class="btn-green" onclick="switchTab('nigeria')">Browse Nigerian States</button>
    </div>
  </div>

</div><!-- /hub-page -->

<!-- STATE DETAIL PANEL (slides in) -->
<div id="state-panel" class="state-panel">
  <button class="state-back" onclick="closeState()">
    <span class="state-back-arrow">←</span>
    <span id="state-back-label">Back</span>
  </button>
  <div id="state-detail-content"></div>
</div>

<!-- HUSTLE DETAIL SHEET -->
<div class="sheet-overlay" id="sheet-overlay" onclick="closeSheet()"></div>
<div class="sheet" id="hustle-sheet">
  <div class="sheet-handle"></div>
  <div class="sheet-inner" id="sheet-body"></div>
</div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav active"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
// ══════════════════════════════════════════════════════════
// DATA — extracted directly from HustleKingdom_v18.html
// ══════════════════════════════════════════════════════════
const NIGERIA_STATES = [
  {
    id:'lagos', name:'Lagos', icon:'🏙️', zone:'south-west', hot:true,
    tagline:'Nigeria\'s commercial capital — highest earning potential in Africa',
    population:'22m+', gdp:'₦20 trillion+', internet:'High', economy:'Commerce & Tech',
    topHustles:[
      {id:'pos-agent', why:'Lagos moves more POS transactions than any city in Africa. Oshodi, Mushin, and Ikorodu bus stops mint ₦10k–₦25k/day for agents. Plant yourself at an estate gate or BRT stop and collect.', bonus:'Earn: ₦7k–₦25k/day | Zone: All Lagos'},
      {id:'dropshipping', why:'Balogun Market, Trade Fair, and Alaba give Lagos the cheapest wholesale sourcing in West Africa. Instagram commerce is most evolved here — Lekki influencers sell out in hours.', bonus:'Earn: ₦100k–₦2m/month | Zone: Island, Alaba, Trade Fair'},
      {id:'logistics-dispatch', why:'Lagos traffic turns same-day delivery into a premium service. GIG, Kwik, and Sendbox all started here. A fleet of 5 bikes in Yaba or Surulere earns ₦200k–₦500k/month for the owner.', bonus:'Earn: ₦200k–₦500k/month | Zone: Mainland + Island'},
      {id:'event-planning', why:'Lagos hosts more weddings, brand activations, and owambe celebrations than any city in sub-Saharan Africa. VI and Lekki event planners invoice ₦500k–₦5m per event routinely.', bonus:'Earn: ₦300k–₦5m/event | Zone: VI, Lekki, Ikeja'},
      {id:'short-let-airbnb', why:'Lekki Phase 1, VI, and Ikeja GRA short-let apartments earn ₦30k–₦120k/night. Expats, corporate travellers, and AMVCA/Lagos Fashion Week visitors keep calendars full year-round.', bonus:'Earn: ₦50k–₦120k/night | Zone: Lekki, VI, Ikeja GRA'},
      {id:'social-media-mgmt', why:'Lagos has Nigeria\'s highest concentration of SMEs, startups, and consumer brands — all competing on Instagram. Social media managers in Lagos charge ₦50k–₦300k/client/month. Victoria Island agencies bill in dollars.', bonus:'Earn: ₦300k–₦1.5m/month | Zone: All Lagos — remote'},
      {id:'food-business', why:'Lagos office workers spend ₦1,500–₦3,000 on lunch daily. A WhatsApp-marketed home kitchen near Ikeja GRA, Yaba, or Marina supplies offices and earns ₦50k–₦150k/week without a shop.', bonus:'Earn: ₦50k–₦150k/week | Zone: Ikeja, Yaba, Lagos Island'},
      {id:'real-estate-agent', why:'One Lekki apartment deal at ₦3m/year earns the agent ₦150k–₦300k in a single transaction. Lagos property market churns thousands of listings monthly — agents with good networks clear ₦500k–₦3m/month.', bonus:'Earn: ₦500k–₦3m/month | Zone: Lekki, VI, Ikoyi, Ajah'},
      {id:'cleaning-service', why:'Lagos corporate offices in Ozumba Mbadiwe and Victoria Island, plus estate homes in Chevron and Agungi, pay ₦50k–₦200k for professional cleaning contracts monthly.', bonus:'Earn: ₦300k–₦1.5m/month | Zone: VI, Lekki, Chevron'},
      {id:'digital-marketing-agency', why:'Every Lagos SME from Alaba electronics sellers to Balogun fashion vendors is scrambling to win on Meta and TikTok. A focused agency handling 5 clients at ₦150k/month retainer earns ₦750k monthly.', bonus:'Earn: ₦500k–₦3m/month | Zone: All Lagos — Ikeja & VI are highest paying'},
    ],
    uniqueInsight:'Lagos has 50,000+ small businesses opening monthly. Every one of them needs a website, POS, delivery, or social media manager. The market is inexhaustible. More importantly — Lagos\'s 22+ million people are concentrated into specific zones with wildly different income levels. A business serving Lekki/VI earns 3–5x more per transaction than the same business in Mushin or Ajegunle — but Mushin has 10x the volume. Know your zone, price accordingly. Alimosho alone has more people than some African countries — it is the sleeping giant of Lagos commerce.',
    tip:'**Island Money (premium):** VI, Ikoyi, Lekki Phase 1 — corporate, expat, and high-net-worth clients. **Mainland Volume:** Ikeja, Surulere, Yaba, Mushin — high-traffic, competitive, strong margins on volume. **Growth Zones:** Gbagada, Ojota, Ketu, Ikorodu — underserved markets growing fast. **Emerging Wealth:** Ajah, Abraham Adesanya, Sangotedo — new-money homeowners. **The Sleeping Giant:** Alimosho, Iyana-Ipaja, Alakuko, Agbado, Ikotun, Idimu — Nigeria\'s most densely populated LGA. **Border Opportunity:** Badagry, Ojo, Agbara — proximity to Benin Republic and Ogun State.'
  },
  {
    id:'abuja', name:'Abuja (FCT)', icon:'🏛️', zone:'north-central', hot:true,
    tagline:'Nigeria\'s capital — government money + a booming middle class',
    population:'4m+', gdp:'₦4 trillion+', internet:'Very High', economy:'Government & Services',
    topHustles:[
      {id:'local-government-contractor', why:'Abuja is the government procurement capital of Nigeria. Even a ₦500k stationery contract from a ministry is life-changing. Register on BPP portal and start bidding on small contracts — MDAs issue hundreds monthly.', bonus:'Earn: ₦500k–₦50m/contract | Zone: CBD, Garki, Wuse'},
      {id:'short-let-airbnb', why:'FCT has the highest short-let demand in Nigeria — civil servants, NGO workers, and diplomats need furnished apartments weekly. Maitama, Asokoro, and Wuse 2 listings earn ₦80k–₦200k/night consistently throughout the year.', bonus:'Earn: ₦50k–₦200k/night | Zone: Maitama, Wuse 2, Asokoro, Jabi'},
      {id:'event-planning', why:'Corporate events, government launches, and ministerial functions happen daily in Abuja. Event planners here earn the most per event — a single ECOWAS or World Bank conference engagement pays ₦2m–₦10m.', bonus:'Earn: ₦500k–₦10m/event | Zone: Wuse, Maitama, Transcorp Hilton axis'},
      {id:'ngo-proposal-writing', why:'USAID, EU, UN agencies and hundreds of Nigerian NGOs are headquartered near Abuja. Grant proposal writers earning 5–10% of grant value secure life-changing income. A ₦50m USAID grant is a ₦2.5m–₦5m payday.', bonus:'Earn: ₦500k–₦10m/proposal | Zone: Remote — high-value consulting'},
      {id:'school-tutoring-centre', why:'Abuja\'s middle-class parents spend ₦50k–₦200k/month per child on tutoring. A JAMB and WAEC prep centre in Wuse, Garki, or Gwarinpa with 30 students earns ₦1.5m–₦6m/month with minimal overheads.', bonus:'Earn: ₦1m–₦6m/month | Zone: Wuse, Garki, Gwarinpa, Kubwa'},
      {id:'laundry-dry-cleaning', why:'Abuja civil servants and expats hate manual laundry. A pick-up-and-deliver laundry service serving 3–4 estates in Maitama, Asokoro, or Gwarinpa earns ₦300k–₦1.2m monthly.', bonus:'Earn: ₦300k–₦1.2m/month | Zone: Maitama, Asokoro, Gwarinpa, Jabi'},
      {id:'real-estate-agent', why:'Abuja real estate is the most expensive outside Lagos. One Maitama property deal at ₦80m earns the agent ₦4m–₦8m in a single transaction. Asokoro and Guzape are rising corridors with fast-appreciating land.', bonus:'Earn: ₦500k–₦10m/deal | Zone: Maitama, Asokoro, Guzape, Wuse 2'},
      {id:'digital-marketing-agency', why:'Every Abuja SME, political campaign, and government agency scrambles for digital visibility. A 5-client agency charging ₦200k–₦500k/month per retainer earns ₦1m–₦2.5m monthly with a small team.', bonus:'Earn: ₦1m–₦5m/month | Zone: Remote — Abuja client base'},
      {id:'food-business', why:'Abuja\'s vast civil service means thousands of workers need lunch daily. A WhatsApp food business delivering fresh meals to Wuse 2 offices at ₦2,500–₦4,500/meal serving 100 clients earns ₦250k–₦450k/day gross.', bonus:'Earn: ₦300k–₦1.5m/week | Zone: Wuse 2, Garki, Maitama, Jabi'},
      {id:'solar-installation', why:'FCT\'s unreliable AEDC supply forces every Abuja household and office to invest in solar. Premium residential estates pay ₦2m–₦15m per installation. Government building contracts are ₦50m+ jobs for registered firms.', bonus:'Earn: ₦500k–₦15m/installation | Zone: Maitama, Asokoro, Gwarinpa, Kubwa'},
    ],
    uniqueInsight:'Abuja civil servants earn some of the highest salaries in Nigeria. Target them with premium services: financial coaching, fitness, home improvement, and concierge. Government procurement remains the single biggest income lever — every registered business should be chasing ministry contracts.',
    tip:'Wuse 2, Maitama, and Jabi are the premium zones. Garki and Nyanya for volume. Gwarinpa for estate-based services. Kubwa is an underrated growth corridor with 500k+ residents underserved by quality businesses.'
  },
  {
    id:'kano', name:'Kano', icon:'🏺', zone:'north-west', hot:true,
    tagline:'Nigeria\'s commercial north — textiles, agriculture & mass markets',
    population:'15m+', gdp:'₦3 trillion+', internet:'Medium', economy:'Trade & Agriculture',
    topHustles:[
      {id:'agric-inputs-supply', why:'Kano sits at the heart of Nigeria\'s northern agricultural belt. Fertilizer and pesticide distribution here serves millions of smallholder farmers in Kano, Jigawa, and Katsina. Distributors supplying directly to aggregators earn ₦2m–₦5m/month in season.', bonus:'Earn: ₦300k–₦5m/month | Zone: Dawanau, Kofar Ruwa, Sharada'},
      {id:'wholesale-distribution', why:'Kano is Nigeria\'s northern distribution capital. Products move from Kano to 11 northern states. Becoming an authorized distributor for any FMCG brand — Indomie, Dangote, BUA, Peak Milk — earns ₦500k–₦10m/month at volume.', bonus:'Earn: ₦500k–₦10m/month | Zone: Sharada Industrial, Bompai Industrial'},
      {id:'second-hand-market', why:'Okirika (used clothing) business is deeply embedded in Kano\'s trade culture. Bale importers from Apapa supply Kano traders who sell to northern states. Buying ₦100k in bales and selling for ₦400k–₦600k is a weekly Kano cycle.', bonus:'Earn: ₦200k–₦1m/month | Zone: Sabon Gari, Kurmi, Singer Markets'},
      {id:'tailoring-fashion', why:'Northern fashion — babban riga, kaftan embroidery, and festive attire — is an all-year business in Kano. A skilled tailor near Kofar Wambai produces 5–10 embroidered pieces daily at ₦15k–₦80k each. Eid season earns 6 months\' income in 3 weeks.', bonus:'Earn: ₦200k–₦1.5m/month | Zone: Kofar Wambai, Fagge, Singer Market'},
      {id:'onion-tomato-trade', why:'Kano\'s Dawanau Market is Nigeria\'s onion trading capital. Buying at harvest (August–October) at ₦5k–₦8k/bag and selling in off-season at ₦30k–₦60k/bag earns 5–10× returns. Kano onion traders are some of Nigeria\'s wealthiest commodity dealers.', bonus:'Earn: ₦1m–₦20m/season | Zone: Dawanau Market, Kano–Katsina Road'},
      {id:'sesame-export', why:'Kano and surrounding states grow 60% of Nigeria\'s sesame seed. An aggregator collecting from farmers at ₦300k/tonne and selling to Alibaba export buyers at $600–$900/tonne earns ₦400k–₦2m per tonne on FX spread alone.', bonus:'Earn: ₦1m–₦20m/shipment | Zone: Dawanau Market, Sharada, Kano Export Hub'},
      {id:'leather-goods-production', why:'Kano\'s Yan Kara leather market is one of Africa\'s oldest and most productive. Sourcing hides locally and producing shoes, belts, bags, and wallets earns massive margins when sold to Lagos, Abuja boutiques, or exported to West African countries.', bonus:'Earn: ₦300k–₦2m/month | Zone: Yan Kara Market, Wudil, Kofar Kabuga'},
      {id:'groundnut-oil-production', why:'Kano sits inside Nigeria\'s groundnut belt. A small oil pressing mill sourcing locally at ₦300k/tonne and selling refined oil in 25-litre kegs earns ₦200k–₦1m per production run.', bonus:'Earn: ₦300k–₦2m/month | Zone: Kano Central, Wudil, Bunkure'},
      {id:'haulage-truck', why:'Kano is the logistics hub of northern Nigeria. Trucks running Kano–Lagos, Kano–Abuja, and Kano–Port Harcourt earn ₦500k–₦2.5m monthly on agricultural produce, manufactured goods, and FMCG cargo.', bonus:'Earn: ₦400k–₦2.5m/month | Zone: Dawanau Market, Sharada, Bompai'},
      {id:'perfume-oil-business', why:'Northern Nigeria\'s perfume culture is among the world\'s most sophisticated — oud, musk, and attar are daily essentials. A perfume oil trader in Sabon Gari sourcing from Dubai or Indian suppliers earns ₦300k–₦3m monthly during festive seasons.', bonus:'Earn: ₦200k–₦3m/month | Zone: Sabon Gari, Kurmi Market, Kofar Wambai'},
    ],
    uniqueInsight:'Kano is a distribution hub — products move from here to 11 northern states. Becoming a wholesale distributor of any FMCG, agricultural input, or commodity earns scale income impossible in other cities. The Dawanau grain market and Yan Kara leather market are world-class commercial ecosystems most entrepreneurs outside Kano haven\'t discovered.',
    tip:'Sabon Gari, Kurmi Market, and Sharada Industrial Area are your prime zones for business activity. Dawanau Market is the secret weapon — it supplies all of northern Nigeria. Nassarawa GRA and Bompai for premium services.'
  },
  {
    id:'rivers', name:'Rivers', icon:'⛽', zone:'south-south', hot:true,
    tagline:'Oil city — high incomes, high demand for premium services',
    population:'8m+', gdp:'₦5 trillion+', internet:'High', economy:'Oil & Gas',
    topHustles:[
      {id:'oil-gas-vendor-supplies', why:'Every oil company operating in Rivers needs consumables — PPE, office supplies, cleaning, catering. CAC-registered vendors with NIPEX portal access win contracts from ₦500k–₦50m. One Chevron or SPDC contract changes a business permanently.', bonus:'Earn: ₦500k–₦50m/contract | Zone: Trans-Amadi, GRA, Rumuola'},
      {id:'catering-event-food', why:'Expat communities, oil company canteens, and Nigerian oil workers in Port Harcourt pay ₦3,000–₦8,000 per meal × 500 employees. A registered catering firm with one oil company canteen contract earns ₦3m–₦15m/month.', bonus:'Earn: ₦2m–₦15m/month | Zone: Trans-Amadi, GRA, Rumuola'},
      {id:'short-let-airbnb', why:'Expat workers on rotation need furnished apartments. Short-let in Port Harcourt GRA earns the highest rates in the south.', bonus:'Earn: ₦60k–₦250k/night'},
      {id:'event-planning', why:'Port Harcourt weddings and oil company events are among Nigeria\'s most expensive. An event planner in PH handles ₦5m–₦50m budgets routinely, especially in GRA and Rumuola.', bonus:'Earn: ₦500k–₦5m/event'},
      {id:'cleaning-service', why:'Oil companies, their contractors, and expat estates in GRA and Rumuobiakani pay ₦200k–₦1m/month for facility management. Industrial cleaning for Trans-Amadi factories pays ₦500k–₦3m per contract.', bonus:'Earn: ₦500k–₦3m/month | Zone: Trans-Amadi, GRA, Eliozu'},
      {id:'real-estate-agent', why:'Port Harcourt GRA, Peter Odili Road, and Rumuola are among Nigeria\'s most expensive land markets. One land deal earns ₦500k–₦5m in commission. Oil company staff constantly need housing.', bonus:'Earn: ₦500k–₦5m/deal'},
      {id:'haulage-truck', why:'Trans-Amadi industrial layout and oil company supply chains need constant haulage. One 10-ton truck on contract with an oil vendor earns ₦800k–₦3m per month in logistics fees.', bonus:'Earn: ₦800k–₦3m/month'},
      {id:'interior-decoration', why:'New GRA apartments and Trans-Amadi executive offices need full interior fit-outs. Expat-standard decoration jobs pay ₦1m–₦20m per project in Port Harcourt.', bonus:'Earn: ₦500k–₦20m/project'},
      {id:'solar-installation', why:'PHED power supply in Rivers is among the worst in Nigeria. Homes and offices pay ₦500k–₦5m for solar systems. GRA residents and oil companies pay premium without negotiating.', bonus:'Earn: ₦200k–₦2m/installation'},
      {id:'palm-oil-trading', why:'Rivers State has abundant palm oil from Ikwerre and Etche LGAs. A bulk trader buying in Igwuruta and selling to Lagos markets earns ₦200k–₦1m per truck consignment.', bonus:'Earn: ₦300k–₦1.5m/month'},
    ],
    uniqueInsight:'Rivers State\'s oil economy means corporate clients pay premium prices without negotiating. One oil company vendor contract can change your business permanently — focus on B2B services, not B2C, for the highest returns. Port Harcourt also has Nigeria\'s highest concentration of expatriates outside Abuja, creating a premium consumer market.',
    tip:'Trans-Amadi Industrial Layout, GRA Phase 1 & 2, and D-Line are the premium business zones. Mile 3 and Creek Road for volume markets.'
  },
  {
    id:'anambra', name:'Anambra', icon:'🏪', zone:'south-east', hot:true,
    tagline:'The commercial south-east — Onitsha is Nigeria\'s biggest market',
    population:'6m+', gdp:'₦2 trillion+', internet:'High', economy:'Trade & Commerce',
    topHustles:[
      {id:'auto-spare-parts', why:'Nnewi is Nigeria\'s auto parts capital — the largest auto spare parts market in West Africa. Trading imported and locally manufactured parts from Nnewi to mechanics across the south earns ₦500k–₦10m/month. One container of Honda or Toyota parts pays school fees for years.', bonus:'Earn: ₦500k–₦10m/month | Zone: Nnewi (main market), Onitsha auto zone'},
      {id:'export-trade', why:'Made-in-Aba and Made-in-Nnewi products — shoes, bags, auto parts, garments — have buyers in the UK, USA, Italy, and East Africa. An export agent connecting Onitsha traders to diaspora buyers earns 5–15% commission on every container.', bonus:'Earn: ₦500k–₦5m/container | Zone: Onitsha, Nnewi, Awka'},
      {id:'dropshipping', why:'Onitsha Main Market is one of the largest markets in Africa. Sourcing and reselling from here creates massive margins across Nigeria.', bonus:'Earn: ₦100k–₦1m/month'},
      {id:'event-planning', why:'Anambra\'s Igbo tradition of elaborate celebrations — Ichi title-taking, Ofala festival, premium weddings — makes event planning a premium business. Onitsha events earn ₦1m–₦20m per planner.', bonus:'Earn: ₦500k–₦10m/event'},
      {id:'real-estate-agent', why:'Awka (state capital), Onitsha (commercial hub), and Nnewi (industrial city) are all appreciating markets. Diaspora investment drives Anambra\'s real estate — one deal earns ₦500k–₦10m.', bonus:'Earn: ₦500k–₦10m/deal'},
      {id:'interior-decoration', why:'Anambra\'s diaspora returnees build premium homes in Awka, Onitsha GRA, and Nnewi. Interior decoration jobs for Igbo diaspora homes earn ₦1m–₦30m per project — cash payments are standard.', bonus:'Earn: ₦1m–₦30m/project'},
      {id:'logistics-dispatch', why:'Onitsha Main Market is Nigeria\'s largest market and import hub. Dispatch and haulage between Onitsha, Nnewi, Awka, and Lagos is one of the most profitable logistics routes in West Africa.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'tailoring-fashion', why:'Igbo traditional wear — George wrappers, Isiagu fabric, Oji — is elaborate. Anambra people invest heavily in asoebi for events. A skilled tailor in Onitsha or Awka earns ₦200k–₦1.5m/month.', bonus:'Earn: ₦200k–₦1.5m/month'},
      {id:'solar-installation', why:'EEDC power supply in Anambra is poor. Onitsha\'s dense commercial activity and Awka government facilities run on generators — solar is the upgrade. Commercial installations earn ₦500k–₦10m.', bonus:'Earn: ₦300k–₦5m/installation'},
      {id:'cassava-processing', why:'Anambra River and its tributaries support cassava farming at scale. Processing garri for Lagos wholesale earns ₦300k–₦2m per production run.', bonus:'Earn: ₦300k–₦2m/month'},
    ],
    uniqueInsight:'Anambra people travel to trade. If you build a business here, your customers will be from across the south-east. Think distribution, not just local retail.',
    tip:'Onitsha Main Market, Nnewi (auto parts capital), and Awka city centre are the highest-earning zones.'
  },
  {
    id:'oyo', name:'Oyo', icon:'🦋', zone:'south-west', hot:false,
    tagline:'Ibadan — Nigeria\'s largest city by land area, academic hub',
    population:'8m+', gdp:'₦2.5 trillion+', internet:'High', economy:'Education & Commerce',
    topHustles:[
      {id:'cocoa-aggregator', why:'Oyo and Osun are Nigeria\'s top cocoa states. Licensed aggregators buying from smallholder farmers in Saki, Iseyin, and Igboho and selling to export companies earn ₦500k–₦5m per tonne above farmgate price.', bonus:'Earn: ₦500k–₦5m/tonne cycle | Zone: Saki, Iseyin, Igboho, Oyo town'},
      {id:'tutoring-online', why:'Ibadan has the highest density of universities in Nigeria including UI, LAUTECH, and Polytechnic Ibadan. Tutoring demand is immense.', bonus:'Earn: ₦100k–₦500k/month'},
      {id:'hotel-guesthouse', why:'Ibadan\'s conference tourism — IITA, UI conferences, and NAFDAC events — means hotel demand is strong year-round. A clean 10-room guesthouse near the academic district earns ₦500k–₦2m/month.', bonus:'Earn: ₦500k–₦2m/month | Zone: Bodija, Jericho, Agodi GRA, UI area'},
      {id:'event-catering', why:'Ibadan is Nigeria\'s owambe capital. The city hosts Nigeria\'s biggest parties — UI events, Ibadan social gatherings, and Yoruba celebrations that feed 500–5,000 guests. Catering one big event earns ₦500k–₦5m.', bonus:'Earn: ₦300k–₦5m/event'},
      {id:'cassava-processing', why:'Oyo State\'s Ibarapa, Oke-Ogun, and Ogbomoso zones are Nigeria\'s cassava belt. Processing cassava into garri and cassava flour for Lagos wholesale earns ₦300k–₦2m per production batch.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'real-estate-agent', why:'Ibadan is Nigeria\'s largest city by area. Bodija, Jericho, and Agodi GRA attract Lagos returnees and diaspora buyers. Property prices are 40–60% below Lagos but appreciating 20% yearly.', bonus:'Earn: ₦300k–₦8m/deal'},
      {id:'food-business', why:'Ibadan\'s large student and civil service population creates huge demand for affordable home-style meals. A WhatsApp-marketed kitchen near UI, Polytechnic Ibadan, or Ring Road supplying boxed lunches earns ₦30k–₦80k/day.', bonus:'Earn: ₦100k–₦400k/month | Zone: UI area, Ring Road, Dugbe, Sango'},
      {id:'solar-installation', why:'IBEDC power supply in Oyo State is poor. Ibadan\'s massive industrial area, University of Ibadan, and residential zones all invest in solar. Commercial installations earn ₦300k–₦8m each.', bonus:'Earn: ₦300k–₦5m/installation'},
      {id:'photography-videography', why:'Ibadan owambe parties are Nigeria\'s most famous celebrations. A photographer serving Bodija, Jericho, and UI events earns ₦150k–₦1.5m per event — weekend bookings are always full.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'tailoring-fashion', why:'Ibadan\'s Yoruba fashion culture — Aso-oke weaving, agbada tailoring, gele tying — is elaborate. Custom Yoruba traditional wear for owambe parties earns ₦20k–₦200k per outfit.', bonus:'Earn: ₦200k–₦1.5m/month'},
    ],
    uniqueInsight:'Ibadan\'s low cost of living vs Lagos means your profit margins are higher. A business earning ₦500k/month in Ibadan leaves you with more real money than the same earning in Lagos.',
    tip:'UI (University of Ibadan) area, Dugbe market, Ring Road, and Challenge are the commercial hotspots.'
  },
  {id:'delta', name:'Delta', icon:'🌊', zone:'south-south', hot:false,
    tagline:'Oil delta — Warri commerce meets agricultural wealth',
    population:'5.5m+', gdp:'₦2 trillion+', internet:'Medium-High', economy:'Oil & Agriculture',
    topHustles:[
      {id:'fish-farming', why:'Delta\'s waterways and Niger River tributaries make it one of Nigeria\'s best states for catfish and tilapia farming. A 10-pond farm in Asaba or Warri periphery earns ₦500k–₦2m per harvest cycle.', bonus:'Earn: ₦500k–₦2m/harvest | Zone: Asaba outskirts, Warri periphery, Sapele'},
      {id:'real-estate-agent', why:'Asaba is experiencing a real estate boom driven by its position opposite Onitsha. Land flipping and property deals near the Niger Bridge earn ₦200k–₦2m per deal as the city expands rapidly.', bonus:'Earn: ₦500k–₦5m/month | Zone: Asaba GRA, Okpanam Road, Ibusa Road'},
      {id:'oil-gas-vendor-supplies', why:'Delta hosts Warri Refinery, NNPC, Agip, and Chevron operations. CAC-registered vendors with DPR certification earn ₦500k–₦50m per supply contract. Warri\'s oil economy rivals Port Harcourt.', bonus:'Earn: ₦500k–₦50m/contract'},
      {id:'palm-oil-processing', why:'Delta is in Nigeria\'s palm oil belt. Processing and selling palm oil is a major income source with huge wholesale demand.', bonus:'Earn: ₦100k–₦1m/batch'},
      {id:'event-catering', why:'Warri and Asaba host big owambe parties. Catering for Urhobo, Ijaw, and Ibo celebrations is a lucrative market.', bonus:'Earn: ₦150k–₦800k/event'},
      {id:'short-let-airbnb', why:'Asaba is growing fast with new businesses. Furnished short-let apartments near the bridge serve Onitsha traders and Abuja travellers.', bonus:'Earn: ₦30k–₦80k/night'},
      {id:'cassava-processing', why:'Delta\'s Ndokwa, Oshimili, and Aniocha LGAs are Nigeria\'s cassava heartlands. Processing garri for Lagos/Abuja markets earns ₦300k–₦2m per production run on near-zero raw material cost.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'event-planning', why:'Delta State hosts massive celebrations — Urhobo Urhoro festival, Itsekiri Iwere festivals, and some of Nigeria\'s biggest weddings. Warri and Asaba event planners earn ₦500k–₦5m per event.', bonus:'Earn: ₦300k–₦3m/event'},
      {id:'solar-installation', why:'BEDC power supply is unreliable across Delta State. Warri businesses, Asaba corporate offices, and Ughelli estates all need solar. One commercial installation earns ₦500k–₦5m.', bonus:'Earn: ₦300k–₦3m/month'},
      {id:'smoked-fish-processing', why:'Delta\'s creeks — Warri, Burutu, Patani — teem with catfish, tilapia, and bonga. A smoked fish processor buying fresh catch at ₦800/kg and selling processed at ₦4,000/kg earns 5× margin.', bonus:'Earn: ₦300k–₦2m/month'},
    ],
    uniqueInsight:'Asaba is growing into a major economic hub, attracting businesses relocating from Lagos and Port Harcourt. Early movers in Asaba are building first-mover advantage across multiple sectors.',
    tip:'Asaba (near the bridge), Warri\'s Effurun axis, and Ughelli are the key commercial zones.'
  },
  {id:'kaduna', name:'Kaduna', icon:'⚙️', zone:'north-west', hot:true,
    tagline:'Industrial north — textiles, manufacturing, military & rising middle class',
    population:'8.5m+', gdp:'₦1.8 trillion+', internet:'Medium', economy:'Industry & Agriculture',
    topHustles:[
      {id:'tailoring-school', why:'Kaduna is Nigeria\'s textile capital — Arewa Textiles, Kaduna Textile Ltd, and dozens of mills create a culture where sewing is a primary skill. Fashion sewing schools in Kawo and Malali fill immediately. 30 students at ₦30k–₦80k/term earns ₦900k–₦2.4m per term.', bonus:'Earn: ₦400k–₦2.4m/term | Zone: Kawo, Malali, Tudun Wada, Barnawa'},
      {id:'agric-inputs-supply', why:'Kaduna State is one of Nigeria\'s top maize, sorghum, and soya producers. Fertilizer and pesticide distribution to Kachia, Kagarko, and Igabi LGA farmers is extremely high volume.', bonus:'Earn: ₦300k–₦5m/month | Zone: Kachia Road, Kagarko, Kaduna South Market'},
      {id:'ginger-processing', why:'Kaduna and Kaduna State\'s Kachia, Sanga, and Kagarko LGAs are major ginger-producing areas. Washing, drying, and packaging dried ginger for export earns ₦5,000–₦8,000/kg (vs ₦200–₦500/kg raw). One tonne processed earns ₦4m–₦7m in export markets.', bonus:'Earn: ₦500k–₦7m/tonne | Zone: Kachia, Sanga, Kagarko LGAs'},
      {id:'haulage-truck', why:'Kaduna sits on the Lagos–Kano corridor — the busiest trade route in northern Nigeria. Trucks running Kaduna–Lagos and Kaduna–Kano routes earn ₦500k–₦2m/month on consistent agricultural and FMCG cargo.', bonus:'Earn: ₦400k–₦2m/month | Zone: Kawo Transport Hub, Kaduna–Kano Road'},
      {id:'solar-installation', why:'KEDC power supply in Kaduna is unreliable — factories in Kakuri and offices in CBD run on generators. Solar is the next step. A solar company serving Kaduna\'s industrial corridor and military estates earns ₦500k–₦10m per installation project.', bonus:'Earn: ₦300k–₦10m/installation | Zone: Kakuri Industrial, GRA, Barnawa, Kawo'},
      {id:'real-estate-agent', why:'Kaduna\'s GRA, Barnawa, and Narayi are growing residential zones attracting military families, civil servants, and investors. Land in Kaduna appreciates 15–25% annually.', bonus:'Earn: ₦300k–₦5m/deal | Zone: GRA Kaduna, Barnawa, Narayi, Rigasa'},
      {id:'cotton-textile-trade', why:'Kaduna\'s cotton-to-fabric value chain is reviving. Trading finished northern fabric (atampa, guinea brocade) wholesaling to southern markets earns ₦300k–₦3m monthly.', bonus:'Earn: ₦300k–₦3m/month | Zone: Textile Mill Road, Kakuri, Kaduna North'},
      {id:'tomato-paste-production', why:'Kaduna and surrounding states supply Nigeria\'s tomato belt. A paste processing plant in Kaduna sourcing directly from Jos plateau farmers earns ₦300k–₦3m per production batch.', bonus:'Earn: ₦300k–₦3m/batch | Zone: Kakuri Industrial, Kacho, Kaduna South'},
      {id:'digital-marketing-agency', why:'Kaduna\'s growing startup ecosystem, political campaigns, and SMEs in Kawo and Barnawa need digital marketing. Political clients pay ₦2m–₦20m per campaign — and Kaduna has election cycles every 4 years.', bonus:'Earn: ₦300k–₦2m/month | Zone: Remote — Kaduna client base'},
      {id:'interior-decoration', why:'Kaduna\'s military officers, civil servants, and diaspora returnees build and renovate homes in GRA and Barnawa regularly. Interior decoration projects earn ₦500k–₦20m each.', bonus:'Earn: ₦500k–₦20m/project | Zone: GRA Kaduna, Barnawa, Narayi, Kaduna North'},
    ],
    uniqueInsight:'Kaduna\'s textile mills are reviving under government investment. Supplying services, consumables, and skilled labour to these mills is a massive opportunity most entrepreneurs overlook. The military economy — NDA, army commands, air force — creates institutional purchasing power that civilian businesses rarely target but consistently win when they do.',
    tip:'Kaduna North (Kawo), Barnawa, and the Tudun Wada axis are the busiest commercial zones. GRA Kaduna for premium services. Kakuri Industrial for B2B contracts. Rigasa is an emerging corridor worth watching.'
  },
  {id:'enugu', name:'Enugu', icon:'⛏️', zone:'south-east', hot:false,
    tagline:'Coal city — Igbo commercial spirit meets academic excellence',
    population:'4.5m+', gdp:'₦1.5 trillion+', internet:'Medium-High', economy:'Commerce & Education',
    topHustles:[
      {id:'real-estate-agent', why:'Enugu is the south-east\'s administrative capital. Independence Layout, GRA, and Trans-Ekulu are premium real estate zones attracting diaspora buyers. One deal earns ₦300k–₦8m in commission.', bonus:'Earn: ₦300k–₦8m/deal'},
      {id:'event-catering', why:'Enugu\'s status as south-east capital means big government functions, university events, and lavish Igbo weddings. A professional caterer earns ₦500k–₦5m per event in this premium market.', bonus:'Earn: ₦300k–₦5m/event'},
      {id:'solar-installation', why:'EEDC power supply in Enugu is poor. Government residences, GRA estates, and UNEC campus facilities all need solar. Commercial installations for government clients earn ₦500k–₦10m.', bonus:'Earn: ₦300k–₦5m/installation'},
      {id:'government-cleaning-contract', why:'Enugu\'s government ministries, UNN facilities, and GRA residences need professional cleaning. Government cleaning contracts are especially lucrative — ₦300k–₦2m/month.', bonus:'Earn: ₦200k–₦2m/month'},
      {id:'event-planning', why:'Enugu is a major event destination for the south-east. Elaborate Igbo celebrations drive consistent event planning revenue.', bonus:'Earn: ₦200k–₦1m/event'},
      {id:'tailoring-fashion', why:'Enugu\'s Igbo fashion culture — Isi-agu wear, George wrappers, elaborate asoebi — means skilled tailors are always in demand. Corporate and ceremonial tailoring earns ₦20k–₦200k per outfit.', bonus:'Earn: ₦200k–₦1.5m/month'},
      {id:'catfish-farming', why:'Enugu\'s Oji River and Anambra River tributaries are ideal for catfish. Farmers in Oji River LGA supply Enugu city markets at ₦2,500–₦3,500/kg — a 2,000-fish pond earns ₦1m per 6-month cycle.', bonus:'Earn: ₦300k–₦1.5m/cycle'},
      {id:'palm-oil-trading', why:'Enugu\'s Nkanu, Igbo-Eze, and Oji River LGAs produce significant palm oil. A bulk trader in Enugu buying at ₦1,000/litre and selling wholesale to Lagos earns ₦200k–₦1m per truck.', bonus:'Earn: ₦200k–₦1m/month'},
      {id:'interior-decoration', why:'Enugu\'s diaspora returnees and government officials build premium homes in GRA and Independence Layout. Interior decoration projects from this clientele earn ₦1m–₦20m each.', bonus:'Earn: ₦500k–₦20m/project'},
      {id:'haulage-truck', why:'Enugu is a distribution point for south-east goods heading north. Haulage from Enugu to Abuja, Kano, and Port Harcourt earns ₦400k–₦2m monthly on consistent cargo routes.', bonus:'Earn: ₦400k–₦2m/month'},
    ],
    uniqueInsight:'Enugu is the administrative capital of the south-east. Government contracts, NGO work, and university supply contracts are available and far less competitive than in Lagos or Abuja.',
    tip:'Ogui Road, New Market, Independence Layout, and around UNEC are the highest-traffic commercial zones.'
  },
  {id:'ogun', name:'Ogun', icon:'📦', zone:'south-west', hot:false,
    tagline:'Gateway state — industrial hub between Lagos and the interior',
    population:'5.5m+', gdp:'₦2 trillion+', internet:'High', economy:'Industry & Commerce',
    topHustles:[
      {id:'factory-supply-b2b', why:'Ogun\'s Ota, Sagamu, and Agbara industrial estates house 200+ factories. Supplying them with consumables — cleaning materials, safety gloves, stationery, canteen food — on monthly contracts earns ₦500k–₦5m/month with minimal marketing once you\'re in.', bonus:'Earn: ₦500k–₦5m/month | Zone: Ota Industrial, Sagamu, Agbara'},
      {id:'cassava-processing', why:'Ogun is Nigeria\'s second-largest cassava producing state. A garri and cassava starch processing mini-factory here, 90 minutes from Lagos, supplies Lagos\'s insatiable demand at a 40–80% margin over farmgate.', bonus:'Earn: ₦1m–₦8m/month | Zone: Abeokuta outskirts, Ijebu-Ode, Odogbolu'},
      {id:'real-estate-agent', why:'Ogun is Nigeria\'s fastest-growing real estate market after Lagos. Mowe, Sagamu, and Abeokuta GRA attract Lagos workers seeking affordable housing. Land values rise 25–40% annually.', bonus:'Earn: ₦500k–₦10m/deal'},
      {id:'industrial-safety-training', why:'Ogun has Nigeria\'s highest industrial density — Sagamu, Agbara, and Otta zones host 1,000+ factories. HSE training certification earns ₦500k–₦5m per corporate training contract.', bonus:'Earn: ₦500k–₦5m/contract'},
      {id:'solar-installation', why:'IBEDC power in Ogun is unreliable despite the industrial base. Every factory in Agbara and Sagamu needs industrial solar or generator — solar installations earn ₦1m–₦20m for industrial clients.', bonus:'Earn: ₦500k–₦10m/installation'},
      {id:'palm-oil-processing', why:'Ogun\'s rainforest belt produces abundant palm fruits. Small-scale oil processing for Lagos wholesale is a strong margin business.', bonus:'Earn: ₦100k–₦800k/batch'},
      {id:'event-catering', why:'Ogun\'s owambe culture mirrors Lagos. Sagamu, Abeokuta, and Ijebu-Ode weddings feed hundreds to thousands. Professional caterers earn ₦300k–₦5m per event.', bonus:'Earn: ₦300k–₦5m/event'},
      {id:'haulage-truck', why:'Ogun\'s factories produce goods for Lagos distribution and import raw materials. A truck on factory-to-market routes earns ₦400k–₦2.5m monthly — industrial logistics is the most reliable cargo.', bonus:'Earn: ₦400k–₦2.5m/month'},
      {id:'logistics-dispatch', why:'Ogun\'s Sagamu interchange and Berger make it the logistics crossroads of south-west Nigeria. Dispatch businesses thrive here.', bonus:'Earn: ₦150k–₦800k/month'},
      {id:'generator-repair', why:'Ogun\'s factory belt never stops — IBEDC cuts mean every industrial client needs 24/7 generator maintenance. An industrial generator mechanic in Agbara earns ₦500k–₦3m monthly on maintenance contracts alone.', bonus:'Earn: ₦300k–₦3m/month'},
    ],
    uniqueInsight:'Ogun\'s Ota industrial estate is Nigeria\'s largest. Supplying consumables, uniforms, printing, cleaning, and catering to factories here is a repeatable B2B income.',
    tip:'Ota (factories), Sagamu (transport hub), Abeokuta (tourism & government), and Ijebu-Ode (trade) are the four zones to focus on.'
  },
  {id:'imo', name:'Imo', icon:'🌿', zone:'south-east', hot:false,
    tagline:'Heartland state — Owerri\'s vibrant entertainment and commerce',
    population:'4m+', gdp:'₦1.5 trillion+', internet:'Medium-High', economy:'Commerce & Entertainment',
    topHustles:[
      {id:'nightlife-venue-bar', why:'Owerri\'s "No Sleep City" tag is earned — the nightlife economy is Nigeria\'s most vibrant outside Lagos. A bar, lounge, or outdoor spot in the Ikenegbu, Okigwe Road, or Douglas Road axis earns ₦300k–₦2m on a single Friday or Saturday night.', bonus:'Earn: ₦1m–₦8m/month | Zone: Ikenegbu, Douglas Road, Okigwe Road'},
      {id:'real-estate-agent', why:'Owerri is known as Nigeria\'s most enjoyable city — MCC Road, New Owerri, and Nekede attract Igbo diaspora buyers constantly. Land prices rise 20–35% yearly. One deal earns ₦300k–₦8m.', bonus:'Earn: ₦300k–₦8m/deal'},
      {id:'hotel-guesthouse', why:'Owerri\'s entertainment reputation means hotels fill up every weekend. A 10-room guesthouse on Wetheral Road earns ₦15k–₦30k/room/night — ₦2m–₦6m monthly with consistent occupancy.', bonus:'Earn: ₦500k–₦5m/month'},
      {id:'event-catering', why:'Owerri is called the "No-Sleep City." Night events, owambes, and celebrations make catering one of the most lucrative businesses here.', bonus:'Earn: ₦150k–₦1m/event'},
      {id:'interior-decoration', why:'New Owerri estates and Owerri\'s diaspora-built homes need premium interior fit-outs. Decoration projects for Igbo returnees earn ₦1m–₦25m each — a lucrative niche.', bonus:'Earn: ₦1m–₦25m/project'},
      {id:'solar-installation', why:'EEDC power supply in Imo is consistently poor. Owerri\'s nightlife district, Douglas Road businesses, and New Owerri homes all invest in solar. Commercial installations earn ₦500k–₦8m.', bonus:'Earn: ₦300k–₦5m/installation'},
      {id:'photography-videography', why:'Owerri\'s premium social scene — Imo State Governor events, fancy weddings, alumni gatherings — makes it one of south-east\'s best photography markets. Events pay ₦200k–₦2m per package.', bonus:'Earn: ₦200k–₦2m/month'},
      {id:'catfish-farming', why:'Imo\'s Oguta Lake and Orlu river system are ideal for catfish farming. Oguta farmers supply Owerri restaurants and Lagos traders at ₦2,500–₦3,500/kg. A large pond earns ₦1m per cycle.', bonus:'Earn: ₦300k–₦1.5m/cycle'},
      {id:'palm-kernel-oil-production', why:'Imo\'s Ohaji-Egbema and Oguta LGAs have abundant oil palms. Palm kernel oil production for Lagos soap manufacturers earns ₦300k–₦2m per production batch.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'event-planning', why:'Imo\'s celebration culture is legendary. An event planner established in Owerri can book almost every weekend.', bonus:'Earn: ₦200k–₦1.5m/event'},
    ],
    uniqueInsight:'Owerri\'s entertainment economy is unique in Nigeria — it runs 7 days a week. Any business serving nightlife, events, or the well-paid civil service and diaspora returnee population earns 30–50% more per transaction than the same business elsewhere in the south-east.',
    tip:'Owerri municipal, Douglas Road, Ikenegbu, and Orlu Road are the commercial hotspots.'
  },
  {id:'cross-river', name:'Cross River', icon:'🌴', zone:'south-south', hot:false,
    tagline:'Tourism capital — Calabar\'s carnival and forest reserves',
    population:'3.8m+', gdp:'₦1 trillion+', internet:'Medium', economy:'Tourism & Agriculture',
    topHustles:[
      {id:'eco-tourism-guide', why:'Cross River is Nigeria\'s tourism capital — Drill Ranch, Obudu Cattle Ranch, Calabar Carnival, and Cross River National Park. Licensed tour guides earn ₦50k–₦500k per group tour and corporate bookings are year-round.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'event-planning', why:'Calabar Carnival (December) is Africa\'s biggest street party with 3 million+ visitors. Event planners, caterers, and suppliers earn their annual income in one month. Year-round government and corporate events add more.', bonus:'Earn: ₦500k–₦10m (December season)'},
      {id:'hotel-guesthouse', why:'Tourism demand during Calabar Carnival means guesthouses earn 10–20× their regular monthly income in December alone. Even small guesthouses charge ₦20k–₦100k/night during the festival.', bonus:'Earn: ₦200k–₦5m/month (peak: ₦2m–₦20m in December)'},
      {id:'short-let-airbnb', why:'Calabar Carnival brings hundreds of thousands of visitors annually in December. Short-let prices triple during this period.', bonus:'Earn: ₦30k–₦100k/night (₦5m+ in December)'},
      {id:'honey-bee-farming', why:'Cross River\'s forest-rich terrain — Oban Hills, Boshi Forest — is ideal for honey production. Cross River honey is high quality; export to Lagos organic markets earns ₦3,000–₦8,000/kg.', bonus:'Earn: ₦200k–₦1.5m/harvest'},
      {id:'tailoring-fashion', why:'Efik, Bekwarra, and Ejagham traditional regalia are elaborate and expensive. Calabar\'s fashion culture is among Nigeria\'s most distinctive. Festival costumes earn ₦50k–₦500k each.', bonus:'Earn: ₦200k–₦1.5m/month'},
      {id:'catfish-farming', why:'Cross River\'s rivers and creeks are naturally suited for catfish. Farmers in Ugep, Obubra, and Ogoja sell fresh catfish to Calabar markets at ₦2,500–₦4,000/kg. Export to Cameroon adds another market.', bonus:'Earn: ₦300k–₦1.5m/cycle'},
      {id:'palm-oil-processing', why:'Cross River is one of Nigeria\'s top palm oil producing states. Processing capacity is still below demand — opportunity is wide open.', bonus:'Earn: ₦100k–₦800k/batch'},
      {id:'grasscutter-farming', why:'Cross River\'s forest resources make grasscutter (greater cane rat) farming ideal. Grasscutter meat fetches ₦5,000–₦10,000/kg in Calabar restaurants and hotels that serve food-tourism clients.', bonus:'Earn: ₦200k–₦1m/month'},
      {id:'smoked-fish-processing', why:'Cross River\'s fishing communities in Bakassi, Calabar South, and Odukpani supply fresh fish cheaply. Smoked and packaged fish sold to Lagos traders earns 3–5× the raw fish price.', bonus:'Earn: ₦200k–₦1.2m/month'},
    ],
    uniqueInsight:'Cross River\'s Carnival is Africa\'s largest street party. If you can build a business that operates year-round but scales massively in December, you\'re positioned perfectly.',
    tip:'Calabar municipal (waterfront area), Watt Market, and Afokang Industrial are the key zones.'
  },
  {id:'borno', name:'Borno', icon:'🏰', zone:'north-east', hot:false, tagline:'Post-conflict recovery — growing trade and reconstruction economy', population:'5.5m+', gdp:'₦800bn+', internet:'Low-Medium', economy:'Trade & Reconstruction',
    topHustles:[
      {id:'mini-supermarket', why:'Maiduguri\'s resettlement population and military presence create steady provision store demand. Monday Market is the commercial hub.', bonus:'Earn: ₦8k–₦30k/day'},
      {id:'pos-agent', why:'Borno has a large unbanked population with rising mobile banking uptake. POS agents serve both civilians and military personnel.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'agric-inputs-supply', why:'Borno\'s Lake Chad basin is fertile ground for agriculture as peace returns. Fertilizer and seed supply to farmers is growing rapidly.', bonus:'Earn: ₦100k–₦1m/month'},
      {id:'bread-bakery', why:'Bread is a staple in Maiduguri. Consistent daily demand from a large population makes this a reliable business.', bonus:'Earn: ₦15k–₦60k/day'},
      {id:'printing-business', why:'University of Maiduguri and government MDAs create printing demand for students, offices, and businesses.', bonus:'Earn: ₦10k–₦40k/day'},
      {id:'logistics-dispatch', why:'Reconstruction supply chains in Borno need logistics operators. NGO and government supply contracts are available.', bonus:'Earn: ₦100k–₦500k/month'},
    ],
    uniqueInsight:'International NGOs and UN agencies operating in Borno offer supply contracts worth millions. CAC registration + UN vendor registration = access to reconstruction contracts.',
    tip:'Maiduguri\'s Monday Market and Bulumkutu axis are the main commercial zones.'
  },
  {id:'kogi', name:'Kogi', icon:'✖️', zone:'north-central', hot:false, tagline:'Confluence state — where Niger and Benue rivers meet Nigeria\'s crossroads', population:'3.5m+', gdp:'₦800bn+', internet:'Low-Medium', economy:'Mining & Agriculture',
    topHustles:[
      {id:'river-transport', why:'Kogi sits at the confluence of the Niger and Benue rivers. Passenger and cargo ferry services connecting Lokoja to Kogi\'s riverine communities earn ₦100k–₦800k/month with extremely low competition.', bonus:'Earn: ₦200k–₦1m/month | Zone: Lokoja waterfront, Idah, Ankpa'},
      {id:'hotel-guesthouse', why:'Lokoja is a major transit stop between Lagos/Abuja and the north-east. Truckers, traders, and civil servants overnight here regularly. A clean 15-room guesthouse near the Kogi State Secretariat earns ₦300k–₦1.2m/month.', bonus:'Earn: ₦300k–₦1.2m/month | Zone: Lokoja city, Ganaja, Kabawa'},
      {id:'agric-inputs-supply', why:'Kogi produces yam, cassava, and rice in large quantities. Farm input supply in Lokoja and surrounding LGAs is growing fast.', bonus:'Earn: ₦100k–₦1.5m/month'},
      {id:'logistics-dispatch', why:'Lokoja is the transit point between Lagos, Abuja, and the north. Logistics businesses positioned here move goods across Nigeria.', bonus:'Earn: ₦100k–₦500k/month'},
      {id:'mini-supermarket', why:'Lokoja city residents are an underserved market. Well-stocked provision stores near government quarters earn reliably.', bonus:'Earn: ₦8k–₦30k/day'},
      {id:'second-hand-market', why:'Okirika trade thrives in Kogi. The Lokoja market serves buyers from surrounding villages who travel to the state capital to shop.', bonus:'Earn: ₦30k–₦150k/month'},
    ],
    uniqueInsight:'Kogi\'s mineral wealth (coal, iron ore) is attracting mining companies. Equipment supply, catering, and logistics for mining operations are emerging opportunities.',
    tip:'Lokoja (state capital), Okene (mineral zone), and Ankpa (agricultural) are the key zones.'
  },
  {id:'plateau', name:'Plateau', icon:'🏔️', zone:'north-central', hot:false, tagline:'The Highlands — temperate climate, tourism & farming', population:'4.2m+', gdp:'₦900bn+', internet:'Medium', economy:'Agriculture & Tourism',
    topHustles:[
      {id:'eco-tourism-guiding', why:'Jos is Nigeria\'s top highland tourism destination — cool weather, Shere Hills, and Kaduna Falls attract Abuja and Lagos visitors every weekend. A licensed tour guide or eco-lodge operator earns ₦50k–₦300k per group.', bonus:'Earn: ₦200k–₦1.5m/month | Zone: Shere Hills, Riyom, Barkin Ladi'},
      {id:'fresh-vegetable-aggregator', why:'Jos produces 60% of Nigeria\'s Irish potatoes and large volumes of carrots, cabbage, and tomatoes. An aggregator collecting from Shendam and Barkin Ladi farmers and trucking produce to Abuja earns ₦300k–₦3m per truckload above farmgate.', bonus:'Earn: ₦300k–₦3m/truckload | Zone: Shendam, Barkin Ladi, Riyom, Mangu'},
      {id:'agric-inputs-supply', why:'Plateau\'s unique temperate climate allows year-round vegetable farming (Irish potato, tomato, carrot). Input demand is very high.', bonus:'Earn: ₦150k–₦2m/month'},
      {id:'short-let-airbnb', why:'Jos is a popular weekend escape from the Lagos and Abuja heat. Tourism short-lets earn well, especially from Abuja visitors.', bonus:'Earn: ₦20k–₦60k/night'},
      {id:'event-planning', why:'Plateau\'s mixed culture of Berom, Hausa, and Christian communities creates strong event culture year-round.', bonus:'Earn: ₦100k–₦600k/event'},
      {id:'tailoring-school', why:'Jos has a strong tailoring tradition. Fashion schools here attract students from across the north-central zone.', bonus:'Earn: ₦300k–₦1.5m/year'},
    ],
    uniqueInsight:'Jos\'s temperate climate is its biggest competitive advantage — it is the only Nigerian city where you can grow Irish potatoes, strawberries, and temperate vegetables. Agribusiness here has built-in advantages unavailable to any competitor in the south.',
    tip:'Jos North (main market), Bukuru, and the Rukuba Road axis are the commercial zones.'
  },
  {id:'akwa-ibom', name:'Akwa Ibom', icon:'🌊', zone:'south-south', hot:false, tagline:'Oil and gas wealth meets agricultural abundance', population:'5.5m+', gdp:'₦2.5 trillion+', internet:'Medium-High', economy:'Oil & Agriculture',
    topHustles:[
      {id:'seafood-supply-export', why:'Akwa Ibom\'s coastline and estuaries produce fresh periwinkle, crayfish, and catfish in abundance. Aggregating and supplying to Lagos, Abuja, and Onitsha earns ₦500k–₦5m/month. Dried crayfish export to the US and UK diaspora adds a premium channel.', bonus:'Earn: ₦500k–₦5m/month | Zone: Oron waterfront, Eket, Ikot Abasi'},
      {id:'oil-gas-vendor-supplies', why:'Akwa Ibom hosts ExxonMobil, Total, and Addax operations. NIMASA-registered marine vendors and DPR-certified suppliers earn ₦1m–₦100m per contract. Eket and Ibeno are the epicenters.', bonus:'Earn: ₦1m–₦100m/contract'},
      {id:'hotel-guesthouse', why:'Uyo\'s government-driven construction boom and oil company activity fill hotels year-round. A well-run guesthouse near Government House or Eket oil corridor charges ₦25k–₦100k/night and runs at 70%+ occupancy.', bonus:'Earn: ₦500k–₦3m/month | Zone: Uyo capital, Eket oil zone, Ikot Ekpene'},
      {id:'palm-oil-processing', why:'Akwa Ibom is Nigeria\'s top palm oil producer. Processing capacity is below demand — especially for Grade A refined oil.', bonus:'Earn: ₦200k–₦2m/batch'},
      {id:'short-let-airbnb', why:'Uyo is one of Nigeria\'s fastest-growing cities. Government projects and oil companies bring business travellers needing furnished apartments.', bonus:'Earn: ₦25k–₦80k/night'},
      {id:'real-estate-agent', why:'Uyo is one of Nigeria\'s fastest-growing cities. Land in Ewet Housing, Shelter Afrique, and Ring Road area appreciates 20–40% annually.', bonus:'Earn: ₦300k–₦5m/deal'},
      {id:'event-planning', why:'Akwa Ibom\'s affluent oil community and government class celebrate lavishly. Event planners here earn some of the highest fees in the south.', bonus:'Earn: ₦300k–₦2m/event'},
      {id:'solar-installation', why:'EEDC power supply in Akwa Ibom fails regularly despite oil wealth. Uyo businesses, ExxonMobil staff homes, and hotels all invest in solar. Industrial solar jobs earn ₦2m–₦20m.', bonus:'Earn: ₦300k–₦5m/installation'},
      {id:'catfish-farming', why:'Akwa Ibom\'s Qua Iboe River and numerous ponds are ideal for catfish. Eket and Ibeno farmers sell fresh catfish at ₦2,500–₦4,000/kg to Uyo restaurants and Lagos traders profitably.', bonus:'Earn: ₦300k–₦1.5m/cycle'},
      {id:'smoked-fish-processing', why:'Eket, Ibeno, and Oron are major fishing communities. Fresh catfish and bonga fish are abundant. Smoking and packaging for Uyo and Lagos markets turns ₦100k into ₦400k per processing run.', bonus:'Earn: ₦300k–₦2m/month'},
    ],
    uniqueInsight:'Akwa Ibom\'s state government is one of Nigeria\'s highest spenders on infrastructure. Local government contract opportunities in supply, construction support, and services are significant.',
    tip:'Uyo (capital), Eket (oil), Ikot Ekpene (crafts hub), and Oron (waterfront) are the key zones.'
  },
  {id:'bauchi', name:'Bauchi', icon:'🐘', zone:'north-east', hot:false, tagline:'Tourism & agriculture in Nigeria\'s scenic north-east', population:'6.5m+', gdp:'₦700bn+', internet:'Low-Medium', economy:'Agriculture & Tourism',
    topHustles:[
      {id:'agric-inputs-supply', why:'Bauchi is a major groundnut, sorghum, and millet production state. Farm input distribution to Alkaleri, Tafawa Balewa, and Dass is high-demand.', bonus:'Earn: ₦100k–₦1.5m/month'},
      {id:'mini-supermarket', why:'Bauchi town\'s growing civil service population creates consistent provision store demand, especially near government quarters.', bonus:'Earn: ₦8k–₦25k/day'},
      {id:'pos-agent', why:'Financial inclusion is low in Bauchi. POS agents serve a large underbanked population in markets and residential areas.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'bread-bakery', why:'Bread is a north-east staple. Consistent demand from Bauchi\'s large population creates reliable daily income for bakers.', bonus:'Earn: ₦15k–₦50k/day'},
      {id:'logistics-dispatch', why:'Bauchi is a junction state connecting the north-east to Abuja. Transit logistics for goods moving through is a solid opportunity.', bonus:'Earn: ₦80k–₦400k/month'},
      {id:'tailoring-school', why:'Fashion and modest clothing demand in Bauchi is high. Tailoring schools here serve a ready market of aspiring tailors.', bonus:'Earn: ₦200k–₦1m/year'},
    ],
    uniqueInsight:'Yankari Game Reserve attracts thousands of tourists annually. Building hospitality services (catering, accommodation, transport) around the tourism sector is an underexploited opportunity.',
    tip:'Bauchi city centre, Jos Road corridor, and Dass agricultural zone are the key areas.'
  },
  {id:'kwara', name:'Kwara', icon:'🌿', zone:'north-central', hot:false, tagline:'Border state between north and south — commercial crossroads', population:'3.5m+', gdp:'₦800bn+', internet:'Medium', economy:'Agriculture & Trade',
    topHustles:[
      {id:'food-business', why:'University of Ilorin\'s 60,000+ student population eats daily. A kitchen near the campus or Tanke area supplying packaged meals to students earns ₦20k–₦60k/day with loyal repeat customers.', bonus:'Earn: ₦80k–₦300k/month | Zone: UNILORIN area, Tanke, Fate'},
      {id:'fashion-clothing', why:'Ilorin sits at the fashion crossroads of Yoruba and northern cultures — women here wear both aso-oke and kaftan. A boutique in Mandate area stocking both styles serves a uniquely wide market.', bonus:'Earn: ₦100k–₦600k/month | Zone: Mandate Area, Fate, Tanke'},
      {id:'agric-inputs-supply', why:'Kwara produces cotton, yam, soya, and tobacco. Farm input supply to Ilorin East, Offa, and Patigi is consistent.', bonus:'Earn: ₦100k–₦1.5m/month'},
      {id:'event-planning', why:'Kwara blends Yoruba and Hausa event culture. Large weddings and celebrations happen frequently and command good fees.', bonus:'Earn: ₦100k–₦600k/event'},
      {id:'mini-supermarket', why:'University of Ilorin and Kwara State University create a huge student provision market. Location near campus is gold.', bonus:'Earn: ₦10k–₦40k/day'},
      {id:'bread-bakery', why:'Ilorin\'s large population and lower cost base make bread supply to kiosks and restaurants reliably profitable.', bonus:'Earn: ₦15k–₦60k/day'},
    ],
    uniqueInsight:'Ilorin sits at Nigeria\'s cultural and geographic midpoint — between Yoruba south and Hausa north. Businesses here can sell to both markets simultaneously. University of Ilorin\'s 60,000+ students create a captive consumer base with consistent spending power year-round.',
    tip:'Ilorin\'s Mandate Area, Oja-Oba market, University Road, and Offa Garage are the key zones.'
  },
  {id:'benue', name:'Benue', icon:'🌾', zone:'north-central', hot:false, tagline:'Food basket of the nation — yam and cassava capital', population:'5.7m+', gdp:'₦900bn+', internet:'Low-Medium', economy:'Agriculture',
    topHustles:[
      {id:'yam-flour-processing', why:'Benue produces Nigeria\'s largest yam volumes — yet yam flour (elubo) processing happens largely in Lagos. A mini-processing plant in Gboko or Makurdi produces pounded yam flour at ₦800–₦1,200/kg and supplies Lagos and Abuja markets at ₦1,500–₦2,500/kg. One 2-tonne monthly run earns ₦1.4m–₦2.6m above input cost.', bonus:'Earn: ₦1m–₦3m/month | Zone: Gboko, Otukpo, Makurdi outskirts'},
      {id:'cassava-processing', why:'Benue\'s cassava surplus is staggering — Nigeria\'s food basket title is real. A garri processing business sourcing from local farmers at ₦30k/tonne and selling 50kg bags to Lagos traders at ₦25k–₦35k earns ₦300k–₦1.5m per production run.', bonus:'Earn: ₦500k–₦2m/month | Zone: Katsina-Ala, Agatu, Otukpo, Gboko'},
      {id:'agric-inputs-supply', why:'Benue is Nigeria\'s number one yam producer. Fertilizer, herbicide, and seed supply to farmers in Gboko, Makurdi, and Otukpo is massive.', bonus:'Earn: ₦200k–₦3m/month'},
      {id:'logistics-dispatch', why:'Moving agricultural produce from Benue to Lagos, Abuja, and Port Harcourt is a big logistics business. Trucks and refrigerated vehicles earn the most.', bonus:'Earn: ₦200k–₦1m/month'},
      {id:'mini-supermarket', why:'Makurdi\'s university community (BSU, University of Agriculture) creates reliable provision store demand year-round.', bonus:'Earn: ₦8k–₦25k/day'},
      {id:'plantain-chips-business', why:'Benue\'s yam and plantain surplus means raw materials are cheapest here. Snack production and distribution to Lagos earns strong margins.', bonus:'Earn: ₦50k–₦500k/month'},
    ],
    uniqueInsight:'Benue produces more food than any other Nigerian state per capita — yet most of that value is captured by Lagos traders who buy cheap and sell expensive. Building an agro-processing or logistics business in Benue means intercepting that value before it leaves the state.',
    tip:'Makurdi (state capital), Gboko (yam zone), Otukpo (commercial), and Katsina-Ala (farming) are the key areas.'
  },
  {id:'ebonyi', name:'Ebonyi', icon:'⛰️', zone:'south-east', hot:false, tagline:'Salt and rice — Nigeria\'s youngest south-east state', population:'2.9m+', gdp:'₦500bn+', internet:'Low-Medium', economy:'Agriculture & Mining',
    topHustles:[
      {id:'rice-processing', why:'Ebonyi is Nigeria\'s rice capital — Abakaliki rice is nationally branded. A rice mill processing paddy from local farmers and selling parboiled rice to Lagos distributors earns ₦500k–₦5m per production run.', bonus:'Earn: ₦500k–₦5m/month'},
      {id:'cassava-processing', why:'Ebonyi produces Nigeria\'s highest cassava yields per hectare in several zones. A garri processing plant buying cassava at ₦15k/tonne and selling garri at ₦80k/50kg bag earns massive margins.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'agric-inputs-supply', why:'Ebonyi is Nigeria\'s top rice-producing state in the south-east. Farm input supply to Abakaliki, Ikwo, and Ezza is high-demand.', bonus:'Earn: ₦100k–₦2m/month'},
      {id:'event-planning', why:'Abakaliki weddings and Igbo celebrations in Ebonyi are elaborate. Event planners serve a market where families spend ₦1m–₦10m on weddings with pride.', bonus:'Earn: ₦200k–₦3m/event'},
      {id:'agro-export', why:'Ebonyi\'s Abakaliki rice and high-quality cassava have international market demand. A registered export business earning forex by shipping processed Abakaliki rice to Cameroon, Ghana, and Europe creates a scalable income stream.', bonus:'Earn: ₦500k–₦10m/shipment'},
      {id:'logistics-dispatch', why:'Moving Ebonyi rice to Lagos, Abuja, and Enugu markets is a major logistics business. Trucks and cold-chain logistics earn the most.', bonus:'Earn: ₦100k–₦500k/month'},
    ],
    uniqueInsight:'Ebonyi\'s salt lake in Uburu and Okposi is one of Nigeria\'s oldest industrial assets. Salt production and distribution — combined with the rice economy — gives Ebonyi unique commodity trading potential.',
    tip:'Abakaliki (state capital), Afikpo (commercial), and Ikwo (rice zone) are the key areas.'
  },
  {id:'nasarawa', name:'Nasarawa', icon:'💎', zone:'north-central', hot:false, tagline:'Abuja\'s backyard — fastest growing satellite state in Nigeria', population:'2.5m+', gdp:'₦600bn+', internet:'Medium', economy:'Mining & Agriculture',
    topHustles:[
      {id:'short-let-airbnb', why:'Nasarawa is 30–60 minutes from Abuja. Furnished apartments in Keffi and Lafia serve Abuja workers seeking affordable accommodation.', bonus:'Earn: ₦15k–₦40k/night'},
      {id:'agric-inputs-supply', why:'Nasarawa is a major soya and maize producer. Input supply to farming communities near Lafia and Akwanga is high-demand.', bonus:'Earn: ₦100k–₦1.5m/month'},
      {id:'logistics-dispatch', why:'Nasarawa\'s proximity to Abuja makes it a logistics hub. Daily supply runs between Nasarawa farms and Abuja markets are consistent.', bonus:'Earn: ₦100k–₦500k/month'},
      {id:'pos-agent', why:'Keffi and Lafia\'s growing civil service and university populations create consistent POS demand.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'mini-supermarket', why:'Nasarawa State University and Nasarawa State Polytechnic create student provision store demand in Lafia.', bonus:'Earn: ₦7k–₦22k/day'},
      {id:'bread-bakery', why:'Keffi\'s large student and commuter population — many working in Abuja — creates strong daily bread demand.', bonus:'Earn: ₦12k–₦45k/day'},
    ],
    uniqueInsight:'Nasarawa is Nigeria\'s top solid mineral state — tin, columbite, tantalite. Equipment supply and logistics for the mining sector is an underexplored but lucrative opportunity.',
    tip:'Lafia (capital), Keffi (Abuja corridor), and Akwanga (mining zone) are the key areas.'
  },
  {id:'niger', name:'Niger', icon:'🌊', zone:'north-central', hot:false, tagline:'Nigeria\'s largest state — hydropower and agricultural abundance', population:'5.6m+', gdp:'₦1 trillion+', internet:'Low-Medium', economy:'Agriculture & Mining',
    topHustles:[
      {id:'agric-inputs-supply', why:'Niger State is Nigeria\'s top cassava and rice producer. Input supply to Bida, Suleja, and Kontagora farming communities is large-scale.', bonus:'Earn: ₦200k–₦3m/month'},
      {id:'logistics-dispatch', why:'Suleja\'s proximity to Abuja makes it a logistics hub. Moving agricultural produce from Niger to FCT daily is a consistent business.', bonus:'Earn: ₦150k–₦600k/month'},
      {id:'short-let-airbnb', why:'Suleja\'s proximity to Abuja (30 mins) makes it a viable short-let market for Abuja workers seeking affordable accommodation.', bonus:'Earn: ₦15k–₦40k/night'},
      {id:'pos-agent', why:'Financial inclusion is low across much of Niger State. POS agents in market towns earn steady income from agricultural payments.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'mini-supermarket', why:'Minna\'s growing civil service and university population (FUTMinna) creates reliable provision store demand.', bonus:'Earn: ₦8k–₦25k/day'},
      {id:'bread-bakery', why:'Minna and Suleja\'s growing populations create steady bread demand. Bakeries supplying schools and estates earn best.', bonus:'Earn: ₦15k–₦55k/day'},
    ],
    uniqueInsight:'Niger State\'s Kainji and Shiroro dams power a significant chunk of Nigeria\'s grid. Energy consulting, solar installation, and generator services for communities far from the grid are major opportunities.',
    tip:'Minna (capital), Suleja (Abuja suburb), Bida (crafts hub), and Kontagora (north Niger) are the key zones.'
  },
  {id:'sokoto', name:'Sokoto', icon:'🕌', zone:'north-west', hot:false, tagline:'Caliphate city — trade, cattle, and cross-border commerce', population:'5.3m+', gdp:'₦700bn+', internet:'Low-Medium', economy:'Trade & Livestock',
    topHustles:[
      {id:'agric-inputs-supply', why:'Sokoto\'s cattle economy and groundnut farming create consistent demand for animal feeds, crop inputs, and veterinary supplies.', bonus:'Earn: ₦100k–₦2m/month'},
      {id:'mini-supermarket', why:'Sokoto\'s civil service class and trading families create provision store demand, especially near government quarters.', bonus:'Earn: ₦8k–₦25k/day'},
      {id:'logistics-dispatch', why:'Cross-border trade with Niger Republic runs through Sokoto. Logistics connecting Nigeria\'s north-west to ECOWAS markets is active.', bonus:'Earn: ₦100k–₦600k/month'},
      {id:'pos-agent', why:'Sokoto has one of the lowest financial inclusion rates in Nigeria. POS agents here serve a market desperate for cash access.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'tailoring-school', why:'Northern fashion — kaftans, babbanriga, hijab — is always in demand in Sokoto. Tailoring training is valued and well-paid.', bonus:'Earn: ₦150k–₦800k/year'},
      {id:'bread-bakery', why:'Sokoto\'s large population and cultural bread consumption create reliable daily income for bakers near markets.', bonus:'Earn: ₦12k–₦45k/day'},
    ],
    uniqueInsight:'Sokoto\'s proximity to Niger Republic means cross-border trade in livestock, grain, and consumer goods is significant. A licensed cross-border trader earns more per transaction than most domestic businesses.',
    tip:'Sokoto Central Market, Mabera, and the Binji Road axis are the main commercial zones.'
  },
  {id:'kebbi', name:'Kebbi', icon:'🌾', zone:'north-west', hot:false, tagline:'Rice production capital — Argungu and Lake Chad basin agriculture', population:'4.4m+', gdp:'₦600bn+', internet:'Low', economy:'Agriculture',
    topHustles:[
      {id:'agric-inputs-supply', why:'Kebbi produces 40% of Nigeria\'s rice. Rice farmers need fertilizer, seeds, and herbicides in huge quantities every season.', bonus:'Earn: ₦300k–₦5m/month'},
      {id:'logistics-dispatch', why:'Moving Kebbi rice from paddy fields to mills and markets across Nigeria is a large-scale transport opportunity.', bonus:'Earn: ₦200k–₦1.5m/month'},
      {id:'mini-supermarket', why:'Birnin Kebbi\'s civil service community creates consistent provision store demand near government residential areas.', bonus:'Earn: ₦8k–₦25k/day'},
      {id:'pos-agent', why:'Financial inclusion is very low in Kebbi. POS agents serve an underbanked farming population who receive bank transfers for produce.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'bread-bakery', why:'Daily bread demand in Kebbi towns is consistent. Northern bread consumption culture makes this reliable year-round.', bonus:'Earn: ₦12k–₦40k/day'},
      {id:'tailoring-school', why:'Kebbi\'s Muslim fashion culture drives demand for tailors specializing in kaftans, jalabiya, and modest wear.', bonus:'Earn: ₦150k–₦800k/year'},
    ],
    uniqueInsight:'Kebbi\'s rice production is worth ₦100 billion+ annually. Becoming a licensed buying agent for Anchor Borrowers Programme or setting up a rice milling operation here creates direct access to this wealth.',
    tip:'Birnin Kebbi, Argungu, and Yauri are the main commercial zones in the state.'
  },
  {id:'zamfara', name:'Zamfara', icon:'🏜️', zone:'north-west', hot:false, tagline:'Mining state — gold and solid mineral wealth', population:'4.5m+', gdp:'₦500bn+', internet:'Low', economy:'Mining & Agriculture',
    topHustles:[
      {id:'agric-inputs-supply', why:'Zamfara grows cotton, sorghum, and millet. Farm input supply to Gusau, Kaura-Namoda, and Anka is consistent.', bonus:'Earn: ₦100k–₦1m/month'},
      {id:'mini-supermarket', why:'Gusau\'s trading community and civil servants create provision store demand. Market proximity is key.', bonus:'Earn: ₦8k–₦22k/day'},
      {id:'pos-agent', why:'Very low financial inclusion rate. POS agents serve a large rural population increasingly receiving bank transfers.', bonus:'Earn: ₦3k–₦10k/day'},
      {id:'logistics-dispatch', why:'Mining supply chains and agricultural produce movement require logistics operators in Zamfara.', bonus:'Earn: ₦80k–₦400k/month'},
      {id:'bread-bakery', why:'Bread supply to markets, schools, and offices in Gusau is consistent. Small bakeries with 2–3 shop supply contracts earn reliably.', bonus:'Earn: ₦10k–₦35k/day'},
      {id:'tailoring-school', why:'Northern fashion culture creates demand for skilled tailors. A training school in Gusau serves students from surrounding LGAs.', bonus:'Earn: ₦100k–₦600k/year'},
    ],
    uniqueInsight:'Zamfara is sitting on billions in gold and solid minerals. Equipment supply, security consulting, and logistics for the mining sector are emerging opportunities as the state stabilizes.',
    tip:'Gusau (state capital) and Kaura-Namoda (commercial) are the main business zones.'
  },
  {id:'katsina', name:'Katsina', icon:'🏜️', zone:'north-west', hot:false, tagline:'Historic emirate — groundnut, cotton and border trade', population:'7.8m+', gdp:'₦1.2 trillion+', internet:'Low-Medium', economy:'Agriculture & Trade',
    topHustles:[
      {id:'agric-inputs-supply', why:'Katsina is Nigeria\'s largest cotton producer. Fertilizer, herbicide, and seed distribution to cotton farmers is massive.', bonus:'Earn: ₦200k–₦4m/month'},
      {id:'logistics-dispatch', why:'Katsina borders Niger Republic. Cross-border logistics for agricultural produce and consumer goods is a strong opportunity.', bonus:'Earn: ₦150k–₦800k/month'},
      {id:'pos-agent', why:'Katsina\'s large population and low banking penetration make POS agents essential in every market.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'mini-supermarket', why:'Katsina city\'s civil service and university community create reliable provision store demand.', bonus:'Earn: ₦8k–₦25k/day'},
      {id:'tailoring-school', why:'Northern fashion traditions are strong in Katsina. Tailors here work year-round with consistent demand for kaftans and traditional wear.', bonus:'Earn: ₦150k–₦900k/year'},
      {id:'bread-bakery', why:'Consistent demand across Katsina\'s many LGA capitals creates reliable bakery income.', bonus:'Earn: ₦12k–₦45k/day'},
    ],
    uniqueInsight:'Katsina\'s groundnut oil industry once made it a global commodity player. A groundnut oil pressing business here, supplying Lagos and Abuja, can rebuild that legacy at modern scale.',
    tip:'Katsina city (emirate market), Funtua (commercial), and Daura (historic) are the key zones.'
  },
  {id:'jigawa', name:'Jigawa', icon:'🌾', zone:'north-west', hot:false, tagline:'Agricultural powerhouse — sesame, millet and melon production', population:'5.8m+', gdp:'₦600bn+', internet:'Low', economy:'Agriculture',
    topHustles:[
      {id:'agric-inputs-supply', why:'Jigawa is Nigeria\'s top sesame (beniseed) producer and a major millet state. Input supply to farmers across all 27 LGAs is extremely high-demand.', bonus:'Earn: ₦200k–₦3m/month'},
      {id:'logistics-dispatch', why:'Jigawa\'s sesame exports to international markets require aggregators and logistics operators with connections to Kano port facilities.', bonus:'Earn: ₦150k–₦1m/month'},
      {id:'pos-agent', why:'One of the lowest bank penetration rates in Nigeria. POS agents here serve a massive underbanked farming population.', bonus:'Earn: ₦3k–₦10k/day'},
      {id:'mini-supermarket', why:'Dutse and Hadejia towns have civil service populations that need well-stocked provision stores.', bonus:'Earn: ₦8k–₦22k/day'},
      {id:'bread-bakery', why:'Bread is a northern staple. Consistent demand across Jigawa\'s many LGA capitals creates reliable bakery income.', bonus:'Earn: ₦10k–₦35k/day'},
      {id:'tailoring-school', why:'Fashion culture is strong in Jigawa. Tailors specializing in northern wear are always busy and respected.', bonus:'Earn: ₦100k–₦600k/year'},
    ],
    uniqueInsight:'Jigawa sesame is exported internationally. Becoming a licensed commodity aggregator — buying from farmers and selling to exporters — earns ₦500k–₦5m per season on single crops.',
    tip:'Dutse (state capital), Hadejia (trade), and Birnin Kudu (commercial) are the key zones.'
  },
  {id:'taraba', name:'Taraba', icon:'🌿', zone:'north-east', hot:false, tagline:'Wildlife and agriculture in Nigeria\'s scenic north-east', population:'3.7m+', gdp:'₦500bn+', internet:'Low', economy:'Agriculture & Tourism',
    topHustles:[
      {id:'agric-inputs-supply', why:'Taraba produces rubber, coffee, and food crops. Agricultural input supply to Jalingo and Wukari farming communities is high-demand.', bonus:'Earn: ₦100k–₦1.5m/month'},
      {id:'pos-agent', why:'Taraba is among Nigeria\'s least financially included states. POS agents in Jalingo and Wukari serve a large underbanked population.', bonus:'Earn: ₦3k–₦10k/day'},
      {id:'logistics-dispatch', why:'Moving agricultural produce from Taraba to markets in Abuja and Lagos is a significant logistics opportunity.', bonus:'Earn: ₦80k–₦400k/month'},
      {id:'mini-supermarket', why:'Jalingo\'s civil service population creates provision store demand. Near government secretariat is the prime location.', bonus:'Earn: ₦6k–₦20k/day'},
      {id:'bread-bakery', why:'Bread demand across Taraba\'s LGA capitals is consistent. Small bakeries with institutional supply contracts earn reliably.', bonus:'Earn: ₦10k–₦35k/day'},
      {id:'tailoring-school', why:'Taraba\'s diverse ethnic groups (Jukun, Mumuye, Tiv) create demand for tailors who can work across styles.', bonus:'Earn: ₦100k–₦600k/year'},
    ],
    uniqueInsight:'Taraba\'s Gashaka-Gumti National Park — Nigeria\'s largest — has huge untapped tourism potential. Early movers in eco-tourism, guide services, and lodge operations can build pioneer advantage.',
    tip:'Jalingo (state capital), Wukari (commercial), and Gembu (highland area) are the key zones.'
  },
  {id:'adamawa', name:'Adamawa', icon:'🦅', zone:'north-east', hot:false, tagline:'Scenic highlands and agricultural wealth — Yola\'s growing commerce', population:'4.2m+', gdp:'₦600bn+', internet:'Low-Medium', economy:'Agriculture & Trade',
    topHustles:[
      {id:'agric-inputs-supply', why:'Adamawa grows cotton, groundnut, and sorghum. Farm input distribution in Yola, Mubi, and Numan is high-demand.', bonus:'Earn: ₦100k–₦1.5m/month'},
      {id:'logistics-dispatch', why:'Mubi\'s proximity to Cameroon creates cross-border trade logistics opportunities. Moving goods across the border is profitable.', bonus:'Earn: ₦80k–₦500k/month'},
      {id:'pos-agent', why:'Adamawa has low financial inclusion. POS agents in Yola and Mubi serve a growing population receiving bank transfers.', bonus:'Earn: ₦3k–₦10k/day'},
      {id:'mini-supermarket', why:'Yola\'s university community (Modibbo Adama University) creates reliable provision store demand near campus.', bonus:'Earn: ₦7k–₦22k/day'},
      {id:'bread-bakery', why:'Daily bread consumption in Adamawa\'s towns is consistent. Supply to schools, hospitals, and government offices earns reliably.', bonus:'Earn: ₦10k–₦40k/day'},
      {id:'tailoring-school', why:'Adamawa\'s diverse ethnic groups create demand for tailors skilled in both northern and southern styles.', bonus:'Earn: ₦100k–₦600k/year'},
    ],
    uniqueInsight:'Adamawa borders Cameroon and the Central African Republic. Cross-border trade for consumer goods and agricultural commodities runs through Mubi and Yola — a significant trading opportunity.',
    tip:'Yola (capital), Mubi (commercial), and Numan (agricultural) are the key zones.'
  },
  {id:'yobe', name:'Yobe', icon:'🏜️', zone:'north-east', hot:false, tagline:'Post-conflict recovery — trade, agriculture and cross-border commerce', population:'3.3m+', gdp:'₦400bn+', internet:'Low', economy:'Trade & Agriculture',
    topHustles:[
      {id:'agric-inputs-supply', why:'Yobe produces millet, cowpea, and sesame. Farm input distribution is needed across the state\'s rural farming communities.', bonus:'Earn: ₦80k–₦1m/month'},
      {id:'pos-agent', why:'Yobe has very low financial inclusion. POS agents in Damaturu and Potiskum serve the growing population accessing mobile banking.', bonus:'Earn: ₦3k–₦10k/day'},
      {id:'mini-supermarket', why:'Damaturu\'s civil service population and NGO workers create provision store demand. Near government estates is prime.', bonus:'Earn: ₦6k–₦18k/day'},
      {id:'logistics-dispatch', why:'Supply chains for NGOs and government programmes operating in Yobe need local logistics operators.', bonus:'Earn: ₦80k–₦400k/month'},
      {id:'bread-bakery', why:'Bread demand is consistent in Damaturu and Potiskum. Supply to markets, offices, and schools earns steady daily income.', bonus:'Earn: ₦10k–₦30k/day'},
      {id:'pos-agent2', id:'ngo-supplies', why:'NGO and government cash disbursements via bank transfer to beneficiaries create high POS withdrawal demand in Yobe.', bonus:'Earn: ₦4k–₦12k/day'},
    ],
    uniqueInsight:'International NGOs in Yobe — UNICEF, WFP, MSF — run large operations. Local supplier registration with these organizations opens access to significant catering, logistics, and supply contracts.',
    tip:'Damaturu (state capital) and Potiskum (commercial hub) are the main business zones.'
  },
  {id:'gombe', name:'Gombe', icon:'🌵', zone:'north-east', hot:false, tagline:'Gateway to the north-east — growing commercial hub', population:'3.3m+', gdp:'₦500bn+', internet:'Low-Medium', economy:'Trade & Agriculture',
    topHustles:[
      {id:'agric-inputs-supply', why:'Gombe produces groundnut, cotton, and cowpea. Input supply to farming communities across Nafada, Funakaye, and Balanga is high-demand.', bonus:'Earn: ₦100k–₦1.5m/month'},
      {id:'pos-agent', why:'Gombe is a growing commercial city. POS agents in Gombe town markets and near ATBU earn steady daily income.', bonus:'Earn: ₦4k–₦12k/day'},
      {id:'mini-supermarket', why:'ATBU (Abubakar Tafawa Balewa University) and civil service populations create strong provision store demand.', bonus:'Earn: ₦7k–₦22k/day'},
      {id:'logistics-dispatch', why:'Gombe is the commercial hub connecting the north-east. Logistics businesses here serve Bauchi, Yobe, Adamawa, and Taraba.', bonus:'Earn: ₦100k–₦500k/month'},
      {id:'bread-bakery', why:'Gombe\'s growing urban population creates consistent bread demand. Supply to schools and estates earns reliably.', bonus:'Earn: ₦12k–₦45k/day'},
      {id:'tailoring-school', why:'Gombe\'s growing urban youth population and fashion consciousness make tailoring schools increasingly popular.', bonus:'Earn: ₦150k–₦800k/year'},
    ],
    uniqueInsight:'Gombe is one of the fastest-growing cities in the north-east. Early business establishment here — before competition intensifies — creates the same first-mover advantage that Lagos businesses had 30 years ago.',
    tip:'Gombe city centre, Tumfure market, and the Pantami area are the commercial hotspots.'
  },
  {id:'bayelsa', name:'Bayelsa', icon:'🐟', zone:'south-south', hot:false, tagline:'Oil-rich creeks — Nigeria\'s youngest state with oil wealth', population:'2.5m+', gdp:'₦1 trillion+', internet:'Medium', economy:'Oil & Fishing',
    topHustles:[
      {id:'oil-gas-vendor-supplies', why:'Bayelsa\'s Oloibiri (Nigeria\'s first oil well) and active NNPC, SPDC, and Agip operations make it a major oil-sector supply market. DPR and NOSDRA vendor registration earns contracts worth ₦1m–₦50m.', bonus:'Earn: ₦500k–₦50m/contract'},
      {id:'smoked-fish-processing', why:'Bayelsa\'s creeks and rivers are Nigeria\'s richest fishing grounds. Smoked bonga fish, catfish, and perch from Yenagoa\'s waterways sell at ₦3,000–₦8,000/kg in Lagos, compared to ₦500/kg raw.', bonus:'Earn: ₦200k–₦1.5m/month'},
      {id:'mini-cold-storage-rental', why:'Fresh fish from Bayelsa\'s creeks spoils fast without cold storage. Renting cold storage space to fishermen and traders at ₦500–₦2,000/day per slot earns passive income in a critical infrastructure gap.', bonus:'Earn: ₦150k–₦800k/month'},
      {id:'short-let-airbnb', why:'Yenagoa is growing fast. Oil company staff on rotation need furnished apartments. Short-let here commands premium oil sector pricing.', bonus:'Earn: ₦25k–₦80k/night'},
      {id:'solar-installation', why:'BEDC power supply to Bayelsa is extremely poor — one of Nigeria\'s least electrified states. Every home, business, and government office needs solar. A completed installation earns ₦300k–₦5m.', bonus:'Earn: ₦300k–₦5m/installation'},
      {id:'interior-decoration', why:'Bayelsa\'s oil-money class builds elaborate homes. A decorator fitting out a GRA Yenagoa home for an oil executive earns ₦1m–₦20m per project with zero price negotiation.', bonus:'Earn: ₦500k–₦20m/project'},
    ],
    uniqueInsight:'Bayelsa\'s oil industry creates a need for local suppliers of everything from catering to printing to cleaning. NOSDRA vendor registration + DPR certification opens doors to lucrative oil sector contracts.',
    tip:'Yenagoa (capital), Ogbia (oil zone), and Nembe (traditional) are the key zones.'
  },
  {id:'edo', name:'Edo', icon:'🏯', zone:'south-south', hot:false, tagline:'Ancient Kingdom — Benin City\'s growing commerce and education', population:'4.7m+', gdp:'₦1.5 trillion+', internet:'Medium-High', economy:'Commerce & Education',
    topHustles:[
      {id:'rubber-processing', why:'Edo is Nigeria\'s rubber capital — Ovia North-East and Uhunmwonde LGAs have massive rubber plantations. Processing raw latex into ribbed smoked sheets earns ₦500k–₦5m per production batch for export.', bonus:'Earn: ₦500k–₦5m/batch'},
      {id:'real-estate-agent', why:'Benin City is one of Nigeria\'s fastest-growing real estate markets. GRA Benin, Ugbowo, and Independence Layout are seeing 25–40% annual appreciation. One land deal earns ₦300k–₦5m.', bonus:'Earn: ₦300k–₦5m/deal'},
      {id:'event-planning', why:'Benin Kingdom\'s cultural events, coronations, and celebrations are legendary. Event planners here earn from high-value traditional celebrations.', bonus:'Earn: ₦200k–₦1.5m/event'},
      {id:'tailoring-fashion', why:'Edo/Benin traditional attire — coral bead regalia, Isiagu fabric, elaborate royal fashion — is expensive and widely worn. Skilled tailors serving Benin royal families and events earn premium rates.', bonus:'Earn: ₦200k–₦1.5m/month'},
      {id:'palm-kernel-oil-production', why:'Benin\'s abundant oil palms yield palm kernel oil used in cosmetics and cooking. Processing for export to Lagos soap manufacturers earns premium margins year-round.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'solar-installation', why:'BEDC power supply in Edo State is unreliable. Benin City homes, UNIBEN facilities, and commercial areas all invest in solar. Installations earn ₦200k–₦5m each.', bonus:'Earn: ₦200k–₦5m/installation'},
    ],
    uniqueInsight:'Benin City\'s large UK and Italian diaspora community sends remittances and travels home frequently. Services catering to diaspora returnees — real estate, events, hospitality — command premium pricing.',
    tip:'Oba Market, New Benin, Uselu (electronics), and GRA are Edo\'s main commercial zones.'
  },
  {id:'osun', name:'Osun', icon:'🌺', zone:'south-west', hot:false, tagline:'The sacred river — Osogbo\'s cultural heritage meets commerce', population:'3.7m+', gdp:'₦800bn+', internet:'Medium', economy:'Agriculture & Commerce',
    topHustles:[
      {id:'cocoa-aggregator', why:'Osun is one of Nigeria\'s top cocoa-producing states — Ilesa, Ife East, and Iwo divisions. Aggregating cocoa at ₦3,000/kg farmgate and selling to Ibadan exporters at ₦5,000–₦6,000/kg earns ₦300k–₦3m per tonne.', bonus:'Earn: ₦300k–₦3m/season'},
      {id:'eco-tourism-guide', why:'UNESCO-listed Osun-Osogbo Sacred Grove draws thousands of visitors annually. A licensed tour guide offering cultural tours of the grove, Ile-Ife\'s Ooni Palace, and Yoruba heritage sites earns ₦20k–₦100k per group.', bonus:'Earn: ₦150k–₦800k/month'},
      {id:'tailoring-fashion', why:'Osun\'s deep Yoruba cultural identity — Osun-Osogbo festival draws global tourists, Ile-Ife\'s royal traditions — makes traditional fashion (aso-oke, yoruba agbada) a premium year-round market.', bonus:'Earn: ₦200k–₦1.5m/month'},
      {id:'event-catering', why:'Osun\'s Yoruba event culture — owambe parties, chieftaincy installations, Osun-Osogbo festival catering — is active year-round. A caterer serving Osogbo events earns ₦200k–₦3m per event.', bonus:'Earn: ₦200k–₦3m/event'},
      {id:'real-estate-agent', why:'Osogbo\'s status as Osun capital and Ile-Ife\'s OAU proximity drive property demand. GRA Osogbo and Ile-Ife university zone attract buyers. Deals earn ₦150k–₦5m in commission.', bonus:'Earn: ₦150k–₦5m/deal'},
      {id:'tutoring-online', why:'OAU\'s academic culture means private tutoring for JAMB and professional exams is in high demand year-round.', bonus:'Earn: ₦80k–₦300k/month'},
    ],
    uniqueInsight:'Osun\'s OAU community is one of Nigeria\'s most vibrant academic ecosystems. Businesses serving researchers, faculty, and graduate students — academic publishing, research tools, tutoring — command premium prices here.',
    tip:'Ile-Ife (OAU), Osogbo (capital/market), and Iwo (trade) are the main zones.'
  },
  {id:'ekiti', name:'Ekiti', icon:'📚', zone:'south-west', hot:false, tagline:'Land of scholars — highest graduate per capita in Nigeria', population:'2.6m+', gdp:'₦500bn+', internet:'Medium', economy:'Education & Agriculture',
    topHustles:[
      {id:'cocoa-aggregator', why:'Ekiti is Nigeria\'s second-largest cocoa state after Ondo. Buying dried cocoa at ₦3,000/kg from farmers and selling to Ibadan and Lagos exporters at ₦4,500–₦5,500/kg earns ₦300k–₦2m per tonne.', bonus:'Earn: ₦300k–₦3m/season'},
      {id:'yam-aggregation', why:'Ekiti is Nigeria\'s top yam producer — Irepodun-Ifelodun and Ido-Osi LGAs. Aggregating yam tubers at ₦500/kg farmgate and shipping to Lagos at ₦1,200–₦1,800/kg earns ₦200k–₦1.5m per truck.', bonus:'Earn: ₦200k–₦1.5m/truckload'},
      {id:'tutoring-online', why:'Ekiti has Nigeria\'s highest literacy rate. Quality tutoring for JAMB, WAEC, and primary school children is always in demand.', bonus:'Earn: ₦80k–₦400k/month'},
      {id:'real-estate-agent', why:'Ado-Ekiti\'s government expansion and UNAD proximity are attracting property investment. Land in GRA Ado-Ekiti and Federal Secretariat area is appreciating steadily.', bonus:'Earn: ₦150k–₦4m/deal'},
      {id:'tailoring-fashion', why:'Ekiti\'s Yoruba culture — aso-oke weaving, traditional royal wear, and owambe fashion — drives consistent tailoring demand.', bonus:'Earn: ₦150k–₦1m/month'},
      {id:'event-catering', why:'Ekiti weddings and chieftaincy events are elaborate. A professional caterer serving Ado-Ekiti events earns ₦200k–₦2m per event from a culture that values large-scale dining.', bonus:'Earn: ₦200k–₦2m/event'},
    ],
    uniqueInsight:'Ekiti\'s massive graduate population creates a unique market — they earn salaries and are willing to pay premium for quality education, health services, and lifestyle products.',
    tip:'Ado Ekiti city centre, University Road (EKSU/ABUAD), and Fajuyi Estate are the main zones.'
  },
  {id:'ondo', name:'Ondo', icon:'🌾', zone:'south-west', hot:false, tagline:'Bitumen and cocoa — natural wealth meets Sunshine State ambition', population:'4.7m+', gdp:'₦1.5 trillion+', internet:'Medium', economy:'Agriculture & Mining',
    topHustles:[
      {id:'cocoa-aggregator', why:'Ondo is Nigeria\'s #1 cocoa-producing state — Idanre, Ondo South, and Ondo West are the heartlands. A cocoa aggregator buys at ₦3,000/kg from farmers and sells to Lagos exporters at ₦5,000–₦6,000/kg. One truck = ₦2m–₦5m profit.', bonus:'Earn: ₦500k–₦5m/season'},
      {id:'agric-inputs-supply', why:'Ondo is Nigeria\'s top cocoa and cashew producer. Farm input supply to Ore, Akure, and Owo cocoa belt farmers is high-volume.', bonus:'Earn: ₦200k–₦3m/month'},
      {id:'palm-oil-processing', why:'Ondo\'s forest zone produces abundant palm fruits. Processing for Lagos and Abuja wholesale markets earns strong margins.', bonus:'Earn: ₦100k–₦1m/batch'},
      {id:'real-estate-agent', why:'Akure is growing rapidly as Ondo\'s capital. Alagbaka, Oda Road, and Sunshine Estate attract buyers. Ondo\'s bitumen oil potential is driving speculative land purchases — deals earn ₦300k–₦8m.', bonus:'Earn: ₦300k–₦8m/deal'},
      {id:'event-catering', why:'Akure and Ondo City host elaborate Yoruba celebrations. Annual Ogun festival, corporate events from Chevron\'s Escravos-linked operations, and Akure Government functions pay ₦300k–₦5m per caterer.', bonus:'Earn: ₦300k–₦5m/event'},
      {id:'logistics-dispatch', why:'Ondo\'s Ore is a key transit point on the Benin-Lagos expressway. Logistics businesses here serve the entire south-west corridor.', bonus:'Earn: ₦150k–₦700k/month'},
    ],
    uniqueInsight:'Ondo State has the world\'s largest bitumen deposit in Ondo coast. Oil and mining companies are acquiring exploration licenses. Supply chain, hospitality, and services for this sector are big opportunities.',
    tip:'Akure (capital), Ore (transit), Ondo city (cocoa zone), and Owo (culture) are the key zones.'
  },
  {id:'abia', name:'Abia', icon:'🏭', zone:'south-east', hot:false, tagline:'Aba — Made in Aba manufacturing hub and commercial south-east', population:'3.7m+', gdp:'₦1 trillion+', internet:'Medium-High', economy:'Manufacturing & Trade',
    topHustles:[
      {id:'aba-goods-middleman', why:'Aba is Africa\'s manufacturing capital for shoes, bags, and garments. A middleman connecting Aba manufacturers to Lagos boutiques, Onitsha retailers, and export buyers earns 15–40% markup on every consignment.', bonus:'Earn: ₦500k–₦5m/month'},
      {id:'export-trade', why:'Aba-made goods — shoes, bags, clothing — are now exported to UK, US, and West Africa. A registered export business shipping Aba goods abroad earns foreign exchange directly, with margins of 100–400%.', bonus:'Earn: ₦1m–₦20m/shipment'},
      {id:'streetwear-clothing-brand', why:'Aba is Nigeria\'s fashion manufacturing capital. Starting a clothing brand here means sourcing production at 30–50% below Lagos prices.', bonus:'Earn: ₦200k–₦3m/month'},
      {id:'printing-business', why:'Aba\'s massive manufacturing community needs custom labels, packaging, brand tags, and signage at scale. A printing press servicing Aba manufacturers earns ₦300k–₦3m monthly on volume B2B contracts.', bonus:'Earn: ₦300k–₦3m/month'},
      {id:'generator-repair', why:'Aba\'s 24/7 manufacturing operations depend entirely on generators. An industrial generator mechanic near Ariaria or Factory Road earns ₦400k–₦2m monthly on emergency and maintenance contracts.', bonus:'Earn: ₦300k–₦2m/month'},
      {id:'haulage-truck', why:'Aba\'s manufacturing output needs constant haulage to Lagos, Onitsha, and export ports. A truck operator on the Aba–Onitsha–Lagos route earns ₦500k–₦3m monthly on manufacturing logistics.', bonus:'Earn: ₦500k–₦3m/month'},
    ],
    uniqueInsight:'Made-in-Aba is a globally recognized brand among Africans in diaspora. Building an export business that takes Aba\'s manufactured shoes, bags, and clothing to the UK, USA, and Europe is a massive opportunity.',
    tip:'Ariaria International Market, Aba township (manufacturing belt), and Osisioma Industrial Area are the key zones.'
  },
];

// Hustle details DB (core hustles referenced above)
const HUSTLE_DB = {
  'pos-agent':{emoji:'💳',name:'POS Agent Business',level:'beginner',tags:['Offline','Daily Cash','No Experience'],desc:'Run a POS terminal for cash withdrawals, transfers, and bill payments. Earn ₦50–₦200 per transaction with 50–100+ daily transactions possible.',income:{beginner:'₦3k–₦6k/day',normal:'₦7k–₦12k/day',advanced:'₦20k+/day'},time:'Start in 1–2 days',capital:'₦30k–₦80k (float)',difficulty:'1/5',bestFor:'Anyone, Market area residents, Housewives',steps:['Get a POS terminal from OPay, GTBank, PalmPay, or Moniepoint (free or cheap)','Load float: minimum ₦30k–₦50k to handle transactions','Set up at high-traffic spot: market, bus stop, church/mosque area, school gate','Charge standard rates: ₦50–₦200 per transaction','Scale: add more terminals, hire agents under you']},
  'dropshipping':{emoji:'📦',name:'Mini Importation / Dropshipping',level:'medium',tags:['Low Capital','Scalable','Trending'],desc:'Source products from China (Alibaba, 1688, AliExpress) and resell locally at 2–5x markup. Works via WhatsApp, Instagram, and Jumia.',income:{beginner:'₦5k–₦20k/week',normal:'₦50k–₦200k/week',advanced:'₦500k–₦2m/month'},time:'First sale in 2–4 weeks',capital:'₦15k–₦50k',difficulty:'3/5',bestFor:'Entrepreneurs, Anyone with hustle',steps:['Research: Find trending products on Jumia/Konga bestsellers','Source from AliExpress, Alibaba, or local Lagos/Alaba market','Set up Instagram Shop + WhatsApp Business catalog with quality photos','Market: Facebook/Instagram ads (₦1k/day budget works)','Process orders, use DHL or GIG Logistics for delivery, collect via Paystack']},
  'logistics-dispatch':{emoji:'🛵',name:'Logistics & Dispatch Business',level:'beginner',tags:['Offline','Daily Income','Scalable'],desc:'Run a delivery fleet serving businesses and individuals. Lagos, Abuja, and PH dispatch companies earn consistently from B2B corporate accounts.',income:{beginner:'₦5k–₦15k/day',normal:'₦200k–₦500k/month',advanced:'₦1m+/month'},time:'Launch in 1 week',capital:'₦100k–₦500k',difficulty:'2/5',bestFor:'Drivers, Fleet owners, Operations-minded entrepreneurs',steps:['Register your business (CAC — ₦25k)','Buy or lease motorbikes/tricycles — start with 2–5 bikes','Partner with GIG, Kwik, or Sendbox for technology','Target B2B clients: restaurants, e-commerce sellers, pharmacies','Scale: add vehicles as clients grow']},
  'event-planning':{emoji:'🎉',name:'Event Planning',level:'medium',tags:['Creative','High Income','Nigeria'],desc:'Plan and execute weddings, corporate events, and celebrations. Nigeria\'s event culture makes this one of the most lucrative service businesses.',income:{beginner:'₦100k–₦300k/event',normal:'₦500k–₦2m/event',advanced:'₦3m–₦10m+/event'},time:'First client in 2–4 weeks',capital:'₦50k–₦200k',difficulty:'3/5',bestFor:'Organized people, Creatives, People with good networks',steps:['Build a portfolio: help plan 2–3 small events for free or cheap','Create an Instagram showcase of your work','Network at industry events, join event planners associations','Price per event: ₦100k beginner, ₦500k+ once established','Scale: hire a team, bring in vendor partnerships']},
  'short-let-airbnb':{emoji:'🏠',name:'Short-Let / Airbnb Management',level:'medium',tags:['Passive Income','Real Estate','High Yield'],desc:'List furnished apartments for short-term rental to corporate travellers, tourists, and expats. Premium yields vs long-term letting.',income:{beginner:'₦20k–₦50k/night',normal:'₦50k–₦120k/night',advanced:'₦120k–₦250k/night'},time:'First booking in 1–2 weeks',capital:'₦500k–₦3m (furnishing)',difficulty:'3/5',bestFor:'Property owners, Investors, Hospitality-minded people',steps:['Identify the right apartment: Lekki, Maitama, PH GRA', 'Furnish to 4-star standard: ₦500k–₦2m investment','List on Airbnb, Booking.com, and Nigerian short-let platforms','Set competitive pricing: start 10% below market to get reviews','Automate with smart locks, WhatsApp, and professional cleaning service']},
  'social-media-mgmt':{emoji:'📊',name:'Social Media Management',level:'beginner',tags:['Remote','Recurring Income','No Capital'],desc:'Manage Instagram, Facebook, and TikTok pages for businesses. Every business needs an online presence — very few can manage it themselves.',income:{beginner:'₦20k–₦50k/month per client',normal:'₦100k–₦250k/month',advanced:'$500–$3k/month (USD)'},time:'First client in 1–2 weeks',capital:'₦0',difficulty:'2/5',bestFor:'Social media users, Students, Creative people',steps:['Build your own social presence first — 2 weeks of consistent posting','Create a simple portfolio: mock manage 2 brand pages','Cold DM 30 local businesses per day on Instagram/WhatsApp','Offer 1 month free management to land first 2 clients','Systematize with tools, scale to 5–10 clients per month']},
  'food-business':{emoji:'🍱',name:'Food Business / Home Kitchen',level:'beginner',tags:['Daily Cash','No Experience','Offline'],desc:'Cook and sell home-style meals, lunch boxes, or snacks. WhatsApp and Instagram marketing makes it easy to build a loyal customer base.',income:{beginner:'₦5k–₦15k/day',normal:'₦50k–₦150k/week',advanced:'₦300k–₦1m/month'},time:'Start same week',capital:'₦20k–₦100k',difficulty:'1/5',bestFor:'Good cooks, Housewives, Anyone who can cook Nigerian food',steps:['Pick your specialty: jollof rice, pounded yam, amala, snacks','Buy cooking equipment and ingredients','Build your customer list via WhatsApp status and Instagram','Offer delivery or fixed pickup point near offices/estates','Scale: hire kitchen assistants, launch subscription meal plans']},
  'real-estate-agent':{emoji:'🏘️',name:'Real Estate Agent',level:'medium',tags:['High Commissions','Network-Based','Scalable'],desc:'Connect property buyers, sellers, and renters. Nigerian real estate is booming — agents earn 5–10% commission on every deal.',income:{beginner:'₦100k–₦500k/deal',normal:'₦500k–₦3m/deal',advanced:'₦3m–₦20m+/deal'},time:'First deal in 1–3 months',capital:'₦0–₦50k',difficulty:'3/5',bestFor:'Networkers, Persuasive people, Those with real estate contacts',steps:['Shadow an experienced agent for 2–4 weeks to learn the market','Get familiar with pricing in 3–5 target areas','Build a network: developers, property owners, buyers','List properties on PropertyPro, Nigeria Property Centre','Standard commission: 5–10% of annual rent or 5% of sale price']},
  'cleaning-service':{emoji:'🧹',name:'Professional Cleaning Service',level:'beginner',tags:['B2B','Recurring','Offline'],desc:'Provide commercial and residential cleaning. Corporate contracts in Lagos and Abuja earn ₦100k–₦500k/month per client.',income:{beginner:'₦50k–₦150k/month',normal:'₦300k–₦1m/month',advanced:'₦1m–₦5m/month'},time:'First contract in 1–2 weeks',capital:'₦50k–₦200k (equipment)',difficulty:'2/5',bestFor:'Organized workers, Small teams, Anyone willing to work hard',steps:['Register business (CAC), get equipment: mops, vacuum, chemicals','Start with residential clients via Facebook/WhatsApp Groups','Target commercial: offices, restaurants, event centers','Build corporate contracts: MDAs, banks, schools','Scale with hired cleaners — take 30–50% management margin']},
  'digital-marketing-agency':{emoji:'📱',name:'Digital Marketing Agency',level:'medium',tags:['Remote','USD Potential','Scalable'],desc:'Run paid ads, manage social media, and create content for businesses. One client at ₦150k+/month builds steady recurring income.',income:{beginner:'₦50k–₦150k/month',normal:'₦300k–₦1.5m/month',advanced:'$5k–$30k/month'},time:'First client in 2–4 weeks',capital:'₦0–₦50k',difficulty:'3/5',bestFor:'Digital natives, Marketing students, People with ad skills',steps:['Learn: Meta Ads, Google Ads, TikTok Ads (free YouTube courses)','Run test campaigns for your own brand or a friend\'s business','Create case studies showing results','Cold outreach: Instagram DMs + WhatsApp + LinkedIn','Package services: Social Media (₦80k/m) + Ads management (₦100k+/m)']},
  'solar-installation':{emoji:'☀️',name:'Solar Installation Business',level:'medium',tags:['High Value','Tech','Growing Market'],desc:'Install solar panels and inverter systems for homes and businesses. Nigeria\'s power crisis makes solar the fastest-growing sector.',income:{beginner:'₦200k–₦500k/installation',normal:'₦500k–₦2m/installation',advanced:'₦2m–₦20m/installation'},time:'First job in 2–4 weeks',capital:'₦200k–₦500k (tools + inventory)',difficulty:'3/5',bestFor:'Electricians, Tech-minded people, Entrepreneurs',steps:['Get certified: NAEB solar installer certification (₦50k–₦100k)','Partner with a solar equipment supplier for credit terms','Start with small home installations: 1kVA–5kVA systems','Market to estate management boards, SMEs, and churches','Scale to commercial installations: ₦5m–₦50m projects']},
  'agric-inputs-supply':{emoji:'🌱',name:'Agricultural Inputs Distribution',level:'medium',tags:['B2B','Seasonal','High Volume'],desc:'Supply fertilizers, herbicides, seeds, and farm tools to smallholder farmers. One of the most profitable distribution businesses in Nigeria.',income:{beginner:'₦200k–₦800k/month',normal:'₦1m–₦5m/month',advanced:'₦5m–₦50m/month'},time:'First sale in 1–2 weeks',capital:'₦500k–₦5m',difficulty:'3/5',bestFor:'Traders, Agribusiness minded, People near farming communities',steps:['Register as an agro-input dealer (Ministry of Agriculture)','Source from AFEX, NASC, or direct manufacturer distributors','Set up near market or via direct farm visits','Offer credit to trusted farmers — build loyalty','Scale with government partnerships (FASCO, Anchor Borrowers)']},
  'palm-oil-processing':{emoji:'🫙',name:'Palm Oil Processing',level:'beginner',tags:['Agricultural','High Demand','Offline'],desc:'Process palm fruits into red palm oil and palm kernel oil. Massive demand from Lagos and Abuja markets with strong margins.',income:{beginner:'₦100k–₦300k/batch',normal:'₦500k–₦2m/month',advanced:'₦2m–₦10m/month'},time:'First batch in 2–4 weeks',capital:'₦500k–₦2m (processing machine)',difficulty:'3/5',bestFor:'Rural entrepreneurs, Farmers, Agribusiness people',steps:['Source palm fruits from local farmers at harvest','Rent or buy a palm oil processing machine (₦500k–₦2m)','Process and package: red palm oil in kegs, kernel oil separately','Sell to Lagos, PH, and Abuja wholesale distributors','Scale with cold storage and brand labeling for export']},
  'haulage-truck':{emoji:'🚛',name:'Haulage & Trucking Business',level:'medium',tags:['Offline','High Income','Capital Intensive'],desc:'Move goods across Nigeria\'s trade routes. One truck on a regular contract earns ₦400k–₦2m per month — fleet owners earn passively.',income:{beginner:'₦200k–₦500k/month',normal:'₦500k–₦2m/month',advanced:'₦5m–₦20m+/month'},time:'First contract in 2–4 weeks',capital:'₦3m–₦15m (truck)',difficulty:'3/5',bestFor:'Fleet owners, Capital investors, Logistics operators',steps:['Buy or lease a 7–18 ton truck (Tokunbo: ₦3m–₦15m)','Get FRSC commercial vehicle permit and LASDRI (Lagos)','Partner with freight companies: GIG, Fedex, YUGO Logistics','Target manufacturing companies for regular cargo contracts','Scale: buy more trucks, form a fleet management company']},
};

// ══════════════════════════════════════════════════════════
// ZONES
// ══════════════════════════════════════════════════════════
const ZONES = [
  {id:'all', label:'All Zones'},
  {id:'south-west', label:'South West'},
  {id:'south-east', label:'South East'},
  {id:'south-south', label:'South South'},
  {id:'north-west', label:'North West'},
  {id:'north-central', label:'North Central'},
  {id:'north-east', label:'North East'},
];

// State
let currentZone = 'all';
let searchQ = '';
let currentState = null;
let history = [];

// ══════════════════════════════════════════════════════════
// INIT
// ══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  renderZonePills();
  renderStateGrid();
});

function renderZonePills(){
  const wrap = document.getElementById('zone-pills');
  wrap.innerHTML = ZONES.map(z => 
    `<button class="zpill ${z.id===currentZone?'active':''}" onclick="setZone('${z.id}')">${z.label}</button>`
  ).join('');
}

function setZone(id){
  currentZone = id;
  renderZonePills();
  renderStateGrid();
}

function filterStates(){
  searchQ = document.getElementById('state-search').value.toLowerCase();
  renderStateGrid();
}

function switchTab(tab){
  const tabs = ['nigeria','countries'];
  tabs.forEach(t => {
    document.getElementById('tab-'+t).style.display = t===tab ? '' : 'none';
    const btns = document.querySelectorAll('.tab-btn');
    btns.forEach(b => {
      if(b.textContent.includes(t==='nigeria'?'Nigerian':'Countries')){
        b.classList.toggle('active', t===tab);
      }
    });
  });
}

function renderStateGrid(){
  const grid = document.getElementById('state-grid');
  const noStates = document.getElementById('no-states');
  
  let states = NIGERIA_STATES.filter(s => {
    const matchZone = currentZone === 'all' || s.zone === currentZone;
    const matchSearch = !searchQ || s.name.toLowerCase().includes(searchQ);
    return matchZone && matchSearch;
  });

  if(states.length === 0){
    grid.innerHTML = '';
    noStates.style.display = '';
    return;
  }
  noStates.style.display = 'none';

  grid.innerHTML = states.map(s => `
    <div class="scard ${s.hot?'hot':''}" onclick="openState('${s.id}')">
      <div class="sc-icon">${s.icon}</div>
      <div class="sc-info">
        <div class="sc-name">
          ${s.name}${s.hot?'<span class="hot-badge">HOT</span>':''}
        </div>
        <div class="sc-count">${s.topHustles.length} top hustles</div>
      </div>
    </div>
  `).join('');
}

// ══════════════════════════════════════════════════════════
// STATE DETAIL
// ══════════════════════════════════════════════════════════
function openState(id){
  const state = NIGERIA_STATES.find(s => s.id === id);
  if(!state) return;
  currentState = state;
  
  // Hide hub, show state panel
  document.getElementById('hub-page').style.display = 'none';
  const panel = document.getElementById('state-panel');
  panel.classList.add('active');
  document.getElementById('state-back-label').textContent = state.name;

  // Format tip with bold
  let tipHtml = state.tip.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

  const hustleCount = state.topHustles.length;

  document.getElementById('state-detail-content').innerHTML = `
    <!-- State Hero -->
    <div class="state-hero">
      <div class="state-hero-top">
        <div class="state-hero-icon">${state.icon}</div>
        <div class="state-hero-text">
          <h2>${state.name}</h2>
          <p>${state.tagline}</p>
        </div>
      </div>
      <div class="state-stats">
        <div class="stat-box"><div class="stat-val">${state.population}</div><div class="stat-lbl">Population</div></div>
        <div class="stat-box"><div class="stat-val">${state.internet}</div><div class="stat-lbl">Internet</div></div>
        <div class="stat-box"><div class="stat-val">${hustleCount}</div><div class="stat-lbl">Top Hustles</div></div>
        <div class="stat-box"><div class="stat-val" style="font-size:10px;line-height:1.3">${state.economy}</div><div class="stat-lbl">Economy</div></div>
      </div>
    </div>

    <!-- Market Intelligence -->
    <div class="market-intel">
      <div class="market-intel-hd">🧠 Market Intelligence</div>
      <p>${state.uniqueInsight}</p>
    </div>

    <!-- Best Zones -->
    <div class="zones-block">
      <div class="zones-hd">📍 BEST ZONES</div>
      <p>${tipHtml}</p>
    </div>

    <!-- Hustles -->
    <div class="hustles-hd">
      <span class="hustles-hd-icon">🔥</span>
      <span class="hustles-hd-text">TOP HUSTLES FOR ${state.name.toUpperCase()}</span>
    </div>
    <p class="hustles-tap-hint">Tap any hustle to see the full guide</p>

    <div class="hustle-list">
      ${state.topHustles.map(h => renderHustleCard(h, state)).join('')}
    </div>

    <div style="height:24px"></div>
  `;

  // Scroll to top
  window.scrollTo(0,0);
}

function renderHustleCard(h, state){
  const db = HUSTLE_DB[h.id] || {};
  const emoji = db.emoji || '💡';
  const name = db.name || h.id.replace(/-/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
  const why = h.why || '';
  const bonus = h.bonus || '';
  
  return `
    <div class="hcard" onclick="openHustle('${h.id}', '${state.id}')">
      <div class="hcard-icon">${emoji}</div>
      <div class="hcard-body">
        <div class="hcard-name">${name}</div>
        <div class="hcard-why">${why}</div>
        <div class="hcard-earn">
          <span class="hcard-earn-icon">💰</span>
          ${bonus}
        </div>
      </div>
      <div class="hcard-arr">›</div>
    </div>
  `;
}

function closeState(){
  document.getElementById('hub-page').style.display = '';
  document.getElementById('state-panel').classList.remove('active');
  currentState = null;
  window.scrollTo(0,0);
}

// ══════════════════════════════════════════════════════════
// HUSTLE DETAIL SHEET
// ══════════════════════════════════════════════════════════
function openHustle(hustleId, stateId){
  const db = HUSTLE_DB[hustleId];
  const state = NIGERIA_STATES.find(s => s.id === stateId);
  const stateHustle = state ? state.topHustles.find(h => h.id === hustleId) : null;

  const emoji = db ? db.emoji : '💡';
  const name = db ? db.name : hustleId.replace(/-/g,' ').replace(/\b\w/g,c=>c.toUpperCase());
  const level = db ? db.level : 'medium';
  const levelLabel = level === 'beginner' ? 'BEGINNER' : level === 'medium' ? 'MEDIUM' : 'ADVANCED';
  const desc = db ? db.desc : (stateHustle ? stateHustle.why : '');
  const income = db ? db.income : {};
  const time = db ? db.time : '—';
  const capital = db ? db.capital : '—';
  const difficulty = db ? db.difficulty : '—';
  const bestFor = db ? db.bestFor : '—';
  const steps = db ? db.steps : [];
  const bonus = stateHustle ? stateHustle.bonus : '';
  const why = stateHustle ? stateHustle.why : '';

  let html = `
    <div class="sheet-emoji">${emoji}</div>
    <div class="sheet-title">${name}</div>
    <div class="sheet-tags">
      <span class="stag stag-level ${level}">${levelLabel}</span>
      <span class="stag stag-match">🎯 ${state ? state.name : 'Nigeria'} Pick</span>
    </div>
    <div class="sheet-desc">${desc}</div>
  `;

  // Why this state
  if(why){
    html += `
      <div class="why-box">
        <div class="why-title">📍 Why ${state ? state.name : ''}</div>
        <div class="why-text">${why}</div>
        ${bonus ? `<div class="why-earn">💰 ${bonus}</div>` : ''}
      </div>
    `;
  }

  // Quick Facts
  html += `
    <div class="qf-title">Quick Facts</div>
    <div class="qf-grid">
      <div class="qf-box"><div class="qf-lbl">Time to Earn</div><div class="qf-val">${time}</div></div>
      <div class="qf-box"><div class="qf-lbl">Capital</div><div class="qf-val">${capital}</div></div>
      <div class="qf-box"><div class="qf-lbl">Difficulty</div><div class="qf-val">${difficulty}</div></div>
      <div class="qf-box"><div class="qf-lbl">Best For</div><div class="qf-val">${bestFor}</div></div>
    </div>
  `;

  // Income potential
  if(income.beginner || income.normal || income.advanced){
    html += `
      <div class="qf-title">Income Potential</div>
      <div class="income-box">
        ${income.beginner ? `<div class="income-row"><div class="income-dot" style="background:#16a05a"></div><span class="income-label">Beginner</span><span class="income-val">${income.beginner}</span></div>` : ''}
        ${income.normal ? `<div class="income-row"><div class="income-dot" style="background:#c8960a"></div><span class="income-label">Regular</span><span class="income-val">${income.normal}</span></div>` : ''}
        ${income.advanced ? `<div class="income-row"><div class="income-dot" style="background:#cc3333"></div><span class="income-label">Advanced</span><span class="income-val">${income.advanced}</span></div>` : ''}
      </div>
    `;
  }

  // Steps
  if(steps.length > 0){
    html += `
      <div class="qf-title">How to Start</div>
      <ul class="steps-list">
        ${steps.map((s,i) => `<li class="step-item"><div class="step-num">${i+1}</div><span>${s}</span></li>`).join('')}
      </ul>
    `;
  }

  document.getElementById('sheet-body').innerHTML = html;

  // CTA below sheet-inner
  const ctaHtml = `<button class="sheet-cta" onclick="window.location='<?= APP_URL ?>/hustle/${hustleId}'">View Full Guide →</button>`;
  document.getElementById('hustle-sheet').insertAdjacentHTML('beforeend', ctaHtml.replace(/PREV_CTA/,''));

  document.getElementById('sheet-overlay').classList.add('open');
  document.getElementById('hustle-sheet').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeSheet(){
  document.getElementById('sheet-overlay').classList.remove('open');
  document.getElementById('hustle-sheet').classList.remove('open');
  document.body.style.overflow = '';
  // Remove any extra CTAs
  const extras = document.querySelectorAll('#hustle-sheet .sheet-cta');
  if(extras.length > 0) extras[extras.length-1].remove();
}
</script>
</body>
</html>
