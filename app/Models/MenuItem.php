<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/** Typed-out menu items (section, name, price) with the source they were read from. */
final class MenuItem extends Model
{
    protected static string $table = 'menu_items';
    protected static bool $hasUpdatedAt = false;

    public static function forPlace(int $placeId): array
    {
        return Database::fetchAll('SELECT * FROM menu_items WHERE place_id = :id ORDER BY sort_order, id', ['id' => $placeId]);
    }

    public static function deleteForPlace(int $placeId): void
    {
        Database::query('DELETE FROM menu_items WHERE place_id = :id', ['id' => $placeId]);
    }
}
