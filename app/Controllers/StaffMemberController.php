<?php
namespace App\Controllers;

use App\Models\Place;
use App\Models\User;
use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;

/**
 * Developers and managers add staff (an existing account, by email), change staff
 * roles and remove staff. Managers work only below Developer: they cannot create,
 * change or remove a Developer. Nobody changes their own role, and the last
 * developer stays. Staff cannot own shops.
 */
final class StaffMemberController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize();
        return $this->view('staff/members/index', ['title' => 'Staff', 'staff' => User::staff(), 'me' => (int) Auth::id(), 'actor' => Auth::role()], 200, 'layouts/admin');
    }

    public function save(Request $request): Response
    {
        $this->authorize();
        $email = trim((string) $request->input('email', ''));
        $role = (string) $request->input('role', '');
        Session::flash('old', ['email' => $email, 'role' => $role]);
        if (!isset(Roles::STAFF[$role])) {
            return $this->fail('Choose a staff role.');
        }
        $user = User::findByEmail($email);
        if (!$user) {
            return $this->fail('No account uses that email. Ask the person to sign up first, then add them here.');
        }
        if ((int) $user['id'] === (int) Auth::id()) {
            return $this->fail('You cannot change your own role.');
        }
        if (!Roles::canManage(Auth::role(), $user['role'], $role)) {
            return $this->fail('Managers cannot create, change or remove a Developer.');
        }
        if (!Roles::isStaff($user['role']) && Place::forOwner((int) $user['id'])) {
            return $this->fail('That account owns shops. Staff accounts cannot own shops.');
        }
        if ($user['role'] === 'developer' && $role !== 'developer' && User::countRole('developer') <= 1) {
            return $this->fail('LocalSpot needs at least one developer.');
        }
        User::setRole((int) $user['id'], $role);
        Session::flash('old', []);
        Session::flash('success', $user['name'] . ' is now ' . strtolower(Roles::label($role)) . '.');
        return $this->redirect('/admin/staff');
    }

    public function remove(Request $request): Response
    {
        $this->authorize();
        $user = User::find((int) $request->param('id'));
        if (!$user || !Roles::isStaff($user['role'])) {
            abort(404, 'That staff member does not exist.');
        }
        if ((int) $user['id'] === (int) Auth::id()) {
            return $this->fail('You cannot remove yourself.');
        }
        if (!Roles::canManage(Auth::role(), $user['role'], 'member')) {
            return $this->fail('Managers cannot remove a Developer.');
        }
        if ($user['role'] === 'developer' && User::countRole('developer') <= 1) {
            return $this->fail('LocalSpot needs at least one developer.');
        }
        User::setRole((int) $user['id'], 'member');
        Session::flash('success', $user['name'] . ' is no longer staff (now a member).');
        return $this->redirect('/admin/staff');
    }

    private function authorize(): void
    {
        if (!Roles::can(Auth::role(), 'manage_staff')) {
            abort(403, 'Only developers and managers can manage staff.');
        }
    }

    private function fail(string $message): Response
    {
        Session::flash('error', $message);
        return $this->redirect('/admin/staff');
    }
}
