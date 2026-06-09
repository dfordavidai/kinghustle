-- ============================================================
-- HustleKingdom — Complete Database Schema
-- Run this in phpMyAdmin → SQL tab, or via: mysql -u user -p db < schema.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── USERS ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`             VARCHAR(100)     NOT NULL,
  `email`            VARCHAR(191)     NOT NULL,
  `password_hash`    VARCHAR(255)     NOT NULL,
  `phone`            VARCHAR(20)      DEFAULT NULL,
  `city`             VARCHAR(100)     DEFAULT NULL,
  `state`            VARCHAR(100)     DEFAULT NULL,
  `avatar_emoji`     VARCHAR(10)      NOT NULL DEFAULT '👑',
  `is_pro`           TINYINT(1)       NOT NULL DEFAULT 0,
  `pro_expires_at`   DATETIME         DEFAULT NULL,
  `ref_code`         VARCHAR(12)      NOT NULL,
  `referred_by`      INT UNSIGNED     DEFAULT NULL,
  `streak_count`     SMALLINT         NOT NULL DEFAULT 0,
  `streak_last_date` DATE             DEFAULT NULL,
  `email_verified`   TINYINT(1)       NOT NULL DEFAULT 0,
  `verify_token`     VARCHAR(64)      DEFAULT NULL,
  `reset_token`      VARCHAR(64)      DEFAULT NULL,
  `reset_expires_at` DATETIME         DEFAULT NULL,
  `is_active`        TINYINT(1)       NOT NULL DEFAULT 1,
  `last_seen_at`     DATETIME         DEFAULT NULL,
  `created_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email`    (`email`),
  UNIQUE KEY `uq_ref_code` (`ref_code`),
  KEY `idx_referred_by`    (`referred_by`),
  KEY `idx_is_pro`         (`is_pro`, `pro_expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── HUSTLES ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `hustles` (
  `id`              INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`            VARCHAR(150)     NOT NULL,
  `slug`            VARCHAR(191)     NOT NULL,
  `emoji`           VARCHAR(10)      NOT NULL DEFAULT '💼',
  `category`        VARCHAR(50)      NOT NULL,
  `description`     TEXT             NOT NULL,
  `income_min`      INT UNSIGNED     NOT NULL DEFAULT 0,
  `income_max`      INT UNSIGNED     NOT NULL DEFAULT 0,
  `income_period`   ENUM('day','week','month') NOT NULL DEFAULT 'month',
  `difficulty`      ENUM('beginner','intermediate','advanced') NOT NULL DEFAULT 'beginner',
  `capital_needed`  INT UNSIGNED     NOT NULL DEFAULT 0,
  `skills_needed`   JSON             DEFAULT NULL,
  `location_tags`   JSON             DEFAULT NULL,   -- ["Lagos","Abuja","Online"]
  `steps`           JSON             DEFAULT NULL,   -- getting started steps
  `is_featured`     TINYINT(1)       NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1)       NOT NULL DEFAULT 1,
  `view_count`      INT UNSIGNED     NOT NULL DEFAULT 0,
  `save_count`      INT UNSIGNED     NOT NULL DEFAULT 0,
  `created_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slug`      (`slug`),
  KEY `idx_category`        (`category`),
  KEY `idx_featured`        (`is_featured`),
  KEY `idx_difficulty`      (`difficulty`),
  FULLTEXT KEY `ft_search`  (`name`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── INCOME LOG ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `income_log` (
  `id`          INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED     NOT NULL,
  `hustle_id`   INT UNSIGNED     DEFAULT NULL,
  `custom_name` VARCHAR(150)     DEFAULT NULL,
  `amount`      DECIMAL(12,2)    NOT NULL,
  `note`        TEXT             DEFAULT NULL,
  `logged_at`   DATE             NOT NULL,
  `created_at`  DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_date`   (`user_id`, `logged_at`),
  KEY `idx_hustle`      (`hustle_id`),
  CONSTRAINT `fk_income_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_income_hustle` FOREIGN KEY (`hustle_id`) REFERENCES `hustles`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── GOALS ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `goals` (
  `id`             INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED     NOT NULL,
  `title`          VARCHAR(200)     NOT NULL,
  `emoji`          VARCHAR(10)      NOT NULL DEFAULT '🎯',
  `target_amount`  DECIMAL(12,2)    NOT NULL DEFAULT 0,
  `current_amount` DECIMAL(12,2)    NOT NULL DEFAULT 0,
  `deadline`       DATE             DEFAULT NULL,
  `is_completed`   TINYINT(1)       NOT NULL DEFAULT 0,
  `completed_at`   DATETIME         DEFAULT NULL,
  `created_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_goals` (`user_id`, `is_completed`),
  CONSTRAINT `fk_goals_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── SAVED HUSTLES ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `saved_hustles` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED NOT NULL,
  `hustle_id`  INT UNSIGNED NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_hustle` (`user_id`, `hustle_id`),
  KEY `idx_user_saved`  (`user_id`),
  CONSTRAINT `fk_saved_user`   FOREIGN KEY (`user_id`)   REFERENCES `users`(`id`)   ON DELETE CASCADE,
  CONSTRAINT `fk_saved_hustle` FOREIGN KEY (`hustle_id`) REFERENCES `hustles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── SUBSCRIPTIONS ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id`                INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`           INT UNSIGNED  NOT NULL,
  `plan`              ENUM('monthly','annual','referral') NOT NULL,
  `paystack_ref`      VARCHAR(100)  DEFAULT NULL,
  `paystack_sub_code` VARCHAR(100)  DEFAULT NULL,
  `amount`            INT UNSIGNED  NOT NULL DEFAULT 0,
  `status`            ENUM('active','cancelled','expired','failed') NOT NULL DEFAULT 'active',
  `started_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at`        DATETIME      NOT NULL,
  `created_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY        `idx_user_sub`    (`user_id`, `status`),
  UNIQUE KEY `uq_paystack_ref` (`paystack_ref`),  -- required for ON DUPLICATE KEY UPDATE idempotency
  CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── REFERRALS ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `referrals` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `referrer_id`    INT UNSIGNED NOT NULL,
  `referred_id`    INT UNSIGNED NOT NULL,
  `reward_granted` TINYINT(1)   NOT NULL DEFAULT 0,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_referred` (`referred_id`),
  KEY `idx_referrer`       (`referrer_id`),
  CONSTRAINT `fk_ref_referrer` FOREIGN KEY (`referrer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ref_referred` FOREIGN KEY (`referred_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── PASSWORD RESETS ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email`      VARCHAR(191) NOT NULL,
  `token`      VARCHAR(64)  NOT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`email`),
  KEY `idx_token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ════════════════════════════════════════════════════════════════════════════
-- SEED DATA — 15 Core Hustles
-- ════════════════════════════════════════════════════════════════════════════

INSERT INTO `hustles`
  (`name`, `slug`, `emoji`, `category`, `description`, `income_min`, `income_max`,
   `income_period`, `difficulty`, `capital_needed`, `skills_needed`, `location_tags`,
   `steps`, `is_featured`)
VALUES
(
  'Freelance Graphics Design', 'freelance-graphics-design', '🎨', 'digital',
  'Create logos, social media posts, flyers and brand assets for Nigerian businesses using Canva or Adobe tools.',
  30000, 300000, 'month', 'beginner', 5000,
  '["Canva","Adobe Illustrator","Creativity","Client Communication"]',
  '["Lagos","Abuja","Port Harcourt","Online"]',
  '["Create a free Canva account","Design 5 sample logos for your portfolio","Open accounts on Fiverr and Upwork","Join Nigerian design Facebook groups","Set pricing starting at ₦5,000 per logo"]',
  1
),
(
  'Social Media Management', 'social-media-management', '📱', 'social',
  'Manage Instagram, Twitter, and Facebook accounts for small businesses. Create content, post daily, and grow their following.',
  50000, 200000, 'month', 'beginner', 0,
  '["Content Creation","Copywriting","Canva","Scheduling Tools"]',
  '["Lagos","Abuja","Online"]',
  '["Pick 2 platforms to specialise in","Create sample content calendars","Reach out to 10 local businesses","Offer a free 2-week trial","Package at ₦30,000-₦80,000/month per client"]',
  1
),
(
  'Dropshipping (Jumia/Konga)', 'dropshipping-nigeria', '🛒', 'trade',
  'Sell products online without holding stock. Source from Alibaba or Aba market, list on Jumia, Konga, or your own WhatsApp store.',
  20000, 500000, 'month', 'intermediate', 10000,
  '["Product Research","Customer Service","Basic Marketing"]',
  '["Lagos","Abuja","Port Harcourt","Nationwide"]',
  '["Research 3 trending product niches","Source samples from Aba or Alibaba","Create a WhatsApp catalogue","List on Jumia Seller Centre","Run ₦2,000 Facebook ads to test demand"]',
  1
),
(
  'Tutoring (Online or In-Person)', 'tutoring-nigeria', '📚', 'education',
  'Teach JAMB, WAEC, NECO subjects, or professional skills like Excel, coding, or English to students and professionals.',
  25000, 150000, 'month', 'beginner', 0,
  '["Subject Expertise","Patience","Communication","Zoom/Google Meet"]',
  '["Lagos","Abuja","Ibadan","Port Harcourt","Online"]',
  '["Choose 1-3 subjects you excel in","Create a simple one-page curriculum","Post on Nairaland and local WhatsApp groups","Set rates (₦3,000-₦10,000/hour)","Move to Google Classroom for online sessions"]',
  1
),
(
  'Content Writing & Copywriting', 'content-writing-copywriting', '✍️', 'digital',
  'Write blog posts, website copy, product descriptions, and email campaigns for Nigerian and international businesses.',
  40000, 400000, 'month', 'beginner', 0,
  '["Writing","Research","SEO Basics","Grammar"]',
  '["Online","Lagos","Abuja"]',
  '["Build a 5-article portfolio on Medium","Create Fiverr and Upwork profiles","Learn basic SEO (free on YouTube)","Target Nigerian startups on LinkedIn","Charge ₦5,000-₦30,000 per article"]',
  1
),
(
  'Video Editing', 'video-editing', '🎬', 'digital',
  'Edit YouTube videos, reels, TikTok content, and ads for content creators and businesses using CapCut or DaVinci Resolve.',
  40000, 350000, 'month', 'intermediate', 0,
  '["CapCut","DaVinci Resolve","Storytelling","Attention to Detail"]',
  '["Online","Lagos"]',
  '["Download CapCut (free) or DaVinci Resolve","Edit 3 sample videos for your portfolio","Contact Nigerian YouTubers with under 50K subs","Offer first edit free","Charge ₦10,000-₦80,000 per video"]',
  0
),
(
  'Airtime & Data Reselling (VTU)', 'vtu-airtime-data-reselling', '📡', 'trade',
  'Buy data and airtime in bulk and resell at profit using VTU platforms. Low capital, daily income, works everywhere in Nigeria.',
  15000, 80000, 'month', 'beginner', 5000,
  '["Basic Maths","Customer Service","WhatsApp Marketing"]',
  '["Nationwide","Lagos","Abuja","Kano","Port Harcourt"]',
  '["Register on VTU platforms like VTpass or Recharge and Get","Fund your wallet with ₦5,000","Set up a WhatsApp Business account","Share your data prices in WhatsApp groups","Reinvest profits to grow wallet balance"]',
  1
),
(
  'Ride-Hailing Driver (Bolt/Uber)', 'ride-hailing-driver', '🚗', 'transport',
  'Drive passengers using Bolt or Uber in major Nigerian cities. Set your own hours. Good earnings in morning and evening rush.',
  80000, 250000, 'month', 'beginner', 0,
  '["Valid Drivers Licence","Clean Car","Navigation Apps","Customer Service"]',
  '["Lagos","Abuja","Port Harcourt","Ibadan"]',
  '["Download Bolt Driver or Uber Driver app","Upload documents (licence, car papers, photos)","Attend verification appointment","Start driving — peak hours 6-9am and 4-8pm","Track earnings daily in the app"]',
  0
),
(
  'Mini Importation', 'mini-importation', '🌍', 'trade',
  'Import trending products from China (1688, Alibaba) and sell on social media, Jumia, or in your local market for 200-500% profit.',
  50000, 800000, 'month', 'intermediate', 30000,
  '["Product Research","Negotiation","Social Media Marketing","Inventory Management"]',
  '["Lagos","Kano","Aba","Nationwide"]',
  '["Research trending products using TikTok and Jumia","Contact suppliers on 1688.com","Use a freight forwarder (e.g. Ekohub)","Sell via WhatsApp catalogue and Instagram","Reinvest first profit into larger orders"]',
  1
),
(
  'Affiliate Marketing', 'affiliate-marketing', '🔗', 'digital',
  'Earn commissions promoting Nigerian products (Jumia, Konga affiliates) or international products (Amazon, ClickBank) through social media or blogs.',
  20000, 500000, 'month', 'beginner', 0,
  '["Content Creation","Social Media","Basic SEO","Email Marketing"]',
  '["Online"]',
  '["Sign up for Jumia KOL affiliate program","Pick 3 product categories you understand","Create a TikTok or Instagram page around those products","Post 2 reviews per day with your affiliate link","Scale to a blog for passive SEO traffic"]',
  1
),
(
  'Catering / Small Chops Business', 'catering-small-chops', '🍱', 'food',
  'Cook and sell small chops, jollof rice, or lunch boxes to offices, events, and individuals. High demand, strong repeat customers.',
  30000, 200000, 'month', 'beginner', 15000,
  '["Cooking","Food Hygiene","Packaging","WhatsApp Marketing"]',
  '["Lagos","Abuja","Port Harcourt","Ibadan","Nationwide"]',
  '["Perfect 3-5 signature dishes","Buy packaging materials (takeaway packs)","Set up WhatsApp Business with food photos","Start with friends/family orders","Target office areas for bulk lunch orders"]',
  0
),
(
  'Fashion Design & Tailoring', 'fashion-design-tailoring', '👗', 'creative',
  'Sew Ankara outfits, corporate wear, and casual clothes. Nigerian fashion market is booming — skilled tailors are always in demand.',
  40000, 300000, 'month', 'intermediate', 50000,
  '["Sewing","Pattern Making","Fashion Sense","Customer Measurement"]',
  '["Lagos","Abuja","Ibadan","Nationwide"]',
  '["Complete a tailoring apprenticeship (3-6 months) or use YouTube","Buy a sewing machine (₦40,000-₦80,000)","Photograph your first 10 outfits","Post on Instagram and join fashion WhatsApp groups","Charge ₦5,000-₦50,000 per outfit depending on complexity"]',
  0
),
(
  'Phone Repair & Accessories', 'phone-repair-accessories', '📱', 'tech-trade',
  'Fix cracked screens, batteries, and software issues. Sell phone cases, chargers, and accessories. Constant demand across Nigeria.',
  50000, 300000, 'month', 'intermediate', 50000,
  '["Technical Skills","Patience","Parts Sourcing","Customer Service"]',
  '["Lagos","Abuja","Kano","Aba","Nationwide"]',
  '["Learn repair basics via YouTube (JerryRigEverything etc.)","Buy basic tools (₦15,000 kit)","Source spare parts from Computer Village Lagos or Aba","Start with screen replacements","Set up a roadside or market stall"]',
  0
),
(
  'Real Estate Agency (Omo-Onile)', 'real-estate-agency', '🏘️', 'realestate',
  'Connect property buyers/renters with landlords and earn 5-10% commission. No capital needed, just local contacts and hustle.',
  0, 1000000, 'month', 'intermediate', 0,
  '["Negotiation","Local Market Knowledge","Communication","Networking"]',
  '["Lagos","Abuja","Port Harcourt","Ibadan"]',
  '["Study property prices in 2 neighbourhoods","Network with landlords and property owners","List properties on PropertyPro and Nigeria Property Centre","Market on WhatsApp and Facebook groups","Charge 10% of annual rent as commission"]',
  1
),
(
  'AI Prompt Engineering & Tools', 'ai-prompt-engineering', '🤖', 'ai',
  'Help businesses use AI tools (ChatGPT, Midjourney, Claude) to write copy, create images, and automate tasks. New and lucrative skill.',
  60000, 500000, 'month', 'beginner', 0,
  '["ChatGPT","Prompt Writing","Business Analysis","Communication"]',
  '["Online","Lagos","Abuja"]',
  '["Learn prompting on Learn Prompting (free website)","Build 5 case studies showing AI solving business problems","Target SMEs on LinkedIn","Offer AI audit packages starting at ₦50,000","Package recurring retainers for ongoing AI support"]',
  1
);

-- ── VERIFY SEED ──────────────────────────────────────────────────────────────
-- SELECT COUNT(*) as hustle_count FROM hustles;   -- should return 15
