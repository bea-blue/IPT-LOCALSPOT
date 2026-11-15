<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/**
 * Photos of a place (table place_images). Each place can have several;
 * sort_order 1 is the photo used on its result card. The path is relative to
 * public/assets/ and is only ever written by database/import-listings.php,
 * never taken from a visitor or the admin form.
 */
final class PlaceImage extends Model
{
    protected static string $table = 'place_images';
    protected static bool $hasUpdatedAt = false;

    /** All photos of one place, in display order. */
    public static function forPlace(int $placeId): array
    {
        return Database::fetchAll(
            'SELECT * FROM place_images WHERE place_id = :id ORDER BY sort_order, id',
            ['id' => $placeId]
        );
    }

    /**
     * The card photo (lowest sort_order) for each of the given places.
     * @param int[] $placeIds
     * @return array<int, array> keyed by place_id
     */
    public static function coversFor(array $placeIds): array
    {
        $placeIds = array_values(array_unique(array_map('intval', $placeIds)));
        if ($placeIds === []) {
            return [];
        }
        $params = [];
        $marks = [];
        foreach ($placeIds as $i => $id) {
            $marks[] = ":p{$i}";
            $params["p{$i}"] = $id;
        }
        $rows = Database::fetchAll(
            'SELECT * FROM place_images WHERE place_id IN (' . implode(', ', $marks) . ') ORDER BY place_id, sort_order, id',
            $params
        );
        $covers = [];
        foreach ($rows as $row) {
            $covers[(int) $row['place_id']] ??= $row;
        }
        return $covers;
    }

    public static function deleteForPlace(int $placeId): void
    {
        Database::query('DELETE FROM place_images WHERE place_id = :id', ['id' => $placeId]);
    }
}
