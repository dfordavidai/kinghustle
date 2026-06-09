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

$toolCategories = [
    [
        'title' => '💳 Payments & Banking',
        'tools' => [
            ['💳', 'Paystack',      'https://paystack.com',       'Accept cards & bank transfers', 'tb', 'Payments',   'Nigeria'],
            ['🏦', 'Moniepoint',    'https://moniepoint.com',     'POS, agency banking, business', 'tg', 'Banking',    'Nigeria'],
            ['💵', 'Flutterwave',   'https://flutterwave.com',    'Global payments & transfers',   'tb', 'Payments',   'Global'],
            ['🏧', 'OPay',         'https://opayweb.com',        'Mobile money & transfers',      'tg', 'Fintech',    'Nigeria'],
            ['💱', 'Binance P2P',  'https://binance.com',        'Crypto trading & P2P USDT',     'tgd','Crypto',     'Global'],
            ['🔁', 'Grey',         'https://grey.co',            'USD/GBP virtual bank accounts', 'tp', 'USD Income', 'Global'],
        ]
    ],
    [
        'title' => '🛒 Sell Online',
        'tools' => [
            ['🛒', 'Selar',         'https://selar.co',           'Sell digital products easily',   'tg', 'Digital Sales', 'Nigeria'],
            ['📦', 'Jumia Seller',  'https://seller.jumia.com.ng','List products on Jumia',         'to', 'E-commerce',    'Nigeria'],
            ['🛍️', 'Konga',        'https://seller.konga.com',   'Sell on Konga marketplace',      'to', 'E-commerce',    'Nigeria'],
            ['🌍', 'Gumroad',       'https://gumroad.com',        'Sell courses, ebooks, templates','tb', 'Digital Sales', 'Global'],
            ['📸', 'Shutterstock',  'https://submit.shutterstock.com', 'Sell photos & videos',    'tp', 'Stock Media',   'Global'],
            ['🎧', 'AudioJungle',   'https://audiojungle.net',    'Sell audio, music, SFX',         'tgd','Audio Sales',  'Global'],
        ]
    ],
    [
        'title' => '💼 Freelance Platforms',
        'tools' => [
            ['💼', 'Fiverr',        'https://fiverr.com',         'Sell gigs from ₦1,500+',         'tp', 'Freelance',  'Global'],
            ['🌍', 'Upwork',        'https://upwork.com',         'USD clients, fixed & hourly',    'tgd','USD Income', 'Global'],
            ['📋', 'Toptal',        'https://toptal.com',         'Top 3% freelancers only',        'to', 'Elite',      'Global'],
            ['🤝', 'PeoplePerHour', 'https://peopleperhour.com',  'UK/EU clients for freelancers',  'tb', 'Freelance',  'Global'],
            ['🇳🇬', 'Workpay',     'https://workpay.com.ng',     'Nigerian freelance platform',    'tg', 'Local',      'Nigeria'],
            ['✍️', 'Contently',    'https://contently.com',      'Content writing for brands',     'tgd','Writing',    'Global'],
        ]
    ],
    [
        'title' => '🎨 Design & Creative',
        'tools' => [
            ['🎨', 'Canva',         'https://canva.com',          'Design graphics, videos, docs',  'tg', 'Free',       'Global'],
            ['🖼️', 'Adobe Express', 'https://express.adobe.com',  'Quick design & social media',    'tb', 'Freemium',   'Global'],
            ['🎬', 'CapCut',        'https://capcut.com',         'Video editing for content',      'tg', 'Free',       'Global'],
            ['🖌️', 'Figma',        'https://figma.com',          'UI/UX design & prototyping',     'tp', 'Design',     'Global'],
            ['🎵', 'Suno AI',       'https://suno.ai',            'AI music generation',            'tgd','AI Creative','Global'],
            ['🤖', 'Midjourney',    'https://midjourney.com',     'AI image generation',            'to', 'AI Art',     'Global'],
        ]
    ],
    [
        'title' => '📣 Marketing & Growth',
        'tools' => [
            ['💬', 'WhatsApp Business','https://business.whatsapp.com','Customer messaging & catalog','tg','Free','Nigeria'],
            ['📧', 'Mailchimp',     'https://mailchimp.com',      'Email marketing automation',     'tg', 'Freemium',   'Global'],
            ['📊', 'Buffer',        'https://buffer.com',         'Schedule social media posts',    'tg', 'Freemium',   'Global'],
            ['🔗', 'Linktree',      'https://linktr.ee',          'One link for all your content',  'tg', 'Free',       'Global'],
            ['📌', 'Notion',        'https://notion.so',          'Plan, track & manage business',  'tg', 'Free',       'Global'],
            ['📈', 'Google Trends', 'https://trends.google.com',  'Find what people search for',    'tg', 'Free',       'Global'],
        ]
    ],
    [
        'title' => '🤖 AI Tools',
        'tools' => [
            ['✨', 'ChatGPT',        'https://chatgpt.com',        'AI writing, coding, ideas',      'tb', 'Freemium',   'Global'],
            ['🧠', 'Claude',         'https://claude.ai',          'AI research & long-form tasks',  'tp', 'Freemium',   'Global'],
            ['🔍', 'Perplexity',     'https://perplexity.ai',      'AI-powered research engine',     'tg', 'Free',       'Global'],
            ['🎤', 'ElevenLabs',     'https://elevenlabs.io',      'AI voice cloning & audio',       'tgd','AI Audio',   'Global'],
            ['🌐', 'DeepL',          'https://deepl.com',          'Best AI translation tool',       'tg', 'Free',       'Global'],
            ['📝', 'Jasper',         'https://jasper.ai',          'AI marketing copy writer',       'to', 'Paid',       'Global'],
        ]
    ],
    [
        'title' => '📚 Learning & Courses',
        'tools' => [
            ['🎓', 'Udemy',          'https://udemy.com',          'Affordable skill courses',       'tgd','Courses',    'Global'],
            ['📹', 'YouTube',         'https://youtube.com',        'Free learning on everything',    'tg', 'Free',       'Global'],
            ['💡', 'Coursera',        'https://coursera.org',       'University-level certifications','tb', 'Freemium',   'Global'],
            ['🎯', 'Skillshare',      'https://skillshare.com',     'Creative & business skills',     'to', 'Paid',       'Global'],
            ['🏆', 'HNG Internship',  'https://hng.tech',           'Nigerian tech talent program',   'tg', 'Free',       'Nigeria'],
            ['💻', 'freeCodeCamp',    'https://freecodecamp.org',   'Free coding certification',      'tg', 'Free',       'Global'],
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
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green3:#0d6e3c;--green-light:#ebf7f1;
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
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;}

.page-hero{padding:20px 16px 18px;background:linear-gradient(135deg,#0d1d3c 0%,#1a3560 55%,#1d4080 100%);position:relative;overflow:hidden;}
.page-hero::before{content:'';position:absolute;right:-30px;top:-30px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.05);}
.ph-label{font-size:11.5px;color:rgba(255,255,255,.6);font-weight:600;margin-bottom:4px;}
.ph-title{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;color:#fff;margin-bottom:6px;}
.ph-sub{font-size:13px;color:rgba(255,255,255,.7);line-height:1.55;}

/* Search */
.search-wrap{padding:12px 16px 0;}
.search-box{display:flex;gap:8px;align-items:center;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:0 12px;height:44px;}
.search-box:focus-within{border-color:var(--green);}
.search-box input{flex:1;background:none;border:none;outline:none;font-size:14px;font-family:'Instrument Sans',sans-serif;color:var(--text);}
.search-box input::placeholder{color:var(--text3);}

/* Filter pills */
.filter-scroll{display:flex;gap:7px;overflow-x:auto;padding:10px 16px 0;scrollbar-width:none;}
.fpill{flex-shrink:0;padding:6px 12px;border-radius:20px;font-size:11px;font-weight:700;border:1.5px solid var(--border);background:var(--surface);color:var(--text2);cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;transition:.15s;}
.fpill.active{background:var(--green);border-color:var(--green);color:#fff;}

/* Tool sections */
.sec-head{display:flex;align-items:center;padding:0 16px;margin:20px 0 10px;}
.sec-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:14.5px;}

.tool-list{display:flex;flex-direction:column;gap:0;border:1.5px solid var(--border);border-radius:var(--r);margin:0 16px;overflow:hidden;}
.trow{display:flex;align-items:center;gap:12px;padding:12px 14px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);transition:.15s;}
.trow:last-child{border-bottom:none;}
.trow:active{background:var(--surface);}
.tr-emoji{font-size:22px;width:36px;height:36px;background:var(--surface);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--border);}
.tr-info{flex:1;min-width:0;}
.tr-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;margin-bottom:2px;}
.tr-desc{font-size:11px;color:var(--text3);line-height:1.4;}
.tr-badges{display:flex;gap:5px;margin-top:4px;flex-wrap:wrap;}
.tr-ext{font-size:16px;color:var(--text3);}

.tag{font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.tb{background:var(--blue-light);color:var(--blue);}
.to{background:var(--orange-light);color:var(--orange);}
.tp{background:var(--purple-light);color:var(--purple);}
.tgd{background:var(--gold-light);color:var(--gold2);}
.tt{background:#ebfaf8;color:#0e9488;}
.tr_{background:var(--red-light);color:var(--red);}

.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
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

<div class="page-hero">
  <div class="ph-label">Everything you need to hustle</div>
  <div class="ph-title">🛠 Tools & Platforms</div>
  <div class="ph-sub"><?= count(array_merge(...array_column($toolCategories, 'tools'))) ?>+ curated tools for Nigerian hustlers — free, cheap, and battle-tested.</div>
</div>

<div class="search-wrap">
  <div class="search-box">
    <span>🔍</span>
    <input type="text" id="tool-search" placeholder="Search tools…" autocomplete="off" oninput="filterTools(this.value)"/>
  </div>
</div>

<div class="filter-scroll" id="filter-pills">
  <div class="fpill active" onclick="filterCat(this,'all')">All</div>
  <div class="fpill" onclick="filterCat(this,'nigeria')">🇳🇬 Nigeria</div>
  <div class="fpill" onclick="filterCat(this,'free')">🆓 Free</div>
  <div class="fpill" onclick="filterCat(this,'payments')">💳 Payments</div>
  <div class="fpill" onclick="filterCat(this,'freelance')">💼 Freelance</div>
  <div class="fpill" onclick="filterCat(this,'design')">🎨 Design</div>
  <div class="fpill" onclick="filterCat(this,'ai')">🤖 AI</div>
</div>

<?php foreach ($toolCategories as $section): ?>
  <div class="sec-head">
    <div class="sec-title"><?= htmlspecialchars($section['title']) ?></div>
  </div>
  <div class="tool-list" data-section>
    <?php foreach ($section['tools'] as [$emoji, $name, $url, $desc, $badgeClass, $badge, $region]): ?>
      <a class="trow" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener"
         data-name="<?= strtolower(htmlspecialchars($name)) ?>"
         data-badge="<?= strtolower(htmlspecialchars($badge)) ?>"
         data-region="<?= strtolower(htmlspecialchars($region)) ?>">
        <div class="tr-emoji"><?= $emoji ?></div>
        <div class="tr-info">
          <div class="tr-name"><?= htmlspecialchars($name) ?></div>
          <div class="tr-desc"><?= htmlspecialchars($desc) ?></div>
          <div class="tr-badges">
            <span class="tag <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($badge) ?></span>
            <span class="tag" style="background:var(--surface);color:var(--text3);"><?= htmlspecialchars($region) ?></span>
          </div>
        </div>
        <div class="tr-ext">↗</div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

<div style="height:12px;"></div>

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
let activeFilter = 'all';

function filterTools(q) {
  const rows = document.querySelectorAll('.trow');
  q = q.toLowerCase().trim();
  rows.forEach(row => {
    const name = row.dataset.name || '';
    const badge = row.dataset.badge || '';
    const show = !q || name.includes(q) || badge.includes(q);
    row.style.display = show ? '' : 'none';
  });
  // Hide empty sections
  document.querySelectorAll('[data-section]').forEach(sec => {
    const visible = [...sec.querySelectorAll('.trow')].some(r => r.style.display !== 'none');
    sec.previousElementSibling.style.display = visible ? '' : 'none';
    sec.style.display = visible ? '' : 'none';
  });
}

function filterCat(pill, cat) {
  document.querySelectorAll('.fpill').forEach(p => p.classList.remove('active'));
  pill.classList.add('active');
  activeFilter = cat;
  const rows = document.querySelectorAll('.trow');
  rows.forEach(row => {
    let show = true;
    if (cat === 'nigeria') show = row.dataset.region === 'nigeria';
    else if (cat === 'free') show = row.dataset.badge === 'free';
    else if (cat === 'payments') show = row.closest('[data-section]') === document.querySelectorAll('[data-section]')[0];
    else if (cat === 'freelance') show = row.dataset.badge === 'freelance' || row.dataset.badge === 'usd income';
    else if (cat === 'design') show = row.dataset.badge === 'design' || row.dataset.badge === 'ai art' || row.dataset.badge === 'ai creative';
    else if (cat === 'ai') show = (row.dataset.badge || '').includes('ai') || row.closest('[data-section]') === document.querySelectorAll('[data-section]')[5];
    row.style.display = show ? '' : 'none';
  });
  document.querySelectorAll('[data-section]').forEach(sec => {
    const visible = [...sec.querySelectorAll('.trow')].some(r => r.style.display !== 'none');
    sec.previousElementSibling.style.display = visible ? '' : 'none';
    sec.style.display = visible ? '' : 'none';
  });
}
</script>
</body>
</html>
