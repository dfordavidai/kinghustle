<?php
/**
 * HustleKingdom — Ideas / Discovery Page
 * Route: GET /ideas
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
Auth::start();

$user  = Auth::isLoggedIn() ? Auth::user() : null;
$isPro = Auth::isPro();

// Fetch featured hustles per category (top 3 each)
$categories = [
    'digital'   => ['💻', 'Digital & Tech',       'cc-digital',  'Build online income from code, apps & services'],
    'social'    => ['📱', 'Social & Content',      'cc-social',   'Monetise your audience on any platform'],
    'ai'        => ['🤖', 'AI-Powered',            'cc-ai',       'Use AI tools to earn faster & smarter'],
    'trade'     => ['🛒', 'Trading & Commerce',    'cc-trade',    'Buy, sell and flip for consistent profit'],
    'creative'  => ['🎨', 'Creative & Media',      'cc-creative', 'Turn your creative skills into income streams'],
    'agro'      => ['🌾', 'Agro & Food',           'cc-agro',     'Farm, process or distribute food products'],
    'trades'    => ['🏗️', 'Trades & Labour',       'cc-trades',   'Skilled hands-on work that pays well'],
    'health'    => ['💆', 'Health & Beauty',       'cc-health',   'Wellness, beauty and personal care services'],
    'education' => ['🎓', 'Education & Coaching',  'cc-edu',      'Share knowledge and coach others to grow'],
];

// Load top 3 hustles for each category
$catHustles = [];
foreach (array_keys($categories) as $slug) {
    $catHustles[$slug] = DB::query(
        'SELECT id, name, slug, emoji, income_min, income_max, income_period, difficulty
         FROM hustles WHERE category = :cat AND is_active = 1
         ORDER BY is_featured DESC, save_count DESC LIMIT 3',
        [':cat' => $slug]
    );
}

// Hustle counts per category
$counts = DB::query(
    'SELECT category, COUNT(*) as cnt FROM hustles WHERE is_active = 1 GROUP BY category',
    []
);
$countMap = [];
foreach ($counts as $row) { $countMap[$row['category']] = (int)$row['cnt']; }

// Trending hustles (most saves, last 30 days)
$trending = DB::query(
    'SELECT h.id, h.name, h.slug, h.emoji, h.income_min, h.income_max, h.income_period,
            h.difficulty, h.category
     FROM hustles h WHERE h.is_active = 1
     ORDER BY h.save_count DESC, h.is_featured DESC LIMIT 10',
    []
);

// Zero capital hustles
$freehustles = DB::query(
    'SELECT id, name, slug, emoji, income_min, income_max, income_period, difficulty
     FROM hustles WHERE is_active = 1 AND capital_needed = 0
     ORDER BY save_count DESC LIMIT 8',
    []
);

$diffColors = ['beginner' => 'tg', 'intermediate' => 'to', 'advanced' => 'tr'];
$diffLabels = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];

function fmtM(float $n): string {
    if ($n >= 1_000_000) return '₦' . number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return '₦' . number_format($n / 1_000, 1) . 'k';
    return '₦' . number_format($n, 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Hustle Ideas — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#ffffff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --orange:#d45820;--orange-light:#fdf0eb;
  --red:#cc3333;--red-light:#fdf0f0;
  --purple:#6b35c8;--purple-light:#f3eefe;
  --teal:#0e9488;--teal-light:#ebfaf8;
  --r:16px;--nav:58px;--bot:64px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;height:0;}

.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;text-decoration:none;}

/* ── PAGE HERO ── */
.page-hero{padding:20px 16px 16px;background:linear-gradient(140deg,#0d6e3c 0%,#16a05a 60%,#22c87a 100%);position:relative;overflow:hidden;}
.page-hero::before{content:'';position:absolute;right:-40px;top:-40px;width:160px;height:160px;border-radius:50%;background:rgba(255,255,255,.06);}
.ph-label{font-size:11.5px;color:rgba(255,255,255,.65);font-weight:600;margin-bottom:4px;}
.ph-title{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;color:#fff;margin-bottom:6px;}
.ph-sub{font-size:13px;color:rgba(255,255,255,.75);line-height:1.55;}

/* ── SECTIONS ── */
.sec-head{display:flex;align-items:center;justify-content:space-between;padding:0 16px;margin:22px 0 12px;}
.sec-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;display:flex;align-items:center;gap:7px;}
.sec-all{font-size:12px;font-weight:700;color:var(--green);text-decoration:none;}

/* ── TRENDING SCROLL ── */
.trend-scroll{display:flex;gap:10px;overflow-x:auto;padding:0 16px 8px;scrollbar-width:none;}
.tcard{flex-shrink:0;width:148px;background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:13px;text-decoration:none;color:var(--text);}
.tcard:active{background:var(--surface);}
.tc-emoji{font-size:26px;margin-bottom:6px;display:block;}
.tc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:12.5px;font-weight:800;margin-bottom:4px;line-height:1.3;}
.tc-income{font-size:11px;color:var(--green3);font-weight:700;margin-bottom:6px;}

/* ── CATEGORY CARDS ── */
.catlist{display:flex;flex-direction:column;gap:12px;padding:0 16px;}
.catblock{border:1.5px solid var(--border);border-radius:var(--r);overflow:hidden;}
.catblock-header{display:flex;align-items:center;justify-content:space-between;padding:13px 14px;cursor:pointer;touch-action:manipulation;background:var(--surface);}
.cbh-left{display:flex;align-items:center;gap:10px;}
.cbh-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:19px;flex-shrink:0;}
.cbh-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;}
.cbh-desc{font-size:11px;color:var(--text3);margin-top:2px;line-height:1.4;}
.cbh-count{font-size:11px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;color:var(--green);white-space:nowrap;}
.catblock-items{display:none;border-top:1px solid var(--border);}
.catblock-items.open{display:block;}
.cat-item{display:flex;align-items:center;gap:11px;padding:11px 14px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);}
.cat-item:last-child{border-bottom:none;}
.cat-item:active{background:var(--surface);}
.ci-emoji{font-size:20px;width:32px;text-align:center;flex-shrink:0;}
.ci-name{font-size:13px;font-weight:700;flex:1;}
.ci-income{font-size:11px;color:var(--green3);font-weight:700;}
.cat-see-all{display:block;text-align:center;padding:10px;font-size:12px;font-weight:800;color:var(--green);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;border-top:1px solid var(--border);}

