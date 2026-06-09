#!/bin/bash
set -e

echo "=== HustleKingdom Boot ==="
echo "PHP: $(php82 -v | head -1)"

# Create required dirs
mkdir -p /tmp/sessions /app/logs

# --- Diagnose what PHP sees ---
echo "Extension dir: $(php82 -r 'echo ini_get('"'"'extension_dir'"'"');')"
echo "Loaded modules: $(php82 -m 2>/dev/null | tr '\n' ' ')"

# --- Check if pdo_mysql is loaded. If not, find and load it from Nix store ---
if ! php82 -m 2>/dev/null | grep -qi pdo_mysql; then
    echo "pdo_mysql not loaded — searching Nix store..."

    # Find the actual .so files in /nix/store
    PDO_SO=$(find /nix/store -name "pdo.so" 2>/dev/null | head -1)
    PDO_MYSQL_SO=$(find /nix/store -name "pdo_mysql.so" 2>/dev/null | head -1)

    echo "Found pdo.so: $PDO_SO"
    echo "Found pdo_mysql.so: $PDO_MYSQL_SO"

    # Write absolute-path ini files to /tmp (always writable)
    mkdir -p /tmp/php-ext
    [ -n "$PDO_SO" ]       && echo "extension=$PDO_SO"       > /tmp/php-ext/05-pdo.ini
    [ -n "$PDO_MYSQL_SO" ] && echo "extension=$PDO_MYSQL_SO" > /tmp/php-ext/10-pdo_mysql.ini

    # Try every conf.d directory that exists
    for DIR in /etc/php82/conf.d /etc/php/8.2/fpm/conf.d /usr/local/etc/php/conf.d; do
        if [ -d "$DIR" ]; then
            [ -n "$PDO_SO" ]       && echo "extension=$PDO_SO"       > "$DIR/05-pdo.ini"
            [ -n "$PDO_MYSQL_SO" ] && echo "extension=$PDO_MYSQL_SO" > "$DIR/10-pdo_mysql.ini"
            echo "Wrote ini files to $DIR"
        fi
    done

    # Final check
    if php82 -m 2>/dev/null | grep -qi pdo_mysql; then
        echo "✅ pdo_mysql now loaded"
    else
        echo "⚠️  pdo_mysql still not loaded — will try -d flag at runtime"
        # Pass extra -d flags to php-fpm via env for runtime fallback
        if [ -n "$PDO_MYSQL_SO" ]; then
            export PHP_EXTRA_ARGS="-d extension=$PDO_MYSQL_SO"
        fi
    fi
else
    echo "✅ pdo_mysql already loaded"
fi

# --- Substitute $PORT into nginx config ---
export PORT="${PORT:-8080}"
envsubst '$PORT' < /app/nginx.conf > /tmp/nginx.conf

echo "Starting on port $PORT"

# --- Start PHP-FPM ---
php-fpm82 --nodaemonize --fpm-config /etc/php82/php-fpm.conf &
PHP_PID=$!
sleep 1

# --- Start Nginx ---
nginx -c /tmp/nginx.conf -g "daemon off;" &
NGINX_PID=$!

trap "kill $PHP_PID $NGINX_PID 2>/dev/null; exit" SIGTERM SIGINT
wait $NGINX_PID
