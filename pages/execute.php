<?php
/**
 * HustleKingdom — Execute: My Hustle Execution Tracker
 * Route: GET /execute
 * Requires auth. View saved hustles + checklist-style execution steps.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();

// Saved hustles with full detail
$saved = DB::query(
    'SELECT h.id, h.name, h.slug, h.emoji, h.category, h.difficulty,
            h.income_min, h.income_max, h.income_period, h.capital_needed,
            h.steps, h.skills_needed, h.description,
            sh.created_at as saved_at
     FROM saved_hustles sh
     JOIN hustles h ON h.id = sh.hustle_id
     WHERE sh.user_id = :uid
     ORDER BY sh.created_at DESC',
    [':uid' => $user['id']]
);

// Execution progress stored as JSON in a user_meta key
try {
    $rawProgress = DB::one(
        'SELECT meta_value FROM user_meta WHERE user_id = :uid AND meta_key = "exec_progress"',
        [':uid' => $user['id']]
    );
    $progress = $rawProgress ? (json_decode($rawProgress['meta_value'], true) ?: []) : [];
} catch (\Exception $e) {
    $progress = [];
}

// Active hustle (selected execution plan)
$activeSlug = $_GET['hustle'] ?? ($saved[0]['slug'] ?? '');
$activeHustle = null;
foreach ($saved as $h) {
    if ($h['slug'] === $activeSlug) { $activeHustle = $h; break; }
}

function fmtE(float $n): string {
    if ($n >= 1_000_000) return '₦' . number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return '₦' . number_format($n / 1_000, 1) . 'k';
    return '₦' . number_format($n, 0);
}

$diffColors = ['beginner' => 'tg', 'intermediate' => 'to', 'advanced' => 'tr'];
$diffLabels = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Execute — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --orange:#d45820;--orange-light:#fdf0eb;
  --red:#cc3333;--red-light:#fdf0f0;
  --purple:#6b35c8;--purple-light:#f3eefe;
  --r:16px;--nav:58px;--bot:64px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;overflow-x:hidden;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;height:0;}

.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}

/* Page header */
.page-hero{padding:18px 16px 16px;background:linear-gradient(135deg,#1a0a3c 0%,#3b1a7a 55%,#5625aa 100%);position:relative;overflow:hidden;}
.page-hero::before{content:'';position:absolute;right:-30px;top:-30px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.05);}
.ph-label{font-size:11.5px;color:rgba(255,255,255,.6);font-weight:600;margin-bottom:4px;}
.ph-title{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;color:#fff;margin-bottom:6px;}
.ph-sub{font-size:12.5px;color:rgba(255,255,255,.7);line-height:1.5;}

/* Hustle selector pills */
.hustle-tabs{display:flex;gap:8px;overflow-x:auto;padding:12px 16px;scrollbar-width:none;}
.htab{flex-shrink:0;display:flex;align-items:center;gap:7px;padding:8px 13px;border-radius:20px;font-size:12px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;transition:.15s;white-space:nowrap;}
.htab.active{background:var(--purple);border-color:var(--purple);color:#fff;}

/* ── EXECUTION CARD ── */
.exec-card{margin:0 16px 16px;border:1.5px solid var(--border);border-radius:var(--r);overflow:hidden;}
.ec-header{display:flex;align-items:center;gap:12px;padding:14px;}
.ec-emoji{font-size:30px;}
.ec-info{flex:1;}
.ec-name{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;margin-bottom:4px;}
.ec-income{font-size:12px;color:var(--green3);font-weight:700;margin-bottom:3px;}
.ec-tags{display:flex;gap:5px;}
.tag{font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.to{background:var(--orange-light);color:var(--orange);}
.tr{background:var(--red-light);color:var(--red);}
.tp{background:var(--purple-light);color:var(--purple);}
.tgd{background:var(--gold-light);color:var(--gold2);}

/* Progress bar */
.prog-wrap{padding:0 14px 14px;border-bottom:1px solid var(--border);}
.prog-label{display:flex;justify-content:space-between;font-size:11.5px;font-weight:700;color:var(--text3);margin-bottom:6px;}
.prog-label span:last-child{color:var(--green);font-family:'Bricolage Grotesque',sans-serif;}
.prog-bar{height:8px;background:var(--border);border-radius:4px;overflow:hidden;}
.prog-fill{height:100%;background:linear-gradient(90deg,var(--green),#2ae87a);border-radius:4px;transition:.4s;}

/* Steps checklist */
.steps-list{padding:0;}
.step-item{display:flex;align-items:flex-start;gap:12px;padding:13px 14px;border-bottom:1px solid var(--border);cursor:pointer;transition:.15s;}
.step-item:last-child{border-bottom:none;}
.step-item.done .step-text{text-decoration:line-through;color:var(--text3);}
.step-check{width:22px;height:22px;border-radius:50%;border:2px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;transition:.2s;}
.step-item.done .step-check{background:var(--green);border-color:var(--green);color:#fff;}
.step-num{font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;color:var(--text3);}
.step-text{font-size:13px;line-height:1.55;flex:1;}

/* Quick win section */
.quick-win{background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:var(--r);margin:0 16px 16px;padding:14px;}
.qw-title{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:var(--green3);margin-bottom:6px;}
.qw-item{display:flex;align-items:center;gap:8px;font-size:12.5px;margin-bottom:5px;color:var(--text2);}
.qw-item:last-child{margin-bottom:0;}

/* CTA to view full hustle */
.detail-link{display:flex;align-items:center;justify-content:center;gap:6px;background:var(--purple);color:#fff;border-radius:12px;padding:13px;margin:0 16px 16px;text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;}

/* Empty state */
.empty{text-align:center;padding:48px 16px;}
.ei{font-size:40px;margin-bottom:12px;}
.empty h3{font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;margin-bottom:8px;}
.empty p{font-size:13px;color:var(--text2);margin-bottom:16px;line-height:1.55;}
.empty a{display:inline-block;background:var(--green);color:#fff;border-radius:12px;padding:11px 20px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;}

/* Toast */
.toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#0d0d0c;color:#fff;font-size:13px;font-weight:600;padding:10px 18px;border-radius:30px;z-index:400;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap;}
.toast.show{opacity:1;}

/* Bottom nav */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
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
    <?php if ($isPro): ?><span class="pro-chip">⭐ PRO</span><?php else: ?><a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a><?php endif; ?>
  </div>
</nav>

<div class="page-hero">
  <div class="ph-label">Take action, not notes</div>
  <div class="ph-title">⚡ Execute</div>
  <div class="ph-sub">Pick a saved hustle and tick off every step to launch it.</div>
</div>

<?php if (empty($saved)): ?>
  <div class="empty">
    <div class="ei">📋</div>
    <h3>No saved hustles yet</h3>
    <p>Save hustles you want to pursue — then come here to execute them step by step.</p>
    <a href="<?= APP_URL ?>/hustles">🔍 Browse Hustles →</a>
  </div>

<?php else: ?>

  <!-- Hustle Selector -->
  <div class="hustle-tabs">
    <?php foreach ($saved as $h): ?>
      <a class="htab <?= $h['slug'] === $activeSlug ? 'active' : '' ?>"
         href="<?= APP_URL ?>/execute?hustle=<?= urlencode($h['slug']) ?>">
        <?= htmlspecialchars($h['emoji']) ?> <?= htmlspecialchars($h['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($activeHustle):
    $steps   = json_decode($activeHustle['steps'] ?? '[]', true) ?: [];
    $skills  = json_decode($activeHustle['skills_needed'] ?? '[]', true) ?: [];
    $hKey    = 'hustle_' . $activeHustle['id'];
    $done    = $progress[$hKey] ?? [];
    $doneCount = count($done);
    $total   = max(1, count($steps));
    $pct     = $total > 0 ? round(($doneCount / $total) * 100) : 0;
  ?>

  <!-- Execution Card -->
  <div class="exec-card">
    <div class="ec-header">
      <div class="ec-emoji"><?= htmlspecialchars($activeHustle['emoji']) ?></div>
      <div class="ec-info">
        <div class="ec-name"><?= htmlspecialchars($activeHustle['name']) ?></div>
        <div class="ec-income"><?= fmtE((float)$activeHustle['income_min']) ?>–<?= fmtE((float)$activeHustle['income_max']) ?>/<?= htmlspecialchars($activeHustle['income_period']) ?></div>
        <div class="ec-tags">
          <span class="tag <?= $diffColors[$activeHustle['difficulty']] ?? 'tg' ?>"><?= $diffLabels[$activeHustle['difficulty']] ?? '' ?></span>
          <?php if ($activeHustle['capital_needed'] == 0): ?>
            <span class="tag tg">Free Start</span>
          <?php else: ?>
            <span class="tag tgd">Capital: <?= fmtE((float)$activeHustle['capital_needed']) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Progress -->
    <div class="prog-wrap">
      <div class="prog-label">
        <span>Progress: <?= $doneCount ?>/<?= $total ?> steps</span>
        <span id="pct-label"><?= $pct ?>% complete</span>
      </div>
      <div class="prog-bar">
        <div class="prog-fill" id="prog-fill" style="width:<?= $pct ?>%;"></div>
      </div>
    </div>

    <!-- Steps -->
    <?php if (!empty($steps)): ?>
    <div class="steps-list" id="steps-list">
      <?php foreach ($steps as $i => $step): ?>
        <?php $isDone = in_array($i, $done); ?>
        <div class="step-item <?= $isDone ? 'done' : '' ?>" id="step-<?= $i ?>"
             onclick="toggleStep(<?= $i ?>, '<?= htmlspecialchars($hKey) ?>', <?= $total ?>)">
          <div class="step-check">
            <?php if ($isDone): ?>✓<?php else: ?><span class="step-num"><?= $i + 1 ?></span><?php endif; ?>
          </div>
          <div class="step-text"><?= htmlspecialchars($step) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div style="padding:14px;text-align:center;color:var(--text3);font-size:13px;">
        No step-by-step guide yet for this hustle. View the full detail page for more info.
      </div>
    <?php endif; ?>
  </div>

  <!-- Skills needed -->
  <?php if (!empty($skills)): ?>
  <div class="quick-win">
    <div class="qw-title">🎯 Skills you'll need</div>
    <?php foreach ($skills as $sk): ?>
      <div class="qw-item">✦ <?= htmlspecialchars($sk) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- View full detail -->
  <a class="detail-link" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($activeHustle['slug']) ?>">
    📖 View Full Hustle Guide →
  </a>

  <?php if ($pct >= 100): ?>
  <div style="text-align:center;padding:16px;background:var(--green-light);border-radius:var(--r);margin:0 16px 16px;border:1.5px solid #b2e0c8;">
    <div style="font-size:32px;margin-bottom:8px;">🎉</div>
    <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:16px;font-weight:800;color:var(--green3);margin-bottom:4px;">All steps complete!</div>
    <div style="font-size:13px;color:var(--text2);">Time to log your first income →</div>
    <a href="<?= APP_URL ?>/dashboard" style="display:inline-block;margin-top:10px;background:var(--green);color:#fff;border-radius:10px;padding:10px 18px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;">💰 Log Income</a>
  </div>
  <?php endif; ?>

  <?php endif; ?>
<?php endif; ?>

<div class="toast" id="toast"></div>

<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">🔥</div><div class="bnl">Hustles</div></a>
  <a href="<?= APP_URL ?>/dashboard" class="bnav-ctr">
    <div class="bnav-ctr-icon">📊</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Dash</div>
  </a>
  <a href="<?= APP_URL ?>/ideas" class="bnav"><div class="bni">💡</div><div class="bnl">Ideas</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
// Progress saved to localStorage + API
const PROGRESS_KEY = 'hk_exec_progress';

function getLocal() {
  try { return JSON.parse(localStorage.getItem(PROGRESS_KEY) || '{}'); } catch { return {}; }
}
function saveLocal(data) {
  try { localStorage.setItem(PROGRESS_KEY, JSON.stringify(data)); } catch {}
}

// Init from server-side progress
const serverProgress = <?= json_encode($progress) ?>;
const localProgress  = getLocal();
// Merge: server is source of truth
const mergedProgress = Object.assign({}, localProgress, serverProgress);
saveLocal(mergedProgress);

function toggleStep(idx, hKey, total) {
  const p   = getLocal();
  const arr = p[hKey] || [];
  const pos = arr.indexOf(idx);
  if (pos === -1) arr.push(idx);
  else            arr.splice(pos, 1);
  p[hKey] = arr;
  saveLocal(p);

  // Update UI
  const item = document.getElementById('step-' + idx);
  const done = arr.includes(idx);
  item.classList.toggle('done', done);
  const check = item.querySelector('.step-check');
  check.innerHTML = done ? '✓' : '<span class="step-num">' + (idx + 1) + '</span>';

  // Update progress bar
  const doneCount = arr.length;
  const pct = Math.round((doneCount / total) * 100);
  document.getElementById('prog-fill').style.width = pct + '%';
  document.getElementById('pct-label').textContent = pct + '% complete';

  // Persist to server
  persistProgress(p);

  if (done) showToast('✅ Step ' + (idx + 1) + ' marked done!');
}

async function persistProgress(data) {
  try {
    await fetch('/api/profile', {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ meta_exec_progress: JSON.stringify(data) }),
      credentials: 'same-origin',
    });
  } catch (_) {}
}

function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 2200);
}
</script>
</body>
</html>
