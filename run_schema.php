<?php
/**
 * HustleKingdom — Schema Runner
 * ─────────────────────────────────────────────────────────
 * Runs migrations/schema.sql to create all database tables.
 *
 * USAGE:
 *   1. This file should already be deployed alongside index.php
 *   2. Visit: https://yourdomain.com/run_schema.php?key=YOUR_SEED_KEY
 *   3. DELETE this file after running it once!
 */

defined('HK_ROOT') || define('HK_ROOT', __DIR__);
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/DB.php';

// ── Security: require a secret key (same one as seed.php) ──────────────────
const SCHEMA_KEY = 'Secretkey123';

if (($_GET['key'] ?? '') !== SCHEMA_KEY) {
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Pass ?key=YOUR_SEED_KEY in the URL.</p>');
}

header('Content-Type: text/html; charset=utf-8');

echo "<h1>👑 HustleKingdom Schema Runner</h1>";

$filepath = HK_ROOT . '/migrations/schema.sql';

if (!file_exists($filepath)) {
    die("<p>❌ File not found: $filepath</p>");
}

$sql = file_get_contents($filepath);
$pdo = DB::get();

// Properly split into individual statements: walk char by char, tracking
// whether we're inside a quoted string, so semicolons inside strings don't
// cause bad splits, and multi-line statements are handled correctly.
function splitSqlStatements(string $sql): array {
    // Strip -- line comments (but not inside strings; schema.sql only uses
    // them at line starts, which is safe enough for this one-time script)
    $lines = explode("\n", $sql);
    $lines = array_filter($lines, fn($l) => !str_starts_with(trim($l), '--'));
    $sql = implode("\n", $lines);

    $statements = [];
    $buffer = '';
    $inString = false;
    $stringChar = '';
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $char = $sql[$i];
        $buffer .= $char;

        if ($inString) {
            if ($char === $stringChar && ($sql[$i - 1] ?? '') !== '\\') {
                $inString = false;
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $inString = true;
            $stringChar = $char;
            continue;
        }

        if ($char === ';') {
            $statements[] = trim($buffer, "; \t\n\r");
            $buffer = '';
        }
    }

    $remainder = trim($buffer, "; \t\n\r");
    if ($remainder !== '') {
        $statements[] = $remainder;
    }

    return array_values(array_filter($statements, fn($s) => $s !== ''));
}

$statements = splitSqlStatements($sql);

$ran = 0;
$errors = [];

foreach ($statements as $stmt) {
    // Strip trailing semicolon if present
    $stmt = rtrim($stmt, "; \t\n\r");
    if ($stmt === '') continue;

    try {
        $pdo->exec($stmt);
        $ran++;
    } catch (PDOException $e) {
        $errors[] = $e->getMessage() . ' -- in statement: ' . substr($stmt, 0, 80) . '...';
    }
}

echo "<p>✅ Executed $ran statement(s).</p>";

if ($errors) {
    echo "<h3>⚠️ Errors:</h3><ul>";
    foreach ($errors as $err) {
        echo "<li>" . htmlspecialchars($err) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>🎉 No errors. Your tables should now exist.</p>";
}

echo "<p><strong>Now delete this file (run_schema.php) from your server/repo.</strong></p>";
echo "<p>Next: visit <code>/seed?key=" . SCHEMA_KEY . "</code> to seed hustles + roadmaps.</p>";
