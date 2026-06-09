<?php
/**
 * HustleKingdom — Database Seeder
 * ─────────────────────────────────────────────────────────
 * Runs the SQL seed files to populate your hustles table
 * from the 488 hustles extracted from the frontend app.
 *
 * USAGE:
 *   1. Upload this file + seed_hustles.sql + seed_roadmaps.sql
 *      to your server root (same folder as index.php)
 *   2. Visit: https://yourdomain.com/seed.php?key=YOUR_SEED_KEY
 *   3. DELETE this file after seeding is done!
 *
 * Set SEED_KEY below to something secret before uploading.
 */

defined('HK_ROOT') || define('HK_ROOT', __DIR__);
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/DB.php';

// ── Security: require a secret key ──────────────────────────────────────────
const SEED_KEY = 'Secretkey123';

if (($_GET['key'] ?? '') !== SEED_KEY) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Pass ?key=YOUR_SEED_KEY in the URL.</p>');
}

$action = $_GET['action'] ?? 'status';
header('Content-Type: text/html; charset=utf-8');

// ── Helper ───────────────────────────────────────────────────────────────────
function runSqlFile(string $filepath): array {
    if (!file_exists($filepath)) {
        return ['ok' => false, 'msg' => "File not found: $filepath"];
    }
    $sql = file_get_contents($filepath);
    $pdo = DB::get();
    $statements = array_filter(
        array_map('trim', explode(";\n", $sql)),
        fn($s) => $s !== '' && !str_starts_with($s, '--')
    );
    $count = 0;
    $errors = [];
    foreach ($statements as $stmt) {
        if (trim($stmt) === '') continue;
        try {
            $pdo->exec($stmt);
            $count++;
        } catch (PDOException $e) {
            $errors[] = htmlspecialchars(substr($stmt, 0, 80)) . ' → ' . $e->getMessage();
        }
    }
    return ['ok' => empty($errors), 'count' => $count, 'errors' => $errors];
}

