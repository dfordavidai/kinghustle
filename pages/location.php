<?php
/**
 * HustleKingdom — Location Page
 * Routes: /location/{state}  and  /location/{state}/{city}
 * Full SSR page — SEO-indexed, Schema.org ItemList, city-aware hustles.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/Response.php';

Auth::start();

// ── Resolve state/city from router-injected $_GET ────────────────────────────
$rawState = trim($_GET['state'] ?? '');
$rawCity  = trim($_GET['city']  ?? '');

// Normalise: replace hyphens back to spaces, title-case
$state = ucwords(str_replace('-', ' ', $rawState));
$city  = ucwords(str_replace('-', ' ', $rawCity));

// Valid Nigerian states
$VALID_STATES = [
    'Abia','Adamawa','Akwa Ibom','Anambra','Bauchi','Bayelsa','Benue','Borno',
    'Cross River','Delta','Ebonyi','Edo','Ekiti','Enugu','FCT','Gombe','Imo',
    'Jigawa','Kaduna','Kano','Katsina','Kebbi','Kogi','Kwara','Lagos','Nasarawa',
    'Niger','Ogun','Ondo','Osun','Oyo','Plateau','Rivers','Sokoto','Taraba','Yobe','Zamfara'
];

// Fallback to Lagos if invalid/empty
if (!in_array($state, $VALID_STATES, true)) {
    $state = 'Lagos';
    $city  = 'Lagos';
}

// ── Population estimates for social proof ────────────────────────────────────
$STATE_POP = [
    'Lagos'=>'22M','Kano'=>'15M','Oyo'=>'10M','Rivers'=>'8M','Kaduna'=>'8M',
    'Katsina'=>'8M','Ogun'=>'7M','Borno'=>'7M','Anambra'=>'6M','Imo'=>'6M',
];
$pop = $STATE_POP[$state] ?? '5M';

// ── Fetch hustles for this location ─────────────────────────────────────────
// Match state-specific OR 'Online' (works everywhere) OR 'All Nigeria'
$hustles = DB::query(
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
     LIMIT 12",
    [':state' => json_encode($state)]
);

// Decode JSON fields
foreach ($hustles as &$h) {
    $h['skills_needed'] = json_decode($h['skills_needed'] ?? '[]', true);
    $h['location_tags'] = json_decode($h['location_tags'] ?? '[]', true);
}
unset($h);

// ── SEO Metadata ─────────────────────────────────────────────────────────────
$locationLabel = $city && $city !== $state ? "$city, $state" : $state;
$pageTitle     = "Top Side Hustles in {$locationLabel} 2025 | Hustle Kingdom";
$metaDesc      = "Discover the best side hustles in {$locationLabel}. Real income figures, step-by-step guides and 700+ hustle ideas for Nigerian entrepreneurs in {$state}.";
$canonicalPath = '/location/' . strtolower(str_replace(' ', '-', $state))
               . ($city && $city !== $state ? '/' . strtolower(str_replace(' ', '-', $city)) : '');
$canonicalUrl  = APP_URL . $canonicalPath;

// ── Schema.org ItemList ──────────────────────────────────────────────────────
$schemaItems = [];
foreach ($hustles as $i => $h) {
    $schemaItems[] = [
        '@type'    => 'ListItem',
        'position' => $i + 1,
        'name'     => $h['name'],
        'url'      => APP_URL . '/hustle/' . $h['slug'],
        'description' => $h['description'],
    ];
}
$schema = json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'ItemList',
    'name'            => "Side Hustles in {$locationLabel}",
    'description'     => $metaDesc,
    'numberOfItems'   => count($hustles),
    'itemListElement' => $schemaItems,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Difficulty helpers
function diffColor(string $d): string {
    return match($d) {
        'beginner'     => '#16a05a',
        'intermediate' => '#c8960a',
        'advanced'     => '#cc3333',
        default        => '#9a9a92',
    };
}
function incomeRange(array $h): string {
    $min = number_format((int)($h['income_min'] ?? 0));
    $max = number_format((int)($h['income_max'] ?? 0));
    $per = $h['income_period'] ?? 'month';
    return "₦{$min}–₦{$max}/{$per}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>"/>
<meta property="og:title"       content="<?= htmlspecialchars($pageTitle) ?>"/>
<meta property="og:description" content="<?= htmlspecialchars($metaDesc) ?>"/>
<meta property="og:url"         content="<?= htmlspecialchars($canonicalUrl) ?>"/>
<meta property="og:type"        content="website"/>
<meta name="twitter:card"       content="summary"/>
<script type="application/ld+json"><?= $schema ?></script>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;--gold:#c8960a;--gold-light:#fdf6e3;--r:16px;--sh:0 2px 16px rgba(0,0,0,0.06);}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;}

/* NAV */
.nav{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border);position:sticky;top:0;background:rgba(255,255,255,.96);backdrop-filter:blur(10px);z-index:100;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;text-decoration:none;color:var(--text);display:flex;align-items:center;gap:8px;}
.nav-logo{width:28px;height:28px;background:var(--green);border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:14px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-cta{background:var(--green);color:#fff;border:none;border-radius:9px;padding:8px 14px;font-size:12px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;cursor:pointer;}

/* HERO */
.hero{background:linear-gradient(140deg,#0d6e3c,#16a05a 55%,#20c870);padding:28px 18px 32px;overflow:hidden;position:relative;}
.hero::before{content:'';position:absolute;right:-50px;top:-50px;width:220px;height:220px;border-radius:50%;background:rgba(255,255,255,.05);}
.hero-eyebrow{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.hero-h{font-family:'Bricolage Grotesque',sans-serif;font-size:27px;font-weight:800;color:#fff;line-height:1.13;margin-bottom:8px;position:relative;z-index:1;}
.hero-h span{color:#a3f0c8;}
.hero-p{font-size:13px;color:rgba(255,255,255,.78);line-height:1.6;margin-bottom:20px;position:relative;z-index:1;}
.hero-stats{display:flex;gap:8px;position:relative;z-index:1;}
.hstat{flex:1;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:10px;text-align:center;}
.hstat-n{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;color:#fff;}
.hstat-l{font-size:10px;color:rgba(255,255,255,.7);margin-top:2px;}

/* BREADCRUMB */
.breadcrumb{padding:12px 16px;font-size:12px;color:var(--text3);}
.breadcrumb a{color:var(--green);text-decoration:none;font-weight:600;}

/* SECTION */
.sec-head{display:flex;align-items:center;justify-content:space-between;padding:0 16px;margin:20px 0 12px;}
.sec-title{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;}
.sec-count{font-size:12px;color:var(--text3);font-weight:600;}

/* HUSTLE CARDS */
.hustle-grid{padding:0 16px;display:flex;flex-direction:column;gap:10px;}
.hcard{background:#fff;border:1px solid var(--border);border-radius:var(--r);padding:15px;display:flex;gap:13px;text-decoration:none;color:var(--text);transition:.15s;box-shadow:var(--sh);}
.hcard:active{background:var(--surface);}
.hcard-icon{width:48px;height:48px;border-radius:12px;background:var(--green-light);display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
.hcard-body{flex:1;min-width:0;}
.hcard-title{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;margin-bottom:4px;}
.hcard-desc{font-size:12px;color:var(--text2);line-height:1.5;margin-bottom:9px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.hcard-meta{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.hcard-income{font-size:11.5px;font-weight:700;color:var(--green3);font-family:'Bricolage Grotesque',sans-serif;}
.diff-badge{font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.hcard-arr{font-size:16px;color:var(--text3);flex-shrink:0;align-self:center;}

/* NEARBY STATES */
.states-section{margin:28px 0 0;padding:0 16px 32px;}
.states-title{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;margin-bottom:10px;}
.states-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
.state-chip{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:9px 8px;text-align:center;text-decoration:none;color:var(--text);}
.state-chip:hover{border-color:var(--green);color:var(--green);}
.state-chip-name{font-size:12px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}

/* CTA BANNER */
.cta-banner{margin:24px 16px;background:linear-gradient(135deg,var(--gold-light),#fffdf5);border:1.5px solid #e8d080;border-radius:var(--r);padding:18px;}
.cta-banner h3{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;margin-bottom:5px;}
.cta-banner p{font-size:13px;color:var(--text2);line-height:1.55;margin-bottom:14px;}
.cta-btn{display:block;text-align:center;background:var(--green);color:#fff;text-decoration:none;border-radius:12px;padding:14px;font-size:14px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;}

/* EMPTY STATE */
.empty{text-align:center;padding:40px 20px;color:var(--text2);}
.empty-icon{font-size:48px;margin-bottom:12px;}
.empty h3{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;margin-bottom:6px;}
.empty p{font-size:13px;line-height:1.6;}

/* FOOTER */
footer{border-top:1px solid var(--border);padding:20px 16px;text-align:center;font-size:11.5px;color:var(--text3);}
footer a{color:var(--green);text-decoration:none;font-weight:600;}
</style>
</head>
<body>

<!-- NAV -->
<nav class="nav">
  <a href="<?= APP_URL ?>" class="nav-brand">
    <div class="nav-logo">👑</div>
    Hustle<em>Kingdom</em>
  </a>
  <a href="<?= APP_URL ?>/auth/register" class="nav-cta">Get Started →</a>
</nav>

<!-- BREADCRUMB -->
<div class="breadcrumb">
  <a href="<?= APP_URL ?>">Home</a> › 
  <a href="<?= APP_URL ?>/location/<?= strtolower(str_replace(' ', '-', $state)) ?>"><?= htmlspecialchars($state) ?></a>
  <?php if ($city && $city !== $state): ?> › <?= htmlspecialchars($city) ?><?php endif; ?>
</div>

<!-- HERO -->
<section class="hero">
  <div class="hero-eyebrow">📍 <?= htmlspecialchars($locationLabel) ?></div>
  <h1 class="hero-h">
    Best Side Hustles in<br/>
    <span><?= htmlspecialchars($locationLabel) ?></span>
  </h1>
  <p class="hero-p">
    Real hustle ideas with actual income ranges, step-by-step guides, and everything you need to start earning in <?= htmlspecialchars($state) ?> today.
  </p>
  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-n"><?= count($hustles) ?>+</div>
      <div class="hstat-l">Hustles found</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?= htmlspecialchars($pop) ?></div>
      <div class="hstat-l">People in <?= htmlspecialchars($state) ?></div>
    </div>
    <div class="hstat">
      <div class="hstat-n">₦0</div>
      <div class="hstat-l">To get started</div>
    </div>
  </div>
</section>

<!-- HUSTLES -->
<div class="sec-head">
  <div class="sec-title">Hustles in <?= htmlspecialchars($locationLabel) ?></div>
  <div class="sec-count"><?= count($hustles) ?> found</div>
</div>

<div class="hustle-grid">
<?php if (empty($hustles)): ?>
  <div class="empty">
    <div class="empty-icon">🔍</div>
    <h3>No hustles yet for <?= htmlspecialchars($locationLabel) ?></h3>
    <p>We're adding more daily. Browse all hustles on the main app.</p>
  </div>
<?php else: ?>
  <?php foreach ($hustles as $h): ?>
    <?php
      $dc = diffColor($h['difficulty']);
      $diffLabel = ucfirst($h['difficulty']);
    ?>
    <a href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>" class="hcard">
      <div class="hcard-icon"><?= htmlspecialchars($h['emoji']) ?></div>
      <div class="hcard-body">
        <div class="hcard-title"><?= htmlspecialchars($h['name']) ?></div>
        <div class="hcard-desc"><?= htmlspecialchars($h['description']) ?></div>
        <div class="hcard-meta">
          <span class="hcard-income"><?= incomeRange($h) ?></span>
          <span class="diff-badge" style="background:<?= $dc ?>22;color:<?= $dc ?>;"><?= $diffLabel ?></span>
        </div>
      </div>
      <div class="hcard-arr">›</div>
    </a>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- CTA BANNER -->
<div class="cta-banner">
  <h3>🚀 Ready to start your hustle in <?= htmlspecialchars($locationLabel) ?>?</h3>
  <p>Get personalised hustle matches, income tracking, and step-by-step roadmaps — built for Nigerian entrepreneurs.</p>
  <a href="<?= APP_URL ?>/auth/register" class="cta-btn">Join Free → Start Earning</a>
</div>

<!-- OTHER STATES -->
<div class="states-section">
  <div class="states-title">Browse by State</div>
  <div class="states-grid">
    <?php
    $showStates = array_diff($VALID_STATES, [$state]);
    shuffle($showStates);
    foreach (array_slice($showStates, 0, 9) as $s):
      $slug = strtolower(str_replace(' ', '-', $s));
    ?>
      <a href="<?= APP_URL ?>/location/<?= $slug ?>" class="state-chip">
        <div class="state-chip-name"><?= htmlspecialchars($s) ?></div>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<footer>
  &copy; <?= date('Y') ?> <a href="<?= APP_URL ?>">Hustle Kingdom</a> · 
  <a href="<?= APP_URL ?>/auth/register">Get Started Free</a>
</footer>

</body>
</html>
