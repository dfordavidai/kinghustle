<?php
/**
 * HustleKingdom — Browse All Hustles
 * Route: GET /hustles
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
Auth::start();

$user   = Auth::isLoggedIn() ? Auth::user() : null;
$isPro  = Auth::isPro();

// Filters from GET
$cat    = $_GET['cat']    ?? '';
$diff   = $_GET['diff']   ?? '';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 24;
$offset = ($page - 1) * $limit;

// Build query
$where  = ['is_active = 1'];
$params = [];

if ($cat)    { $where[] = 'category = :cat';   $params[':cat']  = $cat;  }
if ($diff)   { $where[] = 'difficulty = :diff'; $params[':diff'] = $diff; }
if ($search) {
    $where[] = '(name LIKE :q OR description LIKE :q2)';
    $params[':q']  = "%$search%";
    $params[':q2'] = "%$search%";
}

$whereStr = implode(' AND ', $where);

$hustles = DB::query(
    "SELECT id, name, slug, emoji, category, income_min, income_max, income_period,
            difficulty, capital_needed, description, is_featured, save_count
     FROM hustles WHERE $whereStr
     ORDER BY is_featured DESC, save_count DESC, id ASC
     LIMIT $limit OFFSET $offset",
    $params
);

$total   = (int)(DB::one("SELECT COUNT(*) as cnt FROM hustles WHERE $whereStr", $params)['cnt'] ?? 0);
$pages   = max(1, ceil($total / $limit));

// Category list for filter pills
$categories = [
    'digital'   => ['💻', 'Digital & Tech'],
    'social'    => ['📱', 'Social & Content'],
    'ai'        => ['🤖', 'AI-Powered'],
    'trade'     => ['🛒', 'Trading & Commerce'],
    'creative'  => ['🎨', 'Creative & Media'],
    'agro'      => ['🌾', 'Agro & Food'],
    'trades'    => ['🏗️', 'Trades & Labour'],
    'health'    => ['💆', 'Health & Beauty'],
    'education' => ['🎓', 'Education & Coaching'],
];

$diffColors = ['beginner' => 'tg', 'intermediate' => 'to', 'advanced' => 'tr'];
$diffLabels = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];

function fmtMoney(float $n): string {
    if ($n >= 1_000_000) return '₦' . number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return '₦' . number_format($n / 1_000, 1) . 'k';
    return '₦' . number_format($n, 0);
}

// Build current filter URL base
function filterUrl(array $overrides = []): string {
    $params = array_merge(
        array_filter(['cat' => $_GET['cat'] ?? '', 'diff' => $_GET['diff'] ?? '', 'q' => trim($_GET['q'] ?? '')]),
        $overrides
    );
    $qs = http_build_query(array_filter($params));
    return APP_URL . '/hustles' . ($qs ? "?$qs" : '');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Browse Hustles — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#ffffff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;--border2:#d8d8d0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--text4:#c8c8c0;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --orange:#d45820;--orange-light:#fdf0eb;
  --red:#cc3333;--red-light:#fdf0f0;
  --purple:#6b35c8;--purple-light:#f3eefe;
  --teal:#0e9488;--teal-light:#ebfaf8;
  --r:16px;--rs:10px;--nav:58px;--bot:64px;
  --sh:0 2px 16px rgba(0,0,0,0.06);
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;height:0;}
.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;text-decoration:none;}

/* ── SEARCH BAR ── */
.search-wrap{padding:12px 16px 0;}
.search-box{display:flex;gap:8px;align-items:center;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:0 12px;height:44px;}
.search-box:focus-within{border-color:var(--green);}
.search-box input{flex:1;background:none;border:none;outline:none;font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);}
.search-box input::placeholder{color:var(--text3);}
.search-box button{background:none;border:none;font-size:18px;cursor:pointer;padding:0;}

