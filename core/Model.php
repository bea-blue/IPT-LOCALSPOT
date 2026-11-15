<?php
namespace SpotCore;

/**
 * Tiny base model: each child sets protected static $table and gets
 * find / all / create / updateById / deleteById. All values are bound
 * as prepared-statement parameters.
 */
abstract class Model
{
    protected static string $table;
    protected static bool $hasUpdatedAt = true;

    public static function find(int $id): ?array
    {
        return Database::fetch(sprintf('SELECT * FROM %s WHERE id = :id LIMIT 1', static::$table), ['id' => $id]);
    }

    public static function all(string $orderBy = 'id'): array
    {
        return Database::fetchAll(sprintf('SELECT * FROM %s ORDER BY %s', static::$table, $orderBy));
    }

    public static function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $data += ['created_at' => $now];
        if (static::$hasUpdatedAt) {
            $data += ['updated_at' => $now];
        }
        return Database::insert(static::$table, $data);
    }

    public static function updateById(int $id, array $data): int
    {
        if (static::$hasUpdatedAt) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return Database::update(static::$table, $data, 'id = :pk', ['pk' => $id]);
    }

    public static function deleteById(int $id): int
    {
        return Database::query(sprintf('DELETE FROM %s WHERE id = :id', static::$table), ['id' => $id])->rowCount();
    }
}
