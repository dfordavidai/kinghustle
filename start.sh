#!/bin/bash
set -e

echo "=== HustleKingdom Boot ==="
echo "PHP: $(php -v | head -1)"

# Create required dirs
mkdir -p /tmp/sessions /app/logs /tmp

# Verify pdo_mysql
if php -m 2>/dev/null | grep -qi pdo_mysql; then
    echo "✅ pdo_mysql loaded"
else
    echo "⚠️  pdo_mysql not in php -m, trying to load manually..."
    PDO_MYSQL_SO=$(find /nix/store -name "pdo_mysql.so" 2>/dev/null | head -1)
    echo "Found so: $PDO_MYSQL_SO"

    if [ -n "$PDO_MYSQL_SO" ]; then
        for DIR in /etc/php84/conf.d /etc/php/8.4/fpm/conf.d /usr/local/etc/php/conf.d; do
            if [ -d "$DIR" ]; then
                echo "extension=$PDO_MYSQL_SO" > "$DIR/20-pdo_mysql.ini"
                echo "Wrote to $DIR"
            fi
        done
        # Also write a global php.ini addition
        PHP_INI=$(php --ini 2>/dev/null | grep "Loaded Configuration" | awk '{print $NF}')
        if [ -f "$PHP_INI" ]; then
            grep -q "pdo_mysql" "$PHP_INI" || echo "extension=$PDO_MYSQL_SO" >> "$PHP_INI"
        fi
    fi
fi

echo "Extensions: $(php -m 2>/dev/null | grep -i 'pdo\|mysql' | tr '\n' ' ')"

# Substitute $PORT into nginx config
export PORT="${PORT:-8080}"
envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf

echo "Starting on port $PORT..."

# Find and start PHP-FPM
FPM_BIN=$(command -v php-fpm84 2>/dev/null || command -v php-fpm 2>/dev/null)
FPM_CONF=$(ls /etc/php84/php-fpm.conf /etc/php-fpm.conf /etc/php/8.4/fpm/php-fpm.conf 2>/dev/null | head -1)

echo "FPM binary: $FPM_BIN"
echo "FPM config: $FPM_CONF"

if [ -n "$FPM_CONF" ]; then
    $FPM_BIN --nodaemonize --fpm-config "$FPM_CONF" &
else
    $FPM_BIN --nodaemonize &
fi
PHP_PID=$!
sleep 1

# Start Nginx
nginx -c /tmp/nginx.conf -g "daemon off;" &
NGINX_PID=$!

echo "✅ Boot complete. PHP-FPM PID=$PHP_PID, Nginx PID=$NGINX_PID"

trap "kill $PHP_PID $NGINX_PID 2>/dev/null; exit" SIGTERM SIGINT
wait $NGINX_PID
