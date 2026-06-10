<?php
/**
 * HustleKingdom — Offline Money-Making Tools
 * Route: GET /tools
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
Auth::start();

$user  = Auth::isLoggedIn() ? Auth::user() : null;
$isPro = Auth::isPro();

// ── DATA ─────────────────────────────────────────────────────────────────────
$offlineTools = [
  ['emoji'=>'🚲','name'=>'Delivery Bicycle (Cargo)','earn'=>'₦3k–₦8k/day','badge'=>'Delivery','desc'=>'A cargo bicycle with an insulated box earns ₦3k–₦8k/day as a food and parcel delivery rider. Low fuel cost, easy maintenance, and immediate daily cash income.'],
  ['emoji'=>'🛵','name'=>'Dispatch / Delivery Okada','earn'=>'₦5k–₦15k/day','badge'=>'Dispatch','desc'=>'A motorcycle used for dispatch services earns ₦5k–₦15k/day. Register as a freelance dispatch rider for pharmacies, restaurants, and logistics companies.'],
  ['emoji'=>'🚗','name'=>'Ride-Hailing Car (Bolt/Uber)','earn'=>'₦15k–₦40k/day','badge'=>'Ride-Hailing','desc'=>'A registered car on Bolt or inDrive earns ₦15k–₦40k/day before fuel. Owner-drivers keep 75–80% of every fare. Best asset for daily cash income generation.'],
  ['emoji'=>'🌾','name'=>'Power Tiller / Mini Tractor','earn'=>'₦10k–₦30k/day','badge'=>'Farm Equipment','desc'=>'A power tiller rented to smallholder farmers earns ₦10k–₦30k/day during planting and harvest seasons. Extremely high demand in rural and semi-urban farming communities.'],
  ['emoji'=>'🔨','name'=>'Concrete Mixer Machine','earn'=>'₦5k–₦15k/day','badge'=>'Construction','desc'=>'Rent a concrete mixer to construction sites for ₦5k–₦15k/day. With Nigeria\'s booming construction sector, this asset is hired out almost daily with minimal downtime.'],
  ['emoji'=>'⚡','name'=>'Generator (Industrial)','earn'=>'₦20k–₦80k/day','badge'=>'Power','desc'=>'A 15kVA–45kVA generator leased to estates, hotels, or event centers earns ₦20k–₦80k/day. Power outages guarantee permanent demand across Nigeria.'],
  ['emoji'=>'🏗️','name'=>'Scaffolding Equipment (Set)','earn'=>'₦15k–₦50k/week','badge'=>'Construction','desc'=>'A full scaffolding set rented to construction companies earns ₦15k–₦50k/week. Set it up, walk away — the construction crew does all the work while your equipment earns.'],
  ['emoji'=>'🌽','name'=>'Grain Milling Machine','earn'=>'₦3k–₦10k/day','badge'=>'Food Processing','desc'=>'A posho/grain milling machine earns ₦3k–₦10k/day grinding maize, cassava, and pepper. Station it at a market or motor park and collect fees per kilogram processed.'],
  ['emoji'=>'🥜','name'=>'Groundnut / Palm Oil Press','earn'=>'₦5k–₦20k/day','badge'=>'Oil Processing','desc'=>'An oil extraction machine processes palm fruits or groundnuts into oil. Earn per drum processed or rent the machine by the hour during harvest — both generate strong daily cash.'],
  ['emoji'=>'🌿','name'=>'Cassava Processing Machine','earn'=>'₦5k–₦15k/day','badge'=>'Agro-Processing','desc'=>'A garri-frying machine or cassava grater earns daily fees from farmers. With Nigeria consuming millions of tons of cassava products yearly, the income is consistent and recession-proof.'],
  ['emoji'=>'🪣','name'=>'Water Tanker / Bowser','earn'=>'₦20k–₦100k/day','badge'=>'Water Supply','desc'=>'A water tanker truck earns ₦20k–₦100k per delivery to construction sites, estates, and homes in areas with poor pipe water supply — which covers most of Nigeria.'],
  ['emoji'=>'📸','name'=>'Professional Camera (DSLR Kit)','earn'=>'₦20k–₦150k/event','badge'=>'Photography','desc'=>'A DSLR camera kit earns ₦20k–₦150k per event shoot. Weddings, birthdays, corporate events, and aso-ebi parties happen every weekend in every Nigerian city.'],
  ['emoji'=>'🎤','name'=>'PA Sound System (Full Rig)','earn'=>'₦30k–₦200k/event','badge'=>'Events','desc'=>'A professional PA system rented for parties, churches, and events earns ₦30k–₦200k per weekend. One system books multiple events per month with zero extra effort.'],
  ['emoji'=>'🎪','name'=>'Event Canopy & Chairs (Set)','earn'=>'₦50k–₦200k/event','badge'=>'Event Rentals','desc'=>'A set of 10 canopies and 200 chairs rented for events and celebrations earns ₦50k–₦200k/weekend. Socialization culture in Nigeria makes this a permanent, high-demand business.'],
  ['emoji'=>'❄️','name'=>'Industrial Freezer / Cold Room','earn'=>'₦2k–₦10k/day','badge'=>'Cold Storage','desc'=>'A walk-in cold room or chest freezer rented to meat sellers, fish mongers, and food vendors earns ₦2k–₦10k/day per storage slot — passive income from a single asset.'],
  ['emoji'=>'🧺','name'=>'Commercial Washing Machine','earn'=>'₦3k–₦12k/day','badge'=>'Laundry','desc'=>'A commercial laundry washing machine at a busy laundrette earns ₦3k–₦12k/day. Students, bachelors, and offices are a permanent client base that never disappears.'],
  ['emoji'=>'💈','name'=>'Barbing Chair & Equipment Set','earn'=>'₦5k–₦20k/day','badge'=>'Barbershop','desc'=>'A professional barbershop setup (chair, clippers, mirrors) earns ₦5k–₦20k/day. Rent a chair to a barber at a daily flat rate and earn without picking up a clipper yourself.'],
  ['emoji'=>'🚜','name'=>'Harvesting Machine (Combine)','earn'=>'₦50k–₦200k/day','badge'=>'Agric Harvest','desc'=>'A combine harvester or rice harvesting machine earns ₦50k–₦200k/day during harvest season. Agricultural machinery hire is one of the highest-return physical asset businesses in Nigeria.'],
  ['emoji'=>'🔧','name'=>'Welding & Fabrication Set','earn'=>'₦5k–₦25k/day','badge'=>'Fabrication','desc'=>'A complete welding machine setup earns ₦5k–₦25k/day from gate fabrication, furniture work, and construction contracts. Metal fabricators are consistently in demand everywhere.'],
  ['emoji'=>'🏠','name'=>'Short-Let Apartment (Furnished)','earn'=>'₦20k–₦80k/night','badge'=>'Real Estate','desc'=>'A fully furnished apartment listed on Airbnb, Shortlet.ng, and WhatsApp earns ₦20k–₦80k/night. Nigeria\'s hospitality gap makes short-let one of the top passive income assets.'],
  ['emoji'=>'🖨️','name'=>'Printing Machine (DTF/Vinyl)','earn'=>'₦5k–₦30k/day','badge'=>'Printing','desc'=>'A DTF fabric printer or vinyl cutter earns ₦5k–₦30k/day printing custom T-shirts, jerseys, caps, banners, and branded merch for schools, churches, and corporate events.'],
  ['emoji'=>'🏋️','name'=>'Gym Equipment (Full Set)','earn'=>'₦50k–₦500k/month','badge'=>'Fitness','desc'=>'A set of gym equipment rented into a membership gym earns ₦10k–₦100k/month per machine bay leased. Or operate your own micro-gym charging ₦2k–₦5k/month per member.'],
  ['emoji'=>'🌱','name'=>'Water Pump & Irrigation Set','earn'=>'₦5k–₦20k/day','badge'=>'Irrigation','desc'=>'An irrigation pump rented to vegetable and dry-season farmers earns ₦5k–₦20k/day. Dry-season farming is a booming sector as farmers grow year-round to meet food demand.'],
  ['emoji'=>'🪟','name'=>'Block Moulding Machine','earn'=>'₦10k–₦50k/day','badge'=>'Construction','desc'=>'A concrete block moulding machine produces 500–2,000 blocks/day. Sell at ₦300–₦500/block or rent the machine to builders by the day. Construction is constant in Nigeria.'],
  ['emoji'=>'🐟','name'=>'Fish Pond Setup (Catfish)','earn'=>'₦300k–₦1m/cycle','badge'=>'Aquaculture','desc'=>'A well-managed fish pond earns ₦300k–₦1m per harvest cycle (4–6 months). Catfish farming with a re-circulating system is one of the most profitable agricultural assets.'],
  ['emoji'=>'🚛','name'=>'Tricycle (Keke NAPEP)','earn'=>'₦5k–₦15k/day','badge'=>'Transport','desc'=>'A keke tricycle earns ₦5k–₦15k/day in passenger fare income. Owner-operators keep everything; owners leasing to drivers collect ₦2k–₦5k/day guaranteed commission.'],
  ['emoji'=>'🌀','name'=>'Industrial Sewing Machine','earn'=>'₦3k–₦15k/day','badge'=>'Fashion','desc'=>'A heavy-duty sewing machine used for tailoring, fashion production, or school uniform contracts earns ₦3k–₦15k/day. Rent it to a tailor hourly or operate your own production unit.'],
  ['emoji'=>'🧊','name'=>'Ice Block Making Machine','earn'=>'₦3k–₦15k/day','badge'=>'Ice Supply','desc'=>'An ice block making machine earns ₦3k–₦15k/day selling ice blocks to cold drink sellers, fishmongers, abattoirs, and events. Especially profitable April–September in hot seasons.'],
  ['emoji'=>'📡','name'=>'CCTV & Security Equipment','earn'=>'₦50k–₦500k/install','badge'=>'Security','desc'=>'Install CCTV systems for homes, shops, and estates and charge ₦50k–₦500k per installation. Then earn monthly maintenance fees of ₦5k–₦20k/client — recurring income.'],
  ['emoji'=>'🐔','name'=>'Poultry Battery Cage System','earn'=>'₦100k–₦400k/month','badge'=>'Poultry','desc'=>'A battery cage poultry setup of 500 layers earns ₦100k–₦400k/month from egg sales alone. Eggs are a daily commodity with permanent demand — a true cash machine asset.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Money-Making Tools — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --r:16px;--nav:58px;--bot:64px;
  --sh:0 2px 12px rgba(0,0,0,.06);
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html,body{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;padding-bottom:calc(var(--bot)+24px);-webkit-overflow-scrolling:touch;}
::-webkit-scrollbar{width:0;height:0;}

/* NAV */
.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;}

