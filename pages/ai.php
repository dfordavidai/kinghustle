<?php
/**
 * HustleKingdom — AI Hustle Strategist
 * Route: GET /ai   POST /ai (AJAX)
 * Requires auth. Matches the video "Your Personal Hustle Strategist" design.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();

// ── Daily usage ───────────────────────────────────────────────────────────────
$AI_FREE_LIMIT = 3;
try {
    $todayCount = (int)(DB::one(
        'SELECT COUNT(*) as cnt FROM ai_log WHERE user_id=:uid AND DATE(created_at)=CURDATE()',
        [':uid' => $user['id']]
    )['cnt'] ?? 0);
} catch (\Exception $e) { $todayCount = 0; }
$canAsk    = $isPro || $todayCount < $AI_FREE_LIMIT;
$remaining = $isPro ? null : max(0, $AI_FREE_LIMIT - $todayCount);

// ── Conversation count (for stats) ───────────────────────────────────────────
$convCount = 0;
try {
    $convCount = (int)(DB::one('SELECT COUNT(*) as cnt FROM ai_log WHERE user_id=:uid', [':uid'=>$user['id']])['cnt'] ?? 0);
} catch (\Exception $e) {}

// ── POST: handle AJAX AI request ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!$canAsk) {
        echo json_encode(['ok' => false, 'error' => 'Daily limit reached. Upgrade to Pro for unlimited AI advice.']);
        exit;
    }

    $input    = json_decode(file_get_contents('php://input'), true);
    $question = trim($input['question'] ?? '');
    if (!$question) {
        echo json_encode(['ok' => false, 'error' => 'Please enter a question.']);
        exit;
    }

    $apiKey = defined('ANTHROPIC_API_KEY') ? ANTHROPIC_API_KEY : ($_ENV['ANTHROPIC_API_KEY'] ?? '');
    if (!$apiKey) {
        echo json_encode(['ok' => false, 'error' => 'AI service not configured. Contact support.']);
        exit;
    }

    $systemPrompt = <<<SYS
You are the HK Advisor — Hustle Kingdom's personal hustle strategist for Nigerian entrepreneurs. You are not a generic chatbot.

Your persona:
- Direct, confident, practical — like a smart friend who's already made money online and offline in Nigeria
- Deeply versed in the Nigerian economy: NEPA, data costs, Paystack, Selar, Moniepoint, Flutterwave, WhatsApp Business, Jumia, Konga, social media dynamics
- You understand the real constraints: bandwidth, power, trust, logistics, payment friction, local competition
- You give specific income ranges in ₦, actual platform names, and real-world timelines
- You never give generic advice like "start a blog" without specifics

Format:
- Use **bold** for key actions and terms
- Use numbered lists for steps (1. 2. 3.)
- Use bullet points for options and tips
- Keep responses 200–350 words — tight and actionable
- End every response with: **Next Action:** [one specific thing they can do TODAY]
SYS;

    $payload = [
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 700,
        'system'     => $systemPrompt,
        'messages'   => [['role' => 'user', 'content' => $question]],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'],
        CURLOPT_TIMEOUT        => 30,
    ]);
    $raw  = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (!$raw || $code !== 200) {
        echo json_encode(['ok' => false, 'error' => 'AI service unavailable. Try again shortly.']);
        exit;
    }

    $res  = json_decode($raw, true);
    $text = $res['content'][0]['text'] ?? '';
    if (!$text) {
        echo json_encode(['ok' => false, 'error' => 'No response from AI. Try again.']);
        exit;
    }

    try {
        DB::run('INSERT INTO ai_log (user_id, question, created_at) VALUES (:uid, :q, NOW())', [':uid' => $user['id'], ':q' => mb_substr($question, 0, 500)]);
    } catch (\Exception $e) {}

    echo json_encode(['ok' => true, 'answer' => $text, 'remaining' => $isPro ? null : max(0, $remaining - 1)]);
    exit;
}

// ── Prompt suggestions ────────────────────────────────────────────────────────
$suggestions = [
    'I have ₦0 and a phone — what\'s my fastest path to ₦50k?',
    'I\'m a student in Lagos with 3 free hours daily',
    'I\'m employed, want ₦200k/month side income',
    'I have ₦50k capital and want the best ROI hustle',
    'I want dollar income without leaving Nigeria',
    'Best hustle for someone in Abuja with no skills yet',
    'How do I start selling digital products on Selar?',
    'How to accept USD payments as a Nigerian?',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>AI Hustle Strategist — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --r:16px;--nav:58px;--bot:64px;--input-bar:70px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html,body{height:100%;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;overflow-x:hidden;display:flex;flex-direction:column;height:100dvh;}
::-webkit-scrollbar{width:0;height:0;}

/* ── TOP NAV ── */
.topnav{flex-shrink:0;height:var(--nav);background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}
.nav-bell{width:34px;height:34px;border-radius:50%;background:transparent;border:none;display:flex;align-items:center;justify-content:center;font-size:18px;cursor:pointer;position:relative;}
.nav-bell-dot{position:absolute;top:6px;right:6px;width:8px;height:8px;background:#cc3333;border-radius:50%;border:2px solid #fff;}
.nav-avatar{width:34px;height:34px;border-radius:50%;background:var(--green-light);border:1.5px solid #b2e0c8;display:flex;align-items:center;justify-content:center;font-size:17px;text-decoration:none;}

/* ── CHAT AREA ── */
.chat-wrap{flex:1;overflow-y:auto;padding:0 0 8px;display:flex;flex-direction:column;}

/* ── HERO (shown before chat starts) ── */
.ai-hero{background:linear-gradient(135deg,#060d1f 0%,#0f2040 55%,#1a3a6e 100%);padding:24px 18px 22px;position:relative;overflow:hidden;flex-shrink:0;}
.ai-hero::before{content:'';position:absolute;right:-30px;top:-30px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.04);}
.ai-hero-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:rgba(255,255,255,.85);font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.ai-hero-chip span{color:#fbbf24;}
.ai-hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:24px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;position:relative;z-index:1;}
.ai-hero-sub{font-size:13px;color:rgba(255,255,255,.6);line-height:1.6;margin-bottom:18px;position:relative;z-index:1;}
.ai-hero-stats{display:flex;gap:8px;position:relative;z-index:1;}
.ai-hstat{flex:1;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:10px 8px;text-align:center;}
.ai-hstat-n{font-family:'Bricolage Grotesque',sans-serif;font-size:17px;font-weight:800;color:#fff;}
.ai-hstat-l{font-size:10px;color:rgba(255,255,255,.5);margin-top:2px;}

/* ── SUGGESTIONS ── */
.suggestions-section{padding:14px 16px 0;}
.sugg-label{font-size:10.5px;font-weight:800;color:var(--text3);letter-spacing:.06em;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:9px;}
.sugg-list{display:flex;flex-direction:column;gap:7px;}
.sugg-btn{background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:11px 14px;font-size:13px;cursor:pointer;text-align:left;font-family:'Instrument Sans',sans-serif;color:var(--text2);transition:.15s;line-height:1.4;}
.sugg-btn:active{background:#e8f4ef;border-color:var(--green);}

/* ── AI ADVISOR INTRO BUBBLE ── */
.advisor-intro{display:flex;gap:10px;align-items:flex-start;padding:14px 16px;}
.advisor-avatar{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#060d1f,#1a3a6e);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.advisor-label{font-size:10px;font-weight:800;color:var(--text3);letter-spacing:.05em;margin-bottom:4px;font-family:'Bricolage Grotesque',sans-serif;}
.advisor-bubble{background:var(--surface);border:1.5px solid var(--border);border-radius:16px 16px 16px 4px;padding:13px 15px;font-size:13.5px;line-height:1.6;color:var(--text2);flex:1;}

/* ── CHAT MESSAGES ── */
.messages-area{padding:0 16px;display:flex;flex-direction:column;gap:12px;}
.msg-user{background:var(--green);color:#fff;border-radius:18px 18px 4px 18px;align-self:flex-end;padding:13px 15px;max-width:88%;font-size:13.5px;line-height:1.6;}
.msg-ai-wrap{display:flex;gap:10px;align-items:flex-start;}
.msg-ai-avatar{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#060d1f,#1a3a6e);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;margin-top:2px;}
.msg-ai{background:var(--surface);border:1.5px solid var(--border);border-radius:16px 16px 16px 4px;padding:13px 15px;font-size:13.5px;line-height:1.6;flex:1;}
.msg-ai strong{font-weight:800;}
.msg-ai ol,.msg-ai ul{padding-left:18px;margin:6px 0;}
.msg-ai li{margin-bottom:4px;}
.msg-ai p{margin-bottom:6px;}
.msg-ai p:last-child{margin-bottom:0;}

/* Typing */
.typing-wrap{display:none;gap:10px;align-items:center;padding:0 16px;}
.typing-wrap.show{display:flex;}
.typing-bubble{background:var(--surface);border:1.5px solid var(--border);border-radius:16px;padding:12px 16px;display:flex;gap:5px;align-items:center;}
.typing-dot{width:8px;height:8px;border-radius:50%;background:var(--text3);animation:bounce .8s infinite;}
.typing-dot:nth-child(2){animation-delay:.15s;}
.typing-dot:nth-child(3){animation-delay:.3s;}
@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}

/* ── LIMIT BANNER ── */
.limit-banner{margin:12px 16px;background:var(--gold-light);border:1.5px solid #e8d080;border-radius:var(--r);padding:16px;text-align:center;}
.lb-title{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--gold2);margin-bottom:4px;}
.lb-sub{font-size:12px;color:var(--text2);line-height:1.5;margin-bottom:12px;}

/* ── INPUT BAR (fixed) ── */
.input-bar{position:fixed;bottom:var(--bot);left:50%;transform:translateX(-50%);width:100%;max-width:430px;background:var(--bg);border-top:1px solid var(--border);padding:10px 14px 12px;z-index:150;}
.input-row{display:flex;gap:8px;align-items:flex-end;}
.msg-input{flex:1;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:11px 13px;font-size:13.5px;font-family:'Instrument Sans',sans-serif;color:var(--text);outline:none;resize:none;max-height:100px;line-height:1.5;transition:.2s;}
.msg-input:focus{border-color:var(--green);}
.msg-input::placeholder{color:var(--text3);}
.send-btn{width:44px;height:44px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;border:none;cursor:pointer;flex-shrink:0;transition:.15s;}
.send-btn:disabled{opacity:.4;cursor:not-allowed;}
.usage-note{font-size:11px;color:var(--text3);text-align:center;margin-top:5px;font-weight:600;}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
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
    <?php if ($isPro): ?><span class="pro-chip">⭐ PRO</span><?php else: ?><a href="<?= APP_URL ?>/upgrade" class="pro-chip">Go Pro →</a><?php endif; ?>
    <button class="nav-bell">🔔<span class="nav-bell-dot"></span></button>
    <a href="<?= APP_URL ?>/dashboard" class="nav-avatar"><?= htmlspecialchars($user['avatar_emoji'] ?? '👤') ?></a>
  </div>
</nav>

<!-- CHAT WRAP -->
<div class="chat-wrap" id="chat-wrap" style="padding-bottom:calc(var(--bot) + 80px);">

  <!-- AI Hero Banner -->
  <div class="ai-hero" id="ai-hero">
    <div class="ai-hero-chip">⚡ REAL AI — <span>POWERED BY CLAUDE</span></div>
    <div class="ai-hero-title">Your Personal<br/>Hustle Strategist</div>
    <div class="ai-hero-sub">Not a quiz. Not dropdowns. Talk to the AI like a real advisor — describe your situation in plain language.</div>
    <div class="ai-hero-stats">
      <div class="ai-hstat">
        <div class="ai-hstat-n"><?= $convCount ?></div>
        <div class="ai-hstat-l">Conversations</div>
      </div>
      <div class="ai-hstat">
        <div class="ai-hstat-n">—</div>
        <div class="ai-hstat-l">Profile Match</div>
      </div>
      <div class="ai-hstat">
        <div class="ai-hstat-n">0</div>
        <div class="ai-hstat-l">Plans Built</div>
      </div>
    </div>
  </div>

  <!-- Suggestions (shown initially) -->
  <div id="sugg-section">
    <div class="suggestions-section">
      <div class="sugg-label">QUICK START — TAP A PROMPT</div>
      <div class="sugg-list">
        <?php foreach ($suggestions as $s): ?>
          <button class="sugg-btn" onclick="useSuggestion(this)"><?= htmlspecialchars($s) ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Advisor intro bubble -->
  <div class="advisor-intro" id="advisor-intro">
    <div class="advisor-avatar">👑</div>
    <div>
      <div class="advisor-label">HK ADVISOR</div>
      <div class="advisor-bubble">Hey! I'm your personal hustle strategist. Tell me about yourself — your location, what capital you have, your skills, and what income you're targeting. I'll build you a real, specific plan. The more detail you give, the better the advice.</div>
    </div>
  </div>

  <!-- Messages area -->
  <div class="messages-area" id="messages"></div>

  <!-- Typing indicator -->
  <div class="typing-wrap" id="typing">
    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#060d1f,#1a3a6e);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;">👑</div>
    <div class="typing-bubble">
      <div class="typing-dot"></div>
      <div class="typing-dot"></div>
      <div class="typing-dot"></div>
    </div>
  </div>

  <?php if (!$canAsk): ?>
  <div class="limit-banner">
    <div class="lb-title">🔒 Daily limit reached</div>
    <div class="lb-sub">You've used all <?= $AI_FREE_LIMIT ?> free questions today. Reset at midnight, or upgrade to Pro for unlimited daily advice.</div>
    <a href="<?= APP_URL ?>/upgrade" style="display:block;background:var(--gold);color:#fff;border-radius:10px;padding:12px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;">👑 Go Pro — Unlimited AI</a>
  </div>
  <?php endif; ?>
</div>

<!-- INPUT BAR -->
<div class="input-bar">
  <div class="input-row">
    <textarea class="msg-input" id="msg-input"
      placeholder="Describe your situation — location, capital, skills, income goal…"
      <?= !$canAsk ? 'disabled' : '' ?>
      rows="1" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
    <button class="send-btn" id="send-btn" onclick="sendMsg()" <?= !$canAsk ? 'disabled' : '' ?>>▶</button>
  </div>
  <?php if (!$isPro): ?>
  <div class="usage-note"><?= $remaining ?> free message<?= $remaining !== 1 ? 's' : '' ?> left today</div>
  <?php endif; ?>
</div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute" class="bnav-ctr">
    <div class="bnav-ctr-icon">▶</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div>
  </a>
  <a href="<?= APP_URL ?>/location" class="bnav"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav active"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
let remaining = <?= $remaining === null ? 'null' : (int)$remaining ?>;
let chatStarted = false;

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMsg(); }
}

function useSuggestion(btn) {
  document.getElementById('msg-input').value = btn.textContent;
  sendMsg();
}

function startChat() {
  if (!chatStarted) {
    chatStarted = true;
    // Collapse hero + suggestions gracefully
    const hero = document.getElementById('ai-hero');
    const sugg = document.getElementById('sugg-section');
    if (hero) { hero.style.transition = 'opacity .3s'; hero.style.opacity = '0'; setTimeout(() => hero.style.display = 'none', 300); }
    if (sugg) { sugg.style.display = 'none'; }
  }
}

function appendUser(text) {
  const el = document.createElement('div');
  el.className = 'msg-user';
  el.textContent = text;
  document.getElementById('messages').appendChild(el);
  scrollBottom();
}

function appendAI(html) {
  const wrap = document.createElement('div');
  wrap.className = 'msg-ai-wrap';
  wrap.innerHTML = `<div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#060d1f,#1a3a6e);display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;margin-top:2px;">👑</div><div class="msg-ai">${html}</div>`;
  document.getElementById('messages').appendChild(wrap);
  scrollBottom();
}

function scrollBottom() {
  const wrap = document.getElementById('chat-wrap');
  setTimeout(() => { wrap.scrollTop = wrap.scrollHeight; }, 50);
}

function renderMD(text) {
  text = text
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/^(\d+)\.\s(.+)$/gm, '<li>$2</li>')
    .replace(/^[\-\*]\s(.+)$/gm, '<li>$1</li>');

  text = text.replace(/(<li>[\s\S]*?<\/li>\n?)+/g, m =>
    '<ul style="padding-left:18px;margin:6px 0;">' + m + '</ul>'
  );

  const paras = text.split(/\n\n+/);
  return paras.map(p => p.trim()
    ? (p.includes('<ul>') || p.startsWith('<li>') ? p : `<p>${p}</p>`)
    : ''
  ).join('');
}

async function sendMsg() {
  const input = document.getElementById('msg-input');
  const q = input.value.trim();
  if (!q) return;

  startChat();
  appendUser(q);
  input.value = '';
  input.style.height = 'auto';

  const btn = document.getElementById('send-btn');
  btn.disabled = true;
  document.getElementById('typing').classList.add('show');
  scrollBottom();

  try {
    const res = await fetch(window.location.href, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ question: q }),
      credentials: 'same-origin',
    });
    const data = await res.json();
    document.getElementById('typing').classList.remove('show');

    if (data.ok) {
      appendAI(renderMD(data.answer));
      if (data.remaining !== null) {
        remaining = data.remaining;
        const note = document.querySelector('.usage-note');
        if (note) note.textContent = remaining + ' free message' + (remaining !== 1 ? 's' : '') + ' left today';
        if (remaining === 0) {
          input.disabled = true;
          btn.disabled = true;
          input.placeholder = 'Upgrade to Pro for unlimited advice';
          appendAI('<strong>You\'ve reached your daily limit.</strong> Upgrade to Pro for unlimited questions every day. <a href="<?= APP_URL ?>/upgrade" style="color:var(--green);font-weight:800;">Upgrade →</a>');
          return;
        }
      }
    } else {
      appendAI('⚠️ ' + (data.error || 'Something went wrong. Try again.'));
    }
  } catch (e) {
    document.getElementById('typing').classList.remove('show');
    appendAI('⚠️ Network error. Check your connection and try again.');
  }

  btn.disabled = false;
  scrollBottom();
}
</script>
</body>
</html>
