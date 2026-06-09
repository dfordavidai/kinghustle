#!/bin/bash
set -e

echo "=== HustleKingdom Boot ==="
echo "PHP: $(php -v | head -1)"

# Create required dirs
mkdir -p /tmp/sessions /app/logs

# Verify pdo_mysql
if php -m 2>/dev/null | grep -qi pdo_mysql; then
    echo "✅ pdo_mysql loaded"
else
    echo "❌ pdo_mysql missing — searching for .so file..."
    PDO_MYSQL_SO=$(find /nix/store /usr/local/lib/php/extensions -name "pdo_mysql.so" 2>/dev/null | head -1)
    echo "Found: $PDO_MYSQL_SO"

    if [ -n "$PDO_MYSQL_SO" ]; then
        # Write to every possible conf.d
        for DIR in /etc/php84/conf.d /etc/php/8.4/fpm/conf.d /usr/local/etc/php/conf.d /etc/php82/conf.d; do
            if [ -d "$DIR" ]; then
                echo "extension=$PDO_MYSQL_SO" > "$DIR/20-pdo_mysql.ini"
                echo "Wrote to $DIR"
            fi
        done
    fi
fi

echo "Extensions: $(php -m 2>/dev/null | grep -i 'pdo\|mysql' | tr '\n' ' ')"

# Substitute $PORT into nginx config
export PORT="${PORT:-8080}"
envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf

echo "Starting on port $PORT..."

# Start PHP-FPM (php84)
php-fpm84 --nodaemonize --fpm-config /etc/php84/php-fpm.conf &
PHP_PID=$!
sleep 1

# Start Nginx
nginx -c /tmp/nginx.conf -g "daemon off;" &
NGINX_PID=$!

trap "kill $PHP_PID $NGINX_PID 2>/dev/null; exit" SIGTERM SIGINT
wait $NGINX_PID