/* ── FILTER PILLS ── */
.filter-scroll{display:flex;gap:7px;overflow-x:auto;padding:10px 16px 4px;scrollbar-width:none;}
.fpill{flex-shrink:0;padding:6px 12px;border-radius:20px;font-size:11.5px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;white-space:nowrap;}
.fpill.active{background:var(--green);border-color:var(--green);color:#fff;}
.diff-row{display:flex;gap:7px;padding:0 16px 10px;overflow-x:auto;scrollbar-width:none;}

/* ── RESULTS HEADER ── */
.results-head{display:flex;align-items:center;justify-content:space-between;padding:8px 16px;border-top:1px solid var(--border);}
.rh-count{font-size:12px;font-weight:600;color:var(--text3);}
.rh-clear{font-size:12px;font-weight:700;color:var(--red);text-decoration:none;}

/* ── HUSTLE GRID ── */
.hustle-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:10px 16px 0;}
.hcard{background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:14px;text-decoration:none;color:var(--text);display:flex;flex-direction:column;transition:.15s;touch-action:manipulation;}
.hcard:active{background:var(--surface);}
.hc-emoji{font-size:28px;margin-bottom:8px;}
.hc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;margin-bottom:4px;line-height:1.3;}
.hc-income{font-size:11px;color:var(--green3);font-weight:700;margin-bottom:8px;}
.hc-desc{font-size:11px;color:var(--text3);line-height:1.5;margin-bottom:8px;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.hc-tags{display:flex;gap:5px;flex-wrap:wrap;margin-top:auto;}
.tag{font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.to{background:var(--orange-light);color:var(--orange);}
.tr{background:var(--red-light);color:var(--red);}
.tp{background:var(--purple-light);color:var(--purple);}
.tgd{background:var(--gold-light);color:var(--gold2);}
.hc-feat{position:absolute;top:8px;right:8px;font-size:10px;background:var(--gold-light);color:var(--gold2);border:1px solid #e8d080;padding:2px 6px;border-radius:6px;font-weight:800;font-family:'Bricolage Grotesque',sans-serif;}

/* ── EMPTY ── */
.empty{text-align:center;padding:48px 24px;color:var(--text3);}
.empty .ei{font-size:40px;margin-bottom:12px;}
.empty h3{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:var(--text2);margin-bottom:6px;}

/* ── PAGINATION ── */
.pagination{display:flex;gap:8px;justify-content:center;padding:20px 16px 0;flex-wrap:wrap;}
.ppage{padding:8px 14px;border-radius:10px;font-size:13px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;}
.ppage.active{background:var(--green);border-color:var(--green);color:#fff;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;cursor:pointer;border:none;background:transparent;color:var(--text3);text-decoration:none;transition:.2s;}
.bnav.active{color:var(--green);}
.bni{font-size:18px;}
.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;cursor:pointer;border:none;background:transparent;text-decoration:none;}
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
      <?php if ($isPro): ?>
        <span class="pro-chip">⭐ PRO</span>
      <?php else: ?>
        <a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a>
      <?php endif; ?>
      <a href="<?= APP_URL ?>/dashboard" class="nav-avatar" title="Dashboard"><?= htmlspecialchars($user['avatar_emoji']) ?></a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/auth/login" class="pro-chip">Login →</a>
    <?php endif; ?>
  </div>
</nav>

<!-- Search -->
<div class="search-wrap">
  <form method="GET" action="<?= APP_URL ?>/hustles">
    <?php if ($cat):  ?><input type="hidden" name="cat"  value="<?= htmlspecialchars($cat)  ?>"/><?php endif; ?>
    <?php if ($diff): ?><input type="hidden" name="diff" value="<?= htmlspecialchars($diff) ?>"/><?php endif; ?>
    <div class="search-box">
      <span>🔍</span>
      <input type="text" name="q" placeholder="Search hustles…" value="<?= htmlspecialchars($search) ?>" autocomplete="off"/>
      <button type="submit">→</button>
    </div>
  </form>
</div>

<!-- Category Pills -->
<div class="filter-scroll">
  <a href="<?= filterUrl(['cat' => '', 'page' => '']) ?>" class="fpill <?= !$cat ? 'active' : '' ?>">All</a>
  <?php foreach ($categories as $slug => [$icon, $label]): ?>
    <a href="<?= filterUrl(['cat' => $slug, 'page' => '']) ?>" class="fpill <?= $cat === $slug ? 'active' : '' ?>"><?= $icon ?> <?= $label ?></a>
  <?php endforeach; ?>
</div>

<!-- Difficulty Row -->
<div class="diff-row">
  <a href="<?= filterUrl(['diff' => '', 'page' => '']) ?>" class="fpill <?= !$diff ? 'active' : '' ?>" style="font-size:10.5px;">Any Difficulty</a>
  <a href="<?= filterUrl(['diff' => 'beginner', 'page' => '']) ?>" class="fpill <?= $diff === 'beginner' ? 'active' : '' ?>" style="font-size:10.5px;">🟢 Beginner</a>
  <a href="<?= filterUrl(['diff' => 'intermediate', 'page' => '']) ?>" class="fpill <?= $diff === 'intermediate' ? 'active' : '' ?>" style="font-size:10.5px;">🟡 Intermediate</a>
  <a href="<?= filterUrl(['diff' => 'advanced', 'page' => '']) ?>" class="fpill <?= $diff === 'advanced' ? 'active' : '' ?>" style="font-size:10.5px;">🔴 Advanced</a>
</div>

<!-- Results Header -->
<div class="results-head">
  <div class="rh-count"><?= number_format($total) ?> hustle<?= $total !== 1 ? 's' : '' ?> found</div>
  <?php if ($cat || $diff || $search): ?>
    <a href="<?= APP_URL ?>/hustles" class="rh-clear">✕ Clear filters</a>
  <?php endif; ?>
</div>

<!-- Hustle Grid -->
<?php if (empty($hustles)): ?>
  <div class="empty">
    <div class="ei">🔍</div>
    <h3>No hustles found</h3>
    <p>Try different filters or search terms.</p>
  </div>
<?php else: ?>
  <div class="hustle-grid">
    <?php foreach ($hustles as $h): ?>
      <a class="hcard" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>" style="position:relative;">
        <?php if ($h['is_featured']): ?><span class="hc-feat">⭐ Hot</span><?php endif; ?>
        <div class="hc-emoji"><?= htmlspecialchars($h['emoji']) ?></div>
        <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
        <div class="hc-income"><?= fmtMoney((float)$h['income_min']) ?>–<?= fmtMoney((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
        <div class="hc-desc"><?= htmlspecialchars($h['description'] ?? '') ?></div>
        <div class="hc-tags">
          <span class="tag <?= htmlspecialchars($diffColors[$h['difficulty']] ?? 'tg') ?>"><?= htmlspecialchars($diffLabels[$h['difficulty']] ?? '') ?></span>
          <?php if ($h['capital_needed'] == 0): ?><span class="tag tg">Free Start</span><?php endif; ?>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
    <div class="pagination">
      <?php
      $range = range(max(1, $page - 2), min($pages, $page + 2));
      foreach ($range as $p):
      ?>
        <a href="<?= filterUrl(['page' => $p]) ?>" class="ppage <?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endforeach; ?>
      <?php if ($page < $pages): ?>
        <a href="<?= filterUrl(['page' => $page + 1]) ?>" class="ppage">Next →</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

<div style="height:12px;"></div>

<!-- Bottom Nav -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav">
    <div class="bni">🏠</div><div class="bnl">Home</div>
  </a>
  <a href="<?= APP_URL ?>/hustles" class="bnav active">
    <div class="bni">🔥</div><div class="bnl">Hustles</div>
  </a>
  <a href="<?= APP_URL ?>/dashboard" class="bnav-ctr">
    <div class="bnav-ctr-icon">📊</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Dash</div>
  </a>
  <a href="<?= APP_URL ?>/ideas" class="bnav">
    <div class="bni">💡</div><div class="bnl">Ideas</div>
  </a>
  <a href="<?= APP_URL ?>/ai" class="bnav">
    <div class="bni">🤖</div><div class="bnl">AI</div>
  </a>
</nav>

</body>
</html>
