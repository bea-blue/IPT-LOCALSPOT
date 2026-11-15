<?php
namespace App\Models;

use SpotCore\Database;
use SpotCore\Model;

/** A user's report of a review. Staff resolve it by hiding the review or dismissing the report. */
final class ReviewReport extends Model
{
    protected static string $table = 'review_reports';
    protected static bool $hasUpdatedAt = false;

    public const REASONS = ['spam' => 'Spam', 'abusive' => 'Abusive', 'off_topic' => 'Off-topic'];
    public const STATUSES = ['open' => 'Open', 'hidden' => 'Review hidden', 'dismissed' => 'Dismissed'];

    public static function findByReporter(int $reviewId, int $userId): ?array
    {
        return Database::fetch('SELECT * FROM review_reports WHERE review_id = :r AND reporter_id = :u LIMIT 1', ['r' => $reviewId, 'u' => $userId]);
    }

    public static function forStaff(string $status = 'open'): array
    {
        return Database::fetchAll(
            'SELECT rr.*, r.comment, r.rating, r.author_name, r.is_hidden, r.place_id, p.name AS place_name,
                    u.name AS reporter_name, h.name AS handler_name
             FROM review_reports rr
             JOIN reviews r ON r.id = rr.review_id
             JOIN places p ON p.id = r.place_id
             JOIN users u ON u.id = rr.reporter_id
             LEFT JOIN users h ON h.id = rr.handled_by
             WHERE rr.status = :status ORDER BY rr.created_at DESC, rr.id DESC',
            ['status' => $status]
        );
    }

    public static function countOpen(): int
    {
        return (int) Database::value("SELECT COUNT(*) FROM review_reports WHERE status = 'open'");
    }

    /** Closes every open report of one review with the same outcome. */
    public static function resolveForReview(int $reviewId, string $status, int $staffId): void
    {
        Database::query(
            "UPDATE review_reports SET status = :status, handled_by = :staff, handled_at = :now WHERE review_id = :review AND status = 'open'",
            ['status' => $status, 'staff' => $staffId, 'now' => date('Y-m-d H:i:s'), 'review' => $reviewId]
        );
    }
}
