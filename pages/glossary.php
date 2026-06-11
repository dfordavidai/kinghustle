<?php
/**
 * HustleKingdom — Hustle Glossary
 * Key hustle, finance, and business terms explained simply.
 */
defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';

Auth::start();
$user   = Auth::user();
$isPro  = Auth::isPro();

$terms = [
    ['letter' => 'A', 'term' => 'Arbitrage', 'emoji' => '🔁',
     'def' => 'Buying something at a low price in one place and selling it at a higher price elsewhere. E.g. buying cheap goods in Aba and selling in Lagos.'],
    ['letter' => 'A', 'term' => 'Affiliate Marketing', 'emoji' => '🔗',
     'def' => 'Earning a commission by promoting another person\'s or company\'s products. You get a unique link — every sale through your link pays you a percentage.'],
    ['letter' => 'B', 'term' => 'B2B (Business-to-Business)', 'emoji' => '🤝',
     'def' => 'Selling your product or service to other businesses rather than to individual consumers. B2B deals usually have higher ticket sizes.'],
    ['letter' => 'B', 'term' => 'Bootstrap', 'emoji' => '👟',
     'def' => 'Starting and growing a business using your own savings and revenue — without outside investors or loans.'],
    ['letter' => 'C', 'term' => 'Cash Flow', 'emoji' => '💸',
     'def' => 'The movement of money in and out of your hustle. Positive cash flow means more money is coming in than going out — the lifeblood of any business.'],
    ['letter' => 'C', 'term' => 'Commission', 'emoji' => '💰',
     'def' => 'A percentage of a sale you earn as payment. Common in real estate, insurance, and affiliate marketing.'],
    ['letter' => 'D', 'term' => 'Digital Product', 'emoji' => '📦',
     'def' => 'Something you sell online that has no physical form — e.g. an eBook, template, online course, or software. You create it once and sell it unlimited times.'],
    ['letter' => 'D', 'term' => 'Dropshipping', 'emoji' => '🚚',
     'def' => 'Selling products without holding stock. You take orders, pass them to a supplier, and the supplier ships directly to your customer. Your profit is the markup.'],
    ['letter' => 'E', 'term' => 'Equity', 'emoji' => '📊',
     'def' => 'Ownership stake in a business. If you own 20% equity in a company worth ₦1m, your share is worth ₦200k.'],
    ['letter' => 'F', 'term' => 'Freelancing', 'emoji' => '💻',
     'def' => 'Working independently for multiple clients rather than being employed full-time by one company. You set your own rates and schedule.'],
    ['letter' => 'G', 'term' => 'Gross Profit', 'emoji' => '📈',
     'def' => 'Revenue minus the direct cost to produce or deliver what you sold. Does not include overhead expenses like rent or salaries.'],
    ['letter' => 'I', 'term' => 'Invoice', 'emoji' => '🧾',
     'def' => 'A bill you send to a client requesting payment for goods or services delivered. It shows what was provided, how much is owed, and the due date.'],
    ['letter' => 'L', 'term' => 'Lead', 'emoji' => '🎯',
     'def' => 'A potential customer who has shown interest in your product or service. Turning leads into paying customers is called conversion.'],
    ['letter' => 'M', 'term' => 'Margin (Profit Margin)', 'emoji' => '📉',
     'def' => 'The percentage of revenue you keep as profit after costs. If you charge ₦10,000 and it costs ₦6,000 to deliver, your margin is 40%.'],
    ['letter' => 'M', 'term' => 'MVP (Minimum Viable Product)', 'emoji' => '🧪',
     'def' => 'The simplest version of your product or service that lets you test whether people will pay for it — before investing heavily in building it out.'],
    ['letter' => 'N', 'term' => 'Niche', 'emoji' => '🔍',
     'def' => 'A specific, focused segment of a market. Instead of "selling clothes", a niche would be "plus-size Ankara fashion for Lagos women". Niches convert better.'],
    ['letter' => 'O', 'term' => 'Overhead', 'emoji' => '🏗️',
     'def' => 'Fixed running costs of your business that don\'t change with sales volume — rent, internet, phone bills, subscriptions.'],
    ['letter' => 'P', 'term' => 'Passive Income', 'emoji' => '😴',
     'def' => 'Money earned with minimal ongoing effort after the initial work is done. Examples: rental income, royalties, digital product sales, dividends.'],
    ['letter' => 'P', 'term' => 'Pitch', 'emoji' => '🎤',
     'def' => 'A concise presentation of your business idea or offer — to a potential client, investor, or partner. A good pitch is short, clear, and shows the value immediately.'],
    ['letter' => 'R', 'term' => 'Recurring Revenue', 'emoji' => '🔄',
     'def' => 'Income that comes in repeatedly on a regular schedule — subscriptions, retainers, monthly service fees. It makes income predictable.'],
    ['letter' => 'R', 'term' => 'ROI (Return on Investment)', 'emoji' => '📐',
     'def' => 'How much profit you make relative to what you invested. ROI of 100% means you doubled your money. Formula: (Profit ÷ Cost) × 100.'],
    ['letter' => 'S', 'term' => 'Scale', 'emoji' => '🚀',
     'def' => 'Growing your business in a way that increases revenue faster than costs. A hustle scales when adding one more customer doesn\'t proportionally add effort.'],
    ['letter' => 'S', 'term' => 'Side Hustle', 'emoji' => '💡',
     'def' => 'Income-generating activity done outside of a primary job or source of income. The goal is to create additional revenue streams you control.'],
    ['letter' => 'T', 'term' => 'Target Market', 'emoji' => '🎯',
     'def' => 'The specific group of people most likely to buy what you offer. Defining your target market helps you focus marketing spend where it converts.'],
    ['letter' => 'U', 'term' => 'Upsell', 'emoji' => '⬆️',
     'def' => 'Offering a customer a higher-value or more expensive product after they\'ve already decided to buy. E.g. "Would you like the premium version for ₦5,000 more?"'],
    ['letter' => 'V', 'term' => 'Value Proposition', 'emoji' => '💎',
     'def' => 'The clear reason why a customer should choose you over a competitor. It answers: "What do I get, why does it matter, and why from you?"'],
    ['letter' => 'W', 'term' => 'Working Capital', 'emoji' => '🏦',
     'def' => 'The money available to run day-to-day operations — pay suppliers, cover stock, fund wages. Without it, even profitable businesses can stall.'],
];

