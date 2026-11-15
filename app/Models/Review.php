<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/**
 * Reviews of a place by signed-in users. Shown as soon as they are posted. Only the
 * author edits or deletes a review; staff can only hide one after it was reported.
 */
final class Review extends Model
{
    protected static string $table = 'reviews';
    protected static bool $hasUpdatedAt = false;

    /** Visible reviews; a hidden review is still shown to its own author (with a note). */
    public static function forPlace(int $placeId, ?int $viewerId = null, int $limit = 50): array
    {
        return Database::fetchAll(
            'SELECT * FROM reviews WHERE place_id = :id AND (is_hidden = 0 OR user_id = :me) ORDER BY created_at DESC, id DESC LIMIT ' . max(1, (int) $limit),
            ['id' => $placeId, 'me' => $viewerId ?? 0]
        );
    }

    /**
     * Average rating and count per place, from real reviews only.
     * @param int[] $placeIds
     * @return array<int, array{avg: float, count: int}>
     */
    public static function statsFor(array $placeIds): array
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
        $stats = [];
        foreach (Database::fetchAll(
            'SELECT place_id, AVG(rating) AS avg_rating, COUNT(*) AS n FROM reviews WHERE is_hidden = 0 AND place_id IN (' . implode(', ', $marks) . ') GROUP BY place_id',
            $params
        ) as $row) {
            $stats[(int) $row['place_id']] = ['avg' => round((float) $row['avg_rating'], 1), 'count' => (int) $row['n']];
        }
        return $stats;
    }

    public static function findByUserAndPlace(int $userId, int $placeId): ?array
    {
        return Database::fetch('SELECT * FROM reviews WHERE user_id = :u AND place_id = :p LIMIT 1', ['u' => $userId, 'p' => $placeId]);
    }

    public static function deleteForPlace(int $placeId): void
    {
        Database::query('DELETE FROM reviews WHERE place_id = :id', ['id' => $placeId]);
    }
}
