<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

final class Category extends Model
{
    protected static string $table = 'categories';
    protected static bool $hasUpdatedAt = false;

    public static function options(): array
    {
        return self::all('name');
    }

    /** Categories with how many PUBLISHED places each has (for the browse tiles). */
    public static function withPublishedCounts(): array
    {
        return Database::fetchAll(
            "SELECT c.id, c.name, COUNT(p.id) AS place_count
             FROM categories c
             LEFT JOIN places p ON p.category_id = c.id AND p.status = 'published'
             GROUP BY c.id, c.name
             ORDER BY c.name"
        );
    }
}
