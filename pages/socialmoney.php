<?php
/**
 * HustleKingdom — Social Money Directory
 * Route: GET /socialmoney
 * Route: GET /socialmoney?p=tiktok  (platform detail)
 */

defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
Auth::start();

$user  = Auth::isLoggedIn() ? Auth::user() : null;
$isPro = Auth::isPro();

// ── DATA ─────────────────────────────────────────────────────────────────────
$socialPlatforms = [
  [
    'id'      => 'tiktok',
    'icon'    => '🎵',
    'name'    => 'TikTok',
    'earn'    => '₦1k–₦50k/day',
    'count'   => 8,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'TikTok rewards consistent creators. Nigeria has exploded on TikTok — Afrobeats, fashion, food, and finance niches earn most. Start posting daily for 30 days before expecting income.',
    'methods' => ['TikTok Creator Fund (once viral)', 'Affiliate marketing via bio link', 'TikTok Shop (product promotions)', 'Brand sponsorships (100k+ followers)', 'Live stream gifts from fans', 'Selling your own products', 'Paid shoutouts for small businesses', 'TikTok ads management service'],
  ],
  [
    'id'      => 'instagram',
    'icon'    => '📸',
    'name'    => 'Instagram',
    'earn'    => '₦2k–₦100k/day',
    'count'   => 9,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Instagram is the #1 business-acquisition social media in Nigeria. Fashion, food, beauty, and money niches dominate. Focus on Reels for reach, Stories for sales.',
    'methods' => ['Brand deal / sponsored posts', 'Affiliate links in bio (Selar, Jumia)', 'Instagram Subscriptions (paid followers)', 'Selling your own digital products', 'Selling physical products via Instagram Shop', 'Paid shoutouts to smaller pages', 'Instagram Reels bonuses (if active)', 'Consulting services acquired via DM', 'Selling Instagram accounts in hot niches'],
  ],
  [
    'id'      => 'youtube',
    'icon'    => '▶️',
    'name'    => 'YouTube',
    'earn'    => '$100–$10k/month',
    'count'   => 7,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'YouTube is the longest-lasting platform for income. A video from 2019 still earns today. Automation channels (no face) are booming — finance, lists, and history niches earn the most AdSense.',
    'methods' => ['Google AdSense (after 1k subs)', 'YouTube Memberships', 'Super Thanks / Super Chat on live streams', 'Brand sponsorships', 'Affiliate links in descriptions', 'Selling own courses/ebooks', 'Merchandise shelf'],
  ],
  [
    'id'      => 'facebook',
    'icon'    => '👍',
    'name'    => 'Facebook',
    'earn'    => '₦1k–₦30k/day',
    'count'   => 6,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Facebook is older but still massive in Nigeria. Marketplace is a goldmine for product resellers. In-stream ads pay well for video creators with 10k+ followers.',
    'methods' => ['Facebook Reels bonus program', 'In-stream ads (video monetization)', 'Facebook Stars during live streams', 'Facebook Marketplace reselling', 'Buy-and-sell group admin fees', 'Running ads for local businesses'],
  ],
  [
    'id'      => 'whatsapp',
    'icon'    => '💬',
    'name'    => 'WhatsApp',
    'earn'    => '₦500–₦20k/day',
    'count'   => 5,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'WhatsApp is Nigeria\'s most-used app. Your broadcast list is your most valuable asset. Build a list of 500+ buyers in any niche and you have a built-in sales machine.',
    'methods' => ['Paid broadcast channels (monthly subscription)', 'Selling products via Status views', 'Creating exclusive paid WhatsApp groups', 'Customer support service for businesses', 'WhatsApp Business automation setup service'],
  ],
  [
    'id'      => 'telegram',
    'icon'    => '✈️',
    'name'    => 'Telegram',
    'earn'    => '₦1k–₦50k/day',
    'count'   => 6,
    'bg'      => '#e8f4fc',
    'border'  => '#c5e4f5',
    'desc'    => 'Telegram is the #1 platform for high-value communities. Crypto signal channels charge ₦5k–₦20k/month per subscriber and easily reach ₦1m+/month with 100–200 active members.',
    'methods' => ['Paid Telegram channel subscriptions', 'Crypto/forex signal channel (₦5k–₦20k/member)', 'Selling digital products to channel members', 'Affiliate promotions to channel', 'Telegram bot development service', 'Running paid communities for niches'],
  ],
  [
    'id'      => 'x',
    'icon'    => '🐦',
    'name'    => 'X / Twitter',
    'earn'    => '$10–$2k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'X rewards insightful, consistent content. Build in public — share your journey, lessons, and expertise. Fintech, crypto, and business niches pay most per follower.',
    'methods' => ['X Premium revenue share (Twitter Blue required)', 'Spaces monetization (tips)', 'Affiliate promotion to engaged following', 'Consulting clients acquired via threads', 'Selling and growing Twitter accounts in niches'],
  ],
  [
    'id'      => 'linkedin',
    'icon'    => '💼',
    'name'    => 'LinkedIn',
    'earn'    => '$500–$10k/month',
    'count'   => 6,
    'bg'      => '#e8effe',
    'border'  => '#c5d5f8',
    'desc'    => 'LinkedIn has the highest income per follower of any platform for B2B niches. 10,000 engaged LinkedIn followers in the right niche can generate more income than 1 million TikTok followers.',
    'methods' => ['Freelance client acquisition', 'Consulting deal pipeline', 'Course and ebook sales', 'Sponsored posts (B2B brands)', 'Recruitment referral fees', 'LinkedIn newsletter sponsorships'],
  ],
  [
    'id'      => 'substack',
    'icon'    => '📰',
    'name'    => 'Substack',
    'earn'    => '$100–$10k/month',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'Substack lets you charge subscribers directly for newsletter content. Nigerian finance, tech, and business writers earn $500–$10k/month from paid subs. Free to start — Substack takes only 10% when you earn. Build once, earn monthly forever.',
    'methods' => ['Paid newsletter subscriptions (monthly or annual)', 'One-time premium post sales', 'Substack Recommendations revenue share', 'Affiliate links embedded in newsletters', 'Selling digital products to subscriber base'],
  ],
  [
    'id'      => 'patreon',
    'icon'    => '🎁',
    'name'    => 'Patreon',
    'earn'    => '$200–$50k/month',
    'count'   => 6,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Patreon is the world\'s leading fan membership platform. Set 3–5 tiers, offer exclusive perks, earn monthly. Nigerian creators with 200 patrons at $5/month earn $1,000 in guaranteed recurring income — every single month.',
    'methods' => ['Monthly membership tiers (₦500–₦50k/month per patron)', 'Exclusive behind-the-scenes content for paying fans', 'Early access to videos, music, or writing', 'Discord/community access for top-tier patrons', 'Merchandise drops to patron community only', '1-on-1 coaching sessions for premium tier patrons'],
  ],
  [
    'id'      => 'gumroad',
    'icon'    => '🛍️',
    'name'    => 'Gumroad',
    'earn'    => '₦10k–₦2m/month',
    'count'   => 6,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Gumroad is the simplest platform to sell digital products globally. No monthly fee — they take 10%. Nigerians can receive USD payouts via PayPal. One viral product can earn passively for years.',
    'methods' => ['Sell ebooks, templates, and digital guides', 'Sell software, plugins, and code snippets', 'Offer online courses with video hosting', 'Membership subscriptions via Gumroad', 'Affiliate program — let others sell for you (30–50%)', 'Bundle products for higher average order value'],
  ],
  [
    'id'      => 'udemy',
    'icon'    => '🎓',
    'name'    => 'Udemy',
    'earn'    => '$100–$10k/month',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Udemy has 60+ million students. Upload a course once and earn royalties every time someone enrolls. Top Nigerian instructors on Udemy (tech, design, business) earn $500–$5,000/month from a single course on autopilot.',
    'methods' => ['Create and sell online courses on any skill', 'Earn 37% royalty on organic Udemy sales', 'Earn 97% when you drive your own traffic', 'Upsell students to premium course bundles', 'Build instructor reputation for consulting leads'],
  ],
  [
    'id'      => 'fiverr',
    'icon'    => '💼',
    'name'    => 'Fiverr',
    'earn'    => '$200–$10k/month',
    'count'   => 7,
    'bg'      => '#e8effe',
    'border'  => '#c5d5f8',
    'desc'    => 'Fiverr is Nigeria\'s #1 gateway to USD freelance income. Graphic designers, writers, video editors, developers, and VAs all thrive. One Level 2 Fiverr seller earning $500/month has changed their financial life permanently.',
    'methods' => ['Sell any skill as a "Gig" package (Basic/Standard/Premium)', 'Offer Gig Extras for premium add-ons', 'Fiverr Pro badge for verified top earners ($50–$500/hr)', 'Fiverr Business clients (higher budgets, longer contracts)', 'Collect tips from satisfied buyers', 'Build recurring relationships into private retainer deals', 'Upsell revision packages and faster delivery'],
  ],
  [
    'id'      => 'upwork',
    'icon'    => '🌐',
    'name'    => 'Upwork',
    'earn'    => '$500–$20k/month',
    'count'   => 6,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'Upwork is the world\'s largest professional freelance marketplace. Nigerian developers, writers, and designers with strong profiles earn $2k–$10k/month. Upwork\'s escrow protects payment — you always get paid for work delivered.',
    'methods' => ['Hourly contracts (tracked time = guaranteed pay)', 'Fixed-price project proposals', 'Upwork Expert-Vetted badge (top 1% earners)', 'Long-term client relationships and retainers', 'Agency profile for managing a team of freelancers', 'Talent Scout direct invites for premium profiles'],
  ],
  [
    'id'      => 'etsy',
    'icon'    => '🛒',
    'name'    => 'Etsy',
    'earn'    => '$100–$5k/month',
    'count'   => 6,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Etsy has 90+ million buyers searching for unique products. Nigerian craft sellers, digital template designers, and Ankara fabric creators find global buyers here. Digital product shops on Etsy earn passive income with zero shipping costs.',
    'methods' => ['Sell handmade crafts, jewelry, and fashion items', 'Sell digital downloads — templates, prints, art files', 'Sell vintage or unique thrifted items', 'Etsy Ads to boost listing visibility', 'Star Seller status drives organic search ranking', 'Etsy wholesale for bulk orders from businesses'],
  ],
  [
    'id'      => 'amazon-kdp',
    'icon'    => '📚',
    'name'    => 'Amazon KDP',
    'earn'    => '$50–$5k/month',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Amazon KDP lets anyone publish ebooks and paperbacks globally with zero upfront cost. Nigerians writing about business, finance, romance, self-help, and education earn royalties every month from a global audience of 200 million+ Amazon customers.',
    'methods' => ['Self-publish ebooks and earn 70% royalty on $2.99–$9.99 price range', 'Publish print-on-demand paperbacks globally', 'KDP Select exclusivity bonus (Kindle Unlimited pool)', 'Low-content books: journals, planners, activity books', 'Series of books compound income month over month'],
  ],
  [
    'id'      => 'teachable',
    'icon'    => '🎬',
    'name'    => 'Teachable',
    'earn'    => '₦50k–₦5m/month',
    'count'   => 5,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Teachable gives you full control of your course business — your brand, your pricing, your students. Nigerian trainers in tech, finance, soft skills, and digital marketing use Teachable to earn ₦500k–₦5m per course launch cohort.',
    'methods' => ['Host and sell online video courses', 'Sell coaching packages with 1-on-1 scheduling', 'Build a paid membership community', 'Upsell certificates of completion', 'Affiliate program — students refer and earn commission'],
  ],
  [
    'id'      => 'selar',
    'icon'    => '🇳🇬',
    'name'    => 'Selar',
    'earn'    => '₦20k–₦10m/month',
    'count'   => 7,
    'bg'      => '#e8f4fc',
    'border'  => '#c5e4f5',
    'desc'    => 'Selar is Nigeria\'s #1 digital products marketplace. Built for Nigerians — Paystack integration, naira pricing, and USSD payment means every buyer can pay regardless of bank card access. Top Selar sellers earn ₦500k–₦10m+/month.',
    'methods' => ['Sell ebooks and PDF guides with Paystack checkout', 'Sell online video courses to Nigerian audience', 'Create subscription/membership products', 'Set up affiliate program for your products', 'Bundle products at discounted prices', 'USSD and bank transfer checkout (no card needed)', 'Selar discovery page drives organic Nigerian traffic'],
  ],
  [
    'id'      => 'redbubble',
    'icon'    => '🎨',
    'name'    => 'Redbubble',
    'earn'    => '$50–$3k/month',
    'count'   => 5,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'Redbubble prints your designs on t-shirts, stickers, home decor, phone cases and more — then ships globally. Upload your art once and earn passive income every time someone orders. Nigerian designers with strong Ankara-inspired or African art styles perform extremely well.',
    'methods' => ['Upload art once, sell on 70+ product types (shirts, mugs, phone cases)', 'Set your own artist margin on top of base price', 'No inventory, printing, or shipping — fully automated', 'Redbubble marketplace drives organic buyer traffic', 'Promote best sellers on Pinterest for extra traffic'],
  ],
  [
    'id'      => '99designs',
    'icon'    => '✏️',
    'name'    => '99designs / Dribbble',
    'earn'    => '$200–$5k/month',
    'count'   => 5,
    'bg'      => '#e8effe',
    'border'  => '#c5d5f8',
    'desc'    => '99designs runs thousands of logo, brand, and web design contests daily. Nigerian designers compete globally and win. Even losing entries build portfolio. Dribbble is where top companies find designers — a polished Dribbble profile generates inbound client inquiries worth $1k–$5k/month.',
    'methods' => ['Enter design contests and win prize money ($200–$1,500 per contest)', 'Direct client projects via 99designs marketplace', 'Dribbble Pro portfolio attracts inbound design clients', 'Design job board listings from global companies', 'Dribbble Pro membership unlocks case study features that drive client trust'],
  ],
  [
    'id'      => 'clickbank',
    'icon'    => '💸',
    'name'    => 'ClickBank',
    'earn'    => '$100–$10k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'ClickBank is the world\'s largest digital product affiliate marketplace. Nigerians promote health supplements, finance courses, and business tools to global audiences and earn 50–75% commissions per sale. A single high-converting product promotion can earn $1,000–$5,000/month.',
    'methods' => ['Promote digital products as an affiliate (40–75% commissions)', 'List your own digital product in the ClickBank marketplace', 'ClickBank gravity score shows what is currently selling', 'Upsell and downsell funnels built into products', 'Recurring billing products earn commission every month not just once'],
  ],
  [
    'id'      => 'admob-adsense',
    'icon'    => '📊',
    'name'    => 'Google AdSense / AdMob',
    'earn'    => '$50–$10k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Google AdSense pays you for every visitor who sees or clicks ads on your website or app. A Nigerian blog with 50,000 monthly visitors earns $200–$800/month passively. AdMob inside a simple app or game can earn $500–$5,000/month with enough downloads.',
    'methods' => ['Display ads on your blog or website (AdSense)', 'In-app ads inside your Android or iOS app (AdMob)', 'YouTube AdSense via YouTube Partner Program', 'Auto ads that optimize placement for maximum revenue', 'AdSense for search — monetize internal site search results'],
  ],
  [
    'id'      => 'shutterstock',
    'icon'    => '📷',
    'name'    => 'Shutterstock / Adobe Stock',
    'earn'    => '$50–$3k/month',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Stock image platforms pay royalties every time someone downloads your photo or video. Africa and Nigeria-themed content is massively underserved and in high demand globally. Upload 500+ quality images and earn $200–$1,000/month in truly passive income.',
    'methods' => ['Upload photos and earn royalty on every download ($0.25–$2.85 per image)', 'Upload video clips (10x higher royalty than photos)', 'Submit AI-generated images (policy allows with disclosure)', 'Sell editorial images of Nigerian cities, events, and life', 'Contributor referral bonus — earn when you refer another contributor'],
  ],
  [
    'id'      => 'sharesale',
    'icon'    => '🔗',
    'name'    => 'ShareASale / Impact',
    'earn'    => '$200–$10k/month',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'ShareASale and Impact are premium affiliate networks with thousands of brands paying 5–40% commissions. Fashion, finance, SaaS, hosting, and education brands all pay well. Nigerian bloggers and influencers use these to earn $500–$5,000/month promoting relevant products to their audience.',
    'methods' => ['Promote 4,000+ brands as an affiliate', 'Earn 5–40% commission on every referred sale', 'Real-time tracking dashboard for all campaigns', 'Deep linking to any product page on merchant sites', 'Performance bonuses when you exceed monthly targets'],
  ],
  [
    'id'      => 'toptal',
    'icon'    => '⭐',
    'name'    => 'Toptal',
    'earn'    => '$3k–$25k/month',
    'count'   => 4,
    'bg'      => '#e8effe',
    'border'  => '#c5d5f8',
    'desc'    => 'Toptal is the world\'s most exclusive freelance network — only the top 3% of applicants pass the screening. Nigerian developers, designers, and finance experts who make it earn $60–$200/hour from Fortune 500 companies. One Toptal contract can pay more than a Nigerian bank manager\'s annual salary.',
    'methods' => ['Top 3% acceptance rate unlocks premium global clients', '$60–$200/hour rates for vetted developers and designers', 'Full-time, part-time, and hourly engagements available', 'Finance and project management experts also accepted'],
  ],
  [
    'id'      => '99firms-ppph',
    'icon'    => '⚖️',
    'name'    => 'PeoplePerHour',
    'earn'    => '$200–$5k/month',
    'count'   => 5,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'PeoplePerHour is a UK-based freelance marketplace with strong demand for copywriting, web development, SEO, design, and marketing. UK clients pay in GBP — Nigerian freelancers earn premium rates. A single long-term PeoplePerHour client can anchor ₦500k+/month.',
    'methods' => ['Post "Hourlies" — fixed-price service packages', 'Bid on posted projects from clients', 'Earn WorkStream reputation badge for higher visibility', 'Offer project packages bundled as one deal', 'Repeat client relationships build stable monthly retainers'],
  ],
  [
    'id'      => 'freelancer',
    'icon'    => '💻',
    'name'    => 'Freelancer.com',
    'earn'    => '$200–$6k/month',
    'count'   => 5,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'Freelancer.com has over 60 million registered users posting projects in every category. Nigeria ranks in the top 10 globally for freelancers on this platform. The contest feature allows you to win prize money ($50–$500) without a prior reputation or reviews.',
    'methods' => ['Bid on thousands of live projects daily', 'Freelancer Preferred status for higher bid visibility', 'Contest prizes for design, writing, and naming projects', 'Milestone payment system protects your earnings', 'Hourly projects with live time-tracking tool'],
  ],
  [
    'id'      => 'guru',
    'icon'    => '🧑‍💻',
    'name'    => 'Guru.com',
    'earn'    => '$300–$6k/month',
    'count'   => 4,
    'bg'      => '#e8f4fc',
    'border'  => '#c5e4f5',
    'desc'    => 'Guru.com is an established freelance marketplace with an emphasis on long-term professional relationships. The SafePay escrow system means you always get paid. Strong categories: engineering, programming, writing, translation, and business consulting.',
    'methods' => ['Create a work room for each client — professional relationship management', 'SafePay escrow protects payment on every job', 'Retainer agreements for consistent recurring income', 'Guru job board with 800,000+ employer postings annually'],
  ],
  [
    'id'      => 'hackerone',
    'icon'    => '🔐',
    'name'    => 'HackerOne / Bugcrowd',
    'earn'    => '$100–$50k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Bug bounty programs pay security researchers to find vulnerabilities in websites, apps, and software before hackers do. Nigerians are increasingly winning large bounties. A single critical vulnerability in a major tech company can pay $10,000–$100,000. Zero capital needed — just skill.',
    'methods' => ['Bug bounty hunting — earn $100–$100k per vulnerability found', 'Private program invitations for trusted hunters', 'Pentest engagements (paid per assessment)', 'Leaderboard ranking attracts private program invites', 'HackerOne Clear background verification for enterprise access'],
  ],
  [
    'id'      => '99translations',
    'icon'    => '🌐',
    'name'    => 'ProZ / TranslatorsCafe',
    'earn'    => '$500–$5k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'ProZ and TranslatorsCafe are the world\'s largest translation marketplaces. Nigerians translating English↔Yoruba, English↔Hausa, or English↔French are in high demand globally. Legal and medical translators earn $0.20–$0.40 per word — a 5,000-word legal document pays $1,000–$2,000.',
    'methods' => ['Offer translation services in any language pair', 'Legal, medical, and technical specializations pay 3x more', 'Build a client list of NGOs, embassies, and legal firms', 'Subtitle translation for video content (growing demand)', 'Interpretation services for remote international meetings'],
  ],
  [
    'id'      => 'driverbee',
    'icon'    => '🚗',
    'name'    => 'Bolt / inDrive',
    'earn'    => '₦5k–₦25k/day',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Bolt and inDrive operate across Lagos, Abuja, Port Harcourt, and other Nigerian cities. Drivers with their own car earn ₦8k–₦20k daily. Even rented cars can be profitable. inDrive\'s fare negotiation model means you can earn 20–30% more than Uber/Bolt fixed rates on the same trip.',
    'methods' => ['Drive passengers and earn per trip (70–80% of fare goes to you)', 'Bolt Food delivery during low-passenger periods', 'inDrive allows fare negotiation — earn more on agreed prices', 'Referral bonuses for recruiting new drivers', 'Weekly performance bonuses for high trip counts'],
  ],
  [
    'id'      => 'mtn-momobiz',
    'icon'    => '📲',
    'name'    => 'Mobile Money Agent (MoMo/OPay)',
    'earn'    => '₦3k–₦15k/day',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'MTN MoMo, OPay, PalmPay, and Moniepoint all have agent networks. Becoming an agent costs zero — just get a float. A busy agent at a market or bus stop processes 100+ transactions daily at ₦50–₦200 commission each. The super-agent model (recruiting sub-agents) scales this to ₦100k+/month.',
    'methods' => ['Commission on every cash-in and cash-out transaction', 'Bill payment commissions (electricity, cable TV, water)', 'Airtime and data reselling margin', 'Account opening bonuses for new customers registered', 'Super-agent model — recruit sub-agents and earn override commissions'],
  ],
  [
    'id'      => 'preply',
    'icon'    => '📖',
    'name'    => 'Preply / iTalki',
    'earn'    => '$300–$4k/month',
    'count'   => 5,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Preply and iTalki connect language tutors with learners worldwide. Nigerian English speakers earn $10–$40/hour teaching English to Chinese, Japanese, Korean, and European students. 20 regular weekly students at $15/hour = $1,200/month from home with just a laptop and stable internet.',
    'methods' => ['Teach English, French, or any language to global students', 'Set your own hourly rate ($8–$80/hour)', 'Community Tutor (no formal teaching cert needed)', 'Professional Teacher badge unlocks premium students', 'Trial lessons convert to long-term regular students'],
  ],
  [
    'id'      => 'taskrabbit',
    'icon'    => '🔧',
    'name'    => 'TaskRabbit / Workana',
    'earn'    => '$200–$4k/month',
    'count'   => 5,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'TaskRabbit connects gig workers to tasks in major cities. For Nigerians abroad (US/UK/Canada/Australia) this is a premier source of physical gig income. Workana is the leading freelance platform for Spanish-speaking markets — Nigerian freelancers serving Latin American clients earn in USD at premium rates.',
    'methods' => ['Offer handyman, assembly, moving, and cleaning tasks (TaskRabbit)', 'Freelance projects in Latin American market (Workana)', 'Elite Tasker badge for top-rated workers', 'Subscription model for high-frequency taskers', 'Build a repeat client list for recurring bookings'],
  ],
  [
    'id'      => 'creative-market',
    'icon'    => '🎭',
    'name'    => 'Creative Market / Design Cuts',
    'earn'    => '$100–$5k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Creative Market is the premium marketplace for design assets — fonts, Photoshop templates, Illustrator files, WordPress themes, and UI kits. Nigerian designers price assets at $5–$200. One popular font or template can sell 500–2,000 copies, earning $2,500–$100,000 over its lifetime.',
    'methods' => ['Sell fonts, templates, graphics, and UI kits', 'Design Cuts bundles — earn from curated product bundles', 'Creative Market affiliate program earns 10% on referrals', 'Weekly free goods promotion boosts your shop visibility', 'Bundle your best assets for higher perceived value'],
  ],
  [
    'id'      => 'studypool',
    'icon'    => '📝',
    'name'    => 'Studypool / Course Hero',
    'earn'    => '$200–$3k/month',
    'count'   => 4,
    'bg'      => '#fdf4e8',
    'border'  => '#f5e0c5',
    'desc'    => 'Studypool and Course Hero pay tutors for answering academic questions and uploading study materials. Nigerian graduates and students in STEM, business, and law earn $200–$1,500/month helping US and European students with coursework. Strong English + subject expertise = steady income from your phone.',
    'methods' => ['Answer student questions and earn $2–$50 per question answered', 'Upload your own study notes and past questions for sale', 'Tutor on-demand sessions via video call', 'Earn referral bonuses for recruiting new students or tutors'],
  ],
  [
    'id'      => 'envato',
    'icon'    => '🌟',
    'name'    => 'Envato Market / ThemeForest',
    'earn'    => '$200–$20k/month',
    'count'   => 5,
    'bg'      => '#e8f4fc',
    'border'  => '#c5e4f5',
    'desc'    => 'Envato Market is the world\'s largest digital creative marketplace. A popular WordPress theme on ThemeForest earns $5,000–$50,000+ over its lifespan. Nigerian web developers who build quality themes tap into global passive income. Even graphic templates on GraphicRiver earn $100–$2,000/month from a single well-designed file.',
    'methods' => ['Sell WordPress themes and plugins on ThemeForest', 'Sell graphic templates, logos, and flyers on GraphicRiver', 'Sell After Effects and Premiere templates on VideoHive', 'Envato Elements subscription pool pays per download', 'Author referral program earns 30% of first purchase'],
  ],
  [
    'id'      => 'medium',
    'icon'    => '✍️',
    'name'    => 'Medium Partner Program',
    'earn'    => '$50–$5k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'Medium\'s Partner Program pays writers based on reading time from paying members. Nigerian writers on finance, tech, career growth, and African business consistently earn $200–$2,000/month. Medium also serves as a portfolio and audience-building platform — many writers convert readers to course students or consulting clients.',
    'methods' => ['Earn based on time Medium members spend reading your articles', 'Referred new Medium members earn you a bonus', 'Boost feature — top writers get editorial promotion', 'Build audience that funnels into consulting and course sales', 'Medium publication submissions reach wider existing audiences'],
  ],
  [
    'id'      => 'soundcloud-distrokid',
    'icon'    => '🎵',
    'name'    => 'DistroKid / TuneCore',
    'earn'    => '₦10k–₦2m/month',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'DistroKid and TuneCore let Nigerian musicians distribute songs to every streaming platform for $20–$30/year. Afrobeats and Afropop artists with 1 million monthly Spotify streams earn $3,000–$5,000/month in royalties alone. Viral YouTube sync licensing deals pay $500–$50,000 per placement.',
    'methods' => ['Distribute your music to Spotify, Apple Music, and 150+ platforms', 'Earn streaming royalties on every play ($0.003–$0.005 per stream)', 'YouTube Content ID claims on your music used in videos', 'Sync licensing — sell music for use in films, ads, games', 'Artist merchandise sold via DistroKid HyperFollow page'],
  ],
  [
    'id'      => 'digistore24',
    'icon'    => '💰',
    'name'    => 'Digistore24',
    'earn'    => '$200–$8k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Digistore24 is a fast-growing European digital product marketplace — open to Nigerian affiliates. Commission rates of 40–80% on info products, software, and coaching programs. Payout via PayPal and wire transfer. Many products pay recurring monthly commissions for subscription-based offers.',
    'methods' => ['Promote digital products as affiliate (40–80% commissions)', 'Sell your own digital product in the marketplace', 'Recurring billing products earn monthly commissions', 'High-ticket coaching programs pay $200–$2,000 per sale', 'Upsell and order bump funnels built into every product'],
  ],
  [
    'id'      => 'warriorplus',
    'icon'    => '⚔️',
    'name'    => 'WarriorPlus',
    'earn'    => '$100–$5k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'WarriorPlus specializes in internet marketing, software tools, and make-money-online products. Nigerian affiliates earn 50–100% commissions — vendors pay high because back-end upsells are where they profit. Apply to top vendors, build a small email list, and promote launches. PayPal payout supported.',
    'methods' => ['Promote internet marketing products as affiliate', 'Apply to promote high-converting product launches', 'Earn 50–100% commission on front-end offers (upsell pays)', 'Build a JV email list for launch promotions', 'Launch your own product in the warrior+ marketplace'],
  ],
  [
    'id'      => 'partnerstack',
    'icon'    => '🤝',
    'name'    => 'PartnerStack',
    'earn'    => '$200–$10k/month',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'PartnerStack hosts affiliate programs for 300+ B2B SaaS companies — many paying 20–40% recurring commissions. Nigerians promoting tools like Monday.com, Freshbooks, or SEMrush earn every month a referred customer stays subscribed. One good B2B referral earns more than 100 physical product sales.',
    'methods' => ['Promote B2B SaaS products as affiliate (20–40% recurring)', 'Refer other affiliates and earn override commission', 'Real-time dashboard tracks all clicks, trials, and revenue', 'Long cookie windows (90–180 days) protect your commissions', 'Top-converting SaaS niches: HR, marketing, finance tools'],
  ],
  [
    'id'      => 'flexoffers',
    'icon'    => '📋',
    'name'    => 'FlexOffers',
    'earn'    => '$100–$5k/month',
    'count'   => 5,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'FlexOffers aggregates 12,000+ affiliate programs including major brands in finance, education, retail, and travel. Nigerian affiliates use it to diversify across multiple programs from a single dashboard. Finance and insurance offers pay $50–$200 CPA. Strong approval rate for international affiliates.',
    'methods' => ['Access 12,000+ affiliate programs in one dashboard', 'Finance, education, and tech niches pay highest CPAs', 'Deep linking to any page of any merchant site', 'FlexRev-$hare — earn override from sub-affiliates you recruit', 'Monthly payout via PayPal, wire, or check'],
  ],
  [
    'id'      => 'rakuten-advertising',
    'icon'    => '🛍️',
    'name'    => 'Rakuten Advertising',
    'earn'    => '$200–$8k/month',
    'count'   => 4,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Rakuten Advertising is one of the oldest and most trusted affiliate networks, partnering with global retail giants. Nigerian bloggers, YouTubers, and email marketers promoting fashion, electronics, and home goods earn steady commissions. PayPal payout available — minimum $50 threshold.',
    'methods' => ['Promote top retail brands: Walmart, Best Buy, New Balance', 'Earn 3–15% commission on referred purchases', 'Affiliate links work globally with long cookie windows', 'Exclusive brand deals for top-performing publishers', 'Performance bonuses for monthly revenue milestones'],
  ],
  [
    'id'      => 'ezoic',
    'icon'    => '📊',
    'name'    => 'Ezoic',
    'earn'    => '$200–$5k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Ezoic is a premium ad network that pays significantly more than Google AdSense through AI-optimized ad placement. Nigerian bloggers with 10,000+ monthly visitors earn $5–$25 RPM — some blogs earn $1,000–$5,000/month. Payoneer payout makes it fully accessible to Nigerians.',
    'methods' => ['Display ads on your blog with AI-optimized placement', 'Higher RPMs than AdSense for most Nigerian sites ($5–$25 RPM)', 'Video ads add extra revenue layer to articles', 'Ezoic Humix — distribute your videos to other sites and earn', 'Leap (speed) tool improves Core Web Vitals for better SEO'],
  ],
  [
    'id'      => 'medianet',
    'icon'    => '🌐',
    'name'    => 'Media.net',
    'earn'    => '$100–$3k/month',
    'count'   => 4,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'Media.net powers the Yahoo/Bing contextual ad network — the #2 ad network globally after Google. Nigerian content creators with English-language blogs on finance, health, and technology earn $2–$15 RPM. Payments via PayPal and wire transfer. Minimum payout $100.',
    'methods' => ['Contextual ads powered by Yahoo & Bing network', 'Display and native ad formats that blend with content', 'Higher CPC for finance, tech, and legal blog niches', 'Works alongside AdSense for dual monetization', 'Dashboard reporting shows top-performing ad units'],
  ],
  [
    'id'      => 'propellerads',
    'icon'    => '🚀',
    'name'    => 'PropellerAds',
    'earn'    => '$50–$2k/month',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'PropellerAds is one of the most Nigeria-friendly ad networks — accepting sites with moderate traffic and paying weekly via PayPal. Push notification campaigns earn $0.10–$3 CPM. Entertainment, news, and gaming sites perform best. Minimum $5 payout means beginners get paid fast.',
    'methods' => ['Push notification ads earn per subscriber (CPM model)', 'Popunder and interstitial ads for high-traffic sites', 'OnClick ads — high-paying format for entertainment sites', 'SmartLink — one link auto-selects best offer for each visitor', 'Weekly PayPal payout, minimum $5 threshold'],
  ],
  [
    'id'      => 'adsterra',
    'icon'    => '💵',
    'name'    => 'Adsterra',
    'earn'    => '$50–$3k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Adsterra is a Nigeria-accessible premium ad network paying weekly via PayPal. Known for high CPMs on social bar and popunder formats — Nigerian news, entertainment, and download sites earn $1–$10 CPM. No strict minimum traffic requirements. Approval takes 1–3 days. Very beginner-friendly.',
    'methods' => ['Social Bar ads (chat-like format) earn high CPMs', 'Popunder, direct links, native banners all supported', 'Direct advertiser deals skip network cuts', 'SmartCPM auto-optimizes bids across campaigns', 'Weekly PayPal payout — $5 minimum for publishers'],
  ],
  [
    'id'      => 'monetag',
    'icon'    => '💳',
    'name'    => 'Monetag',
    'earn'    => '$50–$2k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'Monetag (formerly PropellerAds Publisher) is one of the easiest ad networks to get approved on — Nigerian sites with any traffic level qualify. Push notification and popunder ads pay $0.50–$5 CPM. Ideal for entertainment, sports, and download sites. Weekly PayPal payout at just $5 minimum.',
    'methods' => ['Push notification subscriptions pay per subscriber', 'Popunder ads for high-traffic content sites', 'In-page push — ads show without user subscribing', 'Interstitial full-screen ads for maximum visibility', 'Weekly PayPal payments — minimum $5 payout'],
  ],
  [
    'id'      => 'infolinks',
    'icon'    => '🔗',
    'name'    => 'Infolinks',
    'earn'    => '$30–$1k/month',
    'count'   => 4,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Infolinks runs in-text and in-frame ads that work alongside existing ad networks like AdSense. Nigerian bloggers use it as a second revenue layer — InText ads convert underlined article keywords into ad clicks. Works on any blog without approval waiting period. PayPal payout accessible to Nigerians.',
    'methods' => ['InText ads — keywords in articles become ad links', 'InFold ads display at bottom of screen', 'InTag cloud of related advertiser topics', 'InScreen full-page ads between page views', 'PayPal payout — minimum $50 threshold'],
  ],
  [
    'id'      => 'revcontent',
    'icon'    => '📰',
    'name'    => 'Revcontent',
    'earn'    => '$100–$3k/month',
    'count'   => 4,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Revcontent is a premium native advertising network — the sponsored content widget that appears below articles on major news sites. Nigerian publishers with news, health, and entertainment sites earn $2–$15 CPM. Higher quality than most networks. PayPal payout available.',
    'methods' => ['Native content recommendation ads (like Taboola)', 'Widgets display "You may also like" sponsored articles', 'High CPMs for news, health, and finance sites', 'Customizable widget design to match site aesthetic', 'PayPal and wire transfer payout options'],
  ],
  [
    'id'      => 'outbrain',
    'icon'    => '📡',
    'name'    => 'Outbrain',
    'earn'    => '$200–$8k/month',
    'count'   => 4,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'Outbrain is a native advertising platform used to drive traffic to blogs, landing pages, and offers. Nigerian digital marketers use Outbrain to promote affiliate content, lead gen funnels, and ecommerce stores. With $100–$200 budget, a well-optimized campaign can generate $500–$2,000 in affiliate commissions.',
    'methods' => ['Native content ads displayed on premium publisher sites', 'Pay per click on your promoted article/content', 'Lookalike audience targeting for precise reach', 'Smartfeed format integrates natively into article feeds', 'Credit card and PayPal payment for advertisers'],
  ],
  [
    'id'      => 'taboola',
    'icon'    => '📣',
    'name'    => 'Taboola',
    'earn'    => '$200–$10k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Taboola is one of the world\'s largest native advertising platforms — its widgets appear on CNN, BBC, and every major news site. Nigerian affiliates and digital marketers use Taboola to drive traffic to high-converting offers. As a publisher, display Taboola widgets on your blog and earn $2–$15 CPM from premium advertisers.',
    'methods' => ['Native ads on CNN, NBC, MSN, and 9,000+ publisher sites', 'Promote affiliate content, lead gen pages, or products', 'Audience targeting by interest, location, and device', 'Taboola News distribution to Samsung and other devices', 'Publisher program — earn by displaying Taboola widgets on your site'],
  ],
  [
    'id'      => 'payhip',
    'icon'    => '🧾',
    'name'    => 'Payhip',
    'earn'    => '₦30k–₦3m/month',
    'count'   => 5,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'Payhip is a simple, zero-monthly-fee digital products store. Free plan takes 5% fee — upgrade for lower fees. Nigerian creators sell ebooks, templates, and mini-courses via PayPal and receive USD payouts. Easy to set up in under an hour. Ideal for beginners launching their first digital product.',
    'methods' => ['Sell ebooks, courses, software, and memberships', 'Embed a Payhip buy button on any website or blog', 'Affiliate marketing built in — set your own commission rate', 'Discount codes and email list collection included', 'PayPal payout — Nigerians receive USD payments directly'],
  ],
  [
    'id'      => 'sellfy',
    'icon'    => '🛒',
    'name'    => 'Sellfy',
    'earn'    => '$100–$5k/month',
    'count'   => 5,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Sellfy is an all-in-one creator store for digital and physical products. Nigerian designers, writers, and musicians use Sellfy to sell directly to fans without marketplace commissions. Starter plan is $29/month but earns it back fast. PayPal payout makes it fully accessible in Nigeria.',
    'methods' => ['Sell digital downloads, subscriptions, and physical goods', 'Embed a buy button or full store on your website', 'Built-in email marketing to your customer list', 'Print-on-demand integration (t-shirts, mugs, etc)', 'PayPal and Stripe checkout supported'],
  ],
  [
    'id'      => 'podia',
    'icon'    => '🎓',
    'name'    => 'Podia',
    'earn'    => '$200–$10k/month',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Podia is an all-in-one platform for courses, digital downloads, and communities. No transaction fees on paid plans. Nigerian instructors in tech, business, and creative skills build entire income ecosystems on Podia — course + community + email list in one place. PayPal payout supported.',
    'methods' => ['Sell online courses with video hosting included', 'Run paid communities with monthly membership fees', 'Sell digital downloads alongside courses', 'Built-in email marketing to nurture students', 'Affiliate program — your students refer new buyers'],
  ],
  [
    'id'      => 'kajabi',
    'icon'    => '👑',
    'name'    => 'Kajabi',
    'earn'    => '$500–$50k/month',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'Kajabi is the premium all-in-one creator platform — course, community, email, and funnel all in one. More expensive than alternatives ($149/month+) but serious Nigerian educators and coaches use it to run 7-figure course businesses. One popular course + community on Kajabi earns $10,000–$50,000/month.',
    'methods' => ['Build and sell online courses with full video hosting', 'Paid membership communities (monthly recurring revenue)', 'Email marketing automation built in — no extra tool needed', 'Sales funnels and landing pages included', 'Coaching program with scheduling and payment built in'],
  ],
  [
    'id'      => 'skillshare',
    'icon'    => '🎨',
    'name'    => 'Skillshare',
    'earn'    => '$100–$5k/month',
    'count'   => 4,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Skillshare pays teachers a royalty based on minutes watched by Premium members. Nigerian creative instructors in design, illustration, animation, and writing earn $100–$3,000/month from a single popular class. Upload once, earn forever. No production cost — just a laptop and screen recording software.',
    'methods' => ['Upload creative courses and earn royalties per minute watched', 'Skillshare royalty pool — top teachers earn $1,000–$10,000/month', 'Refer students and earn $10 per new Premium referral', 'Build audience on Skillshare that funnels to your own platforms', 'Popular niches: illustration, design, photography, writing'],
  ],
  [
    'id'      => 'storyblocks',
    'icon'    => '🎬',
    'name'    => 'Storyblocks / Pond5',
    'earn'    => '$100–$3k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'Pond5 and Storyblocks are the top stock video and music platforms. Nigerian videographers shooting lifestyle, street scenes, cultural events, and nature earn $1–$50 per clip download. African content is massively underrepresented — high demand, low competition. A library of 200+ clips earns $500–$2,000/month passively.',
    'methods' => ['Upload stock video clips and earn per download', 'Stock music and sound effects earn per license', 'Pond5 exclusive uploads earn higher royalty rates', 'Storyblocks contributor program pays per clip used', 'African and Nigerian lifestyle footage sells at premium rates'],
  ],
  [
    'id'      => 'squarespace',
    'icon'    => '🏠',
    'name'    => 'Squarespace',
    'earn'    => '$500–$10k/month',
    'count'   => 4,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Squarespace is a premium website builder used by creative professionals worldwide. Nigerian web designers who specialize in Squarespace earn $500–$3,000 per client site. Squarespace Circle membership gives access to discounted licenses and client management tools. Growing demand from Nigerian small businesses wanting premium websites.',
    'methods' => ['Build client websites as a certified Squarespace designer', 'Squarespace Circle partner — access client management tools', 'Squarespace e-commerce store for your own products', 'Template design and resale on Squarespace marketplace', 'Monthly retainer for site management and updates'],
  ],
  [
    'id'      => 'kofi',
    'icon'    => '☕',
    'name'    => 'Ko-fi',
    'earn'    => '$50–$5k/month',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Ko-fi is a creator tip and membership platform with ZERO fees on one-time donations. Fans buy you a "coffee" for $3 or more. Nigerian artists, writers, and podcasters use Ko-fi alongside other platforms — even 50 monthly supporters at $5 each = $250/month completely passive. PayPal payout supported.',
    'methods' => ['Receive one-time "coffee" tips from fans ($3–$50 each)', 'Ko-fi Gold — monthly memberships with exclusive content', 'Sell digital downloads directly on Ko-fi shop', 'Commission requests — fans pay for custom artwork or writing', 'Zero platform fees on donations (Ko-fi takes 0% on tips)'],
  ],
  [
    'id'      => 'solidgigs',
    'icon'    => '💼',
    'name'    => 'SolidGigs',
    'earn'    => '$500–$8k/month',
    'count'   => 4,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'SolidGigs curates the top 1% of freelance jobs from 100+ job boards and sends them to subscribers weekly. For $19/month, Nigerian freelancers skip the job board grind and spend time applying instead of searching. Ideal for writers, designers, and developers targeting $50–$200/hour international clients.',
    'methods' => ['Curated freelance job leads delivered to your inbox weekly', 'Save 10+ hours weekly searching multiple job boards', 'Filter by skill: writing, design, development, marketing', 'Apply only to pre-vetted, legitimate high-paying clients', 'Combine with Upwork and Fiverr for maximum opportunity'],
  ],
  [
    'id'      => 'microworkers',
    'icon'    => '⚡',
    'name'    => 'Microworkers',
    'earn'    => '₦5k–₦80k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Microworkers is a micro-task platform paying per completed small online job. Nigerian workers complete tasks like following social media accounts, testing websites, verifying data, and leaving reviews. Earnings accumulate fast — 50 tasks/day at $0.20–$1 each = $10–$50/day. Payoneer withdrawal available.',
    'methods' => ['Complete small online tasks for $0.10–$5 each', 'Tasks: app testing, data entry, surveys, social media actions', 'TurboTask for speed tasks requiring quick completions', 'Refer workers and earn bonus commissions', 'Withdraw earnings via Payoneer, Skrill, or direct bank'],
  ],
  [
    'id'      => 'timebucks',
    'icon'    => '⏰',
    'name'    => 'TimeBucks',
    'earn'    => '₦10k–₦100k/month',
    'count'   => 5,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'TimeBucks is a reward platform fully open to Nigerians — one of the few that pays for watching videos, surveys, and social media tasks. Bitcoin payout available globally. Consistent users earn $50–$200/month. The referral program (25% lifetime override) is the real earner — recruit 20 active workers and earn passively.',
    'methods' => ['Watch videos and earn per view', 'Complete paid surveys ($0.50–$5 each)', 'Follow social media accounts for pay', 'Install apps and earn per installation', 'Refer friends and earn 25% of their lifetime earnings'],
  ],
  [
    'id'      => 'sproutgigs',
    'icon'    => '🌱',
    'name'    => 'SproutGigs',
    'earn'    => '₦8k–₦80k/month',
    'count'   => 4,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'SproutGigs (formerly Picoworkers) is a micro-task marketplace fully accessible to Nigerians. Complete small online jobs — app installs, social media actions, survey completions — and withdraw via PayPal or Payoneer. Not a full income source alone, but earns $20–$100/month as a side activity on your phone.',
    'methods' => ['Complete tasks: website visits, app downloads, social follows', 'Earn $0.10–$2 per completed task', 'Withdraw via PayPal, Payoneer, or crypto', 'Employer role: buy tasks done by workers globally', 'Referral program earns ongoing commission'],
  ],
  [
    'id'      => 'picoworkers',
    'icon'    => '🔧',
    'name'    => 'Picoworkers / RapidWorkers',
    'earn'    => '₦5k–₦50k/month',
    'count'   => 4,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Picoworkers and RapidWorkers are micro-task platforms where Nigerians earn per small online job completed. Perfect for students or anyone with spare time on their phone. Not high income alone, but earns ₦5k–₦30k/month doing app installs, Google searches, and review tasks during free time.',
    'methods' => ['Complete micro-tasks for $0.05–$2 per task', 'Tasks: search engine queries, form fills, app reviews', 'Raise worker level for access to higher-paying tasks', 'Post tasks as an employer to build your app or social presence', 'PayPal and Payoneer withdrawal supported'],
  ],
  [
    'id'      => 'testio',
    'icon'    => '🧪',
    'name'    => 'Test.io / Testbirds',
    'earn'    => '$100–$2k/month',
    'count'   => 4,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'Test.io and Testbirds pay real humans to test software products before launch. Nigerian testers with attention to detail earn $10–$50 per approved bug report. No coding knowledge required — just a computer and the ability to think like an end user. Consistent testers earn $200–$1,000/month from their phone or laptop.',
    'methods' => ['Get paid to test websites and apps for bugs', 'Earn $10–$50 per approved bug report', 'Exploratory testing — find issues companies missed', 'Functional testing for app releases', 'Join tester community for exclusive high-pay projects'],
  ],
  [
    'id'      => 'lionbridge',
    'icon'    => '🦁',
    'name'    => 'Lionbridge / TELUS AI',
    'earn'    => '$300–$2k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Lionbridge and TELUS International (formerly Lionbridge AI) hire Nigerians as online raters — evaluating Google search results, AI responses, and social media content. Earn $10–$15/hour working flexible hours. Search Engine Evaluator role requires passing an exam but pays $200–$800/month consistently from home.',
    'methods' => ['Search engine evaluation — rate search results for quality', 'Social media content rating (harmful content detection)', 'AI data annotation and training data creation', 'Maps quality evaluation from your smartphone', 'Language translation tasks for bilinguals'],
  ],
  [
    'id'      => 'rumble',
    'icon'    => '▶️',
    'name'    => 'Rumble',
    'earn'    => '$100–$5k/month',
    'count'   => 5,
    'bg'      => '#f4f4f4',
    'border'  => '#e0e0e0',
    'desc'    => 'Rumble is the fast-growing alternative video platform with aggressive revenue sharing for creators. Nigerian videographers who capture viral moments — accidents, events, street scenes — can license clips for $500–$5,000. Regular content creators earn monthly ad revenue. Rumble pays via PayPal, fully accessible to Nigeria.',
    'methods' => ['Upload videos and earn from Rumble\'s ad revenue share', 'License viral videos for exclusive media use ($100–$5,000)', 'Rumble partner program for consistent creators', 'Distribute to Rumble, YouTube, and Facebook simultaneously', 'Trending news clips and viral content earn the most'],
  ],
  [
    'id'      => 'kick',
    'icon'    => '🎮',
    'name'    => 'Kick',
    'earn'    => '$100–$10k/month',
    'count'   => 5,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Kick is the creator-first live streaming platform offering 95% subscription revenue share — far superior to Twitch\'s 50%. Nigerian streamers in gaming, music, and IRL content are building audiences here faster than Twitch. With 1,000 subscribers at $4.99/month, you keep $4,740/month. PayPal payout supported.',
    'methods' => ['Live stream gaming, talk shows, or IRL content', 'Earn 95% of subscription revenue (vs Twitch\'s 50%)', 'Channel points and gifted subs from supportive viewers', 'Bit equivalent donations during live streams', 'Brand deals via Kick\'s creator marketplace'],
  ],
  [
    'id'      => 'bandcamp',
    'icon'    => '🎵',
    'name'    => 'Bandcamp',
    'earn'    => '₦20k–₦3m/month',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Bandcamp is the most artist-friendly music platform — you keep 82–85% of every sale, versus Spotify\'s $0.003 per stream. Nigerian musicians in Afrobeats, Afrojuju, gospel, highlife, and indie earn directly from global fans. Bandcamp Friday removes all fees on the first Friday of every month. PayPal payout available.',
    'methods' => ['Sell albums, EPs, and singles — keep 82–85% of revenue', 'Offer "name your price" downloads to attract more fans', 'Sell vinyl, merch, and physical CDs directly to fans', 'Bandcamp Friday — zero fees first Friday of every month', 'Fan subscriptions for exclusive early access and content'],
  ],
  [
    'id'      => 'viralhog',
    'icon'    => '📹',
    'name'    => 'ViralHog',
    'earn'    => '$50–$5k/per video',
    'count'   => 4,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'ViralHog licenses user-submitted viral videos to TV channels, streaming platforms, and news media globally. Nigerian smartphone users who capture viral moments — funny street scenes, wedding fails, surprise events — earn $50–$5,000+ per clip. Submit via their website, sign a licensing agreement, and earn each time it\'s broadcast.',
    'methods' => ['Submit viral video footage for licensing and distribution', 'Earn revenue share every time your clip is broadcast or streamed', 'Exclusive licensing deals for premium footage ($500–$5,000)', 'ViralHog distributes clips to TV channels, news outlets, YouTube', 'One viral Nigerian street scene or event clip can earn for years'],
  ],
  [
    'id'      => 'creative-fabrica',
    'icon'    => '🖌️',
    'name'    => 'Creative Fabrica',
    'earn'    => '$100–$5k/month',
    'count'   => 5,
    'bg'      => '#fce8f3',
    'border'  => '#f5c5e3',
    'desc'    => 'Creative Fabrica is a subscription marketplace for craft and design assets — fonts, SVGs, clipart, and templates. Nigerian designers upload assets and earn from the subscription pool every time a member downloads their file. Cricut and sublimation crafters worldwide spend heavily here — niche but highly profitable for consistent uploaders.',
    'methods' => ['Sell fonts, SVG cut files, and graphic bundles', 'Creative Fabrica subscription pool pays per download', 'Craft niches: sublimation, Cricut designs, embroidery files', 'Sell digital planner and journal templates', 'Affiliate program earns 30% commission on new subscribers'],
  ],
  [
    'id'      => 'designbundles',
    'icon'    => '🎁',
    'name'    => 'DesignBundles',
    'earn'    => '$100–$3k/month',
    'count'   => 4,
    'bg'      => '#e8fce8',
    'border'  => '#c5f0c5',
    'desc'    => 'DesignBundles is a high-traffic design asset marketplace — fonts, graphics, templates, and SVG files. Nigerian graphic designers who upload craft-friendly designs (Cricut, sublimation, mockups) earn from both direct sales and the bundle program. Free file promotions can attract thousands of new followers to your shop.',
    'methods' => ['Sell fonts, mockups, graphics, and templates', 'DesignBundles bundles drive massive volume downloads', 'Become an approved shop contributor', 'Free bundle feature gets your designs in front of 1M+ users', 'Affiliate program earns 25% per referred sale'],
  ],
  [
    'id'      => 'motion-array',
    'icon'    => '🎞️',
    'name'    => 'Motion Array',
    'earn'    => '$200–$5k/month',
    'count'   => 5,
    'bg'      => '#ede8fc',
    'border'  => '#d5c5f5',
    'desc'    => 'Motion Array is the premium marketplace for video production assets — After Effects templates, Premiere presets, stock footage, and LUTs. Nigerian video editors and motion designers who upload quality templates earn from the subscription pool monthly. One viral After Effects template can earn $500–$3,000/month in passive royalties.',
    'methods' => ['Sell After Effects, Premiere, and DaVinci Resolve templates', 'Sell stock footage and motion graphics elements', 'Sell LUTs, transitions, and sound effects', 'Motion Array subscription pool pays per download monthly', 'Promote via YouTube tutorials showing your templates in action'],
  ],
  [
    'id'      => 'systemeio',
    'icon'    => '🔄',
    'name'    => 'Systeme.io',
    'earn'    => '$200–$10k/month',
    'count'   => 5,
    'bg'      => '#fce8e8',
    'border'  => '#f5c5c5',
    'desc'    => 'Systeme.io is the most affordable all-in-one marketing platform — free plan supports up to 2,000 subscribers and 3 sales funnels. Nigerian creators use it to sell courses, run affiliate funnels, and build email lists without paying for multiple tools. Systeme affiliate program pays 40% recurring commission — promote it and earn passively.',
    'methods' => ['Build and sell online courses completely free (free plan)', 'Sales funnels and landing pages included at no extra cost', 'Email marketing automation built in — no extra tool', 'Affiliate marketplace — promote others\' products for 40–60% commission', 'Run a paid membership site with recurring billing'],
  ],
  [
    'id'      => 'codecanyon',
    'icon'    => '💻',
    'name'    => 'CodeCanyon / VideoHive',
    'earn'    => '$500–$20k/month',
    'count'   => 5,
    'bg'      => '#e8f0fe',
    'border'  => '#c5d8fc',
    'desc'    => 'CodeCanyon (code marketplace) and VideoHive (video template marketplace) are part of the Envato family. Nigerian developers sell WordPress plugins for $15–$200 each — a popular plugin sells thousands of copies. VideoHive template sellers earn $3–$30 per download. One high-quality item can earn $1,000–$10,000/month in passive royalties.',
    'methods' => ['Sell WordPress plugins on CodeCanyon', 'Sell JavaScript, PHP, and mobile app templates', 'VideoHive: sell After Effects and Premiere video templates', 'Envato Elements pool — earn per download from subscribers', 'Author referral program pays 30% of new author first purchase'],
  ],
];

