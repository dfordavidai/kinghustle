<?php
/**
 * HustleKingdom — Public Landing Page
 * Route: GET /
 * No auth required. Shows marketing page with CTA to register/login.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';

Auth::start();

// If already logged in, send straight to dashboard
if (Auth::isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0"/>
<title>HustleKingdom — Find Your Hustle. Build Your Empire.</title>
<meta name="description" content="500+ Nigerian side hustles, AI-powered strategy, step-by-step execution plans, social media income guides and offline money tools. Built for the Nigerian grind."/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
/* ── RESET & BASE ── */
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
:root{
  --bg:#fff;
  --surface:#f5f3ed;
  --border:#e8e4db;
  --border2:#ddd8ce;
  --text:#0f1a0f;
  --text2:#5a6b5a;
  --text3:#9aaa9a;
  --green:#0e7a42;
  --green2:#0a5e2f;
  --green-light:#eaf5ee;
  --green-mid:#d4f0e2;
  --green-border:#b5dfc5;
  --r:12px;
  --rl:14px;
}
html{overflow-x:hidden;}
body{
  font-family:'Plus Jakarta Sans',sans-serif;
  background:var(--surface);
  color:var(--text);
  max-width:430px;
  margin:0 auto;
  min-height:100vh;
  display:flex;
  flex-direction:column;
  overflow-x:hidden;
}
a{text-decoration:none;}
::-webkit-scrollbar{width:0;height:0;}

/* ── NAV ── */
nav{
  display:flex;align-items:center;justify-content:space-between;
  padding:14px 18px;background:var(--bg);
  border-bottom:1.5px solid var(--border);
  position:sticky;top:0;z-index:100;
}
.nav-logo{display:flex;align-items:center;gap:9px;}
.nav-crown{
  width:32px;height:32px;background:var(--green);
  border-radius:8px;display:flex;align-items:center;justify-content:center;
  font-size:17px;flex-shrink:0;
}
.nav-brand{font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:16px;color:var(--text);}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-login{
  font-size:13px;color:var(--green);font-weight:700;
  background:var(--green-light);border:1.5px solid var(--green-border);
  padding:7px 15px;border-radius:20px;transition:background .15s;white-space:nowrap;
}
.nav-login:hover{background:var(--green-mid);}

