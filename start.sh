#!/bin/sh
set -e

echo "=== HustleKingdom Boot ==="
php -v | head -1
php -m | grep -i pdo

export PORT="${PORT:-8080}"

# Replace $PORT in nginx template
envsubst '$PORT' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

echo "Starting PHP-FPM..."
php-fpm -D

echo "Starting Nginx on port $PORT..."
exec nginx -g "daemon off;"
