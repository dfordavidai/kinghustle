<?php
/**
 * HustleKingdom — User Dashboard (Revamped)
 * Route: GET /dashboard
 * Requires auth. Full-featured: income, goals, saved hustles, categories, tools, referral, streak.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();

// ── Income summary (last 30 days) ─────────────────────────────────────────────
$income30 = DB::query(
    'SELECT il.id, il.amount, il.logged_at, il.custom_name,
            COALESCE(h.name, il.custom_name) as hustle_name, h.emoji
     FROM income_log il
     LEFT JOIN hustles h ON h.id = il.hustle_id
     WHERE il.user_id = :uid AND il.logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     ORDER BY il.logged_at DESC',
    [':uid' => $user['id']]
);
$total30 = array_sum(array_column($income30, 'amount'));

// Income last 7 days for sparkline
$income7 = DB::query(
    'SELECT DATE(logged_at) as day_date, DATE_FORMAT(MIN(logged_at), "%a") as day_label, SUM(amount) as day_total
     FROM income_log
     WHERE user_id = :uid AND logged_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
     GROUP BY DATE(logged_at) ORDER BY DATE(logged_at) ASC',
    [':uid' => $user['id']]
);

// This month vs last month
$thisMonth = (float)(DB::one(
    'SELECT COALESCE(SUM(amount),0) as t FROM income_log
     WHERE user_id = :uid AND MONTH(logged_at) = MONTH(CURDATE()) AND YEAR(logged_at) = YEAR(CURDATE())',
    [':uid' => $user['id']]
)['t'] ?? 0);

$lastMonth = (float)(DB::one(
    'SELECT COALESCE(SUM(amount),0) as t FROM income_log
     WHERE user_id = :uid AND MONTH(logged_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))
       AND YEAR(logged_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))',
    [':uid' => $user['id']]
)['t'] ?? 0);

$monthChange = $lastMonth > 0 ? round((($thisMonth - $lastMonth) / $lastMonth) * 100) : null;

// ── Goals ─────────────────────────────────────────────────────────────────────
$goals = DB::query(
    'SELECT * FROM goals WHERE user_id = :uid ORDER BY is_completed ASC, created_at DESC LIMIT 6',
    [':uid' => $user['id']]
);
$activeGoals    = array_filter($goals, fn($g) => !(bool)$g['is_completed']);
$completedGoals = array_filter($goals, fn($g) => (bool)$g['is_completed']);

// ── Saved Hustles ─────────────────────────────────────────────────────────────
$savedHustles = DB::query(
    'SELECT h.id, h.name, h.slug, h.emoji, h.category, h.income_min, h.income_max,
            h.income_period, h.difficulty, sh.created_at as saved_at
     FROM saved_hustles sh
     JOIN hustles h ON h.id = sh.hustle_id
     WHERE sh.user_id = :uid
     ORDER BY sh.created_at DESC LIMIT 8',
    [':uid' => $user['id']]
);

// ── Referral ──────────────────────────────────────────────────────────────────
$refCount = (int)(DB::one(
    'SELECT COUNT(*) as cnt FROM referrals WHERE referrer_id = :id',
    [':id' => $user['id']]
)['cnt'] ?? 0);
$shareUrl = APP_URL . '/auth/register?ref=' . urlencode($user['ref_code']);

// ── Top income sources ────────────────────────────────────────────────────────
$topSources = DB::query(
    'SELECT COALESCE(h.name, il.custom_name, "Other") as source_name,
            COALESCE(h.emoji, "💰") as emoji,
            SUM(il.amount) as total,
            COUNT(*) as entries
     FROM income_log il
     LEFT JOIN hustles h ON h.id = il.hustle_id
     WHERE il.user_id = :uid AND il.logged_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
     GROUP BY source_name, emoji
     ORDER BY total DESC LIMIT 5',
    [':uid' => $user['id']]
);

// ── Recent income entries (last 5) ────────────────────────────────────────────
$recentIncome = array_slice($income30, 0, 5);

// ── All hustles for browsing (limited) ────────────────────────────────────────
$allHustles = DB::query(
    'SELECT id, name, slug, emoji, category, income_min, income_max, income_period, difficulty
     FROM hustles WHERE is_active = 1 ORDER BY RAND() LIMIT 50',
    []
);

// ── Helpers ───────────────────────────────────────────────────────────────────
$diffLabel = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];
$diffClass = ['beginner' => 'tg', 'intermediate' => 'to', 'advanced' => 'tr'];

function fmt(float $n): string {
    if ($n >= 1_000_000) return '₦' . number_format($n / 1_000_000, 1) . 'M';
    if ($n >= 1_000)     return '₦' . number_format($n / 1_000, 1) . 'k';
    return '₦' . number_format($n, 0);
}

$csrf = Auth::csrf();
$greeting = date('H') < 12 ? 'Good morning' : (date('H') < 17 ? 'Good afternoon' : 'Good evening');
$firstName = explode(' ', $user['name'])[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Dashboard — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#ffffff;--surface:#f8f8f6;--surface2:#f1f1ee;--border:#e6e6e0;--border2:#d8d8d0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;--text4:#c8c8c0;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;--green-glow:rgba(22,160,90,0.15);
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --orange:#d45820;--orange-light:#fdf0eb;
  --red:#cc3333;--red-light:#fdf0f0;
  --purple:#6b35c8;--purple-light:#f3eefe;
  --teal:#0e9488;--teal-light:#ebfaf8;
  --r:16px;--rs:10px;--rx:22px;
  --nav:58px;--bot:64px;
  --sh:0 2px 16px rgba(0,0,0,0.06);--sh2:0 8px 40px rgba(0,0,0,0.10);
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;max-width:430px;margin:0 auto;overflow-x:hidden;-webkit-overflow-scrolling:touch;padding-bottom:calc(var(--bot) + 16px);}
::-webkit-scrollbar{width:0;height:0;}

/* ── TOP NAV ── */
.topnav{position:sticky;top:0;width:100%;height:var(--nav);background:rgba(255,255,255,0.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;cursor:pointer;text-decoration:none;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border-radius:10px;cursor:pointer;border:none;background:transparent;color:var(--text3);text-decoration:none;transition:.2s;}
.bnav.active{color:var(--green);}
.bni{font-size:18px;}
.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-log{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;cursor:pointer;border:none;background:transparent;text-decoration:none;}
.bnav-log-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}

