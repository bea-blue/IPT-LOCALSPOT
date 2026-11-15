<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/** All SQL for places lives here (none in controllers or views). */
final class Place extends Model
{
    protected static string $table = 'places';

    public const SETTINGS = ['indoor' => 'Indoor', 'outdoor' => 'Outdoor', 'both' => 'Indoor & outdoor'];
    /** Listing lifecycle. Owners move draft/rejected -> pending and published <-> hidden; staff decide the rest. */
    public const STATUSES = [
        'draft'     => 'Draft (not submitted)',
        'pending'   => 'Waiting for approval',
        'published' => 'Published',
        'rejected'  => 'Rejected',
        'suspended' => 'Suspended by staff',
        'hidden'    => 'Hidden by owner',
    ];
    /** Billing unit for a published price. Labels read as "per person", "per meal", ... */
    public const PRICE_UNITS = ['person' => 'per person', 'meal' => 'per meal', 'hour' => 'per hour', 'day_pass' => 'per day pass'];

    private const SELECT = 'SELECT p.*, c.name AS category_name, o.name AS owner_name
        FROM places p JOIN categories c ON c.id = p.category_id LEFT JOIN users o ON o.id = p.owner_id';

    /**
     * Published places matching the filters (all conditions combined with AND).
     * @param array{keyword:?string, category_id:?int, max_budget:?float} $filters
     */
    public static function searchPublished(array $filters): array
    {
        $where = ["p.status = 'published'"];
        $params = [];

        if (!empty($filters['keyword'])) {
            // Two distinct placeholders: name OR address contains the keyword.
            $where[] = '(p.name LIKE :kw_name OR p.address LIKE :kw_address)';
            $like = '%' . self::escapeLike($filters['keyword']) . '%';
            $params['kw_name'] = $like;
            $params['kw_address'] = $like;
        }
        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        if ($filters['max_budget'] !== null) {
            // A place matches when its STARTING estimate fits the visitor's budget.
            // Places with no confirmed budget are left out while a budget is set
            // (the page tells the visitor this).
            $where[] = 'p.budget_min IS NOT NULL AND p.budget_min <= :max_budget';
            $params['max_budget'] = $filters['max_budget'];
        }

        return Database::fetchAll(self::SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.name ASC', $params);
    }

    public static function findPublished(int $id): ?array
    {
        return Database::fetch(self::SELECT . " WHERE p.id = :id AND p.status = 'published' LIMIT 1", ['id' => $id]);
    }

    public static function findWithCategory(int $id): ?array
    {
        return Database::fetch(self::SELECT . ' WHERE p.id = :id LIMIT 1', ['id' => $id]);
    }

    /** A place together with its owner's name and email (staff pages). */
    public static function findForStaff(int $id): ?array
    {
        return Database::fetch(
            'SELECT p.*, c.name AS category_name, u.name AS owner_name, u.email AS owner_email, r.name AS reviewer_name
             FROM places p JOIN categories c ON c.id = p.category_id
             LEFT JOIN users u ON u.id = p.owner_id LEFT JOIN users r ON r.id = p.reviewed_by
             WHERE p.id = :id LIMIT 1',
            ['id' => $id]
        );
    }

    /** Listings staff can see: everything an owner has submitted at least once (drafts stay private to the owner). */
    public static function forStaff(?string $status = null): array
    {
        $sql = 'SELECT p.*, c.name AS category_name, u.name AS owner_name, u.email AS owner_email,
                (SELECT COUNT(*) FROM place_documents d WHERE d.place_id = p.id) AS document_count
             FROM places p JOIN categories c ON c.id = p.category_id LEFT JOIN users u ON u.id = p.owner_id
             WHERE p.status <> \'draft\'';
        $params = [];
        if ($status !== null) {
            $sql .= ' AND p.status = :status';
            $params['status'] = $status;
        }
        return Database::fetchAll($sql . " ORDER BY CASE p.status WHEN 'pending' THEN 0 ELSE 1 END, p.submitted_at DESC, p.updated_at DESC", $params);
    }

    /** The owner's own listing, or null when it does not exist or belongs to someone else. */
    public static function findOwned(int $id, int $ownerId): ?array
    {
        return Database::fetch(self::SELECT . ' WHERE p.id = :id AND p.owner_id = :owner LIMIT 1', ['id' => $id, 'owner' => $ownerId]);
    }

    public static function forOwner(int $ownerId): array
    {
        return Database::fetchAll(self::SELECT . ' WHERE p.owner_id = :owner ORDER BY p.updated_at DESC, p.id DESC', ['owner' => $ownerId]);
    }

    /** @return array<string, int> number of listings per status, plus 'total' */
    public static function counts(): array
    {
        $counts = array_fill_keys(array_keys(self::STATUSES), 0) + ['total' => 0];
        foreach (Database::fetchAll('SELECT status, COUNT(*) AS n FROM places GROUP BY status') as $row) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = (int) $row['n'];
            }
            $counts['total'] += (int) $row['n'];
        }
        return $counts;
    }

    /** Makes % and _ in a search term literal characters instead of wildcards. */
    private static function escapeLike(string $value): string
    {
        return strtr($value, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']);
    }
}
