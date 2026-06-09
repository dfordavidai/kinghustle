#!/bin/bash
set -e

echo "=== HustleKingdom Boot ==="
echo "PHP version: $(php -v | head -1)"

# ── 1. Find the active php.ini and all conf.d directories ──────────────────
PHP_INI=$(php --ini | grep "Loaded Configuration" | awk '{print $NF}')
echo "Loaded php.ini: $PHP_INI"

# Collect every scan dir PHP knows about
SCAN_DIRS=$(php --ini | grep "Additional .ini files parsed" -A 999 | tail -n +2 | grep -oP '/[^,\s]+' | xargs -I{} dirname {} | sort -u)

# Fallback: common Nix/Railway paths
FALLBACK_DIRS="/etc/php82/conf.d /etc/php8/conf.d /etc/php/conf.d /usr/local/etc/php/conf.d /nix/store/*/etc/php82/conf.d"

# ── 2. Find where .so extensions actually live ────────────────────────────
EXT_DIR=$(php -r "echo ini_get('extension_dir');" 2>/dev/null || php -i | grep "extension_dir" | awk '{print $NF}')
echo "Extension dir: $EXT_DIR"

# ── 3. Write pdo + pdo_mysql ini into every conf.d we can find ───────────
enable_ext() {
    local dir="$1"
    if [ -d "$dir" ]; then
        echo "extension=pdo.so"       > "$dir/05-pdo.ini"
        echo "extension=pdo_mysql.so" > "$dir/06-pdo_mysql.ini"
        echo "  → Wrote ini files to $dir"
    fi
}

for d in $SCAN_DIRS $FALLBACK_DIRS; do
    enable_ext "$d"
done

# Also write next to the loaded php.ini if we found it
if [ -f "$PHP_INI" ]; then
    INI_DIR=$(dirname "$PHP_INI")
    enable_ext "$INI_DIR/conf.d"
fi

# ── 4. Confirm pdo_mysql is now loaded ────────────────────────────────────
if php -m 2>/dev/null | grep -qi pdo_mysql; then
    echo "✅ pdo_mysql loaded successfully"
else
    echo "⚠️  pdo_mysql not in -m output — trying direct extension path..."
    # Last resort: if .so exists, add full path to php.ini itself
    PDO_MYSQL_SO=$(find /nix /usr -name "pdo_mysql.so" 2>/dev/null | head -1)
    if [ -n "$PDO_MYSQL_SO" ] && [ -f "$PHP_INI" ]; then
        echo "extension=$PDO_MYSQL_SO" >> "$PHP_INI"
        echo "  → Added $PDO_MYSQL_SO directly to $PHP_INI"
    fi
fi

echo "Loaded extensions: $(php -m 2>/dev/null | tr '\n' ' ')"

# ── 5. Start PHP-FPM ─────────────────────────────────────────────────────
echo "Starting PHP-FPM..."
php-fpm82 -F -R &
FPM_PID=$!

# ── 6. Start Nginx ────────────────────────────────────────────────────────
echo "Starting Nginx..."
nginx -g "daemon off;"
