<?php
/**
 * HustleKingdom — Tools & Platforms Directory
 * Route: GET /tools
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
Auth::start();

$user  = Auth::isLoggedIn() ? Auth::user() : null;
$isPro = Auth::isPro();

// ── DATA ─────────────────────────────────────────────────────────────────────

$socialMoney = [
    [
        'section' => '📱 Social Media Platforms',
        'platforms' => [
            ['emoji'=>'🎵','name'=>'TikTok',     'income'=>'₦1k–₦50k/day',      'methods'=>8,  'color'=>'#e8f0fe','border'=>'#c5d8fc','url'=>'https://tiktok.com'],
            ['emoji'=>'📸','name'=>'Instagram',  'income'=>'₦2k–₦100k/day',     'methods'=>9,  'color'=>'#fce8f3','border'=>'#f5c5e3','url'=>'https://instagram.com'],
            ['emoji'=>'▶️','name'=>'YouTube',    'income'=>'$100–$10k/month',    'methods'=>7,  'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://youtube.com'],
            ['emoji'=>'👍','name'=>'Facebook',   'income'=>'₦1k–₦30k/day',      'methods'=>6,  'color'=>'#ede8fc','border'=>'#d5c5f5','url'=>'https://facebook.com'],
            ['emoji'=>'🐦','name'=>'X (Twitter)','income'=>'₦5k–₦200k/month',   'methods'=>5,  'color'=>'#e8f8fc','border'=>'#c5ecf5','url'=>'https://x.com'],
            ['emoji'=>'💼','name'=>'LinkedIn',   'income'=>'$500–$5k/month',     'methods'=>6,  'color'=>'#e8effe','border'=>'#c5d5f8','url'=>'https://linkedin.com'],
            ['emoji'=>'📌','name'=>'Pinterest',  'income'=>'₦2k–₦50k/month',    'methods'=>4,  'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://pinterest.com'],
            ['emoji'=>'💬','name'=>'WhatsApp',   'income'=>'₦2k–₦100k/month',   'methods'=>8,  'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://business.whatsapp.com'],
            ['emoji'=>'✈️','name'=>'Telegram',   'income'=>'₦5k–₦500k/month',   'methods'=>7,  'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://telegram.org'],
        ]
    ],
    [
        'section' => '🛒 Sell Your Skills Online',
        'platforms' => [
            ['emoji'=>'💼','name'=>'Fiverr',        'income'=>'$5–$500/gig',        'methods'=>10, 'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://fiverr.com'],
            ['emoji'=>'🌍','name'=>'Upwork',         'income'=>'$10–$150/hr',        'methods'=>8,  'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://upwork.com'],
            ['emoji'=>'🎨','name'=>'99designs',      'income'=>'$200–$2k/project',   'methods'=>4,  'color'=>'#fce8f3','border'=>'#f5c5e3','url'=>'https://99designs.com'],
            ['emoji'=>'✍️','name'=>'Contently',      'income'=>'$0.10–$1/word',      'methods'=>3,  'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://contently.com'],
            ['emoji'=>'🤝','name'=>'PeoplePerHour',  'income'=>'$10–$100/hr',        'methods'=>6,  'color'=>'#ede8fc','border'=>'#d5c5f5','url'=>'https://peopleperhour.com'],
            ['emoji'=>'🇳🇬','name'=>'Workpay NG',   'income'=>'₦5k–₦200k/month',   'methods'=>5,  'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://workpay.com.ng'],
        ]
    ],
    [
        'section' => '📦 Sell Digital Products',
        'platforms' => [
            ['emoji'=>'🛒','name'=>'Selar',          'income'=>'₦1k–₦1M/month',     'methods'=>7,  'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://selar.co'],
            ['emoji'=>'🌍','name'=>'Gumroad',         'income'=>'$100–$10k/month',    'methods'=>6,  'color'=>'#fce8f3','border'=>'#f5c5e3','url'=>'https://gumroad.com'],
            ['emoji'=>'📚','name'=>'Amazon KDP',      'income'=>'$50–$5k/month',      'methods'=>4,  'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://kdp.amazon.com'],
            ['emoji'=>'📸','name'=>'Shutterstock',    'income'=>'$100–$2k/month',     'methods'=>3,  'color'=>'#e8effe','border'=>'#c5d5f8','url'=>'https://submit.shutterstock.com'],
            ['emoji'=>'🎧','name'=>'AudioJungle',     'income'=>'$50–$1k/month',      'methods'=>3,  'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://audiojungle.net'],
            ['emoji'=>'🎓','name'=>'Udemy',           'income'=>'$100–$5k/month',     'methods'=>4,  'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://udemy.com'],
        ]
    ],
];

$offlineMoney = [
    [
        'section' => '💳 Payments & Banking',
        'platforms' => [
            ['emoji'=>'💳','name'=>'Paystack',      'income'=>'Accept & collect',   'methods'=>5,  'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://paystack.com'],
            ['emoji'=>'🏦','name'=>'Moniepoint',    'income'=>'POS & Agency Banking','methods'=>4, 'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://moniepoint.com'],
            ['emoji'=>'💵','name'=>'Flutterwave',   'income'=>'Global payments',     'methods'=>4,  'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://flutterwave.com'],
            ['emoji'=>'🏧','name'=>'OPay',           'income'=>'Mobile money',       'methods'=>5,  'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://opayweb.com'],
            ['emoji'=>'💱','name'=>'Binance P2P',   'income'=>'₦1k–₦50k/day',       'methods'=>4,  'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://binance.com'],
            ['emoji'=>'🔁','name'=>'Grey',           'income'=>'USD virtual account', 'methods'=>3,  'color'=>'#ede8fc','border'=>'#d5c5f5','url'=>'https://grey.co'],
        ]
    ],
    [
        'section' => '🛍️ Nigerian Marketplaces',
        'platforms' => [
            ['emoji'=>'📦','name'=>'Jumia',          'income'=>'₦5k–₦500k/month',   'methods'=>6,  'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://seller.jumia.com.ng'],
            ['emoji'=>'🛍️','name'=>'Konga',          'income'=>'₦5k–₦300k/month',   'methods'=>5,  'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://seller.konga.com'],
            ['emoji'=>'🚀','name'=>'Jiji',            'income'=>'₦2k–₦200k/month',   'methods'=>4,  'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://jiji.ng'],
            ['emoji'=>'📱','name'=>'WhatsApp Shop',  'income'=>'₦2k–₦100k/month',   'methods'=>5,  'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://business.whatsapp.com'],
            ['emoji'=>'🌐','name'=>'Shopify',         'income'=>'₦10k–₦1M/month',    'methods'=>6,  'color'=>'#ede8fc','border'=>'#d5c5f5','url'=>'https://shopify.com'],
            ['emoji'=>'🏪','name'=>'Paystack Store',  'income'=>'₦5k–₦500k/month',   'methods'=>4,  'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://paystack.com/store'],
        ]
    ],
    [
        'section' => '🚗 Gig & On-Demand',
        'platforms' => [
            ['emoji'=>'🚕','name'=>'Uber',           'income'=>'₦5k–₦50k/day',      'methods'=>3,  'color'=>'#e8f0fe','border'=>'#c5d8fc','url'=>'https://uber.com/ng'],
            ['emoji'=>'🛵','name'=>'Bolt',            'income'=>'₦3k–₦40k/day',      'methods'=>3,  'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://bolt.eu/ng'],
            ['emoji'=>'🍔','name'=>'Chowdeck',        'income'=>'₦3k–₦30k/day',      'methods'=>2,  'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://chowdeck.com'],
            ['emoji'=>'📦','name'=>'Glovo',           'income'=>'₦2k–₦25k/day',      'methods'=>2,  'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://glovoapp.com/ng'],
            ['emoji'=>'🛺','name'=>'Gokada',          'income'=>'₦3k–₦20k/day',      'methods'=>2,  'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://gokada.ng'],
            ['emoji'=>'🏠','name'=>'Airbnb',          'income'=>'₦10k–₦200k/month',  'methods'=>4,  'color'=>'#fce8f3','border'=>'#f5c5e3','url'=>'https://airbnb.com'],
        ]
    ],
];

$topTools = [
    [
        'section' => '🤖 AI Tools',
        'platforms' => [
            ['emoji'=>'✨','name'=>'ChatGPT',        'income'=>'Freemium',            'methods'=>null,'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://chatgpt.com'],
            ['emoji'=>'🧠','name'=>'Claude',          'income'=>'Freemium',            'methods'=>null,'color'=>'#ede8fc','border'=>'#d5c5f5','url'=>'https://claude.ai'],
            ['emoji'=>'🎨','name'=>'Midjourney',      'income'=>'Paid',                'methods'=>null,'color'=>'#fce8f3','border'=>'#f5c5e3','url'=>'https://midjourney.com'],
            ['emoji'=>'🎤','name'=>'ElevenLabs',      'income'=>'Freemium',            'methods'=>null,'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://elevenlabs.io'],
            ['emoji'=>'🔍','name'=>'Perplexity',      'income'=>'Free',                'methods'=>null,'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://perplexity.ai'],
            ['emoji'=>'🎵','name'=>'Suno AI',         'income'=>'Freemium',            'methods'=>null,'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://suno.ai'],
        ]
    ],
    [
        'section' => '🎨 Design & Creative',
        'platforms' => [
            ['emoji'=>'🎨','name'=>'Canva',           'income'=>'Free',                'methods'=>null,'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://canva.com'],
            ['emoji'=>'🎬','name'=>'CapCut',           'income'=>'Free',                'methods'=>null,'color'=>'#e8f0fe','border'=>'#c5d8fc','url'=>'https://capcut.com'],
            ['emoji'=>'🖌️','name'=>'Figma',           'income'=>'Freemium',            'methods'=>null,'color'=>'#fce8f3','border'=>'#f5c5e3','url'=>'https://figma.com'],
            ['emoji'=>'🖼️','name'=>'Adobe Express',   'income'=>'Freemium',            'methods'=>null,'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://express.adobe.com'],
            ['emoji'=>'🎥','name'=>'DaVinci Resolve',  'income'=>'Free',                'methods'=>null,'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://blackmagicdesign.com'],
            ['emoji'=>'🎯','name'=>'Notion',           'income'=>'Free',                'methods'=>null,'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://notion.so'],
        ]
    ],
    [
        'section' => '📣 Marketing & Growth',
        'platforms' => [
            ['emoji'=>'📧','name'=>'Mailchimp',       'income'=>'Freemium',            'methods'=>null,'color'=>'#fdf4e8','border'=>'#f5e0c5','url'=>'https://mailchimp.com'],
            ['emoji'=>'📊','name'=>'Buffer',           'income'=>'Freemium',            'methods'=>null,'color'=>'#e8f4fc','border'=>'#c5e4f5','url'=>'https://buffer.com'],
            ['emoji'=>'🔗','name'=>'Linktree',         'income'=>'Free',                'methods'=>null,'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://linktr.ee'],
            ['emoji'=>'📈','name'=>'Google Trends',    'income'=>'Free',                'methods'=>null,'color'=>'#fce8e8','border'=>'#f5c5c5','url'=>'https://trends.google.com'],
            ['emoji'=>'📌','name'=>'Hootsuite',        'income'=>'Paid',                'methods'=>null,'color'=>'#ede8fc','border'=>'#d5c5f5','url'=>'https://hootsuite.com'],
            ['emoji'=>'📱','name'=>'WhatsApp Business','income'=>'Free',                'methods'=>null,'color'=>'#e8fce8','border'=>'#c5f0c5','url'=>'https://business.whatsapp.com'],
        ]
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Tools & Platforms — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --purple:#6b21a8;--purple2:#5b1a9a;
  --r:16px;--nav:58px;--bot:64px;
  --sh:0 2px 12px rgba(0,0,0,.06);
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;overflow-x:hidden;padding-bottom:calc(var(--bot)+24px);-webkit-overflow-scrolling:touch;}
::-webkit-scrollbar{width:0;height:0;}

/* ── TOP NAV ── */
.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;}