/* ── HERO ── */
.hero{background:var(--bg);padding:28px 18px 26px;border-bottom:1.5px solid var(--border);}
.hero-flag{display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.hero-flag-bar{
  display:flex;height:10px;width:52px;border-radius:4px;overflow:hidden;
  border:1px solid #cce5d8;flex-shrink:0;
}
.hero-flag-bar span:nth-child(1){flex:1;background:#008751;}
.hero-flag-bar span:nth-child(2){flex:1;background:#fff;}
.hero-flag-bar span:nth-child(3){flex:1;background:#008751;}
.hero-flag-txt{font-size:11px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.6px;}
.hero h1{
  font-family:'Space Grotesk',sans-serif;font-size:31px;font-weight:700;
  line-height:1.12;margin-bottom:12px;color:var(--text);
}
.hero h1 mark{
  background:var(--green-mid);color:var(--green2);
  padding:2px 7px;border-radius:6px;font-style:normal;
}
.hero p{font-size:14px;color:var(--text2);line-height:1.72;margin-bottom:22px;}
.btn-primary{
  display:flex;align-items:center;justify-content:center;gap:8px;
  background:var(--green);color:#fff;
  font-family:'Plus Jakarta Sans',sans-serif;font-size:15px;font-weight:700;
  padding:16px;border-radius:var(--rl);transition:background .15s;margin-bottom:10px;
}
.btn-primary:hover{background:var(--green2);}
.btn-secondary{
  display:flex;align-items:center;justify-content:center;
  background:var(--surface);color:var(--text);
  font-size:13px;font-weight:600;padding:13px;border-radius:var(--rl);
  border:1.5px solid var(--border2);transition:border-color .15s;
}
.btn-secondary:hover{border-color:var(--green);}
.hero-note{font-size:11px;color:var(--text3);text-align:center;margin-top:10px;}

/* ── STATS BAR ── */
.stats-bar{display:grid;grid-template-columns:repeat(4,1fr);background:var(--green);}
.stat-box{padding:13px 8px;text-align:center;border-right:1px solid rgba(255,255,255,.15);}
.stat-box:last-child{border-right:none;}
.stat-num{font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:#fff;}
.stat-label{font-size:9px;color:rgba(255,255,255,.68);font-weight:600;margin-top:2px;text-transform:uppercase;letter-spacing:.4px;}

/* ── SHARED SECTION ── */
.section-eyebrow{font-size:10px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.7px;margin-bottom:5px;}
.section-title{font-family:'Space Grotesk',sans-serif;font-size:19px;font-weight:700;margin-bottom:4px;color:var(--text);}
.section-sub{font-size:13px;color:var(--text2);line-height:1.65;margin-bottom:14px;}
.sec-white{padding:22px 18px;background:var(--bg);border-bottom:1.5px solid var(--border);}
.sec-cream{padding:22px 18px;background:var(--surface);border-bottom:1.5px solid var(--border2);}
.see-all-link{
  display:flex;align-items:center;justify-content:center;gap:6px;margin-top:12px;
  background:var(--green-light);border:1.5px solid var(--green-border);
  border-radius:var(--r);padding:12px;font-size:13px;font-weight:700;color:var(--green);transition:background .15s;
}
.see-all-link:hover{background:var(--green-mid);}
.see-all-link-cream{
  display:flex;align-items:center;justify-content:center;gap:6px;margin-top:12px;
  background:var(--bg);border:1.5px solid var(--border2);
  border-radius:var(--r);padding:12px;font-size:13px;font-weight:700;color:var(--green);transition:border-color .15s;
}
.see-all-link-cream:hover{border-color:var(--green);}

/* ── CATEGORY GRID ── */
.cat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;}
.cat-card{background:var(--surface);border:1.5px solid var(--border2);border-radius:10px;padding:12px 10px;text-align:center;}
.cat-card .cat-emoji{font-size:22px;margin-bottom:5px;display:block;}
.cat-card .cat-name{font-size:10px;font-weight:700;color:#444;text-transform:uppercase;letter-spacing:.3px;line-height:1.3;}
.cat-card .cat-count{font-size:10px;color:var(--green);font-weight:600;margin-top:2px;}

/* ── FEATURE ROWS ── */
.feature-list{display:flex;flex-direction:column;gap:8px;}
.feature-row{
  display:flex;align-items:flex-start;gap:14px;
  background:var(--bg);border:1.5px solid var(--border);border-radius:var(--rl);padding:15px;
}
.feat-icon{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.feat-icon.g{background:#d4f0e2;}
.feat-icon.b{background:#ddeeff;}
.feat-icon.a{background:#fef3d0;}
.feat-icon.p{background:#ede8ff;}
.feat-icon.t{background:#d4f0ee;}
.feat-icon.k{background:#ffe8f0;}
.feat-body h3{font-family:'Space Grotesk',sans-serif;font-size:14px;font-weight:700;margin-bottom:3px;color:var(--text);}
.feat-body p{font-size:12px;color:var(--text2);line-height:1.58;}
.feat-tag{display:inline-block;font-size:9px;font-weight:700;padding:2px 7px;border-radius:5px;margin-top:6px;text-transform:uppercase;letter-spacing:.4px;}
.feat-tag.free{background:var(--green-mid);color:var(--green2);}
.feat-tag.pro{background:#fef3d0;color:#7a5000;}

/* ── SOCIAL PLATFORMS ── */
.platform-scroll{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;padding-bottom:4px;}
.platform-scroll::-webkit-scrollbar{display:none;}
.platform-card{
  flex-shrink:0;background:var(--surface);border:1.5px solid var(--border2);
  border-radius:10px;padding:13px 14px;text-align:center;min-width:92px;
}
.plat-icon{font-size:22px;margin-bottom:4px;}
.plat-name{font-family:'Space Grotesk',sans-serif;font-size:11px;font-weight:700;color:var(--text);}
.plat-earn{font-size:10px;color:var(--green);font-weight:600;margin-top:2px;}

/* ── OFFLINE TOOLS ── */
.tools-scroll{display:flex;gap:8px;overflow-x:auto;scrollbar-width:none;padding-bottom:4px;}
.tools-scroll::-webkit-scrollbar{display:none;}
.tool-card{
  flex-shrink:0;background:var(--bg);border:1.5px solid var(--border);
  border-radius:10px;padding:13px 12px;width:112px;
}
.tool-emoji{font-size:22px;margin-bottom:6px;display:block;}
.tool-name{font-family:'Space Grotesk',sans-serif;font-size:11px;font-weight:700;color:var(--text);line-height:1.35;margin-bottom:4px;}
.tool-earn{font-size:10px;color:var(--green);font-weight:600;margin-bottom:4px;}
.tool-badge{display:inline-block;font-size:9px;font-weight:700;background:#fff3cc;color:#7a5800;padding:2px 6px;border-radius:5px;text-transform:uppercase;letter-spacing:.3px;}

/* ── PRO CTA ── */
.pro-cta{margin:0 18px;background:var(--green);border-radius:var(--rl);padding:24px 20px;text-align:center;}
.pro-cta h2{font-family:'Space Grotesk',sans-serif;font-size:21px;font-weight:700;color:#fff;margin-bottom:6px;}
.pro-cta .pro-sub{font-size:13px;color:rgba(255,255,255,.75);margin-bottom:16px;line-height:1.58;}
.pro-perks{display:flex;flex-direction:column;gap:7px;margin-bottom:18px;text-align:left;}
.pro-perk{display:flex;align-items:center;gap:9px;font-size:12px;color:rgba(255,255,255,.88);}
.pro-perk-dot{width:18px;height:18px;flex-shrink:0;background:rgba(255,255,255,.18);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:10px;color:#fff;}
.btn-white{display:flex;align-items:center;justify-content:center;background:#fff;color:var(--green);font-weight:700;font-size:14px;padding:14px;border-radius:10px;transition:opacity .15s;}
.btn-white:hover{opacity:.9;}

/* ── SIGNUP CTA ── */
.signup-cta{margin:12px 18px 0;border:2px solid var(--green);border-radius:var(--rl);padding:22px 18px;text-align:center;background:var(--bg);}
.signup-cta h2{font-family:'Space Grotesk',sans-serif;font-size:19px;font-weight:700;color:var(--text);margin-bottom:7px;}
.signup-cta p{font-size:13px;color:var(--text2);margin-bottom:18px;line-height:1.58;}

/* ── FOOTER ── */
footer{margin-top:12px;padding:18px;text-align:center;background:var(--surface);border-top:1.5px solid var(--border2);}
footer p{font-size:11px;color:var(--text3);}
footer a{color:var(--green);font-weight:600;}
footer a:hover{text-decoration:underline;}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="<?= APP_URL ?>/" class="nav-logo">
    <div class="nav-crown">👑</div>
    <span class="nav-brand">Hustle<em>Kingdom</em></span>
  </a>
  <a href="<?= APP_URL ?>/auth/login" class="nav-login">Log in →</a>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-flag">
    <div class="hero-flag-bar"><span></span><span></span><span></span></div>
    <span class="hero-flag-txt">Built for Nigerian Hustlers</span>
  </div>
  <h1>Find your hustle.<br><mark>Build your empire.</mark></h1>
  <p>500+ side hustles, AI-powered strategy, step-by-step execution plans, social media income guides and offline money tools — all in one place.</p>
  <a href="<?= APP_URL ?>/auth/register" class="btn-primary">🚀 Start for free</a>
  <a href="<?= APP_URL ?>/auth/login" class="btn-secondary">Already have an account →</a>
  <p class="hero-note">No credit card · Always free to start</p>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stat-box">
    <div class="stat-num">500+</div>
    <div class="stat-label">Hustles</div>
  </div>
  <div class="stat-box">
    <div class="stat-num">12</div>
    <div class="stat-label">Categories</div>
  </div>
  <div class="stat-box">
    <div class="stat-num">36</div>
    <div class="stat-label">States</div>
  </div>
  <div class="stat-box">
    <div class="stat-num">🔥</div>
    <div class="stat-label">Streaks</div>
  </div>
</div>

<!-- HUSTLE CATEGORIES -->
<section class="sec-white">
  <div class="section-eyebrow">Discover hustles</div>
  <div class="section-title">Browse 12 hustle categories</div>
  <div class="section-sub">From digital freelancing to agro-business — find what fits your skills, capital, and location.</div>
  <div class="cat-grid">
    <div class="cat-card">
      <span class="cat-emoji">💻</span>
      <div class="cat-name">Digital &amp; Tech</div>
      <div class="cat-count">198 hustles</div>
    </div>
    <div class="cat-card">
      <span class="cat-emoji">🎨</span>
      <div class="cat-name">Creative &amp; Media</div>
      <div class="cat-count">74 hustles</div>
    </div>
    <div class="cat-card">
      <span class="cat-emoji">🛒</span>
      <div class="cat-name">Trading &amp; Commerce</div>
      <div class="cat-count">53 hustles</div>
    </div>
    <div class="cat-card">
      <span class="cat-emoji">📱</span>
      <div class="cat-name">Social &amp; Content</div>
      <div class="cat-count">49 hustles</div>
    </div>
    <div class="cat-card">
      <span class="cat-emoji">🌿</span>
      <div class="cat-name">Agro &amp; Food</div>
      <div class="cat-count">42 hustles</div>
    </div>
    <div class="cat-card">
      <span class="cat-emoji">🤖</span>
      <div class="cat-name">AI-Powered</div>
      <div class="cat-count">22 hustles</div>
    </div>
  </div>
  <a href="<?= APP_URL ?>/auth/register" class="see-all-link">View all 500+ hustles by category →</a>
</section>

<!-- APP FEATURES -->
<section class="sec-cream">
  <div class="section-eyebrow">Inside the app</div>
  <div class="section-title">Everything you need to grow</div>
  <div class="feature-list">

    <div class="feature-row">
      <div class="feat-icon g">🔍</div>
      <div class="feat-body">
        <h3>Hustle Discovery</h3>
        <p>Filter 500+ hustles by category, income range, difficulty, and your state. Find exactly what fits your situation.</p>
        <span class="feat-tag free">Free</span>
      </div>
    </div>

    <div class="feature-row">
      <div class="feat-icon b">⚡</div>
      <div class="feat-body">
        <h3>Execution Hub</h3>
        <p>Every hustle comes with a step-by-step action plan, startup cost, and realistic income range — so you can start tomorrow, not someday.</p>
        <span class="feat-tag free">Free</span>
      </div>
    </div>

    <div class="feature-row">
      <div class="feat-icon a">🤖</div>
      <div class="feat-body">
        <h3>AI Hustle Strategist</h3>
        <p>Ask your personal AI business coach anything — "which hustle fits my skills?" or "how do I scale my POS business?" 3 free questions daily.</p>
        <span class="feat-tag pro">Pro — unlimited</span>
      </div>
    </div>

    <div class="feature-row">
      <div class="feat-icon t">🎯</div>
      <div class="feat-body">
        <h3>Goal Tracking</h3>
        <p>Set income goals with deadlines, track progress, and build discipline with daily streak accountability.</p>
        <span class="feat-tag free">Free</span>
      </div>
    </div>

    <div class="feature-row">
      <div class="feat-icon p">🗺️</div>
      <div class="feat-body">
        <h3>Hustle Roadmaps</h3>
        <p>Structured week-by-week plans that take you from zero to earning in each hustle category. No guesswork — just follow the map.</p>
        <span class="feat-tag pro">Pro</span>
      </div>
    </div>

    <div class="feature-row">
      <div class="feat-icon k">📍</div>
      <div class="feat-body">
        <h3>Location-Based Hustles</h3>
        <p>Discover what's accessible near you — hustles filtered by your state and city across all 36 states.</p>
        <span class="feat-tag free">Free</span>
      </div>
    </div>

  </div>
</section>

<!-- SOCIAL MONEY DIRECTORY -->
<section class="sec-white">
  <div class="section-eyebrow">Social money directory</div>
  <div class="section-title">Turn your phone into a paycheck</div>
  <div class="section-sub">See exactly how Nigerians are earning from every major social platform — with realistic income ranges and specific methods.</div>
  <div class="platform-scroll">
    <div class="platform-card">
      <div class="plat-icon">🎵</div>
      <div class="plat-name">TikTok</div>
      <div class="plat-earn">₦1k–₦50k/day</div>
    </div>
    <div class="platform-card">
      <div class="plat-icon">📸</div>
      <div class="plat-name">Instagram</div>
      <div class="plat-earn">₦2k–₦100k/day</div>
    </div>
    <div class="platform-card">
      <div class="plat-icon">▶️</div>
      <div class="plat-name">YouTube</div>
      <div class="plat-earn">₦5k–₦200k/mo</div>
    </div>
    <div class="platform-card">
      <div class="plat-icon">🐦</div>
      <div class="plat-name">X / Twitter</div>
      <div class="plat-earn">₦1k–₦30k/day</div>
    </div>
    <div class="platform-card">
      <div class="plat-icon">📲</div>
      <div class="plat-name">WhatsApp</div>
      <div class="plat-earn">₦15k–₦80k/mo</div>
    </div>
  </div>
  <a href="<?= APP_URL ?>/auth/register" class="see-all-link">Explore all platform income guides →</a>
</section>

<!-- OFFLINE MONEY TOOLS -->
<section class="sec-cream">
  <div class="section-eyebrow">Offline money tools</div>
  <div class="section-title">Physical assets that earn daily</div>
  <div class="section-sub">From keke napep to sound systems — discover 30+ physical assets, what each earns, and how to put yours to work.</div>
  <div class="tools-scroll">
    <div class="tool-card">
      <span class="tool-emoji">🚗</span>
      <div class="tool-name">Ride-Hailing Car</div>
      <div class="tool-earn">₦15k–₦40k/day</div>
      <span class="tool-badge">Transport</span>
    </div>
    <div class="tool-card">
      <span class="tool-emoji">⚡</span>
      <div class="tool-name">Industrial Generator</div>
      <div class="tool-earn">₦20k–₦80k/day</div>
      <span class="tool-badge">Power</span>
    </div>
    <div class="tool-card">
      <span class="tool-emoji">🎤</span>
      <div class="tool-name">PA Sound System</div>
      <div class="tool-earn">₦30k–₦200k/event</div>
      <span class="tool-badge">Events</span>
    </div>
    <div class="tool-card">
      <span class="tool-emoji">🏠</span>
      <div class="tool-name">Shortlet Apartment</div>
      <div class="tool-earn">₦20k–₦80k/night</div>
      <span class="tool-badge">Real Estate</span>
    </div>
    <div class="tool-card">
      <span class="tool-emoji">📸</span>
      <div class="tool-name">DSLR Camera Kit</div>
      <div class="tool-earn">₦20k–₦150k/event</div>
      <span class="tool-badge">Photography</span>
    </div>
    <div class="tool-card">
      <span class="tool-emoji">🐔</span>
      <div class="tool-name">Poultry Battery Cage</div>
      <div class="tool-earn">₦100k–₦400k/mo</div>
      <span class="tool-badge">Poultry</span>
    </div>
  </div>
  <a href="<?= APP_URL ?>/auth/register" class="see-all-link-cream">View all 30+ earning assets →</a>
</section>

<!-- PRO CTA -->
<div style="padding:22px 0 0;">
  <div class="pro-cta">
    <h2>⭐ Go Pro, earn more</h2>
    <p class="pro-sub">Unlock every edge HustleKingdom has to offer and give yourself a serious advantage.</p>
    <div class="pro-perks">
      <div class="pro-perk"><div class="pro-perk-dot">✓</div>Unlimited AI Hustle Strategist queries</div>
      <div class="pro-perk"><div class="pro-perk-dot">✓</div>Full week-by-week Roadmaps for every category</div>
      <div class="pro-perk"><div class="pro-perk-dot">✓</div>Earn ₦500 for every subscriber you refer</div>
      <div class="pro-perk"><div class="pro-perk-dot">✓</div>Priority access to new hustle drops</div>
    </div>
    <a href="<?= APP_URL ?>/auth/register" class="btn-white">Start free — upgrade anytime →</a>
  </div>
</div>

<!-- SIGNUP CTA -->
<div class="signup-cta">
  <h2>Ready to find your hustle? 💪</h2>
  <p>Join hustlers across Nigeria already building their income empire with HustleKingdom.</p>
  <a href="<?= APP_URL ?>/auth/register" class="btn-primary" style="margin-bottom:0;">Create free account →</a>
</div>

<!-- FOOTER -->
<footer>
  <p>Made with ❤️ for Nigerian hustlers &nbsp;·&nbsp; <a href="<?= APP_URL ?>/auth/login">Log in</a> &nbsp;·&nbsp; <a href="<?= APP_URL ?>/auth/register">Sign up</a></p>
</footer>

</body>
</html>