$platformSections = [
  ['📱 Social Media Platforms', [0, 1, 2, 3, 4, 5, 6, 7]],
  ['💰 Sell & Earn Platforms', [8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19]],
  ['💼 Freelance Marketplaces', [20, 21, 22, 23, 24, 25, 26, 27, 28, 29]],
  ['🚀 Gig & Side Income', [30, 31, 32, 33, 34, 35, 36, 37]],
  ['🎵 Music & Creative', [38, 39]],
  ['🤝 Affiliate Networks', [40, 41, 42, 43]],
  ['📊 Ad Networks', [44, 45, 46, 47, 48, 49, 50, 51, 52, 53]],
  ['🛒 Creator Stores', [54, 55, 56, 57, 58, 59, 60, 61]],
  ['⚡ Micro-Tasks', [62, 63, 64, 65, 66, 67]],
  ['🎬 Video & Media', [68, 69, 70, 71, 72, 73, 74, 75, 76]],
];


$topSocialTools = [
  ['emoji'=>'📱','name'=>'TikTok Creator Marketplace','earn'=>'₦50k–₦500k/post','badge'=>'Brand Deals','desc'=>'The official brand deal platform inside TikTok. Creators with 10k+ followers get matched with brands paying ₦50k–₦500k per sponsored post.'],
  ['emoji'=>'🎵','name'=>'CapCut Pro','earn'=>'Tool for growth','badge'=>'Video Editing','desc'=>'The #1 video editing app for short-form creators. Professional transitions, AI captions, and templates that increase video retention and follower growth.'],
  ['emoji'=>'🖼️','name'=>'Canva Pro','earn'=>'₦5k–₦20k/client','badge'=>'Design','desc'=>'Design social media graphics, thumbnails, story templates, and media kits that attract brand deals. Top creators bill ₦5k–₦20k extra per client for design services.'],
  ['emoji'=>'📬','name'=>'ConvertKit / Kit','earn'=>'₦50k–₦500k/month','badge'=>'Email List','desc'=>'The top email platform for creators turning social followers into paying subscribers. A list of 1,000 emails earns more reliably than 100,000 followers on any platform.'],
  ['emoji'=>'🔗','name'=>'Linktree (Pro)','earn'=>'Drives all income','badge'=>'Link-in-Bio','desc'=>'Bio link page that houses all your monetization links — affiliate products, digital downloads, booking pages, and paid communities in one tap.'],
  ['emoji'=>'📊','name'=>'Buffer / Later','earn'=>'₦80k–₦250k/client','badge'=>'Scheduling','desc'=>'Schedule content across TikTok, Instagram, Facebook, and LinkedIn automatically. Agencies use this to manage 10+ client accounts simultaneously.'],
  ['emoji'=>'🎙️','name'=>'ElevenLabs AI Voice','earn'=>'$500–$5k/month','badge'=>'AI Voice','desc'=>'Generate professional voiceovers with AI for YouTube Automation channels and faceless TikToks. One channel with 3 AI voices earns $500–$5k/month in AdSense.'],
  ['emoji'=>'🤖','name'=>'ChatGPT Plus','earn'=>'10x output','badge'=>'AI Writing','desc'=>'Write scripts, captions, hooks, email sequences, and content calendars 10x faster. Top creators use AI to produce 30 days of content in a single afternoon.'],
  ['emoji'=>'📈','name'=>'Metricool','earn'=>'₦150k–₦500k/month','badge'=>'Analytics','desc'=>'Analytics and scheduling tool for serious social media managers. Track competitor growth, content performance, and client reporting dashboards.'],
  ['emoji'=>'🛒','name'=>'Selar (Creator Store)','earn'=>'₦100k–₦5m/month','badge'=>'Digital Sales','desc'=>''],
  ['emoji'=>'💳','name'=>'Paystack (Links)','earn'=>'All income','badge'=>'Payments','desc'=>'Generate payment links instantly. Share in TikTok bio, WhatsApp status, or Instagram DMs to collect naira payments for any service or product.'],
  ['emoji'=>'🎓','name'=>'Teachable / Thinkific','earn'=>'₦500k–₦5m/month','badge'=>'Course Platform','desc'=>'Build and sell your own online course for any skill you have. A ₦25k course with 100 buyers every month = ₦2.5m passive income, recurring.'],
  ['emoji'=>'📌','name'=>'Pinterest Business','earn'=>'$500–$5k/month','badge'=>'Passive Traffic','desc'=>'Drive evergreen traffic to affiliate offers and digital products. A well-pinned board generates free clicks and commissions years after posting — true passive income.'],
  ['emoji'=>'🌐','name'=>'Beehiiv','earn'=>'$500–$10k/month','badge'=>'Newsletter','desc'=>'The fastest-growing newsletter platform. Monetize with paid subscriptions, sponsorships, and the Beehiiv Ad Network — get matched with brands willing to pay per subscriber.'],
  ['emoji'=>'🎮','name'=>'StreamElements','earn'=>'$200–$10k/month','badge'=>'Live Streaming','desc'=>'Top streaming overlay and tip jar tool for Twitch/YouTube live streamers. Viewers send tips, subscriptions, and channel points — best income per live hour.'],
  ['emoji'=>'📹','name'=>'InVideo AI','earn'=>'$500–$8k/month','badge'=>'AI Video','desc'=>'Create faceless YouTube videos and TikToks using AI. Type a topic — get a full video with voiceover, visuals, and subtitles in minutes. Best for automation channel creators.'],
  ['emoji'=>'🏪','name'=>'Shopify + TikTok Shop','earn'=>'₦200k–₦5m/month','badge'=>'Social Commerce','desc'=>'Connect your product store directly to TikTok Shop. Sell while posting — viewers buy without leaving TikTok. Fastest-growing social commerce channel in Nigeria.'],
  ['emoji'=>'📡','name'=>'Hootsuite / Sprout Social','earn'=>'₦200k–₦500k/client','badge'=>'Agency Tool','desc'=>'Enterprise-grade social media management for agencies running 20+ client accounts. Commands premium retainer pricing of ₦200k–₦500k/client/month.'],
  ['emoji'=>'🔥','name'=>'TubeBuddy (Legend)','earn'=>'2x channel growth','badge'=>'YouTube SEO','desc'=>'YouTube keyword research, SEO optimization, and A/B thumbnail testing. The tool that doubles YouTube channel growth speed and AdSense income.'],
  ['emoji'=>'🖥️','name'=>'Gumroad','earn'=>'₦200k–₦10m/month','badge'=>'Digital Store','desc'=>'Simplest way to sell digital products globally with instant payouts. Sell ebooks, templates, presets, plugins — one product can earn ₦1m/month passively.'],
  ['emoji'=>'🌟','name'=>'Fiverr (Seller Account)','earn'=>'₦300k–₦2m/month','badge'=>'Freelance','desc'=>'The world\'s largest gig marketplace. Nigerian creators earn ₦300k–₦2m/month selling graphic design, copywriting, video editing, and voiceovers to international buyers.'],
  ['emoji'=>'💼','name'=>'Upwork (Agency Profile)','earn'=>'$2k–$20k/month','badge'=>'High-Ticket','desc'=>'Build a freelance agency profile on Upwork and bid on $2k–$50k projects for international clients. Nigeria\'s top Upwork profiles bill $50–$150/hour.'],
  ['emoji'=>'📲','name'=>'Manychat','earn'=>'Converts followers','badge'=>'DM Automation','desc'=>'Automated Instagram and Facebook DM funnels. Run giveaways, product launches, and lead capture on autopilot. Used by top influencers and e-commerce brands.'],
  ['emoji'=>'🎧','name'=>'Spotify for Podcasters','earn'=>'$500–$5k/month','badge'=>'Podcast','desc'=>'Host your podcast on Spotify and monetize through listener subscriptions, brand deals, and Spotify\'s Podcast Ads program. Top Nigerian podcasters earn $500–$5k/month.'],
  ['emoji'=>'💎','name'=>'Patreon','earn'=>'₦500k–₦5m/month','badge'=>'Memberships','desc'=>'Set up monthly membership tiers for your most loyal fans. Just 200 patrons at ₦5k/month = ₦1m guaranteed monthly income — regardless of algorithm changes.'],
  ['emoji'=>'🧩','name'=>'Stan Store','earn'=>'₦200k–₦3m/month','badge'=>'Creator OS','desc'=>'All-in-one creator store: sell digital products, book 1-on-1 calls, run paid communities, and collect email leads — all from one link. Replacing Linktree for top earners.'],
  ['emoji'=>'🏆','name'=>'Ahrefs / SEMrush','earn'=>'₦500k–₦5m/month','badge'=>'SEO','desc'=>'SEO tools for building affiliate blogs and niche websites that earn passive AdSense + affiliate income. One well-ranked page earns ₦50k–₦500k/month forever.'],
  ['emoji'=>'📝','name'=>'Substack','earn'=>'$1k–$15k/month','badge'=>'Newsletters','desc'=>'Launch a paid newsletter and charge subscribers monthly or annually. 500 subscribers at $10/month = $5,000/month stable, predictable creator income.'],
  ['emoji'=>'🔑','name'=>'Mighty Networks','earn'=>'₦500k–₦10m/month','badge'=>'Community','desc'=>'Build and monetize your own paid community. Charge members monthly and offer courses, live events, and coaching inside one private platform you fully own.'],
  ['emoji'=>'🤝','name'=>'Aspire / Grin (UGC Platforms)','earn'=>'₦10k–₦150k/video','badge'=>'UGC','desc'=>'Platforms where brands search for UGC creators to make authentic product videos. Nigerian creators earn ₦10k–₦150k per video without needing a large following.'],
];

