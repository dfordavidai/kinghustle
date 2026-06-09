<?php
/**
 * HustleKingdom — Middleware
 *
 * Usage:
 *   Middleware::rateLimit('login', 5, 60);   // 5 attempts per 60s per IP
 *   Middleware::verifyCsrf();                 // abort if POST CSRF invalid
 *   Middleware::sanitize($_POST);             // strip tags, trim all values
 *   Middleware::requireMethod('POST');        // abort if wrong method
 */

require_once dirname(__DIR__) . '/core/Response.php';
require_once dirname(__DIR__) . '/core/Auth.php';

class Middleware {

    // ── RATE LIMITING (session-based, no Redis needed) ───────────────────────

    /**
     * Simple IP+key rate limiter stored in session.
     * @param string $key    identifier (e.g. 'login', 'register')
     * @param int    $max    max attempts in the window
     * @param int    $window seconds
     */
    public static function rateLimit(string $key, int $max = 10, int $window = 60): void {
        Auth::start();
        $ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $sKey   = "rl_{$key}_{$ip}";
        $now    = time();
        $record = $_SESSION[$sKey] ?? ['count' => 0, 'reset_at' => $now + $window];

        if ($now > $record['reset_at']) {
            $record = ['count' => 0, 'reset_at' => $now + $window];
        }

        $record['count']++;
        $_SESSION[$sKey] = $record;

        if ($record['count'] > $max) {
            Response::tooManyRequests();
        }
    }

    // ── CSRF ────────────────────────────────────────────────────────────────

    /**
     * Abort with 403 if the CSRF token in POST/headers doesn't match session.
     */
    public static function verifyCsrf(): void {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!Auth::verifyCsrf($token)) {
            Response::forbidden('Invalid or expired form token. Please refresh and try again.');
        }
    }

    // ── METHOD ──────────────────────────────────────────────────────────────

    public static function requireMethod(string ...$methods): void {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $allowed = array_map('strtoupper', $methods);
        if (!in_array($method, $allowed, true)) {
            http_response_code(405);
            header('Allow: ' . implode(', ', $allowed));
            Response::error('Method not allowed.', 405);
        }
    }

    // ── INPUT SANITISATION ───────────────────────────────────────────────────

    /**
     * Recursively strip tags and trim all string values in an array.
     * Returns clean copy — does not mutate original.
     */
    public static function sanitize(array $data): array {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $clean[$key] = self::sanitize($value);
            } elseif (is_string($value)) {
                $clean[$key] = trim(strip_tags($value));
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    /**
     * Parse JSON request body. Aborts 400 on malformed JSON.
     */
    public static function jsonBody(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Malformed JSON body.', 400);
        }
        return self::sanitize($data);
    }

    /**
     * Validate required fields exist and are non-empty in an array.
     * Returns array of missing field names.
     */
    public static function required(array $data, array $fields): array {
        $missing = [];
        foreach ($fields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                $missing[] = $field;
            }
        }
        return $missing;
    }
}
