<?php
/**
 * HustleKingdom — AI Hustle Advisor
 * Route: GET /ai
 * Requires auth. Free: 3 questions/day. Pro: unlimited.
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
Auth::start();
Auth::guard();

$user  = Auth::user();
$isPro = Auth::isPro();

// Check daily usage (free users: 3 questions/day)
$AI_FREE_LIMIT = 3;
try {
    $todayCount = (int)(DB::one(
        'SELECT COUNT(*) as cnt FROM ai_log WHERE user_id = :uid AND DATE(created_at) = CURDATE()',
        [':uid' => $user['id']]
    )['cnt'] ?? 0);
} catch (\Exception $e) {
    $todayCount = 0;
}
$canAsk     = $isPro || $todayCount < $AI_FREE_LIMIT;
$remaining  = $isPro ? null : max(0, $AI_FREE_LIMIT - $todayCount);

// Handle AJAX API call — POST /ai
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
You are the Hustle Kingdom AI Advisor — Nigeria's sharpest side-hustle coach. You help everyday Nigerians start and scale income streams.

Your persona:
- Deeply knowledgeable about the Nigerian economy, naira income, local platforms (Paystack, Selar, Moniepoint, WhatsApp Business, Jumia, etc.)
- You understand data plans, power cuts, logistics, and the real constraints Nigerians face
- Practical, direct, encouraging — no fluff, no generic advice
- You give specific actionable steps, realistic income ranges in ₦, and flag common pitfalls

Format your responses using simple markdown:
- Use **bold** for key terms and action items
- Use numbered lists for steps
- Use bullet points for options or tips
- Keep responses concise but complete (200–400 words max)
- End with one specific "Next Action" the person can take today

Always ground advice in Nigeria's real context: NEPA, data costs, logistics, payment processors, social media platforms popular locally, etc.
SYS;

    $payload = [
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 700,
        'system'     => $systemPrompt,
        'messages'   => [
            ['role' => 'user', 'content' => $question]
        ],
    ];

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
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

    // Log usage
    DB::run(
        'INSERT INTO ai_log (user_id, question, created_at) VALUES (:uid, :q, NOW())',
        [':uid' => $user['id'], ':q' => mb_substr($question, 0, 500)]
    );

    echo json_encode(['ok' => true, 'answer' => $text, 'remaining' => $isPro ? null : max(0, $remaining - 1)]);
    exit;
}

// Prompt suggestions
$suggestions = [
    'How do I start freelancing on Fiverr as a Nigerian?',
    'What hustles can I start with ₦10,000 or less?',
    'How do I accept USD payments in Nigeria?',
    'Best way to grow a WhatsApp business from scratch?',
    'How to start selling digital products on Selar?',
    'What\'s the fastest way to make ₦50k this month?',
    'How do I start a POS business?',
    'How can I use AI tools to make money in Nigeria?',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>AI Hustle Advisor — Hustle Kingdom</title>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,300;12..96,400;12..96,500;12..96,600;12..96,700;12..96,800&family=Instrument+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
:root{
  --bg:#fff;--surface:#f8f8f6;--border:#e6e6e0;
  --text:#0d0d0c;--text2:#4a4a45;--text3:#9a9a92;
  --green:#16a05a;--green2:#128f4f;--green3:#0d6e3c;--green-light:#ebf7f1;
  --gold:#c8960a;--gold-light:#fdf6e3;--gold2:#a87d08;
  --blue:#2055d4;--blue-light:#edf2fd;
  --orange:#d45820;--orange-light:#fdf0eb;
  --r:16px;--nav:58px;--bot:64px;
}
*{margin:0;padding:0;box-sizing:border-box;-webkit-tap-highlight-color:transparent;}
html{overflow-x:hidden;}
body{font-family:'Instrument Sans',sans-serif;background:var(--bg);color:var(--text);max-width:430px;margin:0 auto;overflow-x:hidden;display:flex;flex-direction:column;height:100dvh;}
::-webkit-scrollbar{width:0;height:0;}

/* ── TOP NAV ── */
.topnav{flex-shrink:0;height:var(--nav);background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 16px;z-index:200;}
.nav-brand{font-family:'Bricolage Grotesque',sans-serif;font-weight:800;font-size:18px;display:flex;align-items:center;gap:8px;text-decoration:none;color:var(--text);}
.nav-logo-box{width:30px;height:30px;background:var(--green);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:15px;}
.nav-brand em{color:var(--green);font-style:normal;}
.nav-r{display:flex;gap:8px;align-items:center;}
.pro-chip{background:var(--gold-light);border:1px solid #e8d080;color:var(--gold2);font-size:10px;font-weight:800;padding:4px 10px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;text-decoration:none;}

/* ── CHAT AREA ── */
.chat-wrap{flex:1;overflow-y:auto;padding:16px 16px calc(var(--bot)+90px);display:flex;flex-direction:column;gap:14px;}

.msg-bubble{max-width:88%;border-radius:18px;padding:13px 15px;line-height:1.6;font-size:13.5px;}
.msg-user{background:var(--green);color:#fff;border-radius:18px 18px 4px 18px;align-self:flex-end;}
.msg-ai{background:var(--surface);border:1.5px solid var(--border);border-radius:18px 18px 18px 4px;align-self:flex-start;max-width:94%;}
.msg-ai strong{font-weight:800;}
.msg-ai ol{padding-left:18px;margin:6px 0;}
.msg-ai ul{padding-left:16px;margin:6px 0;}
.msg-ai li{margin-bottom:4px;}
.msg-ai p{margin-bottom:6px;}
.msg-ai p:last-child{margin-bottom:0;}

/* Welcome state */
.welcome{padding:24px 16px;text-align:center;}
.ai-avatar{width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#060d1f,#0f2040);display:flex;align-items:center;justify-content:center;font-size:30px;margin:0 auto 12px;}
.welcome h2{font-family:'Bricolage Grotesque',sans-serif;font-size:20px;font-weight:800;margin-bottom:6px;}
.welcome p{font-size:13px;color:var(--text2);line-height:1.6;margin-bottom:4px;}
.usage-pill{display:inline-flex;align-items:center;gap:5px;background:<?= $isPro ? 'var(--gold-light)' : 'var(--green-light)' ?>;border:1px solid <?= $isPro ? '#e8d080' : '#b2e0c8' ?>;color:<?= $isPro ? 'var(--gold2)' : 'var(--green3)' ?>;font-size:11px;font-weight:800;padding:5px 12px;border-radius:20px;font-family:'Bricolage Grotesque',sans-serif;margin:10px auto 0;display:inline-flex;}

/* Suggestions */
.suggestions{padding:0 16px;}
.sugg-label{font-size:11.5px;font-weight:700;color:var(--text3);margin-bottom:8px;font-family:'Bricolage Grotesque',sans-serif;}
.sugg-chips{display:flex;flex-direction:column;gap:7px;}
.sugg-chip{background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:10px 13px;font-size:12.5px;cursor:pointer;text-align:left;font-family:'Instrument Sans',sans-serif;color:var(--text2);transition:.15s;line-height:1.4;}
.sugg-chip:active{background:#e8f4ef;border-color:var(--green);}

/* ── LIMIT BANNER ── */
.limit-banner{background:linear-gradient(135deg,#fdf6e3,#fffdf5);border:1.5px solid #e8d080;border-radius:var(--r);padding:14px;margin:0 16px;text-align:center;}
.lb-title{font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--gold2);margin-bottom:4px;}
.lb-sub{font-size:12px;color:var(--text2);margin-bottom:10px;line-height:1.5;}

/* ── INPUT BAR ── */
.input-bar{position:fixed;bottom:var(--bot);left:50%;transform:translateX(-50%);width:100%;max-width:430px;background:var(--bg);border-top:1px solid var(--border);padding:10px 14px 12px;z-index:150;}
.input-row{display:flex;gap:8px;align-items:flex-end;}
.msg-input{flex:1;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:11px 13px;font-size:13.5px;font-family:'Instrument Sans',sans-serif;color:var(--text);outline:none;resize:none;max-height:100px;line-height:1.5;transition:.2s;}
.msg-input:focus{border-color:var(--green);}
.msg-input::placeholder{color:var(--text3);}
.send-btn{width:44px;height:44px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;border:none;cursor:pointer;flex-shrink:0;transition:.15s;}
.send-btn:disabled{opacity:.5;cursor:not-allowed;}
.send-btn:active:not(:disabled){background:var(--green2);}

/* Typing indicator */
.typing{display:none;align-items:center;gap:5px;padding:10px 13px;background:var(--surface);border:1.5px solid var(--border);border-radius:18px 18px 18px 4px;width:fit-content;}
.typing.show{display:flex;}
.dot{width:7px;height:7px;border-radius:50%;background:var(--text3);animation:bounce .9s infinite;}
.dot:nth-child(2){animation-delay:.2s;}
.dot:nth-child(3){animation-delay:.4s;}
@keyframes bounce{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-5px)}}

/* ── BOTTOM NAV ── */
.botnav{position:fixed;bottom:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:var(--bot);background:#fff;border-top:1px solid var(--border);display:flex;align-items:center;z-index:200;padding:0 4px;}
.bnav{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;border:none;background:transparent;color:var(--text3);text-decoration:none;}
.bnav.active{color:var(--green);}
.bni{font-size:18px;}.bnl{font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;}
.bnav-ctr{flex:1;display:flex;flex-direction:column;align-items:center;gap:2px;padding:6px 1px;text-decoration:none;}
.bnav-ctr-icon{width:40px;height:40px;background:var(--green);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:20px;margin-top:-12px;box-shadow:0 4px 14px rgba(22,160,90,.4);}

/* Markdown render */
.md-bold{font-weight:800;}
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

<!-- Chat Area -->
<div class="chat-wrap" id="chat">

  <!-- Welcome -->
  <div class="welcome" id="welcome-block">
    <div class="ai-avatar">🤖</div>
    <h2>AI Hustle Advisor</h2>
    <p>Nigeria's sharpest side-hustle coach.<br>Ask me anything about making money.</p>
    <div class="usage-pill">
      <?php if ($isPro): ?>
        ⭐ Pro — Unlimited questions
      <?php else: ?>
        <?= $remaining ?> of <?= $AI_FREE_LIMIT ?> free questions left today
      <?php endif; ?>
    </div>
  </div>

  <?php if (!$canAsk): ?>
  <!-- Limit reached -->
  <div class="limit-banner">
    <div class="lb-title">🔒 Daily limit reached</div>
    <div class="lb-sub">You've used all <?= $AI_FREE_LIMIT ?> free questions today. Upgrade to Pro for unlimited AI advice — anytime, every day.</div>
    <a href="<?= APP_URL ?>/upgrade" style="display:block;background:var(--gold);color:#fff;border-radius:10px;padding:11px;font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;text-decoration:none;">👑 Upgrade to Pro — ₦2,500/month</a>
  </div>
  <?php endif; ?>

  <!-- Typing indicator -->
  <div class="typing" id="typing">
    <div class="dot"></div><div class="dot"></div><div class="dot"></div>
  </div>
</div>

<!-- Suggestions (shown before first message) -->
<div id="sugg-panel" style="position:fixed;bottom:calc(var(--bot)+66px);left:50%;transform:translateX(-50%);width:100%;max-width:430px;background:var(--bg);padding:0 0 10px;z-index:140;overflow-y:auto;max-height:40vh;<?= !$canAsk ? 'display:none;' : '' ?>">
  <div class="suggestions">
    <div class="sugg-label">💬 Try asking…</div>
    <div class="sugg-chips" id="sugg-chips">
      <?php foreach (array_slice($suggestions, 0, 5) as $s): ?>
        <button class="sugg-chip" onclick="useSuggestion(this)"><?= htmlspecialchars($s) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Input Bar -->
<div class="input-bar">
  <div class="input-row">
    <textarea class="msg-input" id="msg-input" placeholder="<?= $canAsk ? 'Ask your hustle question…' : 'Upgrade to Pro to continue' ?>"
      <?= !$canAsk ? 'disabled' : '' ?>
      rows="1" onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
    <button class="send-btn" id="send-btn" onclick="sendMessage()" <?= !$canAsk ? 'disabled' : '' ?>>↑</button>
  </div>
</div>

<!-- Bottom Nav -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/" class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles" class="bnav"><div class="bni">🔥</div><div class="bnl">Hustles</div></a>
  <a href="<?= APP_URL ?>/dashboard" class="bnav-ctr">
    <div class="bnav-ctr-icon">📊</div>
    <div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Dash</div>
  </a>
  <a href="<?= APP_URL ?>/ideas" class="bnav"><div class="bni">💡</div><div class="bnl">Ideas</div></a>
  <a href="<?= APP_URL ?>/ai" class="bnav active"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
let remaining = <?= $remaining === null ? 'null' : (int)$remaining ?>;
let suggHidden = false;

function hideSugg() {
  if (!suggHidden) {
    document.getElementById('sugg-panel').style.display = 'none';
    suggHidden = true;
  }
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

function handleKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function useSuggestion(btn) {
  document.getElementById('msg-input').value = btn.textContent;
  sendMessage();
}

function appendMsg(content, isUser) {
  const chat = document.getElementById('chat');
  const div = document.createElement('div');
  div.className = 'msg-bubble ' + (isUser ? 'msg-user' : 'msg-ai');
  if (isUser) {
    div.textContent = content;
  } else {
    div.innerHTML = renderMarkdown(content);
  }
  // Insert before typing indicator
  const typing = document.getElementById('typing');
  chat.insertBefore(div, typing);
  chat.scrollTop = chat.scrollHeight;
}

function renderMarkdown(text) {
  // Basic markdown: bold, numbered lists, bullets, paragraphs
  text = text
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/^\d+\.\s(.+)$/gm, '<li>$1</li>')
    .replace(/^[\-\*]\s(.+)$/gm, '<li>$1</li>');

  // Wrap consecutive <li> in <ol> or <ul>
  text = text.replace(/(<li>.*?<\/li>\n?)+/gs, m => {
    // Check if original was numbered
    return '<ul style="padding-left:16px;margin:6px 0;">' + m + '</ul>';
  });

  // Paragraphs
  const paras = text.split(/\n\n+/);
  return paras.map(p => p.trim() ? (p.includes('<ul>') || p.includes('<li>') ? p : `<p>${p}</p>`) : '').join('');
}

async function sendMessage() {
  const input = document.getElementById('msg-input');
  const q = input.value.trim();
  if (!q) return;

  hideSugg();
  document.getElementById('welcome-block').style.display = 'none';
  appendMsg(q, true);
  input.value = '';
  input.style.height = 'auto';

  const btn = document.getElementById('send-btn');
  btn.disabled = true;
  document.getElementById('typing').classList.add('show');
  document.getElementById('chat').scrollTop = 9999;

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
      appendMsg(data.answer, false);
      if (data.remaining !== null) {
        remaining = data.remaining;
        if (remaining === 0) {
          input.disabled = true;
          btn.disabled = true;
          input.placeholder = 'Upgrade to Pro to continue';
          appendLimitMsg();
        }
      }
    } else {
      appendMsg('⚠️ ' + (data.error || 'Something went wrong. Try again.'), false);
      btn.disabled = false;
    }
  } catch (e) {
    document.getElementById('typing').classList.remove('show');
    appendMsg('⚠️ Network error. Check your connection and try again.', false);
    btn.disabled = false;
  }

  document.getElementById('chat').scrollTop = 9999;
}

function appendLimitMsg() {
  const chat = document.getElementById('chat');
  const div = document.createElement('div');
  div.innerHTML = `<div style="background:var(--gold-light);border:1.5px solid #e8d080;border-radius:var(--r);padding:14px;margin:4px 0;text-align:center;">
    <div style="font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:var(--gold2);margin-bottom:4px;">Daily limit reached 🔒</div>
    <div style="font-size:12px;color:var(--text2);margin-bottom:10px;">Upgrade to Pro for unlimited questions every day.</div>
    <a href="<?= APP_URL ?>/upgrade" style="display:block;background:var(--gold);color:#fff;border-radius:10px;padding:10px;font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;text-decoration:none;">👑 Go Pro — ₦2,500/month</a>
  </div>`;
  chat.appendChild(div);
  chat.scrollTop = 9999;
}
</script>
</body>
</html>
