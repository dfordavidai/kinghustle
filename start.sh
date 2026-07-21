#!/bin/sh
set -e

echo "=== HustleKingdom Boot ==="
php -v | head -1

# Ensure session dir exists and is writable
mkdir -p /tmp/sessions
chmod 777 /tmp/sessions

# Ensure nginx temp dirs exist
mkdir -p /tmp/nginx_client_body /tmp/nginx_proxy /tmp/nginx_fastcgi /tmp/nginx_uwsgi /tmp/nginx_scgi
chmod 777 /tmp/nginx_client_body /tmp/nginx_proxy /tmp/nginx_fastcgi /tmp/nginx_uwsgi /tmp/nginx_scgi

export PORT="${PORT:-8080}"

# Substitute $PORT from /app/nginx.conf → /tmp/nginx.conf
envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf

echo "Starting PHP-FPM..."
php-fpm -D --force-stderr
sleep 1

echo "--- php-fpm process check ---"
ps aux | grep '[p]hp-fpm' || echo "WARNING: no php-fpm process found"

echo "Starting Nginx on port $PORT..."
echo "--- nginx config test ---"
nginx -t -c /tmp/nginx.conf -e /dev/stderr
echo "--- launching nginx ---"
exec nginx -c /tmp/nginx.conf -e /dev/stderr -g "daemon off;"
