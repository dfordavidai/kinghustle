#!/bin/sh
set -e

echo "=== HustleKingdom Boot ==="
php -v | head -1

# Ensure session dir exists and is writable
mkdir -p /tmp/sessions
chmod 777 /tmp/sessions

# Ensure nginx can write its pid and temp files
mkdir -p /tmp/nginx
chmod 777 /tmp/nginx

export PORT="${PORT:-8080}"

# With nixpacks, nginx.conf lives at /app/nginx.conf (not copied to /etc/nginx/)
# Substitute $PORT and write to the location nginx expects
envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf

echo "Starting PHP-FPM..."
php-fpm -D
sleep 1

echo "Starting Nginx on port $PORT..."
exec nginx -c /tmp/nginx.conf -g "daemon off;"