/* HERO */
.hero{background:linear-gradient(135deg,#1a6e2e 0%,#16a05a 50%,#22c870 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);}
.hero::after{content:'';position:absolute;left:60%;bottom:-50px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.04);}
.hero-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:26px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;position:relative;z-index:1;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.75);line-height:1.55;position:relative;z-index:1;}

/* SECTION HEADS */
.sec-head{padding:20px 16px 10px;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--text);}

/* TOOL LIST */
.tool-list{display:flex;flex-direction:column;gap:0;border:1.5px solid var(--border);border-radius:var(--r);margin:0 16px;overflow:hidden;}
.tool-row{display:flex;align-items:flex-start;gap:12px;padding:12px 14px;border-bottom:1px solid var(--border);text-decoration:none;color:var(--text);}
.tool-row:last-child{border-bottom:none;}
.tool-row:active{background:var(--surface);}
.tr-icon{font-size:22px;width:38px;height:38px;background:var(--surface);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--border);}
.tr-info{flex:1;}
.tr-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;margin-bottom:2px;}
.tr-earn{font-size:11.5px;font-weight:800;color:var(--green3);margin-bottom:3px;}
.tr-desc{font-size:11px;color:var(--text3);line-height:1.45;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.tr-badge{display:inline-block;font-size:9.5px;font-weight:700;padding:2px 7px;border-radius:6px;background:var(--green-light);color:var(--green3);font-family:'Bricolage Grotesque',sans-serif;margin-top:4px;}

/* BOTTOM NAV */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
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
      <a href="<?= APP_URL ?>/dashboard" class="nav-avatar"><?= htmlspecialchars($user['avatar_emoji'] ?? '👤') ?></a>
    <?php else: ?>
      <a href="<?= APP_URL ?>/auth/login" class="pro-chip">Login →</a>
    <?php endif; ?>
  </div>
</nav>

<!-- HERO -->
<div class="hero">
  <div class="hero-chip">🏗 OFFLINE TOOLS</div>
  <div class="hero-title">Money-Making Tools</div>
  <div class="hero-sub">Physical equipment and assets that generate real daily income in Nigeria.</div>
</div>

<div class="sec-head">🏗️ Top Offline Money-Making Tools & Equipment</div>
<div class="tool-list">
  <?php foreach ($offlineTools as $t): ?>
    <div class="tool-row">
      <div class="tr-icon"><?= $t['emoji'] ?></div>
      <div class="tr-info">
        <div class="tr-name"><?= htmlspecialchars($t['name']) ?></div>
        <div class="tr-earn"><?= htmlspecialchars($t['earn']) ?></div>
        <div class="tr-desc"><?= htmlspecialchars($t['desc']) ?></div>
        <span class="tr-badge"><?= htmlspecialchars($t['badge']) ?></span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/"         class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles"  class="bnav"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute"  class="bnav-ctr"><div class="bnav-ctr-icon">▶</div><div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div></a>
  <a href="<?= APP_URL ?>/location" class="bnav"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai"       class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

</body>
</html>
