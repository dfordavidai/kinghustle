<?php
/**
 * HustleKingdom — Hustle Detail Page
 * Route: /hustle/{slug}
 * Full server-rendered SEO page with Schema.org HowTo markup.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Auth.php';
Auth::start();

$slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($_GET['slug'] ?? ''));
if (!$slug) { header('Location: ' . APP_URL . '/'); exit; }

$hustle = DB::one(
    'SELECT * FROM hustles WHERE slug = :slug AND is_active = 1',
    [':slug' => $slug]
);
if (!$hustle) {
    http_response_code(404);
    include HK_ROOT . '/pages/404.php';
    exit;
}

// Decode JSON fields
$skills = json_decode($hustle['skills_needed'] ?? '[]', true) ?: [];
$locs   = json_decode($hustle['location_tags']  ?? '[]', true) ?: [];
$steps  = json_decode($hustle['steps']          ?? '[]', true) ?: [];

// Increment view count
DB::run('UPDATE hustles SET view_count = view_count + 1 WHERE id = :id', [':id' => $hustle['id']]);

// Related hustles (same category, excluding self)
$related = DB::query(
    'SELECT id, name, slug, emoji, income_min, income_max, income_period, difficulty
     FROM hustles WHERE category = :cat AND id != :id AND is_active = 1
     ORDER BY is_featured DESC LIMIT 3',
    [':cat' => $hustle['category'], ':id' => $hustle['id']]
);

$difficultyLabel = ['beginner' => 'Beginner Friendly', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];
$diffColor = ['beginner' => '#16a05a', 'intermediate' => '#e07b00', 'advanced' => '#cc3333'];

$pageTitle = $hustle['name'] . " in Nigeria — Income, Steps & Guide 2025 | Hustle Kingdom";
$pageDesc  = "How to start " . $hustle['name'] . " in Nigeria. Earn ₦" . number_format($hustle['income_min']) . "–₦" . number_format($hustle['income_max']) . "/" . $hustle['income_period'] . ". Step-by-step guide, skills needed, and real income ranges.";
$canonical = APP_URL . '/hustle/' . $hustle['slug'];

// Schema.org HowTo
$howToSteps = [];
foreach ($steps as $i => $step) {
    $howToSteps[] = [
        '@type' => 'HowToStep',
        'position' => $i + 1,
        'text' => $step,
    ];
}
$jsonLd = json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'HowTo',
    'name'        => 'How to Start ' . $hustle['name'] . ' in Nigeria',
    'description' => $pageDesc,
    'estimatedCost' => [
        '@type'    => 'MonetaryAmount',
        'currency' => 'NGN',
        'value'    => $hustle['capital_needed'],
    ],
    'step' => $howToSteps,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$isLoggedIn = Auth::isLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="description" content="<?= htmlspecialchars($pageDesc) ?>"/>
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>"/>
<meta property="og:title"       content="<?= htmlspecialchars($pageTitle) ?>"/>
<meta property="og:description" content="<?= htmlspecialchars($pageDesc) ?>"/>
<meta property="og:type"        content="article"/>
<meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>"/>
<script type="application/ld+json"><?= $jsonLd ?></script>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--green:#16a05a;--green2:#128f4f;--green-light:#ebf7f1;--r:14px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;min-height:100vh;}
nav{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);}
.nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.nav-logo-icon{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:15px;color:var(--text);}
.nav-brand em{color:var(--green);font-style:normal;}
.back-link{font-size:13px;color:var(--green);font-weight:700;text-decoration:none;}
/* HERO */
.detail-hero{padding:24px 20px 20px;border-bottom:1px solid var(--border);}
.hero-emoji{font-size:48px;margin-bottom:10px;}
.hero-cat{font-size:11px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
h1{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;line-height:1.2;margin-bottom:10px;}
.hero-desc{font-size:14px;color:var(--text2);line-height:1.65;margin-bottom:16px;}
.meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.meta-box{background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:12px;}
.meta-label{font-size:10px;font-weight:700;color:var(--text3);text-transform:uppercase;margin-bottom:4px;}
.meta-value{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;}
.meta-value.green{color:var(--green);}
/* SECTIONS */
.section{padding:20px;}
.section-title{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;margin-bottom:12px;}
.skill-chips{display:flex;flex-wrap:wrap;gap:7px;}
.chip{font-size:12px;font-weight:600;padding:5px 11px;border-radius:20px;background:var(--surface);border:1.5px solid var(--border);}
.loc-chips .chip{background:var(--green-light);border-color:#b2e0c8;color:var(--green);}
/* STEPS */
.steps-list{list-style:none;}
.step-item{display:flex;gap:12px;margin-bottom:14px;align-items:flex-start;}
.step-num{width:26px;height:26px;background:var(--green);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:13px;flex-shrink:0;margin-top:1px;}
.step-text{font-size:13.5px;line-height:1.6;color:var(--text2);}
/* CTA */
.cta-box{margin:0 20px 24px;background:var(--green);border-radius:16px;padding:20px;text-align:center;color:#fff;}
.cta-box h3{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;margin-bottom:6px;}
.cta-box p{font-size:13px;opacity:.9;margin-bottom:14px;}
.cta-btn{display:inline-block;background:#fff;color:var(--green);border-radius:10px;padding:11px 22px;font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:13px;text-decoration:none;}
/* RELATED */
.related-card{display:flex;gap:10px;align-items:center;padding:12px 0;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);}
.related-emoji{font-size:24px;}
.related-name{font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:14px;}
.related-income{font-size:12px;color:var(--green);font-weight:600;}
footer{padding:16px 20px;text-align:center;font-size:12px;color:var(--text3);border-top:1px solid var(--border);}
footer a{color:var(--green);text-decoration:none;font-weight:600;}
</style>
</head>
<body>

<nav>
  <a href="<?= APP_URL ?>" class="nav-logo">
    <div class="nav-logo-icon">👑</div>
    <div class="nav-brand">Hustle<em>Kingdom</em></div>
  </a>
  <a href="javascript:history.back()" class="back-link">← Back</a>
</nav>

<div class="detail-hero">
  <div class="hero-emoji"><?= htmlspecialchars($hustle['emoji']) ?></div>
  <div class="hero-cat"><?= htmlspecialchars($hustle['category']) ?></div>
  <h1><?= htmlspecialchars($hustle['name']) ?></h1>
  <p class="hero-desc"><?= htmlspecialchars($hustle['description']) ?></p>

  <div class="meta-grid">
    <div class="meta-box">
      <div class="meta-label">Monthly Potential</div>
      <div class="meta-value green">₦<?= number_format($hustle['income_min']) ?>–<?= number_format($hustle['income_max']) ?></div>
    </div>
    <div class="meta-box">
      <div class="meta-label">Difficulty</div>
      <div class="meta-value" style="color:<?= $diffColor[$hustle['difficulty']] ?>">
        <?= $difficultyLabel[$hustle['difficulty']] ?? ucfirst($hustle['difficulty']) ?>
      </div>
    </div>
    <div class="meta-box">
      <div class="meta-label">Capital Needed</div>
      <div class="meta-value"><?= $hustle['capital_needed'] > 0 ? '₦' . number_format($hustle['capital_needed']) : 'None' ?></div>
    </div>
    <div class="meta-box">
      <div class="meta-label">Income Period</div>
      <div class="meta-value">Per <?= ucfirst($hustle['income_period']) ?></div>
    </div>
  </div>
</div>

<?php if ($skills): ?>
<div class="section">
  <div class="section-title">🧠 Skills You'll Need</div>
  <div class="skill-chips">
    <?php foreach ($skills as $s): ?>
      <span class="chip"><?= htmlspecialchars($s) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($locs): ?>
<div class="section" style="padding-top:0">
  <div class="section-title">📍 Works Best In</div>
  <div class="skill-chips loc-chips">
    <?php foreach ($locs as $l): ?>
      <span class="chip"><?= htmlspecialchars($l) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if ($steps): ?>
<div class="section">
  <div class="section-title">🚀 How to Get Started</div>
  <ol class="steps-list">
    <?php foreach ($steps as $i => $step): ?>
    <li class="step-item">
      <div class="step-num"><?= $i + 1 ?></div>
      <div class="step-text"><?= htmlspecialchars($step) ?></div>
    </li>
    <?php endforeach; ?>
  </ol>
</div>
<?php endif; ?>

<div class="cta-box">
  <h3>Track Your <?= htmlspecialchars($hustle['name']) ?> Income 📊</h3>
  <p>Log every payment, set goals, and watch your hustle grow — all free on Hustle Kingdom.</p>
  <a href="<?= APP_URL . ($isLoggedIn ? '/' : '/auth/register') ?>" class="cta-btn">
    <?= $isLoggedIn ? 'Open Dashboard →' : 'Start Free Today →' ?>
  </a>
</div>

<?php if ($related): ?>
<div class="section">
  <div class="section-title">👀 Similar Hustles</div>
  <?php foreach ($related as $r): ?>
  <a href="<?= APP_URL . '/hustle/' . htmlspecialchars($r['slug']) ?>" class="related-card">
    <div class="related-emoji"><?= htmlspecialchars($r['emoji']) ?></div>
    <div>
      <div class="related-name"><?= htmlspecialchars($r['name']) ?></div>
      <div class="related-income">₦<?= number_format($r['income_min']) ?>–₦<?= number_format($r['income_max']) ?>/<?= $r['income_period'] ?></div>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<footer>
  <p>© <?= date('Y') ?> <a href="<?= APP_URL ?>">Hustle Kingdom</a> · Helping Nigerian hustlers earn more</p>
</footer>

</body>
</html>
