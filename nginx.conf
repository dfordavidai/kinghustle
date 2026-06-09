<?php
// HustleKingdom DB Diagnostic — DELETE after confirming everything works
header('Content-Type: text/plain');

echo "=== PHP Info ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Extension dir: " . ini_get('extension_dir') . "\n\n";

echo "=== PDO Status ===\n";
echo "PDO loaded: "        . (extension_loaded('pdo')       ? "✅ YES" : "❌ NO") . "\n";
echo "pdo_mysql loaded: "  . (extension_loaded('pdo_mysql') ? "✅ YES" : "❌ NO") . "\n";

if (class_exists('PDO')) {
    echo "PDO drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
}

echo "\n=== DB Env Vars ===\n";
$keys = ['MYSQL_URL','DATABASE_URL','DB_HOST','DB_PORT','DB_NAME','DB_USER','MYSQLHOST','MYSQLPORT','MYSQLDATABASE','MYSQLUSER'];
foreach ($keys as $k) {
    $v = $_ENV[$k] ?? $_SERVER[$k] ?? getenv($k) ?: null;
    if ($v !== null) {
        $display = (stripos($k,'pass')!==false || stripos($k,'url')!==false) ? substr($v,0,10).'***' : $v;
        echo "  $k = $display\n";
    }
}

echo "\n=== Connection Test ===\n";
// Try MYSQL_URL first
$mysql_url = $_ENV['MYSQL_URL'] ?? $_SERVER['MYSQL_URL'] ?? getenv('MYSQL_URL') ?: null;
if ($mysql_url) {
    $p = parse_url($mysql_url);
    $host = $p['host'] ?? '';
    $port = $p['port'] ?? 3306;
    $name = ltrim($p['path'] ?? '', '/');
    $user = $p['user'] ?? '';
    $pass = isset($p['pass']) ? urldecode($p['pass']) : '';
} else {
    $host = $_ENV['MYSQLHOST'] ?? getenv('MYSQLHOST') ?: (getenv('DB_HOST') ?: '');
    $port = $_ENV['MYSQLPORT'] ?? getenv('MYSQLPORT') ?: (getenv('DB_PORT') ?: 3306);
    $name = $_ENV['MYSQLDATABASE'] ?? getenv('MYSQLDATABASE') ?: (getenv('DB_NAME') ?: '');
    $user = $_ENV['MYSQLUSER'] ?? getenv('MYSQLUSER') ?: (getenv('DB_USER') ?: '');
    $pass = $_ENV['MYSQLPASSWORD'] ?? getenv('MYSQLPASSWORD') ?: (getenv('DB_PASS') ?: '');
}

if ($host && $name && $user) {
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        echo "✅ Connected to $name @ $host:$port\n";
    } catch (Exception $e) {
        echo "❌ Failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  Missing DB vars. Need MYSQL_URL or MYSQLHOST+MYSQLDATABASE+MYSQLUSER+MYSQLPASSWORD\n";
}
