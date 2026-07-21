#!/bin/sh

echo "=== HustleKingdom Boot ==="
php -v | head -1

# Ensure session dir exists and is writable
mkdir -p /tmp/sessions
chmod 777 /tmp/sessions

# Ensure nginx temp dirs exist
mkdir -p /tmp/nginx_client_body /tmp/nginx_proxy /tmp/nginx_fastcgi /tmp/nginx_uwsgi /tmp/nginx_scgi
chmod 777 /tmp/nginx_client_body /tmp/nginx_proxy /tmp/nginx_fastcgi /tmp/nginx_uwsgi /tmp/nginx_scgi

export PORT="${PORT:-8080}"
echo "PORT is set to: $PORT"

# Substitute $PORT from /app/nginx.conf → /tmp/nginx.conf
if ! envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf; then
    echo "FATAL: envsubst failed to generate nginx.conf"
    exit 1
fi
echo "--- generated /tmp/nginx.conf ---"
cat /tmp/nginx.conf

echo "Starting PHP-FPM..."
if ! php-fpm -D --force-stderr; then
    echo "FATAL: php-fpm failed to start"
    exit 1
fi

sleep 1

echo "--- php-fpm process check ---"
if ps aux | grep -v grep | grep -q php-fpm; then
    echo "php-fpm is running"
else
    echo "FATAL: no php-fpm process found after startup"
    exit 1
fi

echo "--- nginx config test ---"
if ! nginx -t -c /tmp/nginx.conf -e /dev/stderr; then
    echo "FATAL: nginx config test failed"
    exit 1
fi

echo "Starting Nginx on port $PORT..."
exec nginx -c /tmp/nginx.conf -e /dev/stderr -g "daemon off;"
