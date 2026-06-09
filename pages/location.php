<?php
/**
 * HustleKingdom — Location Hub
 * Routes: /location  /location/{state}  /location/{state}/{city}
 * Matches the video "Location Hustles" design exactly.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/Response.php';
Auth::start();

$user  = Auth::isLoggedIn() ? Auth::user() : null;
$isPro = Auth::isPro();

// ── Route params ─────────────────────────────────────────────────────────────
$rawState = trim($_GET['state'] ?? '');
$rawCity  = trim($_GET['city']  ?? '');
$tab      = $_GET['tab']   ?? 'nigeria'; // nigeria | countries
$zone     = $_GET['zone']  ?? 'all';     // all | south-west | south-east | etc.
$search   = trim($_GET['q'] ?? '');

$state = ucwords(str_replace('-', ' ', $rawState));
$city  = ucwords(str_replace('-', ' ', $rawCity));

// ── Nigerian states with meta ─────────────────────────────────────────────────
$STATES = [
    'Lagos'      => ['emoji'=>'🏙️','zone'=>'South West','hot'=>true],
    'Abuja (FCT)'=> ['emoji'=>'🏛️','zone'=>'North Central','hot'=>true,'slug'=>'abuja-fct'],
    'Kano'       => ['emoji'=>'🏺','zone'=>'North West','hot'=>true],
    'Rivers'     => ['emoji'=>'🏭','zone'=>'South South','hot'=>true],
    'Anambra'    => ['emoji'=>'🏢','zone'=>'South East','hot'=>true],
    'Oyo'        => ['emoji'=>'🦋','zone'=>'South West','hot'=>false],
    'Delta'      => ['emoji'=>'🌊','zone'=>'South South','hot'=>false],
    'Kaduna'     => ['emoji'=>'⚙️','zone'=>'North West','hot'=>true],
    'Enugu'      => ['emoji'=>'⛏️','zone'=>'South East','hot'=>false],
    'Ogun'       => ['emoji'=>'📦','zone'=>'South West','hot'=>false],
    'Imo'        => ['emoji'=>'🌿','zone'=>'South East','hot'=>false],
    'Cross River'=> ['emoji'=>'🌴','zone'=>'South South','hot'=>false],
    'Borno'      => ['emoji'=>'🏰','zone'=>'North East','hot'=>false],
    'Kogi'       => ['emoji'=>'✖️','zone'=>'North Central','hot'=>false],
    'Plateau'    => ['emoji'=>'🏔️','zone'=>'North Central','hot'=>false],
    'Akwa Ibom'  => ['emoji'=>'🌊','zone'=>'South South','hot'=>false],
    'Bauchi'     => ['emoji'=>'🐘','zone'=>'North East','hot'=>false],
    'Kwara'      => ['emoji'=>'🌿','zone'=>'North Central','hot'=>false],
    'Edo'        => ['emoji'=>'🏯','zone'=>'South South','hot'=>false],
    'Osun'       => ['emoji'=>'🌺','zone'=>'South West','hot'=>false],
    'Ekiti'      => ['emoji'=>'📚','zone'=>'South West','hot'=>false],
    'Ondo'       => ['emoji'=>'🌾','zone'=>'South West','hot'=>false],
    'Benue'      => ['emoji'=>'🌾','zone'=>'North Central','hot'=>false],
    'Ebonyi'     => ['emoji'=>'⛰️','zone'=>'South East','hot'=>false],
    'Nasarawa'   => ['emoji'=>'💎','zone'=>'North Central','hot'=>false],
    'Niger'      => ['emoji'=>'🌊','zone'=>'North Central','hot'=>false],
    'Sokoto'     => ['emoji'=>'🕌','zone'=>'North West','hot'=>false],
    'Kebbi'      => ['emoji'=>'🌾','zone'=>'North West','hot'=>false],
    'Zamfara'    => ['emoji'=>'🏜️','zone'=>'North West','hot'=>false],
    'Jigawa'     => ['emoji'=>'🌾','zone'=>'North West','hot'=>false],
    'Katsina'    => ['emoji'=>'🏜️','zone'=>'North West','hot'=>false],
    'Bayelsa'    => ['emoji'=>'🐟','zone'=>'South South','hot'=>false],
    'Gombe'      => ['emoji'=>'🌵','zone'=>'North East','hot'=>false],
    'Adamawa'    => ['emoji'=>'🦅','zone'=>'North East','hot'=>false],
    'Taraba'     => ['emoji'=>'🌿','zone'=>'North East','hot'=>false],
    'Yobe'       => ['emoji'=>'🏜️','zone'=>'North East','hot'=>false],
    'Abia'       => ['emoji'=>'🏭','zone'=>'South East','hot'=>false],
];

$ZONES = ['all'=>'All Zones','south-west'=>'South West','south-east'=>'South East','south-south'=>'South South','north-west'=>'North West','north-central'=>'North Central','north-east'=>'North East'];

// ── Filter states by zone + search ───────────────────────────────────────────
$displayStates = $STATES;
if ($zone !== 'all') {
    $zoneLabel = str_replace('-', ' ', $zone);
    $displayStates = array_filter($displayStates, fn($s) => strtolower($s['zone']) === strtolower($zoneLabel));
}
if ($search) {
    $displayStates = array_filter($displayStates, fn($name) => stripos($name, $search) !== false, ARRAY_FILTER_USE_KEY);
}

// ── Hustle counts per state ───────────────────────────────────────────────────
// Default counts (in a real app, query per state; here we provide sensible defaults)
$STATE_COUNTS = [
    'Lagos'=>53,'Abuja (FCT)'=>30,'Kano'=>30,'Rivers'=>30,'Anambra'=>30,
    'Oyo'=>30,'Delta'=>30,'Kaduna'=>30,'Enugu'=>30,'Ogun'=>30,
    'Imo'=>30,'Cross River'=>30,'Borno'=>6,'Kogi'=>8,'Plateau'=>8,
    'Akwa Ibom'=>30,'Bauchi'=>6,'Kwara'=>8,'Edo'=>30,'Osun'=>8,
    'Ekiti'=>6,'Ondo'=>8,'Benue'=>6,'Ebonyi'=>6,'Nasarawa'=>6,
    'Niger'=>6,'Sokoto'=>6,'Kebbi'=>6,'Zamfara'=>6,'Jigawa'=>6,
    'Katsina'=>6,'Bayelsa'=>6,'Gombe'=>6,'Adamawa'=>6,'Taraba'=>6,
    'Yobe'=>6,'Abia'=>30,
];

// ── Single state page ─────────────────────────────────────────────────────────
$isStatePage = !empty($state) && isset($STATES[$state]);
$statehustles = [];
$locationLabel = $state ?: 'Nigeria';

if ($isStatePage) {
    $locationLabel = $city && $city !== $state ? "$city, $state" : $state;
    try {
        $statehustles = DB::query(
            "SELECT id, name, slug, emoji, category, description,
                    income_min, income_max, income_period, difficulty,
                    skills_needed, capital_needed, location_tags, is_featured
             FROM hustles
             WHERE is_active = 1
               AND (
                   JSON_CONTAINS(location_tags, :state)
                   OR JSON_CONTAINS(location_tags, '\"Online\"')
                   OR JSON_CONTAINS(location_tags, '\"All Nigeria\"')
               )
             ORDER BY is_featured DESC, save_count DESC
             LIMIT 20",
            [':state' => json_encode($state)]
        );
    } catch (\Exception $e) {
        // fallback: any active hustles
        try {
            $statehustles = DB::query("SELECT id, name, slug, emoji, category, description, income_min, income_max, income_period, difficulty, capital_needed FROM hustles WHERE is_active=1 ORDER BY is_featured DESC, save_count DESC LIMIT 20", []);
        } catch (\Exception $e2) { $statehustles = []; }
    }
}

// SEO
$pageTitle  = $isStatePage ? "Top Hustles in {$locationLabel} | Hustle Kingdom" : "Find Hustles by Location | Hustle Kingdom";
$metaDesc   = $isStatePage ? "Discover the best side hustles in {$locationLabel}. Real income figures and step-by-step guides for Nigerian entrepreneurs." : "Find the best side hustles in your Nigerian state or country. 700+ hustle ideas with income data.";
$canonicalP = $isStatePage ? '/location/' . strtolower(str_replace(' ', '-', $state)) : '/location';
$canonicalU = APP_URL . $canonicalP;

function stateSlug(string $name): string {
    return strtolower(str_replace([' ', '(', ')'], ['-', '', ''], $name));
}

function diffColorLoc(string $d): string {
    return match($d) { 'beginner'=>'#16a05a', 'intermediate'=>'#c8960a', 'advanced'=>'#cc3333', default=>'#9a9a92' };
}
function incomeRangeLoc(array $h): string {
    $min = number_format((int)($h['income_min'] ?? 0));
    $max = number_format((int)($h['income_max'] ?? 0));
    return "₦{$min}–₦{$max}/" . ($h['income_period'] ?? 'month');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<link rel="canonical" href="<?= htmlspecialchars($canonicalU) ?>"/>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
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
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-bell{width:34px;height:34px;border-radius:50%;background:transparent;border:none;display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;position:relative;}
.nav-bell-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:var(--red,#cc3333);border-radius:50%;border:2px solid #fff;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,#0d4a6e 0%,#0f6e9e 55%,#1a9ecf 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.06);}
.hero-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:25px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;position:relative;z-index:1;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.72);line-height:1.55;position:relative;z-index:1;}

/* State page hero */
.hero-state{background:linear-gradient(135deg,#0d6e3c 0%,#16a05a 55%,#1ec870 100%);}

/* ── TABS ── */
.tab-row{display:flex;gap:0;padding:14px 16px;gap:8px;}
.tab-btn{flex:1;padding:11px;border-radius:12px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;border:none;cursor:pointer;text-decoration:none;text-align:center;transition:.2s;}
.tab-btn.active{background:var(--green);color:#fff;}
.tab-btn:not(.active){background:var(--surface);color:var(--text2);border:1.5px solid var(--border);}

/* ── SEARCH ── */
.search-wrap{padding:0 16px 12px;}
.search-box{display:flex;gap:8px;align-items:center;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:0 13px;height:46px;}
.search-box:focus-within{border-color:var(--green);}
.search-box input{flex:1;background:none;border:none;outline:none;font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);}
.search-box input::placeholder{color:var(--text3);}

/* ── ZONE PILLS ── */
.zone-scroll{display:flex;gap:7px;overflow-x:auto;scrollbar-width:none;padding:0 16px 12px;}
.zone-scroll::-webkit-scrollbar{display:none;}
.zpill{flex-shrink:0;padding:7px 14px;border-radius:20px;font-size:12px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;}
.zpill.active{background:var(--green);border-color:var(--green);color:#fff;}

/* ── STATE GRID ── */
.state-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 16px;}
.scard{background:#fff;border:1.5px solid var(--border);border-radius:14px;padding:13px 14px;display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);transition:.15s;}
.scard:active{background:var(--surface);}
.scard.hot{border-color:#b2e0c8;background:var(--green-light);}
.sc-icon{width:40px;height:40px;border-radius:10px;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.sc-info{flex:1;min-width:0;}
.sc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;display:flex;align-items:center;gap:6px;}
.hot-badge{font-size:9px;font-weight:800;background:var(--green);color:#fff;padding:2px 6px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.sc-count{font-size:11px;color:var(--text3);margin-top:2px;}

/* ── BREADCRUMB (state page) ── */
.breadcrumb{padding:10px 16px;font-size:12px;color:var(--text3);}
.breadcrumb a{color:var(--green);text-decoration:none;font-weight:600;}

/* ── HUSTLE CARDS (state page) ── */
.hustle-list-loc{padding:0 16px;display:flex;flex-direction:column;gap:10px;}
.hcard-loc{background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:14px;display:flex;gap:12px;text-decoration:none;color:var(--text);box-shadow:var(--sh);transition:.15s;}
.hcard-loc:active{background:var(--surface);}
.hcard-icon{width:46px;height:46px;border-radius:12px;background:var(--green-light);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;}
.hcard-body{flex:1;min-width:0;}
.hcard-title{font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;margin-bottom:3px;}
.hcard-desc{font-size:11.5px;color:var(--text3);line-height:1.5;margin-bottom:7px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.hcard-meta{display:flex;align-items:center;gap:6px;flex-wrap:wrap;}
.hcard-income{font-size:11.5px;font-weight:800;color:var(--green3);font-family:'Bricolage Grotesque',sans-serif;}
.diff-badge{font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.hcard-arr{font-size:18px;color:var(--text3);flex-shrink:0;align-self:center;}

/* ── CTA BANNER ── */
.cta-banner{margin:20px 16px 0;background:linear-gradient(135deg,var(--gold-light),#fffdf5);border:1.5px solid #e8d080;border-radius:var(--r);padding:18px;}
.cta-title{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;margin-bottom:4px;}
.cta-sub{font-size:13px;color:var(--text2);line-height:1.55;margin-bottom:14px;}
.cta-btn{display:block;text-align:center;background:var(--green);color:#fff;text-decoration:none;border-radius:12px;padding:14px;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;}

/* ── SECTION HEAD ── */
.sec-head{padding:16px 16px 10px;display:flex;align-items:center;justify-content:space-between;}
.sec-title{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;}
.sec-count{font-size:12px;color:var(--text3);font-weight:600;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
.bnav.active{color:var(--green);}
.bni{font-size:20px;}.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;text-decoration:none;}
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
      <?php if ($isPro): ?><span class="pro-chip">⭐ PRO</span><?php else: ?><a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a><?php endif; ?>
      <button class="nav-bell">🔔<span class="nav-bell-dot"></span></button>
      <a href="<?= APP_URL ?>/dashboard" class="nav-avatar"><?= htmlspecialchars($user['avatar_emoji'] ?? '👤') ?></a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/auth/register" class="pro-chip">Get Started →</a>
    <?php endif; ?>
  </div>
</nav>

<?php if ($isStatePage): ?>
<!-- ── STATE PAGE ── -->
<div class="breadcrumb">
  <a href="<?= APP_URL ?>/location">Location</a> ›
  <a href="<?= APP_URL ?>/location/<?= stateSlug($state) ?>"><?= htmlspecialchars($state) ?></a>
  <?php if ($city && $city !== $state): ?> › <?= htmlspecialchars($city) ?><?php endif; ?>
</div>

<div class="hero hero-state">
  <div class="hero-chip">📍 <?= htmlspecialchars($locationLabel) ?></div>
  <div class="hero-title">Best Hustles in<br/><?= htmlspecialchars($locationLabel) ?></div>
  <div class="hero-sub">Real income ranges, step-by-step guides, and everything to start earning in <?= htmlspecialchars($state) ?> today.</div>
</div>

<div class="sec-head">
  <div class="sec-title">Hustles in <?= htmlspecialchars($locationLabel) ?></div>
  <div class="sec-count"><?= count($statehustles) ?>+ found</div>
</div>

<div class="hustle-list-loc">
  <?php if (empty($statehustles)): ?>
    <div style="text-align:center;padding:40px 16px;color:var(--text3);">
      <div style="font-size:40px;margin-bottom:12px;">🔍</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:var(--text2);margin-bottom:6px;">No hustles yet for <?= htmlspecialchars($locationLabel) ?></div>
      <p style="font-size:13px;">We're adding more daily. <a href="<?= APP_URL ?>/hustles" style="color:var(--green);font-weight:700;">Browse all hustles →</a></p>
    </div>
  <?php else: ?>
    <?php foreach ($statehustles as $h): ?>
      <a href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>" class="hcard-loc">
        <div class="hcard-icon"><?= htmlspecialchars($h['emoji']) ?></div>
        <div class="hcard-body">
          <div class="hcard-title"><?= htmlspecialchars($h['name']) ?></div>
          <div class="hcard-desc"><?= htmlspecialchars($h['description']) ?></div>
          <div class="hcard-meta">
            <span class="hcard-income"><?= incomeRangeLoc($h) ?></span>
            <span class="diff-badge" style="background:<?= diffColorLoc($h['difficulty']) ?>22;color:<?= diffColorLoc($h['difficulty']) ?>;"><?= ucfirst($h['difficulty']) ?></span>
          </div>
        </div>
        <div class="hcard-arr">›</div>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<div class="cta-banner">
  <div class="cta-title">🚀 Ready to hustle in <?= htmlspecialchars($locationLabel) ?>?</div>
  <div class="cta-sub">Get personalised hustle matches, income tracking, and step-by-step roadmaps — built for Nigerian entrepreneurs.</div>
  <a href="<?= APP_URL ?>/auth/register" class="cta-btn">Join Free → Start Earning</a>
</div>

<?php else: ?>
<!-- ── HUB PAGE ── -->
<div class="hero">
  <div class="hero-chip">📍 LOCATION HUSTLES</div>
  <div class="hero-title">Find Hustles Near You</div>
  <div class="hero-sub">Browse by Nigerian state or explore 50+ countries worldwide.</div>
</div>

<!-- Tabs: Nigerian States / Countries -->
<div class="tab-row">
  <a href="<?= APP_URL ?>/location?tab=nigeria" class="tab-btn <?= $tab==='nigeria'?'active':'' ?>">
    🇳🇬 Nigerian States
  </a>
  <a href="<?= APP_URL ?>/location?tab=countries" class="tab-btn <?= $tab==='countries'?'active':'' ?>">
    🌍 Countries
  </a>
</div>

<?php if ($tab === 'countries'): ?>
  <!-- Countries placeholder -->
  <div style="padding:40px 16px;text-align:center;color:var(--text3);">
    <div style="font-size:48px;margin-bottom:12px;">🌍</div>
    <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:var(--text2);margin-bottom:8px;">50+ Countries Coming Soon</div>
    <p style="font-size:13px;line-height:1.6;">We're mapping hustle opportunities worldwide. Start with Nigerian states for now.</p>
    <a href="<?= APP_URL ?>/location?tab=nigeria" style="display:inline-block;margin-top:16px;background:var(--green);color:#fff;border-radius:12px;padding:12px 24px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;">Browse Nigerian States</a>
  </div>
<?php else: ?>
  <!-- Search -->
  <div class="search-wrap">
    <form method="GET" action="<?= APP_URL ?>/location">
      <input type="hidden" name="tab" value="nigeria"/>
      <?php if ($zone !== 'all'): ?><input type="hidden" name="zone" value="<?= htmlspecialchars($zone) ?>"/><?php endif; ?>
      <div class="search-box">
        <span style="font-size:16px;color:var(--text3);">🔍</span>
        <input type="text" name="q" placeholder="Search state…" value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
      </div>
    </form>
  </div>

  <!-- Zone filter -->
  <div class="zone-scroll">
    <?php foreach ($ZONES as $zSlug => $zLabel): ?>
      <a href="<?= APP_URL ?>/location?tab=nigeria&zone=<?= $zSlug ?><?= $search ? '&q='.urlencode($search) : '' ?>"
         class="zpill <?= $zone === $zSlug ? 'active' : '' ?>"><?= htmlspecialchars($zLabel) ?></a>
    <?php endforeach; ?>
  </div>

  <!-- State grid -->
  <div class="state-grid">
    <?php foreach ($displayStates as $sName => $sMeta):
      $slug  = $sMeta['slug'] ?? stateSlug($sName);
      $count = $STATE_COUNTS[$sName] ?? 6;
      $isHot = $sMeta['hot'] ?? false;
    ?>
      <a href="<?= APP_URL ?>/location/<?= $slug ?>" class="scard <?= $isHot ? 'hot' : '' ?>">
        <div class="sc-icon"><?= $sMeta['emoji'] ?></div>
        <div class="sc-info">
          <div class="sc-name">
            <?= htmlspecialchars($sName) ?>
            <?php if ($isHot): ?><span class="hot-badge">HOT</span><?php endif; ?>
          </div>
          <div class="sc-count"><?= $count ?> top hustles</div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php endif; ?>

<div style="height:16px;"></div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav active"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

</body>
</html>