/* ── HERO ── */
.hero{background:linear-gradient(140deg,#0d6e3c 0%,#16a05a 55%,#20c870 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,0.06);}
.hero::after{content:'';position:absolute;left:-20px;bottom:-30px;width:120px;height:120px;border-radius:50%;background:rgba(0,0,0,0.06);}
.hero-greeting{font-size:12.5px;color:rgba(255,255,255,.7);font-weight:600;margin-bottom:4px;}
.hero-name{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;}
.hero-name span{color:#a3f0c8;}
.streak-pill{display:inline-flex;align-items:center;gap:5px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.3);border-radius:20px;padding:4px 10px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:14px;cursor:pointer;}
.hero-stats{display:flex;gap:8px;position:relative;z-index:1;}
.hstat{flex:1;background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:10px;text-align:center;}
.hstat-n{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;color:#fff;}
.hstat-l{font-size:10px;color:rgba(255,255,255,.65);margin-top:2px;}

/* ── SPARKLINE ── */
.sparkline-wrap{display:flex;align-items:flex-end;gap:4px;height:28px;margin:10px 0 14px;position:relative;z-index:1;}
.spark-bar{flex:1;background:rgba(255,255,255,.3);border-radius:3px 3px 0 0;min-height:3px;}
.change-up{color:#7ffab0;font-size:11px;font-weight:700;}
.change-down{color:#ffb3b3;font-size:11px;font-weight:700;}

/* ── SHARED ── */
.sec-head{display:flex;align-items:center;justify-content:space-between;padding:0 16px;margin:20px 0 12px;}
.sec-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:700;font-size:15px;display:flex;align-items:center;gap:7px;}
.sec-all{font-size:12px;font-weight:700;color:var(--green);background:none;border:none;cursor:pointer;text-decoration:none;}
.page-inner{padding:0;}

/* ── QUICK ACTIONS ── */
.qa-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:16px 16px 0;}
.qa-btn{display:flex;flex-direction:column;align-items:center;gap:6px;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:13px 6px;text-decoration:none;color:var(--text);cursor:pointer;transition:.15s;touch-action:manipulation;}
.qa-btn:active{background:var(--surface2);}
.qa-icon{font-size:20px;line-height:1;}
.qa-label{font-size:10px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;text-align:center;color:var(--text2);}

/* ── INCOME CARD ── */
.income-month-card{margin:16px 16px 0;background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:16px;display:flex;gap:14px;align-items:center;}
.imc-amount{font-family:'Bricolage Grotesque',sans-serif;font-size:28px;font-weight:800;color:var(--green3);line-height:1;}
.imc-label{font-size:11px;color:var(--text3);margin-bottom:3px;font-weight:600;}
.imc-change{font-size:11.5px;font-weight:700;}
.imc-right{flex:1;min-width:0;}

/* ── STAT GRID ── */
.stat-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;padding:0 16px;}
.stat-card{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:14px;}
.sc-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;}
.sc-val{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;}
.sc-sub{font-size:11px;color:var(--text3);margin-top:2px;}

/* ── GOAL CARDS ── */
.goal-card{border:1.5px solid var(--border);border-radius:var(--r);padding:14px;margin-bottom:10px;}
.goal-card.done{opacity:.6;background:var(--surface);}
.goal-header{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;}
.goal-title-row{display:flex;align-items:center;gap:8px;}
.goal-title{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:13.5px;line-height:1.2;}
.goal-deadline{font-size:11px;color:var(--text3);margin-top:1px;}
.goal-pct{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:var(--green);}
.goal-pct.done{color:var(--text3);}
.goal-bar-bg{height:6px;background:var(--border);border-radius:4px;overflow:hidden;}
.goal-bar-fill{height:100%;background:var(--green);border-radius:4px;}
.goal-bar-fill.done{background:var(--text3);}
.goal-amounts{display:flex;justify-content:space-between;margin-top:6px;font-size:11px;color:var(--text3);}
.goal-amounts strong{color:var(--text2);font-weight:700;}
.goal-actions{display:flex;gap:7px;margin-top:10px;}
.goal-actions button{flex:1;border-radius:10px;padding:7px 8px;font-size:11.5px;font-weight:700;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;border:none;}

/* ── INCOME LIST ── */
.income-row{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);}
.income-row:last-child{border-bottom:none;}
.income-emoji{width:36px;height:36px;background:var(--green-light);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
.income-info{flex:1;min-width:0;}
.income-name{font-size:13px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.income-date{font-size:11px;color:var(--text3);}
.income-amount{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--green);}

/* ── HUSTLE CARDS ── */
.hustle-scroll{display:flex;gap:10px;overflow-x:auto;padding:0 16px 8px;scrollbar-width:none;-webkit-overflow-scrolling:touch;}
.hustle-card{flex-shrink:0;width:160px;background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:13px;cursor:pointer;text-decoration:none;color:var(--text);transition:.15s;touch-action:manipulation;}
.hustle-card:active{background:var(--surface);}
.hc-emoji{font-size:26px;margin-bottom:7px;display:block;}
.hc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:12.5px;font-weight:800;margin-bottom:4px;line-height:1.3;}
.hc-income{font-size:11px;color:var(--green3);font-weight:700;margin-bottom:6px;}
.hc-tags{display:flex;gap:5px;flex-wrap:wrap;}

/* ── TAGS ── */
.tag{font-size:10px;font-weight:700;padding:2px 7px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}
.tg{background:var(--green-light);color:var(--green3);}
.tb{background:var(--blue-light);color:var(--blue);}
.to{background:var(--orange-light);color:var(--orange);}
.tp{background:var(--purple-light);color:var(--purple);}
.tgd{background:var(--gold-light);color:var(--gold2);}
.tt{background:var(--teal-light);color:var(--teal);}
.tr{background:var(--red-light);color:var(--red);}

