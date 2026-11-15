<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/** Menu photos (kind=image, file under public/assets/) and menu links (kind=link) with their source. */
final class PlaceMenu extends Model
{
    protected static string $table = 'place_menus';
    protected static bool $hasUpdatedAt = false;

    public static function forPlace(int $placeId): array
    {
        return Database::fetchAll('SELECT * FROM place_menus WHERE place_id = :id ORDER BY sort_order, id', ['id' => $placeId]);
    }

    /** @param int[] $placeIds  @return array<int, true> place ids that have at least one menu photo or link */
    public static function placesWithMenus(array $placeIds): array
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
        $out = [];
        foreach (Database::fetchAll('SELECT DISTINCT place_id FROM place_menus WHERE place_id IN (' . implode(', ', $marks) . ')', $params) as $r) {
            $out[(int) $r['place_id']] = true;
        }
        return $out;
    }

    public static function deleteForPlace(int $placeId): void
    {
        Database::query('DELETE FROM place_menus WHERE place_id = :id', ['id' => $placeId]);
    }
}
