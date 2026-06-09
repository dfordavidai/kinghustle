<?php
defined('HK_ROOT') || define('HK_ROOT', dirname(__DIR__));
require_once HK_ROOT . '/config/app.php';
require_once HK_ROOT . '/core/Auth.php';
require_once HK_ROOT . '/core/DB.php';
require_once HK_ROOT . '/core/Response.php';
require_once HK_ROOT . '/core/Middleware.php';

header('Content-Type: application/json');
Auth::start();
Auth::guard();
Middleware::requireMethod('POST');

$userId = Auth::id();
$body   = Middleware::jsonBody();
$result = ['migrated' => [], 'errors' => []];

DB::begin();
try {
    // ── Income log ────────────────────────────────────────────────────────────
    $incomeLog = $body['incomeLog'] ?? [];
    foreach ($incomeLog as $entry) {
        $amount = (float)($entry['amount'] ?? 0);
        if ($amount <= 0) continue;

        $loggedAt = date('Y-m-d', isset($entry['date'])
            ? (is_numeric($entry['date']) ? (int)($entry['date']/1000) : strtotime($entry['date']))
            : time()
        );

        DB::insert(
            'INSERT INTO income_log (user_id, custom_name, amount, logged_at)
             VALUES (:uid, :name, :amt, :dat)',
            [':uid' => $userId, ':name' => $entry['name'] ?? 'Migrated',
             ':amt' => $amount, ':dat' => $loggedAt]
        );
    }
    $result['migrated'][] = 'income_log:' . count($incomeLog);

    // ── Goals ─────────────────────────────────────────────────────────────────
    $goals = $body['goals'] ?? [];
    foreach ($goals as $g) {
        if (empty($g['title'])) continue;
        DB::insert(
            'INSERT INTO goals (user_id, title, emoji, target_amount, current_amount)
             VALUES (:uid, :title, :emoji, :target, :current)',
            [':uid' => $userId, ':title' => $g['title'],
             ':emoji'   => $g['emoji']   ?? '🎯',
             ':target'  => (float)($g['target']  ?? 0),
             ':current' => (float)($g['current'] ?? 0)]
        );
    }
    $result['migrated'][] = 'goals:' . count($goals);

    // ── Saved hustles (by slug or name — best effort) ─────────────────────────
    $savedSlugs = $body['savedHustles'] ?? [];
    foreach ($savedSlugs as $slug) {
        $hustle = DB::one('SELECT id FROM hustles WHERE slug = :s OR name = :s LIMIT 1', [':s' => $slug]);
        if (!$hustle) continue;
        DB::run(
            'INSERT IGNORE INTO saved_hustles (user_id, hustle_id) VALUES (:uid, :hid)',
            [':uid' => $userId, ':hid' => $hustle['id']]
        );
    }
    $result['migrated'][] = 'saved:' . count($savedSlugs);

    DB::commit();
    Response::success('Migration complete.', $result);

} catch (Throwable $e) {
    DB::rollback();
    if (APP_DEBUG) throw $e;
    Response::error('Migration failed. Your data is safe — try again.', 500);
}
