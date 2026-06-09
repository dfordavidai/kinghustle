#!/bin/bash
set -e

# ── Create session directory ──────────────────────────────────────────────────
mkdir -p /tmp/sessions

# ── Create logs directory ─────────────────────────────────────────────────────
mkdir -p /app/logs

# ── Force-enable PDO MySQL in every possible PHP ini location ─────────────────
for DIR in \
    /etc/php82/conf.d \
    /etc/php/8.2/fpm/conf.d \
    /etc/php/8.2/cli/conf.d \
    /usr/local/etc/php/conf.d \
    $(php82 --ini 2>/dev/null | grep "Scan for additional" | awk -F': ' '{print $2}')
do
    if [ -d "$DIR" ]; then
        echo "extension=pdo.so"       > "$DIR/10-pdo.ini"
        echo "extension=pdo_mysql.so" > "$DIR/20-pdo_mysql.ini"
        echo "Wrote extensions to $DIR"
    fi
done

# ── Verify pdo_mysql is actually loaded ───────────────────────────────────────
php82 -r "
if (!extension_loaded('pdo_mysql')) {
    // Try loading from common nix store paths
    \$paths = glob('/nix/store/*/lib/php/extensions/*/pdo_mysql.so');
    if (!empty(\$paths)) {
        \$ext = \$paths[0];
        echo 'Found at: ' . \$ext . PHP_EOL;
        // Write absolute path ini
        foreach (['/etc/php82/conf.d', '/tmp'] as \$d) {
            @file_put_contents(\$d . '/20-pdo_mysql.ini', 'extension=' . \$ext . PHP_EOL);
        }
    }
} else {
    echo 'pdo_mysql already loaded OK' . PHP_EOL;
}
"

# ── Substitute $PORT into nginx config (Railway sets PORT dynamically) ────────
export PORT="${PORT:-8080}"
envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf

# ── Start PHP-FPM ────────────────────────────────────────────────────────────
php-fpm82 --nodaemonize --fpm-config /etc/php82/php-fpm.conf &
PHP_PID=$!

sleep 1

# ── Start Nginx ───────────────────────────────────────────────────────────────
nginx -c /tmp/nginx.conf -g "daemon off;" &
NGINX_PID=$!

trap "kill $PHP_PID $NGINX_PID 2>/dev/null; exit" SIGTERM SIGINT

wait $NGINX_PID