// Group by first letter
$grouped = [];
foreach ($terms as $t) {
    $grouped[$t['letter']][] = $t;
}
ksort($grouped);
$letters = array_keys($grouped);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Hustle Glossary — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#ffffff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;--border2:#d8d8d0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--text4:#c8c8c0;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --r:16px;--rs:10px;--nav:58px;--bot:64px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;padding-bottom:calc(var(--bot)+24px);}
::-webkit-scrollbar{width:0;height:0;}

.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;text-decoration:none;}
.back-btn{display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text2);text-decoration:none;font-weight:600;padding:6px 0;}

.hero-bar{background:linear-gradient(135deg,var(--green) 0%,var(--green2) 100%);padding:20px 16px 16px;color:#fff;}
.hero-bar h1{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;margin-bottom:4px;}
.hero-bar p{font-size:13px;opacity:.8;line-height:1.5;}

.search-wrap{padding:14px 16px 0;}
#search{width:100%;border:1.5px solid var(--border2);border-radius:var(--rs);padding:10px 14px;font-family:'Instrument Sans',sans-serif;font-size:14px;color:var(--text);background:var(--surface);outline:none;}
#search:focus{border-color:var(--green);}

.letter-nav{display:flex;gap:4px;overflow-x:auto;padding:12px 16px 4px;scrollbar-width:none;}
.letter-nav::-webkit-scrollbar{display:none;}
.letter-pill{flex-shrink:0;padding:5px 11px;border-radius:20px;font-size:12px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;background:var(--surface2);color:var(--text2);text-decoration:none;border:none;cursor:pointer;}
.letter-pill.active,.letter-pill:hover{background:var(--green);color:#fff;}

.section-block{padding:0 16px;margin-top:18px;}
.letter-heading{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;color:var(--green);margin-bottom:10px;}
.term-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--rs);padding:14px;margin-bottom:8px;}
.term-head{display:flex;align-items:center;gap:8px;margin-bottom:6px;}
.term-emoji{font-size:18px;}
.term-name{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;}
.term-def{font-size:13px;color:var(--text2);line-height:1.6;}
.hidden{display:none;}

