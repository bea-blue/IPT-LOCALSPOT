<?php

use App\Support\Roles;
use SpotCore\Testing\TestCase;

final class RolesTest extends TestCase
{
    public function testEmployeeChecksButCannotDecide(): void
    {
        $this->assertTrue(Roles::can('employee', 'view_documents'));
        $this->assertTrue(Roles::can('employee', 'add_check_note'));
        $this->assertFalse(Roles::can('employee', 'decide_submission'), 'customer service approves');
        $this->assertFalse(Roles::can('employee', 'resolve_reports'), 'employee hides reviews');
    }

    public function testManagersManageStaffOnlyBelowDeveloper(): void
    {
        $this->assertTrue(Roles::canManage('developer', 'developer', 'member'));
        $this->assertTrue(Roles::canManage('manager', 'member', 'employee'));
        $this->assertTrue(Roles::canManage('manager', 'employee', 'manager'));
        $this->assertFalse(Roles::canManage('manager', 'member', 'developer'), 'manager creates a developer');
        $this->assertFalse(Roles::canManage('manager', 'developer', 'member'), 'manager removes a developer');
        $this->assertFalse(Roles::canManage('employee', 'member', 'employee'), 'customer service adds staff');
    }

    public function testOwnersAndMembersAreNotStaff(): void
    {
        foreach (['owner', 'member', null, 'admin'] as $role) {
            $this->assertFalse(Roles::isStaff($role), 'is staff: ' . var_export($role, true));
            $this->assertFalse(Roles::can($role, 'view_submissions'), 'can view: ' . var_export($role, true));
        }
    }

    public function testUnknownAbilityIsAnError(): void
    {
        $this->assertThrows(\InvalidArgumentException::class, fn () => Roles::can('developer', 'edit_any_shop'), 'Unknown ability');
    }
}