/* ── CATEGORY GRID ── */
.catgrid{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;padding:0 16px;}
.catcard{border:1.5px solid var(--border);border-radius:var(--r);padding:13px 9px 11px;text-align:center;cursor:pointer;transition:.18s;touch-action:manipulation;text-decoration:none;display:block;}
.catcard:active{transform:scale(.96);}
.catcard .ci{font-size:23px;margin-bottom:5px;display:block;}
.catcard .cn{font-family:'Bricolage Grotesque',sans-serif;font-size:10.5px;font-weight:700;line-height:1.3;color:var(--text);}
.catcard .cc{font-size:9.5px;font-weight:600;margin-top:3px;}
.cc-digital{background:#e6f1fb;border-color:#b5d4f4;}.cc-digital .cc{color:#185fa5;}
.cc-social{background:#eeedfe;border-color:#afa9ec;}.cc-social .cc{color:#534ab7;}
.cc-ai{background:#faeeda;border-color:#fac775;}.cc-ai .cc{color:#854f0b;}
.cc-trade{background:#faece7;border-color:#f5c4b3;}.cc-trade .cc{color:#993c1d;}
.cc-creative{background:#fcebeb;border-color:#f7c1c1;}.cc-creative .cc{color:#a32d2d;}
.cc-agro{background:#eaf7f1;border-color:#b2e0c8;}.cc-agro .cc{color:#0f6e56;}
.cc-trades{background:#e1f5ee;border-color:#9fe1cb;}.cc-trades .cc{color:#0f6e56;}
.cc-health{background:#e6f1fb;border-color:#85b7eb;}.cc-health .cc{color:#0c447c;}
.cc-edu{background:#fbeaf0;border-color:#f4c0d1;}.cc-edu .cc{color:#993556;}

/* ── TOOLS SECTION ── */
.tools-scroll{display:flex;gap:10px;overflow-x:auto;padding:0 16px 8px;scrollbar-width:none;-webkit-overflow-scrolling:touch;}
.tool-card{flex-shrink:0;width:130px;background:#fff;border:1.5px solid var(--border);border-radius:var(--r);padding:13px 11px;cursor:pointer;text-decoration:none;transition:.15s;touch-action:manipulation;}
.tool-card:active{background:var(--surface);}
.tc-emoji{font-size:24px;margin-bottom:6px;display:block;}
.tc-name{font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--text);margin-bottom:3px;}
.tc-badge{font-size:9.5px;font-weight:700;padding:2px 6px;border-radius:6px;font-family:'Bricolage Grotesque',sans-serif;}

/* ── PRO BANNERS ── */
.pro-banner{border-radius:var(--r);padding:16px;display:flex;align-items:center;gap:13px;cursor:pointer;touch-action:manipulation;}
.pro-banner h3{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:14px;margin-bottom:3px;}
.pro-banner p{font-size:11.5px;opacity:.85;line-height:1.5;}
.pb-btn{font-size:11px;font-weight:800;padding:7px 12px;border-radius:10px;text-decoration:none;font-family:'Bricolage Grotesque',sans-serif;white-space:nowrap;flex-shrink:0;border:none;cursor:pointer;}

/* ── REFERRAL CARD ── */
.ref-card{background:var(--gold-light);border:1.5px solid #e8d080;border-radius:var(--r);padding:16px;}
.ref-title{font-family:'Bricolage Grotesque',sans-serif;font-size:14.5px;font-weight:800;margin-bottom:4px;}
.ref-sub{font-size:12px;color:var(--text2);line-height:1.55;margin-bottom:12px;}
.ref-dots{display:flex;gap:6px;margin-bottom:12px;}
.ref-dot{width:26px;height:26px;border-radius:50%;border:2px solid #e8d080;display:flex;align-items:center;justify-content:center;font-size:12px;}
.ref-dot.filled{background:var(--gold);border-color:var(--gold);color:#fff;}
.ref-copy-row{display:flex;gap:8px;}
.ref-code-box{flex:1;background:#fff;border:1.5px solid #e8d080;border-radius:10px;padding:10px 12px;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--text);}
.ref-copy-btn{background:var(--gold);color:#fff;border:none;border-radius:10px;padding:10px 14px;font-size:12px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:24px 16px;color:var(--text3);font-size:13px;}
.empty-state .ei{font-size:32px;margin-bottom:8px;}
.empty-state a{color:var(--green);font-weight:700;text-decoration:none;}

/* ── ACCOUNT ── */
.account-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px;padding:0 16px;}
.acc-card{background:var(--surface);border:1.5px solid var(--border);border-radius:var(--r);padding:13px;}
.acc-label{font-size:11px;font-weight:600;color:var(--text3);margin-bottom:4px;}
.acc-val{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;line-height:1.2;}
.acc-sub{font-size:11px;color:var(--text3);margin-top:2px;}

/* ── MODALS ── */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:300;align-items:flex-end;justify-content:center;backdrop-filter:blur(6px);}
.modal-overlay.open{display:flex;}
.modal{background:var(--bg);border-radius:22px 22px 0 0;padding:20px 20px 40px;width:100%;max-width:430px;animation:slideUp .25s ease;}
@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
.modal-handle{width:36px;height:4px;background:var(--border);border-radius:2px;margin:0 auto 18px;}
.modal-title{font-family:'Bricolage Grotesque',sans-serif;font-size:18px;font-weight:800;margin-bottom:16px;}
.field{margin-bottom:13px;}
.field label{display:block;font-size:11.5px;font-weight:700;color:var(--text2);margin-bottom:5px;font-family:'Bricolage Grotesque',sans-serif;}
.field input,.field select,.field textarea{width:100%;padding:12px 13px;border:1.5px solid var(--border);border-radius:12px;font-size:13.5px;font-family:'Instrument Sans',sans-serif;color:var(--text);background:#fff;outline:none;transition:.2s;}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--green);}
.field textarea{resize:none;height:70px;}
.modal-submit{width:100%;background:var(--green);color:#fff;border:none;border-radius:12px;padding:14px;font-size:14px;font-weight:800;cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;margin-top:4px;transition:.15s;}
.modal-submit:active{background:var(--green2);}

/* ── TOAST ── */
.toast{position:fixed;bottom:80px;left:50%;transform:translateX(-50%);background:#0d0d0c;color:#fff;font-size:13px;font-weight:600;padding:10px 18px;border-radius:30px;z-index:400;opacity:0;pointer-events:none;transition:.3s;white-space:nowrap;}
.toast.show{opacity:1;}

/* ── DIVIDER ── */
.divider{height:1px;background:var(--border);margin:0 16px;}
.section-gap{height:24px;}
</style>
</head>
<body>

<!-- ── TOP NAV ── -->
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
    <a href="<?= APP_URL ?>/auth/logout" class="nav-avatar" title="Log out"><?= htmlspecialchars($user['avatar_emoji']) ?></a>
  </div>
</nav>

<!-- ═══════════════════════════════════
     HERO
═══════════════════════════════════ -->
<div class="hero">
  <div class="hero-greeting"><?= htmlspecialchars($greeting) ?></div>
  <div class="hero-name"><?= htmlspecialchars($firstName) ?> <span><?= htmlspecialchars($user['avatar_emoji']) ?></span></div>

  <?php if ($user['streak_count'] > 0): ?>
    <div class="streak-pill">🔥 <?= (int)$user['streak_count'] ?> day streak — keep it up!</div>
  <?php endif; ?>

  <?php if (!empty($income7)): ?>
    <?php $maxDay = max(array_column($income7, 'day_total')) ?: 1; ?>
    <div class="sparkline-wrap">
      <?php foreach ($income7 as $day): ?>
        <?php $h = max(3, round(($day['day_total'] / $maxDay) * 28)); ?>
        <div class="spark-bar" style="height:<?= $h ?>px;" title="<?= htmlspecialchars($day['day_label']) ?>: <?= fmt((float)$day['day_total']) ?>"></div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div style="height:14px;"></div>
  <?php endif; ?>

  <div class="hero-stats">
    <div class="hstat">
      <div class="hstat-n"><?= fmt($thisMonth) ?></div>
      <div class="hstat-l">This month</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?= fmt($total30) ?></div>
      <div class="hstat-l">Last 30 days</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?= count($income30) ?></div>
      <div class="hstat-l">Entries</div>
    </div>
    <div class="hstat">
      <div class="hstat-n"><?= count($activeGoals) ?></div>
      <div class="hstat-l">Active goals</div>
    </div>
  </div>

  <?php if ($monthChange !== null): ?>
    <div style="margin-top:10px;font-size:11.5px;position:relative;z-index:1;">
      <?php if ($monthChange >= 0): ?>
        <span class="change-up">↑ <?= abs($monthChange) ?>% vs last month</span>
      <?php else: ?>
        <span class="change-down">↓ <?= abs($monthChange) ?>% vs last month</span>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════
     QUICK ACTIONS
═══════════════════════════════════ -->
<div class="qa-grid">
  <button class="qa-btn" onclick="openLogModal()">
    <div class="qa-icon">💰</div>
    <div class="qa-label">Log Income</div>
  </button>
  <button class="qa-btn" onclick="openGoalModal()">
    <div class="qa-icon">🎯</div>
    <div class="qa-label">Add Goal</div>
  </button>
  <a href="<?= APP_URL ?>/hustles" class="qa-btn">
    <div class="qa-icon">🔥</div>
    <div class="qa-label">Browse Hustles</div>
  </a>
  <a href="<?= APP_URL ?>/execute" class="qa-btn">
    <div class="qa-icon">⚡</div>
    <div class="qa-label">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/ideas" class="qa-btn">
    <div class="qa-icon">💡</div>
    <div class="qa-label">Ideas</div>
  </a>
  <a href="<?= APP_URL ?>/tools" class="qa-btn">
    <div class="qa-icon">🛠</div>
    <div class="qa-label">Tools</div>
  </a>
  <a href="<?= APP_URL ?>/ai" class="qa-btn">
    <div class="qa-icon">🤖</div>
    <div class="qa-label">AI Advisor</div>
  </a>
  <a href="<?= APP_URL ?>/upgrade" class="qa-btn">
    <div class="qa-icon">⭐</div>
    <div class="qa-label"><?= $isPro ? 'Pro Active' : 'Go Pro' ?></div>
  </a>
</div>

<!-- ═══════════════════════════════════
     GOALS
═══════════════════════════════════ -->
<div class="sec-head">
  <div class="sec-title">🎯 My Goals</div>
  <button class="sec-all" onclick="openGoalModal()">+ Add goal</button>
</div>

<div style="padding:0 16px;">
  <?php if (empty($goals)): ?>
    <div class="empty-state">
      <div class="ei">🎯</div>
      No goals yet. <a href="#" onclick="openGoalModal();return false;">Set your first goal →</a>
    </div>
  <?php else: ?>
    <?php foreach ($activeGoals as $goal):
      $pct = $goal['target_amount'] > 0 ? min(100, round(($goal['current_amount'] / $goal['target_amount']) * 100)) : 0;
    ?>
      <div class="goal-card" id="goal-<?= (int)$goal['id'] ?>">
        <div class="goal-header">
          <div class="goal-title-row">
            <span style="font-size:22px;"><?= htmlspecialchars($goal['emoji']) ?></span>
            <div>
              <div class="goal-title"><?= htmlspecialchars($goal['title']) ?></div>
              <?php if ($goal['deadline']): ?>
                <div class="goal-deadline">📅 <?= date('d M Y', strtotime($goal['deadline'])) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="goal-pct"><?= $pct ?>%</div>
        </div>
        <div class="goal-bar-bg"><div class="goal-bar-fill" style="width:<?= $pct ?>%"></div></div>
        <div class="goal-amounts">
          <span>Saved: <strong><?= fmt((float)$goal['current_amount']) ?></strong></span>
          <span>Target: <strong><?= fmt((float)$goal['target_amount']) ?></strong></span>
        </div>
        <div class="goal-actions">
          <button onclick="addToGoal(<?= (int)$goal['id'] ?>,'<?= htmlspecialchars(addslashes($goal['title'])) ?>')" style="background:var(--green-light);color:var(--green3);">+ Add funds</button>
          <button onclick="markGoalDone(<?= (int)$goal['id'] ?>)" style="background:var(--surface);color:var(--text3);">✓ Done</button>
          <button onclick="deleteGoal(<?= (int)$goal['id'] ?>)" style="background:var(--red-light);color:var(--red);flex:0;padding:7px 10px;">🗑</button>
        </div>
      </div>
    <?php endforeach; ?>
    <?php foreach ($completedGoals as $goal): ?>
      <div class="goal-card done">
        <div class="goal-header">
          <div class="goal-title-row">
            <span style="font-size:22px;">✅</span>
            <div>
              <div class="goal-title"><?= htmlspecialchars($goal['title']) ?></div>
              <div class="goal-deadline">Completed <?= date('d M Y', strtotime($goal['completed_at'])) ?></div>
            </div>
          </div>
          <div class="goal-pct done">100%</div>
        </div>
        <div class="goal-bar-bg"><div class="goal-bar-fill done" style="width:100%"></div></div>
        <div class="goal-amounts"><span>Target: <strong><?= fmt((float)$goal['target_amount']) ?></strong></span></div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- ═══════════════════════════════════
     RECENT INCOME
═══════════════════════════════════ -->
<div class="sec-head">
  <div class="sec-title">💸 Recent Income</div>
  <button class="sec-all" onclick="openLogModal()">+ Log</button>
</div>

<?php if (empty($recentIncome)): ?>
  <div class="empty-state">
    <div class="ei">💸</div>
    No income logged yet. <a href="#" onclick="openLogModal();return false;">Log your first ₦ →</a>
  </div>
<?php else: ?>
  <div style="border:1.5px solid var(--border);border-radius:var(--r);margin:0 16px;overflow:hidden;" id="income-list">
    <?php foreach ($recentIncome as $entry): ?>
      <div class="income-row" id="income-<?= (int)$entry['id'] ?>">
        <div class="income-emoji"><?= htmlspecialchars($entry['emoji'] ?? '💰') ?></div>
        <div class="income-info">
          <div class="income-name"><?= htmlspecialchars($entry['hustle_name'] ?? 'Custom Income') ?></div>
          <div class="income-date"><?= date('d M Y', strtotime($entry['logged_at'])) ?></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
          <div class="income-amount">+<?= fmt((float)$entry['amount']) ?></div>
          <button onclick="deleteEntry(<?= (int)$entry['id'] ?>)" style="background:none;border:none;font-size:14px;color:var(--text3);cursor:pointer;padding:4px;">🗑</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ═══════════════════════════════════
     TOP SOURCES
═══════════════════════════════════ -->
<?php if (!empty($topSources)): ?>
  <div class="sec-head">
    <div class="sec-title">📊 Top Sources (30d)</div>
  </div>
  <div class="stat-grid">
    <?php foreach (array_slice($topSources, 0, 4) as $src): ?>
      <div class="stat-card">
        <div class="sc-label"><?= htmlspecialchars($src['emoji']) ?> <?= htmlspecialchars($src['source_name']) ?></div>
        <div class="sc-val"><?= fmt((float)$src['total']) ?></div>
        <div class="sc-sub"><?= (int)$src['entries'] ?> entr<?= $src['entries'] == 1 ? 'y' : 'ies' ?></div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ═══════════════════════════════════
     SAVED HUSTLES
═══════════════════════════════════ -->
<div class="sec-head">
  <div class="sec-title">🔖 Saved Hustles</div>
  <a href="<?= APP_URL ?>/hustles" class="sec-all">Browse all →</a>
</div>

<?php if (empty($savedHustles)): ?>
  <div class="empty-state">
    <div class="ei">🔖</div>
    No saved hustles. <a href="<?= APP_URL ?>/">Explore hustles →</a>
  </div>
<?php else: ?>
  <div class="hustle-scroll">
    <?php foreach ($savedHustles as $h): ?>
      <a class="hustle-card" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
        <span class="hc-emoji"><?= htmlspecialchars($h['emoji']) ?></span>
        <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
        <div class="hc-income"><?= fmt((float)$h['income_min']) ?>–<?= fmt((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
        <div class="hc-tags">
          <span class="tag <?= htmlspecialchars($diffClass[$h['difficulty']] ?? 'tg') ?>"><?= htmlspecialchars($diffLabel[$h['difficulty']] ?? '') ?></span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- ═══════════════════════════════════
     BROWSE CATEGORIES
═══════════════════════════════════ -->
<div class="sec-head">
  <div class="sec-title">📂 Hustle Categories</div>
  <a href="<?= APP_URL ?>/ideas" class="sec-all">See all →</a>
</div>
<div class="catgrid">
  <a class="catcard cc-digital" href="<?= APP_URL ?>/?cat=digital"><span class="ci">💻</span><div class="cn">Digital & Tech</div><div class="cc">198 hustles</div></a>
  <a class="catcard cc-social" href="<?= APP_URL ?>/?cat=social"><span class="ci">📱</span><div class="cn">Social & Content</div><div class="cc">49 hustles</div></a>
  <a class="catcard cc-ai" href="<?= APP_URL ?>/?cat=ai"><span class="ci">🤖</span><div class="cn">AI-Powered</div><div class="cc">22 hustles</div></a>
  <a class="catcard cc-trade" href="<?= APP_URL ?>/?cat=trade"><span class="ci">🛒</span><div class="cn">Trading & Commerce</div><div class="cc">53 hustles</div></a>
  <a class="catcard cc-creative" href="<?= APP_URL ?>/?cat=creative"><span class="ci">🎨</span><div class="cn">Creative & Media</div><div class="cc">74 hustles</div></a>
  <a class="catcard cc-agro" href="<?= APP_URL ?>/?cat=agro"><span class="ci">🌾</span><div class="cn">Agro & Food</div><div class="cc">42 hustles</div></a>
  <a class="catcard cc-trades" href="<?= APP_URL ?>/?cat=trades"><span class="ci">🏗️</span><div class="cn">Trades & Labour</div><div class="cc">12 hustles</div></a>
  <a class="catcard cc-health" href="<?= APP_URL ?>/?cat=health"><span class="ci">💆</span><div class="cn">Health & Beauty</div><div class="cc">45 hustles</div></a>
  <a class="catcard cc-edu" href="<?= APP_URL ?>/?cat=education"><span class="ci">🎓</span><div class="cn">Education & Coaching</div><div class="cc">41 hustles</div></a>
</div>

<!-- ═══════════════════════════════════
     TOOLS & PLATFORMS
═══════════════════════════════════ -->
<div class="sec-head" style="margin-top:24px;">
  <div class="sec-title">🛠 Tools & Platforms</div>
  <a href="<?= APP_URL ?>/tools" class="sec-all">See all →</a>
</div>
<div class="tools-scroll">
  <a class="tool-card" href="https://wa.me" target="_blank" rel="noopener">
    <span class="tc-emoji">💬</span>
    <div class="tc-name">WhatsApp Business</div>
    <span class="tc-badge tg">Free</span>
  </a>
  <a class="tool-card" href="https://selar.co" target="_blank" rel="noopener">
    <span class="tc-emoji">🛒</span>
    <div class="tc-name">Selar</div>
    <span class="tc-badge tg">Sell Online</span>
  </a>
  <a class="tool-card" href="https://paystack.com" target="_blank" rel="noopener">
    <span class="tc-emoji">💳</span>
    <div class="tc-name">Paystack</div>
    <span class="tc-badge tb">Payments</span>
  </a>
  <a class="tool-card" href="https://fiverr.com" target="_blank" rel="noopener">
    <span class="tc-emoji">💼</span>
    <div class="tc-name">Fiverr</div>
    <span class="tc-badge tp">Freelance</span>
  </a>
  <a class="tool-card" href="https://upwork.com" target="_blank" rel="noopener">
    <span class="tc-emoji">🌍</span>
    <div class="tc-name">Upwork</div>
    <span class="tc-badge tgd">USD Income</span>
  </a>
  <a class="tool-card" href="https://canva.com" target="_blank" rel="noopener">
    <span class="tc-emoji">🎨</span>
    <div class="tc-name">Canva</div>
    <span class="tc-badge tg">Design</span>
  </a>
  <a class="tool-card" href="https://jumia.com.ng" target="_blank" rel="noopener">
    <span class="tc-emoji">📦</span>
    <div class="tc-name">Jumia Seller</div>
    <span class="tc-badge to">E-commerce</span>
  </a>
  <a class="tool-card" href="https://moniepoint.com" target="_blank" rel="noopener">
    <span class="tc-emoji">🏦</span>
    <div class="tc-name">Moniepoint</div>
    <span class="tc-badge tg">POS/Agency</span>
  </a>
</div>

<!-- ═══════════════════════════════════
     PRO UPSELL (free users only)
═══════════════════════════════════ -->
<?php if (!$isPro): ?>
<div style="padding:0 16px;margin-top:6px;">
  <!-- AI Advisor promo -->
  <a href="<?= APP_URL ?>/upgrade" style="display:block;background:linear-gradient(140deg,#060d1f 0%,#0f2040 55%,#0a1628 100%);border-radius:var(--r);padding:16px;text-decoration:none;margin-bottom:10px;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-20px;top:-20px;width:100px;height:100px;border-radius:50%;background:radial-gradient(circle,rgba(99,179,237,0.15),transparent);"></div>
    <div style="display:flex;align-items:center;gap:12px;">
      <div style="width:44px;height:44px;border-radius:12px;background:rgba(99,179,237,0.15);border:1px solid rgba(99,179,237,0.3);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">✨</div>
      <div style="flex:1;">
        <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;color:#fff;margin-bottom:3px;">Unlimited AI Hustle Advisor</div>
        <div style="font-size:11.5px;color:rgba(255,255,255,.5);line-height:1.5;">Free users get 3 questions. Pro: unlimited personalized hustle plans anytime.</div>
      </div>
    </div>
    <div style="margin-top:12px;background:linear-gradient(135deg,#c8960a,#f0b820);border-radius:10px;padding:10px 14px;text-align:center;">
      <span style="font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:#fff;">👑 Unlock Pro — ₦2,500/month</span>
    </div>
  </a>

  <!-- Pro features grid -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:10px;">
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#fdf6e3,#fffdf5);border:1.5px solid #e8d080;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">🔒</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--gold2);margin-bottom:3px;">30 Exclusive Hustles</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Premium high-income ideas for Pro members only.</div>
    </a>
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#eeedfe,#f5f0ff);border:1.5px solid #c4a8f4;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">🗺️</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--purple);margin-bottom:3px;">Full Roadmaps</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Step-by-step plans with tools & income milestones.</div>
    </a>
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#ebf7f1,#f0fdf6);border:1.5px solid #b2e0c8;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">📊</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--green3);margin-bottom:3px;">Income Analytics</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Charts, top earners, monthly breakdown & CSV export.</div>
    </a>
    <a href="<?= APP_URL ?>/upgrade" style="background:linear-gradient(135deg,#e6f1fb,#edf5ff);border:1.5px solid #b5d4f4;border-radius:var(--r);padding:14px;text-decoration:none;display:block;">
      <div style="font-size:22px;margin-bottom:6px;">🌍</div>
      <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--blue);margin-bottom:3px;">Country Hustle Maps</div>
      <div style="font-size:11px;color:var(--text2);line-height:1.5;">Curated hustles for 50+ countries worldwide.</div>
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════
     BROWSE HUSTLES (from DB)
═══════════════════════════════════ -->
<?php if (!empty($allHustles)): ?>
<div class="sec-head">
  <div class="sec-title">🔥 Discover Hustles</div>
  <a href="<?= APP_URL ?>/hustles" class="sec-all">See all →</a>
</div>
<div class="hustle-scroll">
  <?php foreach (array_slice($allHustles, 0, 12) as $h): ?>
    <a class="hustle-card" href="<?= APP_URL ?>/hustle/<?= htmlspecialchars($h['slug']) ?>">
      <span class="hc-emoji"><?= htmlspecialchars($h['emoji']) ?></span>
      <div class="hc-name"><?= htmlspecialchars($h['name']) ?></div>
      <div class="hc-income"><?= fmt((float)$h['income_min']) ?>–<?= fmt((float)$h['income_max']) ?>/<?= htmlspecialchars($h['income_period']) ?></div>
      <div class="hc-tags">
        <span class="tag <?= htmlspecialchars($diffClass[$h['difficulty']] ?? 'tg') ?>"><?= htmlspecialchars($diffLabel[$h['difficulty']] ?? '') ?></span>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ═══════════════════════════════════
     REFERRAL
═══════════════════════════════════ -->
<div class="sec-head">
  <div class="sec-title">🤝 Refer & Earn</div>
</div>
<div style="padding:0 16px;">
  <div class="ref-card">
    <div class="ref-title">Invite friends, get Pro free 🎁</div>
    <div class="ref-sub">Refer <?= REFERRAL_NEEDED ?> friends and get <?= REFERRAL_REWARD_DAYS ?> days of Pro free. You've referred <strong><?= $refCount ?></strong> so far.</div>
    <div class="ref-dots">
      <?php for ($i = 0; $i < REFERRAL_NEEDED; $i++): ?>
        <div class="ref-dot <?= $i < $refCount ? 'filled' : '' ?>"><?= $i < $refCount ? '✓' : ($i + 1) ?></div>
      <?php endfor; ?>
      <div style="font-size:12px;color:var(--text2);margin-left:6px;line-height:26px;"><?= max(0, REFERRAL_NEEDED - $refCount) ?> more to unlock</div>
    </div>
    <div class="ref-copy-row">
      <div class="ref-code-box" id="ref-code"><?= htmlspecialchars($user['ref_code']) ?></div>
      <button class="ref-copy-btn" onclick="copyRef()">Copy link</button>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════
     ACCOUNT
═══════════════════════════════════ -->
<div class="sec-head" style="margin-top:24px;">
  <div class="sec-title">👤 Account</div>
  <a href="<?= APP_URL ?>/auth/logout" class="sec-all" style="color:var(--red);">Log out</a>
</div>
<div class="account-grid">
  <div class="acc-card">
    <div class="acc-label">Name</div>
    <div class="acc-val"><?= htmlspecialchars($user['name']) ?></div>
    <div class="acc-sub"><?= htmlspecialchars($user['email']) ?></div>
  </div>
  <div class="acc-card">
    <div class="acc-label">Plan</div>
    <div class="acc-val" style="color:<?= $isPro ? 'var(--gold)' : 'var(--text2)' ?>"><?= $isPro ? '⭐ Pro' : 'Free' ?></div>
    <div class="acc-sub"><?= $isPro ? 'Expires ' . date('d M Y', strtotime($user['pro_expires_at'])) : 'Limited access' ?></div>
  </div>
</div>

<div style="height:20px;"></div>

<!-- ═══════════════════════════════════
     BOTTOM NAV
═══════════════════════════════════ -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav">
    <div class="bni">🏠</div>
    <div class="bnl">Home</div>
  </a>
  <a href="<?= APP_URL ?>/hustles" class="bnav">
    <div class="bni">🔥</div>
    <div class="bnl">Hustles</div>
  </a>
  <button class="bnav-log" onclick="openLogModal()">
    <div class="bnav-log-icon">+</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Log</div>
  </button>
  <a href="<?= APP_URL ?>/ideas" class="bnav">
    <div class="bni">💡</div>
    <div class="bnl">Ideas</div>
  </a>
  <a href="<?= APP_URL ?>/ai" class="bnav">
    <div class="bni">🤖</div>
    <div class="bnl">AI</div>
  </a>
</nav>

<!-- ═══════════════════════════════════
     LOG INCOME MODAL
═══════════════════════════════════ -->
<div class="modal-overlay" id="log-modal" onclick="closeOnBg(event,'log-modal')">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-title">💰 Log Income</div>
    <div class="field">
      <label>Amount (₦)</label>
      <input type="number" id="log-amount" placeholder="e.g. 15000" min="1" inputmode="numeric"/>
    </div>
    <div class="field">
      <label>Source / Hustle Name</label>
      <input type="text" id="log-source" placeholder="e.g. Freelance Design, VTU, Catering…"/>
    </div>
    <div class="field">
      <label>Date</label>
      <input type="date" id="log-date" value="<?= date('Y-m-d') ?>"/>
    </div>
    <div class="field">
      <label>Note (optional)</label>
      <textarea id="log-note" placeholder="Any extra details…"></textarea>
    </div>
    <button class="modal-submit" onclick="submitIncome()">Save Income →</button>
  </div>
</div>

<!-- ═══════════════════════════════════
     ADD GOAL MODAL
═══════════════════════════════════ -->
<div class="modal-overlay" id="goal-modal" onclick="closeOnBg(event,'goal-modal')">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-title">🎯 New Goal</div>
    <div class="field">
      <label>Goal Title</label>
      <input type="text" id="goal-title" placeholder="e.g. Buy a laptop, Emergency fund…"/>
    </div>
    <div class="field">
      <label>Emoji</label>
      <input type="text" id="goal-emoji" placeholder="🎯" maxlength="2" value="🎯" style="max-width:80px;"/>
    </div>
    <div class="field">
      <label>Target Amount (₦)</label>
      <input type="number" id="goal-target" placeholder="e.g. 200000" min="1" inputmode="numeric"/>
    </div>
    <div class="field">
      <label>Deadline (optional)</label>
      <input type="date" id="goal-deadline"/>
    </div>
    <button class="modal-submit" onclick="submitGoal()">Create Goal →</button>
  </div>
</div>

<!-- ═══════════════════════════════════
     ADD FUNDS MODAL
═══════════════════════════════════ -->
<div class="modal-overlay" id="funds-modal" onclick="closeOnBg(event,'funds-modal')">
  <div class="modal">
    <div class="modal-handle"></div>
    <div class="modal-title" id="funds-modal-title">Add Funds to Goal</div>
    <input type="hidden" id="funds-goal-id"/>
    <div class="field">
      <label>Amount to Add (₦)</label>
      <input type="number" id="funds-amount" placeholder="e.g. 5000" min="1" inputmode="numeric"/>
    </div>
    <button class="modal-submit" onclick="submitFunds()">Add Funds →</button>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"></div>

<script>
const CSRF = <?= json_encode($csrf) ?>;

function showToast(msg, ms = 2500) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), ms);
}

function openLogModal()  { document.getElementById('log-modal').classList.add('open'); }
function openGoalModal() { document.getElementById('goal-modal').classList.add('open'); }
function closeModal(id)  { document.getElementById(id).classList.remove('open'); }
function closeOnBg(e, id){ if (e.target === document.getElementById(id)) closeModal(id); }

function addToGoal(id, title) {
  document.getElementById('funds-goal-id').value = id;
  document.getElementById('funds-modal-title').textContent = '+ Add Funds: ' + title;
  document.getElementById('funds-amount').value = '';
  document.getElementById('funds-modal').classList.add('open');
}

async function api(method, url, body = null) {
  const opts = { method, headers: {'Content-Type':'application/json','Accept':'application/json'}, credentials:'same-origin' };
  if (body) opts.body = JSON.stringify(body);
  const res = await fetch(url, opts);
  return res.json();
}

async function submitIncome() {
  const amount = parseFloat(document.getElementById('log-amount').value);
  const source = document.getElementById('log-source').value.trim();
  const date   = document.getElementById('log-date').value;
  const note   = document.getElementById('log-note').value.trim();
  if (!amount || amount <= 0) { showToast('⚠️ Enter a valid amount'); return; }
  if (!source) { showToast('⚠️ Enter a source name'); return; }
  const r = await api('POST', '/api/income', { amount, custom_name: source, logged_at: date, note: note || null });
  if (r.ok) {
    showToast('✅ Income logged!');
    closeModal('log-modal');
    document.getElementById('log-amount').value = '';
    document.getElementById('log-source').value = '';
    document.getElementById('log-note').value = '';
    const list = document.getElementById('income-list');
    if (list) {
      const fmtAmt = amount >= 1000000 ? '₦'+(amount/1000000).toFixed(1)+'M' : amount >= 1000 ? '₦'+(amount/1000).toFixed(1)+'k' : '₦'+amount.toLocaleString();
      const row = document.createElement('div');
      row.className = 'income-row';
      row.id = 'income-'+(r.data?.id||Date.now());
      row.innerHTML = `<div class="income-emoji">💰</div><div class="income-info"><div class="income-name">${source}</div><div class="income-date">${new Date(date).toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'})}</div></div><div style="display:flex;align-items:center;gap:8px;"><div class="income-amount">+${fmtAmt}</div><button onclick="deleteEntry(${r.data?.id})" style="background:none;border:none;font-size:14px;color:var(--text3);cursor:pointer;padding:4px;">🗑</button></div>`;
      list.prepend(row);
    }
  } else { showToast('❌ ' + (r.error || 'Failed to log income')); }
}

async function deleteEntry(id) {
  if (!confirm('Delete this income entry?')) return;
  const r = await api('DELETE', '/api/income?id=' + id);
  if (r.ok) { document.getElementById('income-'+id)?.remove(); showToast('🗑 Entry deleted'); }
  else showToast('❌ Could not delete');
}

async function submitGoal() {
  const title  = document.getElementById('goal-title').value.trim();
  const emoji  = document.getElementById('goal-emoji').value.trim() || '🎯';
  const target = parseFloat(document.getElementById('goal-target').value);
  const deadline = document.getElementById('goal-deadline').value || null;
  if (!title)             { showToast('⚠️ Enter a goal title'); return; }
  if (!target || target <= 0) { showToast('⚠️ Enter a valid target amount'); return; }
  const r = await api('POST', '/api/goals', { title, emoji, target_amount: target, deadline });
  if (r.ok) { showToast('✅ Goal created!'); closeModal('goal-modal'); location.reload(); }
  else showToast('❌ ' + (r.error || 'Failed to create goal'));
}

async function markGoalDone(id) {
  if (!confirm('Mark this goal as completed? 🎉')) return;
  const r = await api('PUT', '/api/goals?id=' + id, { is_completed: 1 });
  if (r.ok) { showToast('🎉 Goal completed!'); location.reload(); }
  else showToast('❌ ' + (r.error || 'Failed'));
}

async function deleteGoal(id) {
  if (!confirm('Delete this goal?')) return;
  const r = await api('DELETE', '/api/goals?id=' + id);
  if (r.ok) { document.getElementById('goal-'+id)?.remove(); showToast('🗑 Goal deleted'); }
  else showToast('❌ ' + (r.error || 'Failed'));
}

async function submitFunds() {
  const id     = parseInt(document.getElementById('funds-goal-id').value);
  const amount = parseFloat(document.getElementById('funds-amount').value);
  if (!amount || amount <= 0) { showToast('⚠️ Enter a valid amount'); return; }
  const goals = await api('GET', '/api/goals');
  const goal  = goals.data?.find(g => g.id === id);
  if (!goal) { showToast('❌ Goal not found'); return; }
  const newAmount  = parseFloat(goal.current_amount) + amount;
  const isComplete = newAmount >= parseFloat(goal.target_amount) ? 1 : 0;
  const r = await api('PUT', '/api/goals?id=' + id, { current_amount: newAmount, is_completed: isComplete });
  if (r.ok) { showToast(isComplete ? '🎉 Goal reached!' : '✅ Funds added!'); closeModal('funds-modal'); location.reload(); }
  else showToast('❌ ' + (r.error || 'Failed'));
}

function copyRef() {
  const url = <?= json_encode($shareUrl) ?>;
  if (navigator.clipboard) { navigator.clipboard.writeText(url).then(() => showToast('🔗 Link copied!')); }
  else {
    const ta = document.createElement('textarea');
    ta.value = url;
    document.body.appendChild(ta);
    ta.select();
    document.execCommand('copy');
    document.body.removeChild(ta);
    showToast('🔗 Link copied!');
  }
}
</script>
</body>
</html>
