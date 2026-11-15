<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/** Notes staff add while checking a submission. Notes are never edited or deleted. */
final class PlaceCheck extends Model
{
    protected static string $table = 'place_checks';
    protected static bool $hasUpdatedAt = false;

    public static function forPlace(int $placeId): array
    {
        return Database::fetchAll(
            'SELECT c.*, u.name AS staff_name, u.role AS staff_role FROM place_checks c LEFT JOIN users u ON u.id = c.user_id
             WHERE c.place_id = :id ORDER BY c.created_at, c.id',
            ['id' => $placeId]
        );
    }
}