.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;text-decoration:none;padding:8px 0;}
.bni{font-size:20px;}
.bnl{font-size:9.5px;font-weight:600;color:var(--text3);}
.bnav.active .bnl{color:var(--green);}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;text-decoration:none;padding:4px 0;}
.bnav-ctr-icon{width:42px;height:42px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:17px;margin-bottom:0;}
.empty-state{text-align:center;padding:40px 20px;color:var(--text3);}
.empty-state p{font-size:14px;margin-top:8px;}
</style>
</head>
<body>

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
    <?php if ($user): ?>
      <a href="<?= APP_URL ?>/auth/logout" class="nav-avatar" title="Log out"><?= htmlspecialchars($user['avatar_emoji']) ?></a>
    <?php endif; ?>
  </div>
</nav>

<div class="hero-bar">
  <h1>📚 Hustle Glossary</h1>
  <p>Key hustle, finance &amp; business terms — explained simply, without jargon.</p>
</div>

<div class="search-wrap">
  <input type="search" id="search" placeholder="Search terms… e.g. ROI, cash flow" autocomplete="off"/>
</div>

<div class="letter-nav" id="letter-nav">
  <button class="letter-pill active" onclick="filterLetter('all', this)">All</button>
  <?php foreach ($letters as $l): ?>
    <button class="letter-pill" onclick="filterLetter('<?= $l ?>', this)"><?= $l ?></button>
  <?php endforeach; ?>
</div>

<div id="terms-container">
<?php foreach ($grouped as $letter => $items): ?>
  <div class="section-block letter-section" data-letter="<?= $letter ?>">
    <div class="letter-heading"><?= $letter ?></div>
    <?php foreach ($items as $t): ?>
      <div class="term-card" data-term="<?= strtolower(htmlspecialchars($t['term'])) ?>" data-def="<?= strtolower(htmlspecialchars($t['def'])) ?>">
        <div class="term-head">
          <span class="term-emoji"><?= $t['emoji'] ?></span>
          <span class="term-name"><?= htmlspecialchars($t['term']) ?></span>
        </div>
        <div class="term-def"><?= htmlspecialchars($t['def']) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
</div>

<div id="empty-state" class="empty-state hidden">
  <div style="font-size:32px;">🔍</div>
  <p>No terms match your search.</p>
</div>

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
  <a href="<?= APP_URL ?>/ai" class="bnav">
    <div class="bni">🤖</div>
    <div class="bnl">AI</div>
  </a>
</nav>

<script>
let activeLetter = 'all';

function filterLetter(letter, btn) {
  activeLetter = letter;
  document.querySelectorAll('.letter-pill').forEach(p => p.classList.remove('active'));
  if (btn) btn.classList.add('active');
  applyFilters();
}

document.getElementById('search').addEventListener('input', function() {
  // Reset letter filter when typing
  if (this.value.trim()) {
    activeLetter = 'all';
    document.querySelectorAll('.letter-pill').forEach(p => p.classList.remove('active'));
    document.querySelector('.letter-pill').classList.add('active');
  }
  applyFilters();
});

function applyFilters() {
  const q = document.getElementById('search').value.trim().toLowerCase();
  let visibleCount = 0;

  document.querySelectorAll('.letter-section').forEach(section => {
    const sLetter = section.dataset.letter;
    const letterMatch = activeLetter === 'all' || activeLetter === sLetter;
    let sectionVisible = false;

    section.querySelectorAll('.term-card').forEach(card => {
      const termMatch = !q || card.dataset.term.includes(q) || card.dataset.def.includes(q);
      const show = letterMatch && termMatch;
      card.classList.toggle('hidden', !show);
      if (show) { sectionVisible = true; visibleCount++; }
    });

    section.classList.toggle('hidden', !sectionVisible);
  });

  document.getElementById('empty-state').classList.toggle('hidden', visibleCount > 0);
}
</script>
</body>
</html>