/* ── HERO ── */
.hero{background:linear-gradient(135deg,#3b0fa0 0%,#5c2ec4 50%,#7b4fd4 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);}
.hero::after{content:'';position:absolute;left:60%;bottom:-50px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.04);}
.hero-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:26px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;position:relative;z-index:1;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.75);line-height:1.55;position:relative;z-index:1;}

/* ── TAB SWITCHER ── */
.tab-wrap{padding:14px 16px 0;}
.tabs{display:flex;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:4px;gap:3px;}
.tab{flex:1;padding:9px 6px;border-radius:10px;font-size:12px;font-weight:700;text-align:center;border:none;background:transparent;color:var(--text3);cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;transition:.2s;white-space:nowrap;}
.tab.active{background:#fff;color:var(--text);box-shadow:0 2px 8px rgba(0,0,0,.1);}
.tab-icon{display:block;font-size:14px;margin-bottom:2px;}

/* ── SECTION HEADER ── */
.sec-head{padding:20px 16px 10px;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--text);display:flex;align-items:center;gap:8px;}

/* ── PLATFORM GRID ── */
.plat-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 16px;}
.plat-card{border-radius:16px;padding:18px 12px 16px;text-align:center;text-decoration:none;color:var(--text);display:flex;flex-direction:column;align-items:center;gap:4px;border:1.5px solid transparent;transition:.15s;cursor:pointer;}
.plat-card:active{transform:scale(.96);opacity:.9;}
.plat-emoji{font-size:38px;line-height:1;margin-bottom:6px;display:block;}
.plat-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;color:var(--text);margin-bottom:2px;}
.plat-income{font-size:11px;color:var(--text2);font-weight:600;margin-bottom:4px;}
.plat-methods{font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;color:var(--green3);}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;border:none;background:transparent;color:var(--text3);text-decoration:none;transition:.2s;}
.bnav.active{color:var(--green);}
.bni{font-size:20px;}
.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}

