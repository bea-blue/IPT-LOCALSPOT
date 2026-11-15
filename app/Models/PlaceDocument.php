<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/**
 * Private evidence for a listing (business permit, shop photo, other). The file is
 * stored in storage/uploads/documents under a random name; only staff and the
 * shop's owner can open it, through a controller that checks permission.
 */
final class PlaceDocument extends Model
{
    protected static string $table = 'place_documents';
    protected static bool $hasUpdatedAt = false;

    public const KINDS = ['permit' => 'Business permit', 'shop_photo' => 'Photo of the shop', 'other' => 'Other proof'];
    /** Approval needs at least one of each. */
    public const REQUIRED = ['permit', 'shop_photo'];

    public static function forPlace(int $placeId): array
    {
        return Database::fetchAll('SELECT * FROM place_documents WHERE place_id = :id ORDER BY kind, id', ['id' => $placeId]);
    }

    /** @return string[] required kinds that are still missing */
    public static function missingFor(int $placeId): array
    {
        $have = array_column(Database::fetchAll('SELECT DISTINCT kind FROM place_documents WHERE place_id = :id', ['id' => $placeId]), 'kind');
        return array_values(array_diff(self::REQUIRED, $have));
    }

    public static function path(array $doc): string
    {
        return BASE_PATH . '/storage/uploads/documents/' . basename($doc['stored_name']);
    }
}