/* ── FREE HUSTLE GRID ── */
.free-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;padding:0 16px;}
.fcard{background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:var(--r);padding:13px;text-decoration:none;color:var(--text);}
.fcard:active{opacity:.85;}
.fc-emoji{font-size:22px;margin-bottom:6px;}
.fc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;margin-bottom:3px;line-height:1.3;}
.fc-income{font-size:11px;color:var(--green3);font-weight:700;}

/* ── TAGS ── */
.tag{font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.to{background:var(--orange-light);color:var(--orange);}
.tr{background:var(--red-light);color:var(--red);}

/* Category colour accents */
.cc-digital .cbh-icon{background:#e6f1fb;} .cc-social .cbh-icon{background:#eeedfe;} .cc-ai .cbh-icon{background:#faeeda;}
.cc-trade .cbh-icon{background:#faece7;}  .cc-creative .cbh-icon{background:#fcebeb;} .cc-agro .cbh-icon{background:#eaf7f1;}
.cc-trades .cbh-icon{background:#e1f5ee;} .cc-health .cbh-icon{background:#e6f1fb;} .cc-edu .cbh-icon{background:#fbeaf0;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;cursor:pointer;border:none;background:transparent;color:var(--text3);text-decoration:none;}
.bnav.active{color:var(--green);}
.bni{font-size:18px;}.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}
</style>
</head>
<body>

<nav class="topnav">
  <a href="<?= APP_URL ?>/" class="nav-brand">
    <div class="nav-logo-box">👑</div>
    Hustle<em>Kingdom</em>
  </a>
  <div class="nav-r">
    <?php if ($user): ?>
      <?php if ($isPro): ?><span class="pro-chip">⭐ PRO</span><?php else: ?><a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a><?php endif; ?>
      <a href="<?= APP_URL ?>/dashboard" class="nav-avatar"><?= htmlspecialchars($user['avatar_emoji']) ?></a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/auth/login" class="pro-chip">Login →</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Hero -->
<div class="page-hero">
  <div class="ph-label">Discover your next move</div>
  <div class="ph-title">💡 Hustle Ideas</div>
  <div class="ph-sub">Browse <?= number_format(array_sum($countMap)) ?>+ hustles across 9 categories — from zero capital to serious income.</div>
</div>

<!-- Trending -->
<div class="sec-head">
  <div class="sec-title">🔥 Trending Hustles</div>
  <a href="<?= APP_URL ?>/hustles" class="sec-all">See all →</a>
</div>
<div class="trend-scroll">
  <?php foreach ($trending as $h): ?>
    <a class="tcard" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
      <span class="tc-emoji"><?= htmlspecialchars($h['emoji']) ?></span>
      <div class="tc-name"><?= htmlspecialchars($h['name']) ?></div>
      <div class="tc-income"><?= fmtM((float)$h['income_min']) ?>–<?= fmtM((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
      <span class="tag <?= $diffColors[$h['difficulty']] ?? 'tg' ?>"><?= $diffLabels[$h['difficulty']] ?? '' ?></span>
    </a>
  <?php endforeach; ?>
</div>

<!-- Free to start -->
<?php if (!empty($freehustles)): ?>
<div class="sec-head">
  <div class="sec-title">🆓 Start for Free</div>
  <a href="<?= APP_URL ?>/hustles?cap=0" class="sec-all">See all →</a>
</div>
<div class="free-grid">
  <?php foreach (array_slice($freehustles, 0, 6) as $h): ?>
    <a class="fcard" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
      <div class="fc-emoji"><?= htmlspecialchars($h['emoji']) ?></div>
      <div class="fc-name"><?= htmlspecialchars($h['name']) ?></div>
      <div class="fc-income"><?= fmtM((float)$h['income_min']) ?>–<?= fmtM((float)$h['income_max']) ?>/mo</div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Category accordion list -->
<div class="sec-head">
  <div class="sec-title">📂 Browse by Category</div>
</div>

<div class="catlist">
  <?php foreach ($categories as $slug => [$icon, $name, $cls, $desc]): ?>
  <div class="catblock <?= $cls ?>">
    <div class="catblock-header" onclick="toggleCat('cat-<?= $slug ?>')">
      <div class="cbh-left">
        <div class="cbh-icon"><?= $icon ?></div>
        <div>
          <div class="cbh-name"><?= $name ?></div>
          <div class="cbh-desc"><?= $desc ?></div>
        </div>
      </div>
      <div class="cbh-count"><?= $countMap[$slug] ?? 0 ?> →</div>
    </div>
    <div class="catblock-items" id="cat-<?= $slug ?>">
      <?php foreach ($catHustles[$slug] as $h): ?>
        <a class="cat-item" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
          <div class="ci-emoji"><?= htmlspecialchars($h['emoji']) ?></div>
          <div class="ci-name"><?= htmlspecialchars($h['name']) ?></div>
          <div class="ci-income"><?= fmtM((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
        </a>
      <?php endforeach; ?>
      <a class="cat-see-all" href="<?= APP_URL ?>/hustles?cat=<?= $slug ?>">See all <?= $name ?> hustles →</a>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div style="height:12px;"></div>

<!-- Bottom Nav -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">🔥</div><div class="bnl">Hustles</div></a>
  <a href="<?= APP_URL ?>/dashboard" class="bnav-ctr">
    <div class="bnav-ctr-icon">📊</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Dash</div>
  </a>
  <a href="<?= APP_URL ?>/ideas" class="bnav active"><div class="bni">💡</div><div class="bnl">Ideas</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
function toggleCat(id) {
  const el = document.getElementById(id);
  el.classList.toggle('open');
}
</script>
</body>
</html>