// ── ROUTE: platform detail page ──────────────────────────────────────────────
$platformId = trim($_GET['p'] ?? '');
$activePlatform = null;
if ($platformId) {
    foreach ($socialPlatforms as $p) {
        if ($p['id'] === $platformId) { $activePlatform = $p; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no"/>
<title>Social Money — Hustle Kingdom</title>
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
.hero{background:linear-gradient(135deg,#3b0fa0 0%,#5c2ec4 50%,#7b4fd4 100%);padding:22px 18px 24px;position:relative;overflow:hidden;}
.hero::before{content:'';position:absolute;right:-40px;top:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06);}
.hero::after{content:'';position:absolute;left:60%;bottom:-50px;width:140px;height:140px;border-radius:50%;background:rgba(255,255,255,.04);}
.hero-chip{display:inline-flex;align-items:center;gap:6px;background:rgba(255,255,255,.18);border:1px solid rgba(255,255,255,.28);border-radius:20px;padding:5px 12px;font-size:11px;font-weight:700;color:#fff;font-family:'Bricolage Grotesque',sans-serif;margin-bottom:12px;position:relative;z-index:1;}
.hero-title{font-family:'Bricolage Grotesque',sans-serif;font-size:26px;font-weight:800;color:#fff;line-height:1.15;margin-bottom:6px;position:relative;z-index:1;}
.hero-sub{font-size:13px;color:rgba(255,255,255,.75);line-height:1.55;position:relative;z-index:1;}

/* TABS */
.tab-wrap{padding:14px 16px 0;}
.tabs{display:flex;background:var(--surface);border:1.5px solid var(--border);border-radius:14px;padding:4px;gap:3px;}
.tab{flex:1;padding:9px 6px;border-radius:10px;font-size:12px;font-weight:700;text-align:center;border:none;background:transparent;color:var(--text3);cursor:pointer;font-family:'Bricolage Grotesque',sans-serif;transition:.2s;white-space:nowrap;}
.tab.active{background:#fff;color:var(--text);box-shadow:0 2px 8px rgba(0,0,0,.1);}
.tab-icon{display:block;font-size:14px;margin-bottom:2px;}

/* SECTION HEADS */
.sec-head{padding:20px 16px 10px;font-family:'Bricolage Grotesque',sans-serif;font-size:14px;font-weight:800;color:var(--text);}

/* PLATFORM GRID */
.plat-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:0 16px;}
.plat-card{border-radius:16px;padding:18px 12px 16px;text-align:center;text-decoration:none;color:var(--text);display:flex;flex-direction:column;align-items:center;gap:4px;border:1.5px solid transparent;transition:.15s;cursor:pointer;}
.plat-card:active{transform:scale(.96);}
.plat-emoji{font-size:38px;line-height:1;margin-bottom:6px;display:block;}
.plat-name{font-family:'Bricolage Grotesque',sans-serif;font-size:13.5px;font-weight:800;color:var(--text);margin-bottom:2px;}
.plat-income{font-size:11px;color:var(--text2);font-weight:600;margin-bottom:4px;}
.plat-methods{font-family:'Bricolage Grotesque',sans-serif;font-size:11px;font-weight:800;color:var(--green3);}

/* TOP TOOLS LIST */
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

/* TAB CONTENT */
.tab-content{display:none;}
.tab-content.active{display:block;}

/* ─── DETAIL PAGE ─────────────────────────────────────────────────── */
.detail-page{display:none;position:fixed;top:0;left:50%;transform:translateX(-50%);width:100%;max-width:430px;height:100%;background:#fff;z-index:300;overflow-y:auto;padding-bottom:80px;}
.detail-page.open{display:block;}
.detail-header{display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--border);position:sticky;top:0;background:#fff;z-index:10;}
.detail-back{width:34px;height:34px;border-radius:50%;background:var(--surface);border:1.5px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer;flex-shrink:0;text-decoration:none;color:var(--text);}
.detail-header-name{font-family:'Bricolage Grotesque',sans-serif;font-size:15px;font-weight:800;}

.detail-hero{padding:24px 20px 20px;}
.detail-icon{font-size:60px;margin-bottom:14px;display:block;}
.detail-title{font-family:'Bricolage Grotesque',sans-serif;font-size:22px;font-weight:800;margin-bottom:10px;}
.detail-desc{font-size:13.5px;color:var(--text2);line-height:1.65;}
.detail-earn{display:inline-flex;align-items:center;gap:6px;background:var(--green-light);border:1px solid #b2e0c8;border-radius:20px;padding:6px 14px;font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;color:var(--green3);margin-top:14px;}

.methods-label{font-size:10.5px;font-weight:800;color:var(--text3);letter-spacing:.08em;padding:20px 20px 8px;font-family:'Bricolage Grotesque',sans-serif;}
.method-list{display:flex;flex-direction:column;gap:8px;padding:0 16px;}
.method-item{display:flex;align-items:center;gap:12px;background:var(--surface);border:1.5px solid var(--border);border-radius:12px;padding:12px 14px;}
.method-num{width:28px;height:28px;border-radius:50%;background:var(--green);color:#fff;font-family:'Bricolage Grotesque',sans-serif;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.method-text{font-size:13px;font-weight:600;color:var(--text);}

.detail-cta{margin:20px 16px 0;background:linear-gradient(135deg,#3b0fa0,#7b4fd4);border-radius:14px;padding:16px;text-align:center;text-decoration:none;display:block;}
.detail-cta-label{font-family:'Bricolage Grotesque',sans-serif;font-size:13px;font-weight:800;color:#fff;margin-bottom:4px;}
.detail-cta-sub{font-size:11px;color:rgba(255,255,255,.7);}

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
  <div class="hero-chip">📱 SOCIAL MONEY</div>
  <div class="hero-title">Social Money Platforms</div>
  <div class="hero-sub">Every platform you can monetize online — from social media to freelance, affiliate, and digital products.</div>
</div>


<!-- TABS -->
<div class="tab-wrap">
  <div class="tabs">
    <button class="tab active" onclick="switchTab(this,'platforms')"><span class="tab-icon">📱</span>Platforms</button>
    <button class="tab" onclick="switchTab(this,'tools')"><span class="tab-icon">🛠️</span>Platform Tools</button>
  </div>
</div>

<!-- ══ TAB: PLATFORMS ══ -->
<div class="tab-content active" id="tab-platforms">
<?php foreach ($platformSections as [$label, $indices]): ?>
  <div class="sec-head"><?= htmlspecialchars($label) ?></div>
  <div class="plat-grid">
    <?php foreach ($indices as $idx):
      if (!isset($socialPlatforms[$idx])) continue;
      $p = $socialPlatforms[$idx];
    ?>
      <div class="plat-card" onclick="openPlatform('<?= htmlspecialchars($p['id']) ?>')"
           style="background:<?= $p['bg'] ?>;border-color:<?= $p['border'] ?>;">
        <span class="plat-emoji"><?= $p['icon'] ?></span>
        <div class="plat-name"><?= htmlspecialchars($p['name']) ?></div>
        <div class="plat-income"><?= htmlspecialchars($p['earn']) ?></div>
        <div class="plat-methods"><?= $p['count'] ?> methods</div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
</div>

<!-- ══ TAB: PLATFORM TOOLS ══ -->
<div class="tab-content" id="tab-tools">
  <div class="sec-head">🛠️ Social Money Tools</div>
  <div class="tool-list">
    <?php foreach ($topSocialTools as $t): ?>
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
</div>

<!-- ══ PLATFORM DETAIL OVERLAY ══ -->
<div class="detail-page" id="detail-page">
  <div class="detail-header">
    <a class="detail-back" onclick="closePlatform()" href="#">←</a>
    <span class="detail-header-name" id="detail-header-name"></span>
  </div>
  <div class="detail-hero">
    <span class="detail-icon" id="detail-icon"></span>
    <div class="detail-title" id="detail-title"></div>
    <div class="detail-desc" id="detail-desc"></div>
    <div class="detail-earn" id="detail-earn"></div>
  </div>
  <div class="methods-label" id="detail-methods-label"></div>
  <div class="method-list" id="detail-methods"></div>
  <a class="detail-cta" id="detail-cta" href="#" target="_blank" rel="noopener">
    <div class="detail-cta-label">Open Platform →</div>
    <div class="detail-cta-sub">Visit the platform to get started</div>
  </a>
</div>

<!-- BOTTOM NAV -->
<nav class="botnav">
  <a href="<?= APP_URL ?>/"         class="bnav"><div class="bni">🏠</div><div class="bnl">Home</div></a>
  <a href="<?= APP_URL ?>/hustles"  class="bnav"><div class="bni">💡</div><div class="bnl">Discover</div></a>
  <a href="<?= APP_URL ?>/execute"  class="bnav-ctr"><div class="bnav-ctr-icon">▶</div><div class="bnl" style="font-size:8.5px;font-weight:700;font-family:'Bricolage Grotesque',sans-serif;color:var(--text3);">Execute</div></a>
  <a href="<?= APP_URL ?>/location" class="bnav"><div class="bni">📍</div><div class="bnl">Location</div></a>
  <a href="<?= APP_URL ?>/ai"       class="bnav"><div class="bni">🤖</div><div class="bnl">AI</div></a>
</nav>

<script>
function switchTab(btn, tabId) {
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
  document.getElementById('tab-' + tabId).classList.add('active');
  window.scrollTo({top: 0, behavior: 'smooth'});
}

// ── Platform data (PHP → JS) ─────────────────────────────────────────────────
const PLATFORMS = <?= json_encode(array_values($socialPlatforms), JSON_UNESCAPED_UNICODE) ?>;

function openPlatform(id) {
  const p = PLATFORMS.find(x => x.id === id);
  if (!p) return;

  document.getElementById('detail-header-name').textContent = p.icon + ' ' + p.name;
  document.getElementById('detail-icon').textContent = p.icon;
  document.getElementById('detail-title').textContent = p.name + ' Money Methods';
  document.getElementById('detail-desc').textContent = p.desc;
  document.getElementById('detail-earn').textContent = '💰 ' + p.earn;
  document.getElementById('detail-methods-label').textContent = p.methods.length + ' WAYS TO EARN';

  const ml = document.getElementById('detail-methods');
  ml.innerHTML = p.methods.map((m, i) =>
    `<div class="method-item"><div class="method-num">${i+1}</div><div class="method-text">${m}</div></div>`
  ).join('');

  // Platform URLs
  const urls = {
    'tiktok':'https://tiktok.com','instagram':'https://instagram.com',
    'youtube':'https://youtube.com','facebook':'https://facebook.com',
    'whatsapp':'https://business.whatsapp.com','telegram':'https://telegram.org',
    'x':'https://x.com','linkedin':'https://linkedin.com',
    'substack':'https://substack.com','patreon':'https://patreon.com',
    'gumroad':'https://gumroad.com','udemy':'https://udemy.com',
    'fiverr':'https://fiverr.com','upwork':'https://upwork.com',
    'etsy':'https://etsy.com','amazon-kdp':'https://kdp.amazon.com',
    'teachable':'https://teachable.com','selar':'https://selar.co',
    'redbubble':'https://redbubble.com','99designs':'https://99designs.com',
    'clickbank':'https://clickbank.com','admob-adsense':'https://adsense.google.com',
    'shutterstock':'https://submit.shutterstock.com','sharesale':'https://shareasale.com',
    'toptal':'https://toptal.com','99firms-ppph':'https://peopleperhour.com',
    'freelancer':'https://freelancer.com','guru':'https://guru.com',
    'hackerone':'https://hackerone.com','99translations':'https://proz.com',
    'driverbee':'https://bolt.eu/ng','mtn-momobiz':'https://mtn.com.ng/momo',
    'preply':'https://preply.com','taskrabbit':'https://taskrabbit.com',
    'creative-market':'https://creativemarket.com','studypool':'https://studypool.com',
    'envato':'https://market.envato.com','medium':'https://medium.com/partner-program',
    'soundcloud-distrokid':'https://distrokid.com','digistore24':'https://digistore24.com',
    'warriorplus':'https://warriorplus.com','partnerstack':'https://partnerstack.com',
    'flexoffers':'https://flexoffers.com','rakuten-advertising':'https://rakutenadvertising.com',
    'ezoic':'https://ezoic.com','medianet':'https://media.net',
    'propellerads':'https://propellerads.com','adsterra':'https://adsterra.com',
    'monetag':'https://monetag.com','infolinks':'https://infolinks.com',
    'revcontent':'https://revcontent.com','outbrain':'https://outbrain.com',
    'taboola':'https://taboola.com','payhip':'https://payhip.com',
    'sellfy':'https://sellfy.com','podia':'https://podia.com',
    'kajabi':'https://kajabi.com','skillshare':'https://skillshare.com',
    'storyblocks':'https://storyblocks.com','squarespace':'https://squarespace.com',
    'kofi':'https://ko-fi.com','solidgigs':'https://solidgigs.com',
    'microworkers':'https://microworkers.com','timebucks':'https://timebucks.com',
    'sproutgigs':'https://sproutgigs.com','picoworkers':'https://picoworkers.com',
    'testio':'https://test.io','lionbridge':'https://telusinternational.com',
    'rumble':'https://rumble.com','kick':'https://kick.com',
    'bandcamp':'https://bandcamp.com','viralhog':'https://viralhog.com',
    'creative-fabrica':'https://creativefabrica.com','designbundles':'https://designbundles.net',
    'motion-array':'https://motionarray.com','systemeio':'https://systeme.io',
    'codecanyon':'https://codecanyon.net',
  };
  document.getElementById('detail-cta').href = urls[id] || '#';

  const page = document.getElementById('detail-page');
  page.classList.add('open');
  page.scrollTop = 0;
  document.body.style.overflow = 'hidden';
}

function closePlatform() {
  document.getElementById('detail-page').classList.remove('open');
  document.body.style.overflow = '';
}

// Back gesture
document.getElementById('detail-page').addEventListener('touchstart', function(e) {
  this._ts = e.touches[0].clientX;
}, {passive:true});
document.getElementById('detail-page').addEventListener('touchend', function(e) {
  if (this._ts < 30 && e.changedTouches[0].clientX - this._ts > 60) closePlatform();
}, {passive:true});
</script>
</body>
</html>