/* ── TAB CONTENT ── */
.tab-content{display:none;}
.tab-content.active{display:block;}
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
      <a href="<?= APP_URL ?>/dashboard" class="nav-avatar"><?= htmlspecialchars($user['avatar_emoji'] ?? '👤') ?></a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/auth/login" class="pro-chip">Login →</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div class="hero-chip">🛠 PLATFORMS & TOOLS</div>
  <div class="hero-title">Tools & Platforms</div>
  <div class="hero-sub">Every platform and tool you need to start earning today.</div>
</div>

<!-- TAB SWITCHER -->
<div class="tab-wrap">
  <div class="tabs">
    <button class="tab active" onclick="switchTab(this,'social')">
      <span class="tab-icon">📱</span>Social Money
    </button>
    <button class="tab" onclick="switchTab(this,'offline')">
      <span class="tab-icon">🏪</span>Offline Money
    </button>
    <button class="tab" onclick="switchTab(this,'top')">
      <span class="tab-icon">🏆</span>Top Tools
    </button>
  </div>
</div>

<!-- TAB: SOCIAL MONEY -->
<div class="tab-content active" id="tab-social">
  <?php foreach ($socialMoney as $section): ?>
    <div class="sec-head"><?= htmlspecialchars($section['section']) ?></div>
    <div class="plat-grid">
      <?php foreach ($section['platforms'] as $p): ?>
        <a class="plat-card"
           href="<?= htmlspecialchars($p['url']) ?>"
           target="_blank" rel="noopener"
           style="background:<?= $p['color'] ?>;border-color:<?= $p['border'] ?>;">
          <span class="plat-emoji"><?= $p['emoji'] ?></span>
          <div class="plat-name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="plat-income"><?= htmlspecialchars($p['income']) ?></div>
          <?php if ($p['methods']): ?>
            <div class="plat-methods"><?= $p['methods'] ?> methods</div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>