function badge(bool $ok): string {
    return $ok
        ? '<span style="color:#16a05a;font-weight:700">✅ OK</span>'
        : '<span style="color:#cc3333;font-weight:700">❌ Error</span>';
}

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>HustleKingdom — Seeder</title>
<style>
  body{font-family:system-ui,sans-serif;max-width:800px;margin:40px auto;padding:20px;background:#f8f8f6;color:#0d0d0c;}
  h1{color:#16a05a;}pre{background:#fff;border:1px solid #e0e0d8;padding:12px;border-radius:8px;overflow-x:auto;font-size:13px;}
  .btn{display:inline-block;padding:10px 20px;background:#16a05a;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;margin:6px 4px;}
  .btn-red{background:#cc3333;}
  .card{background:#fff;border:1px solid #e6e6e0;border-radius:12px;padding:20px;margin:16px 0;}
  table{width:100%;border-collapse:collapse;}td,th{padding:8px 12px;border-bottom:1px solid #eee;text-align:left;}
  th{background:#f1f1ee;font-weight:600;}
  .warn{background:#fdf6e3;border:1px solid #e0c060;border-radius:8px;padding:12px 16px;margin:16px 0;}
</style>
</head>
<body>
<h1>👑 HustleKingdom Seeder</h1>

<?php if ($action === 'status'): ?>
<div class="card">
  <h3>Current Database Status</h3>
  <?php
    try {
        $count = DB::one('SELECT COUNT(*) as cnt FROM hustles');
        echo '<p>✅ Database connected.</p>';
        echo '<p><strong>Hustles in DB:</strong> ' . ($count['cnt'] ?? 0) . '</p>';
    } catch (Throwable $e) {
        echo '<p>❌ DB error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>Make sure your <code>config/.env</code> has correct DB credentials.</p>';
    }
  ?>
  <?php
    try {
        $rmCount = DB::one('SELECT COUNT(*) as cnt FROM roadmaps');
        echo '<p><strong>Roadmaps in DB:</strong> ' . ($rmCount['cnt'] ?? 0) . '</p>';
    } catch (Throwable $e) {
        echo '<p><em>Roadmaps table not yet created — run seed_roadmaps.sql first.</em></p>';
    }
  ?>
</div>

<div class="warn">
  ⚠️ <strong>Instructions:</strong><br>
  1. Make sure <code>schema.sql</code> has already been run (gives you the base 15 hustles + table structure).<br>
  2. Click the buttons below in order.<br>
  3. <strong>Delete this file after seeding!</strong>
</div>

<div class="card">
  <h3>Step 1 — Seed 476 Hustles</h3>
  <p>Inserts all hustles from the index.html app. Uses <code>INSERT IGNORE</code> — safe to run multiple times.</p>
  <a class="btn" href="?key=<?= htmlspecialchars(SEED_KEY) ?>&action=seed_hustles">▶ Run seed_hustles.sql</a>
</div>

<div class="card">
  <h3>Step 2 — Create Roadmaps Table + Seed</h3>
  <p>Creates the <code>roadmaps</code> table and seeds 3 detailed roadmaps (Freelance Writing, Social Media, Crypto P2P).</p>
  <a class="btn" href="?key=<?= htmlspecialchars(SEED_KEY) ?>&action=seed_roadmaps">▶ Run seed_roadmaps.sql</a>
</div>

<div class="card">
  <h3>Step 3 — Verify</h3>
  <a class="btn" href="?key=<?= htmlspecialchars(SEED_KEY) ?>&action=verify">🔍 Check Counts</a>
</div>

<?php elseif ($action === 'seed_hustles'): ?>
<div class="card">
  <h3>Seeding Hustles...</h3>
  <?php
    $result = runSqlFile(__DIR__ . '/seed_hustles.sql');
    echo '<p>' . badge($result['ok']) . ' Executed ' . ($result['count'] ?? 0) . ' statements.</p>';
    if (!empty($result['errors'])) {
        echo '<p>Errors (' . count($result['errors']) . '):</p><pre>';
        foreach (array_slice($result['errors'], 0, 20) as $err) {
            echo htmlspecialchars($err) . "\n";
        }
        echo '</pre>';
    }
    // Show count
    try {
        $c = DB::one('SELECT COUNT(*) as cnt FROM hustles');
        echo '<p><strong>Total hustles now:</strong> ' . $c['cnt'] . '</p>';
    } catch (Throwable $e) {}
  ?>
  <a class="btn" href="?key=<?= htmlspecialchars(SEED_KEY) ?>&action=status">← Back</a>
</div>

<?php elseif ($action === 'seed_roadmaps'): ?>
<div class="card">
  <h3>Seeding Roadmaps...</h3>
  <?php
    $result = runSqlFile(__DIR__ . '/seed_roadmaps.sql');
    echo '<p>' . badge($result['ok']) . ' Executed ' . ($result['count'] ?? 0) . ' statements.</p>';
    if (!empty($result['errors'])) {
        echo '<p>Errors:</p><pre>';
        foreach ($result['errors'] as $err) echo htmlspecialchars($err) . "\n";
        echo '</pre>';
    }
    try {
        $c = DB::one('SELECT COUNT(*) as cnt FROM roadmaps');
        echo '<p><strong>Total roadmaps now:</strong> ' . $c['cnt'] . '</p>';
    } catch (Throwable $e) {
        echo '<p>❌ ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
  ?>
  <a class="btn" href="?key=<?= htmlspecialchars(SEED_KEY) ?>&action=status">← Back</a>
</div>

<?php elseif ($action === 'verify'): ?>
<div class="card">
  <h3>Database Verification</h3>
  <?php
    try {
        $total = DB::one('SELECT COUNT(*) as cnt FROM hustles')['cnt'];
        $byDiff = DB::query('SELECT difficulty, COUNT(*) as cnt FROM hustles GROUP BY difficulty');
        $byCat  = DB::query('SELECT category, COUNT(*) as cnt FROM hustles GROUP BY category ORDER BY cnt DESC');
        echo "<p><strong>Total hustles:</strong> $total</p>";
        echo "<h4>By Difficulty</h4><table><tr><th>Difficulty</th><th>Count</th></tr>";
        foreach ($byDiff as $r) echo "<tr><td>{$r['difficulty']}</td><td>{$r['cnt']}</td></tr>";
        echo "</table>";
        echo "<h4>By Category (top 10)</h4><table><tr><th>Category</th><th>Count</th></tr>";
        foreach (array_slice($byCat, 0, 10) as $r) echo "<tr><td>{$r['category']}</td><td>{$r['cnt']}</td></tr>";
        echo "</table>";
    } catch (Throwable $e) {
        echo '<p>❌ ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    try {
        $rm = DB::one('SELECT COUNT(*) as cnt FROM roadmaps')['cnt'];
        echo "<p><strong>Total roadmaps:</strong> $rm</p>";
    } catch (Throwable $e) {
        echo '<p>Roadmaps: table not yet created.</p>';
    }
  ?>
  <br>
  <div class="warn">
    🗑️ <strong>Remember to delete seed.php, seed_hustles.sql, and seed_roadmaps.sql from your server after seeding!</strong>
  </div>
  <a class="btn" href="?key=<?= htmlspecialchars(SEED_KEY) ?>&action=status">← Back</a>
</div>
<?php endif; ?>

</body>
</html>
