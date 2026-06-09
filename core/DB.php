<?php
/**
 * HustleKingdom — Database Layer
 * PDO singleton. utf8mb4. Strict error mode.
 * Usage: $db = DB::get(); $db->prepare(...)->execute([...]);
 */

require_once dirname(__DIR__) . '/config/app.php';

class DB {

    private static ?PDO $instance = null;

    /** Returns the shared PDO connection, creating it on first call. */
    public static function get(): PDO {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
            );
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_PERSISTENT         => false,
                ]);
            } catch (PDOException $e) {
                if (APP_DEBUG) {
                    throw new RuntimeException('DB connection failed: ' . $e->getMessage());
                }
                http_response_code(503);
                die(json_encode(['error' => 'Service temporarily unavailable.']));
            }
        }
        return self::$instance;
    }

    /**
     * Shorthand: prepare + execute + return all rows.
     * @param string $sql  SQL with named :placeholders
     * @param array  $params  ['key' => value]
     */
    public static function query(string $sql, array $params = []): array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Shorthand: prepare + execute + return first row or null.
     */
    public static function one(string $sql, array $params = []): ?array {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Shorthand: prepare + execute + return last insert ID.
     */
    public static function insert(string $sql, array $params = []): int {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return (int) self::get()->lastInsertId();
    }

    /**
     * Shorthand: prepare + execute + return affected row count.
     */
    public static function run(string $sql, array $params = []): int {
        $stmt = self::get()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /** Begin a transaction. */
    public static function begin(): void {
        self::get()->beginTransaction();
    }

    /** Commit an open transaction. */
    public static function commit(): void {
        self::get()->commit();
    }

    /** Roll back an open transaction. */
    public static function rollback(): void {
        if (self::get()->inTransaction()) {
            self::get()->rollBack();
        }
    }

    /** Prevent instantiation and cloning. */
    private function __construct() {}
    private function __clone() {}
}
