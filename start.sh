#!/bin/bash
set -e

# ── Create session directory ──────────────────────────────────────────────────
mkdir -p /tmp/sessions

# ── Create logs directory ─────────────────────────────────────────────────────
mkdir -p /app/logs

# ── Ensure PDO MySQL extension is enabled ────────────────────────────────────
PHP_INI_DIR=$(php82 --ini 2>/dev/null | grep "Scan for additional" | awk -F': ' '{print $2}')
if [ -n "$PHP_INI_DIR" ]; then
    echo "extension=pdo.so"        > "$PHP_INI_DIR/10_pdo.ini"
    echo "extension=pdo_mysql.so"  > "$PHP_INI_DIR/20_pdo_mysql.ini"
fi

# ── Substitute $PORT into nginx config (Railway sets PORT dynamically) ────────
export PORT="${PORT:-8080}"
envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf

# ── Start PHP-FPM ────────────────────────────────────────────────────────────
php-fpm82 --nodaemonize --fpm-config /etc/php82/php-fpm.conf &
PHP_PID=$!

# Give FPM a moment to start
sleep 1

# ── Start Nginx ───────────────────────────────────────────────────────────────
nginx -c /tmp/nginx.conf -g "daemon off;" &
NGINX_PID=$!

# ── Trap signals so both processes shut down cleanly ─────────────────────────
trap "kill $PHP_PID $NGINX_PID 2>/dev/null; exit" SIGTERM SIGINT

# ── Wait ─────────────────────────────────────────────────────────────────────
wait $NGINX_PID
