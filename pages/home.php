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
<title>HustleKingdom — Track Your Side Hustles & Income</title>
<meta name="description" content="Track your side hustles, log income, set goals and grow your earnings. Free to start."/>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Instrument+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<style>
:root{--bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;--text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--green:#16a05a;--green2:#128f4f;--green-light:#ebf7f1;--r:14px;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;min-height:100vh;display:flex;flex-direction:column;}

/* NAV */
nav{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);}
.nav-logo{display:flex;align-items:center;gap:8px;text-decoration:none;}
.nav-logo-icon{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:15px;color:var(--text);}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-login{font-size:13px;color:var(--green);font-weight:700;text-decoration:none;}

/* HERO */
.hero{padding:36px 20px 28px;text-align:center;}
.hero-badge{display:inline-flex;align-items:center;gap:6px;background:var(--green-light);border:1.5px solid #b2e0c8;border-radius:20px;padding:5px 13px;font-size:12px;font-weight:700;color:var(--green);margin-bottom:18px;}
.hero h1{font-family:'Bricolage Grotesque',sans-serif;font-size:30px;font-weight:800;line-height:1.15;margin-bottom:14px;}
.hero h1 span{color:var(--green);}
.hero p{font-size:15px;color:var(--text2);line-height:1.65;margin-bottom:28px;}
.btn-primary{display:flex;align-items:center;justify-content:center;gap:8px;background:var(--green);color:#fff;font-family:'Instrument Sans',sans-serif;font-size:16px;font-weight:700;padding:16px 24px;border-radius:12px;text-decoration:none;width:100%;margin-bottom:12px;transition:background .15s;}
.btn-primary:hover{background:var(--green2);}
.btn-secondary{display:flex;align-items:center;justify-content:center;gap:8px;background:var(--surface);color:var(--text);font-size:15px;font-weight:600;padding:14px 24px;border-radius:12px;text-decoration:none;width:100%;border:1.5px solid var(--border);transition:border-color .15s;}
.btn-secondary:hover{border-color:var(--green);}
.hero-note{font-size:12px;color:var(--text3);margin-top:14px;}

/* STATS STRIP */
.stats-strip{display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:var(--border);border-top:1px solid var(--border);border-bottom:1px solid var(--border);}
.stat-box{background:var(--bg);padding:16px 10px;text-align:center;}
.stat-num{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;color:var(--green);}
.stat-label{font-size:11px;color:var(--text3);font-weight:600;margin-top:2px;}

/* FEATURES */
.features{padding:28px 20px;}
.section-label{font-size:11px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;}
.features h2{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;margin-bottom:20px;line-height:1.2;}
.feature-card{display:flex;align-items:flex-start;gap:14px;padding:16px;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);margin-bottom:12px;}
.feature-icon{width:40px;height:40px;background:var(--green-light);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;}
.feature-text h3{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;margin-bottom:3px;}
.feature-text p{font-size:13px;color:var(--text2);line-height:1.5;}

/* HUSTLE PREVIEW */
.preview{padding:0 20px 28px;}
.preview h2{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;margin-bottom:16px;}
.hustle-chip{display:inline-flex;align-items:center;gap:6px;background:var(--surface);border:1.5px solid var(--border);border-radius:20px;padding:8px 14px;font-size:13px;font-weight:600;margin:0 6px 8px 0;text-decoration:none;color:var(--text);}
.hustle-chip:hover{border-color:var(--green);color:var(--green);}

/* CTA BOTTOM */
.cta-bottom{margin:0 20px 28px;padding:24px 20px;background:var(--green);border-radius:16px;text-align:center;color:#fff;}
.cta-bottom h2{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;margin-bottom:8px;}
.cta-bottom p{font-size:14px;opacity:.85;margin-bottom:20px;line-height:1.5;}
.btn-white{display:flex;align-items:center;justify-content:center;background:#fff;color:var(--green);font-size:15px;font-weight:700;padding:14px 24px;border-radius:10px;text-decoration:none;transition:opacity .15s;}
.btn-white:hover{opacity:.9;}

/* FOOTER */
footer{margin-top:auto;padding:20px;border-top:1px solid var(--border);text-align:center;}
footer p{font-size:12px;color:var(--text3);}
footer a{color:var(--green);text-decoration:none;font-weight:600;}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <a href="/" class="nav-logo">
    <div class="nav-logo-icon">👑</div>
    <span class="nav-brand">Hustle<em>Kingdom</em></span>
  </a>
  <a href="/auth/login" class="nav-login">Log in →</a>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-badge">🇳🇬 Built for Nigerian hustlers</div>
  <h1>Turn your side hustle into a <span>money machine</span></h1>
  <p>Track your income, set goals, discover new hustles and watch your earnings grow — all in one place.</p>
  <a href="/auth/register" class="btn-primary">🚀 Start for free</a>
  <a href="/auth/login" class="btn-secondary">I already have an account</a>
  <p class="hero-note">No credit card. Always free to start.</p>
</section>

<!-- STATS -->
<div class="stats-strip">
  <div class="stat-box">
    <div class="stat-num">50+</div>
    <div class="stat-label">Hustles listed</div>
  </div>
  <div class="stat-box">
    <div class="stat-num">Free</div>
    <div class="stat-label">To get started</div>
  </div>
  <div class="stat-box">
    <div class="stat-num">🔥</div>
    <div class="stat-label">Daily streaks</div>
  </div>
</div>

<!-- FEATURES -->
<section class="features">
  <div class="section-label">What you get</div>
  <h2>Everything a hustler needs</h2>

  <div class="feature-card">
    <div class="feature-icon">💰</div>
    <div class="feature-text">
      <h3>Income Tracker</h3>
      <p>Log every naira you earn, see your 30-day total and weekly sparkline at a glance.</p>
    </div>
  </div>

  <div class="feature-card">
    <div class="feature-icon">🎯</div>
    <div class="feature-text">
      <h3>Goal Setting</h3>
      <p>Set income goals and track your progress. Know exactly how far you are from your target.</p>
    </div>
  </div>

  <div class="feature-card">
    <div class="feature-icon">📍</div>
    <div class="feature-text">
      <h3>Location-Based Hustles</h3>
      <p>Discover side hustles available in your state and city — opportunities near you, always.</p>
    </div>
  </div>

  <div class="feature-card">
    <div class="feature-icon">🔥</div>
    <div class="feature-text">
      <h3>Daily Streaks</h3>
      <p>Stay motivated with streak tracking. Log income daily and build an unstoppable habit.</p>
    </div>
  </div>
</section>

<!-- HUSTLE PREVIEW -->
<section class="preview">
  <h2>Popular hustles to explore</h2>
  <a href="/hustle/freelance-writing" class="hustle-chip">✍️ Freelance Writing</a>
  <a href="/hustle/graphic-design" class="hustle-chip">🎨 Graphic Design</a>
  <a href="/hustle/food-delivery" class="hustle-chip">🛵 Food Delivery</a>
  <a href="/hustle/social-media-management" class="hustle-chip">📱 Social Media</a>
  <a href="/hustle/tutoring" class="hustle-chip">📚 Tutoring</a>
  <a href="/hustle/photography" class="hustle-chip">📸 Photography</a>
  <a href="/location/lagos" class="hustle-chip">📍 Lagos hustles →</a>
</section>

<!-- CTA BOTTOM -->
<div class="cta-bottom">
  <h2>Ready to start earning? 💪</h2>
  <p>Join hustlers already tracking their income and growing their side hustle game.</p>
  <a href="/auth/register" class="btn-white">Create free account →</a>
</div>

<footer>
  <p>Made with ❤️ for Nigerian hustlers · <a href="/auth/login">Log in</a> · <a href="/auth/register">Sign up</a></p>
</footer>

</body>
</html>
