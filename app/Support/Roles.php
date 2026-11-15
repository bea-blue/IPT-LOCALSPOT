<?php
namespace App\Support;

/**
 * Who may do what. Every staff action checks Roles::can() on the server.
 * Staff never create, edit or delete shops or reviews: owners manage their own
 * shops, users manage their own reviews.
 */
final class Roles
{
    public const STAFF = ['developer' => 'Developer', 'manager' => 'Manager', 'employee' => 'Customer Service'];
    public const ALL = self::STAFF + ['owner' => 'Shop owner', 'member' => 'Member'];

    private const ABILITIES = [
        'view_submissions'  => ['developer', 'manager', 'employee'],
        'view_documents'    => ['developer', 'manager', 'employee'],
        'add_check_note'    => ['developer', 'manager', 'employee'],
        'decide_submission' => ['developer', 'manager'],   // approve / reject
        'suspend_place'     => ['developer', 'manager'],   // suspend / reinstate
        'view_reports'      => ['developer', 'manager', 'employee'],
        'resolve_reports'   => ['developer', 'manager'],   // hide review / dismiss report
        'manage_staff'      => ['developer', 'manager'],   // managers only below Developer (see canManage)
    ];

    public static function isStaff(?string $role): bool
    {
        return $role !== null && isset(self::STAFF[$role]);
    }

    public static function can(?string $role, string $ability): bool
    {
        if (!isset(self::ABILITIES[$ability])) {
            throw new \InvalidArgumentException("Unknown ability: {$ability}");
        }
        return $role !== null && in_array($role, self::ABILITIES[$ability], true);
    }

    /**
     * Whether $actor may give $newRole to someone whose role is now $targetRole.
     * Developers manage everyone; managers only Managers and Customer Service,
     * never a Developer (they cannot create, change or remove one).
     */
    public static function canManage(?string $actor, ?string $targetRole, ?string $newRole): bool
    {
        if (!self::can($actor, 'manage_staff')) {
            return false;
        }
        if ($actor === 'developer') {
            return true;
        }
        return $targetRole !== 'developer' && $newRole !== 'developer';
    }

    public static function label(?string $role): string
    {
        return self::ALL[$role] ?? 'Member';
    }

    /** @return array<string, string[]> ability => roles, for the "who can do what" table. */
    public static function matrix(): array
    {
        return self::ABILITIES;
    }
}
