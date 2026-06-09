<?php
/**
 * HustleKingdom — Authentication & Session
 *
 * Usage:
 *   Auth::start();                  // call once in index.php
 *   Auth::login($userId);           // after verifying password
 *   Auth::logout();                 // destroy session
 *   Auth::user();                   // returns full user row or null
 *   Auth::id();                     // returns user ID or null
 *   Auth::isLoggedIn();             // bool
 *   Auth::isPro();                  // bool — server-side check only
 *   Auth::guard();                  // abort 401/redirect if not logged in
 *   Auth::guardPro();               // abort 403 if not Pro
 *   Auth::csrf();                   // get token for forms
 *   Auth::verifyCsrf($token);       // validate submitted token
 */

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/core/DB.php';

class Auth {

    private static ?array $cachedUser = null;

    // ── BOOT ────────────────────────────────────────────────────────────────

    public static function start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'domain'   => '',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    // ── LOGIN / LOGOUT ───────────────────────────────────────────────────────

    public static function login(int $userId): void {
        session_regenerate_id(true);
        $_SESSION['hk_uid']    = $userId;
        $_SESSION['hk_login']  = time();
        self::$cachedUser      = null;
        // JS-readable flag so api.js isLoggedIn() / HK_MIGRATE can detect the session.
        // Intentionally NOT httponly — it carries no secret, just signals presence.
        setcookie('hk_authed', '1', [
            'expires'  => time() + SESSION_LIFETIME,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
    }

    public static function logout(): void {
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        // Expire the JS-readable auth flag
        setcookie('hk_authed', '', [
            'expires'  => time() - 42000,
            'path'     => '/',
            'domain'   => '',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        session_destroy();
    }

    // ── USER DATA ────────────────────────────────────────────────────────────

    public static function id(): ?int {
        return isset($_SESSION['hk_uid']) ? (int)$_SESSION['hk_uid'] : null;
    }

    public static function isLoggedIn(): bool {
        return isset($_SESSION['hk_uid']);
    }

    /**
     * Returns the authenticated user row (cached per request).
     * Excludes password_hash.
     */
    public static function user(): ?array {
        if (!self::isLoggedIn()) return null;
        if (self::$cachedUser !== null) return self::$cachedUser;

        $user = DB::one(
            'SELECT id, name, email, phone, city, state, avatar_emoji,
                    is_pro, pro_expires_at, ref_code, streak_count,
                    streak_last_date, created_at
             FROM users WHERE id = :id AND is_active = 1 LIMIT 1',
            [':id' => self::id()]
        );

        self::$cachedUser = $user ?: null;
        if (!self::$cachedUser) self::logout(); // account deleted mid-session
        return self::$cachedUser;
    }

    /** Refresh cached user (call after updating profile). */
    public static function refresh(): void {
        self::$cachedUser = null;
    }

    // ── PRO STATUS ───────────────────────────────────────────────────────────

    /**
     * Source of truth for Pro status — DB only, never trust client JS.
     */
    public static function isPro(): bool {
        $user = self::user();
        if (!$user) return false;
        if (!(bool)$user['is_pro']) return false;
        if (empty($user['pro_expires_at'])) return false;
        return strtotime($user['pro_expires_at']) > time();
    }

    // ── GUARDS ───────────────────────────────────────────────────────────────

    /**
     * Abort with 401 JSON or redirect to login for HTML requests.
     */
    public static function guard(): void {
        if (self::isLoggedIn()) return;

        if (self::wantsJson()) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthenticated', 'message' => 'Login required.']);
            exit;
        }
        header('Location: ' . APP_URL . '/auth/login?next=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }

    /**
     * Abort with 403 if user is not Pro.
     */
    public static function guardPro(): void {
        self::guard();
        if (self::isPro()) return;

        if (self::wantsJson()) {
            http_response_code(403);
            echo json_encode(['error' => 'pro_required', 'message' => 'Upgrade to Pro to access this feature.']);
            exit;
        }
        header('Location: ' . APP_URL . '/upgrade');
        exit;
    }

    // ── CSRF ─────────────────────────────────────────────────────────────────

    public static function csrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool {
        if (empty($token) || empty($_SESSION['csrf_token'])) return false;
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    // ── HELPERS ──────────────────────────────────────────────────────────────

    private static function wantsJson(): bool {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $ct     = $_SERVER['CONTENT_TYPE'] ?? '';
        return str_contains($accept, 'application/json')
            || str_contains($ct, 'application/json')
            || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');
    }
}