<!-- TAB: OFFLINE MONEY -->
<div class="tab-content" id="tab-offline">
  <?php foreach ($offlineMoney as $section): ?>
    <div class="sec-head"><?= htmlspecialchars($section['section']) ?></div>
    <div class="plat-grid">
      <?php foreach ($section['platforms'] as $p): ?>
        <a class="plat-card"
           href="<?= htmlspecialchars($p['url']) ?>"
           target="_blank" rel="noopener"
           style="background:<?= $p['color'] ?>;border-color:<?= $p['border'] ?>;">
          <span class="plat-emoji"><?= $p['emoji'] ?></span>
          <div class="plat-name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="plat-income"><?= htmlspecialchars($p['income']) ?></div>
          <?php if ($p['methods']): ?>
            <div class="plat-methods"><?= $p['methods'] ?> methods</div>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>

<!-- TAB: TOP TOOLS -->
<div class="tab-content" id="tab-top">
  <?php foreach ($topTools as $section): ?>
    <div class="sec-head"><?= htmlspecialchars($section['section']) ?></div>
    <div class="plat-grid">
      <?php foreach ($section['platforms'] as $p): ?>
        <a class="plat-card"
           href="<?= htmlspecialchars($p['url']) ?>"
           target="_blank" rel="noopener"
           style="background:<?= $p['color'] ?>;border-color:<?= $p['border'] ?>;">
          <span class="plat-emoji"><?= $p['emoji'] ?></span>
          <div class="plat-name"><?= htmlspecialchars($p['name']) ?></div>
          <div class="plat-income"><?= htmlspecialchars($p['income']) ?></div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>
</div>

<div style="height:12px;"></div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/"        class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai"       class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
function switchTab(btn, tabId) {
  // Update tab buttons
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  // Update content panels
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  document.getElementById('tab-' + tabId).classList.add('active');
  // Scroll to top of content
  window.scrollTo({top: 0, behavior: 'smooth'});
}
</script>
</body>
</html>
