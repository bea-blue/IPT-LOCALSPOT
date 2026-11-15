<?php
namespace SpotCore;

use PDO;
use PDOStatement;

/**
 * One shared PDO connection (lazy — only opens when first used).
 * Every query uses prepared statements, so user input can't inject SQL.
 */
final class Database
{
    private static ?PDO $pdo = null;
    private static array $config = [];

    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    /** Lets tests swap in an in-memory SQLite connection. */
    public static function setConnection(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::connect(self::$config);
        }
        return self::$pdo;
    }

    public static function connect(array $c, bool $withDatabase = true): PDO
    {
        $driver = $c['driver'] ?? 'mysql';

        $dsn = match ($driver) {
            'sqlite' => 'sqlite:' . $c['database'],
            default  => sprintf(
                'mysql:host=%s;port=%s;%scharset=%s',
                $c['host'] ?? '127.0.0.1',
                $c['port'] ?? 3306,
                $withDatabase ? 'dbname=' . $c['database'] . ';' : '',
                $c['charset'] ?? 'utf8mb4',
            ),
        };

        return new PDO($dsn, $c['username'] ?? null, $c['password'] ?? null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::query($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    public static function insert(string $table, array $data): int
    {
        $cols = array_keys($data);
        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $cols),
            implode(', ', array_map(fn($c) => ':' . $c, $cols)),
        );
        self::query($sql, $data);
        return (int) self::pdo()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($c) => "{$c} = :set_{$c}", array_keys($data)));
        $params = [];
        foreach ($data as $k => $v) {
            $params["set_{$k}"] = $v;
        }
        return self::query("UPDATE {$table} SET {$set} WHERE {$where}", array_merge($params, $whereParams))->rowCount();
    }

    /** Runs $callback inside a transaction; rolls back if anything throws. */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback();
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
