<?php
/**
 * HustleKingdom — HTTP Response Helpers
 *
 * Usage:
 *   Response::json(['key' => 'value']);
 *   Response::json(['error' => 'bad request'], 400);
 *   Response::redirect('/auth/login.php');
 *   Response::notFound();
 *   Response::forbidden();
 *   Response::success('Saved!', ['id' => 5]);
 *   Response::error('Validation failed', 422, ['field' => 'email']);
 */

class Response {

    // ── JSON ────────────────────────────────────────────────────────────────

    public static function json(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /** Standardised success envelope. */
    public static function success(string $message = 'OK', mixed $data = null, int $status = 200): never {
        self::json([
            'ok'      => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /** Standardised error envelope. */
    public static function error(string $message, int $status = 400, mixed $details = null): never {
        self::json([
            'ok'      => false,
            'error'   => $message,
            'details' => $details,
        ], $status);
    }

    // ── REDIRECTS ───────────────────────────────────────────────────────────

    public static function redirect(string $url, int $status = 302): never {
        http_response_code($status);
        header('Location: ' . $url);
        exit;
    }

    public static function back(string $fallback = '/'): never {
        $ref = $_SERVER['HTTP_REFERER'] ?? $fallback;
        self::redirect($ref);
    }

    // ── HTTP ERRORS ─────────────────────────────────────────────────────────

    public static function notFound(string $message = 'Page not found.'): never {
        http_response_code(404);
        // If API request, return JSON
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            self::json(['error' => $message], 404);
        }
        // Inline HTML 404 page
        $page404 = defined('ROOT_DIR') ? ROOT_DIR . '/pages/404.php' : dirname(__DIR__) . '/pages/404.php';
        if (file_exists($page404)) {
            include $page404;
        } else {
            echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"/>'
               . '<meta name="viewport" content="width=device-width,initial-scale=1"/>'
               . '<title>404 — Hustle Kingdom</title>'
               . '<style>body{font-family:sans-serif;display:flex;align-items:center;justify-content:center;'
               . 'min-height:100vh;margin:0;background:#f8f8f6;color:#0d0d0c;text-align:center;}'
               . 'h1{font-size:48px;margin:0 0 8px;}p{color:#4a4a45;}a{color:#16a05a;font-weight:700;}</style>'
               . '</head><body><div><h1>404</h1><p>' . htmlspecialchars($message) . '</p>'
               . '<p><a href="/">← Back to Home</a></p></div></body></html>';
        }
        exit;
    }

    public static function forbidden(string $message = 'Access denied.'): never {
        http_response_code(403);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            self::json(['error' => $message], 403);
        }
        http_response_code(403);
        echo '<h1>403 — ' . htmlspecialchars($message) . '</h1>';
        exit;
    }

    public static function serverError(string $message = 'Internal server error.'): never {
        http_response_code(500);
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json')) {
            self::json(['error' => $message], 500);
        }
        echo '<h1>500 — ' . htmlspecialchars($message) . '</h1>';
        exit;
    }

    // ── RATE LIMIT ──────────────────────────────────────────────────────────

    public static function tooManyRequests(): never {
        http_response_code(429);
        header('Retry-After: 60');
        self::json(['error' => 'Too many requests. Please slow down.'], 429);
    }
}
