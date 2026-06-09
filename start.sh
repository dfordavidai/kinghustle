#!/bin/sh
set -e

echo "=== HustleKingdom Boot ==="
php -v | head -1

# Ensure session dir exists and is writable
mkdir -p /tmp/sessions
chmod 777 /tmp/sessions

export PORT="${PORT:-8080}"
envsubst '$PORT' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

echo "Starting PHP-FPM..."
php-fpm -D
sleep 1

echo "Starting Nginx on port $PORT..."
exec nginx -g "daemon off;"
