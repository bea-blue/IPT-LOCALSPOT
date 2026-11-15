<?php
namespace App\Controllers;

use App\Models\MenuItem;
use App\Models\Place;
use App\Models\PlaceCheck;
use App\Models\PlaceDocument;
use App\Models\PlaceImage;
use App\Models\PlaceMenu;
use App\Models\ReviewReport;
use App\Models\User;
use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;

/**
 * Staff panel: check submitted shops and decide on them. Staff never edit or
 * delete a shop; they approve, reject, suspend or reinstate it with a reason.
 * Each action checks the staff member's permission (Roles::can) on the server.
 */
final class StaffController extends Controller
{
    public function dashboard(Request $request): Response
    {
        return $this->view('staff/dashboard', [
            'title' => 'Staff dashboard',
            'counts' => Place::counts(),
            'openReports' => ReviewReport::countOpen(),
            'pending' => Place::forStaff('pending'),
            'role' => Auth::role(),
        ], 200, 'layouts/admin');
    }

    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $status = isset(Place::STATUSES[$status]) && $status !== 'draft' ? $status : null;
        return $this->view('staff/submissions/index', [
            'title' => 'Shops', 'places' => Place::forStaff($status), 'status' => $status,
        ], 200, 'layouts/admin');
    }

    public function show(Request $request): Response
    {
        $place = $this->find($request);
        $id = (int) $place['id'];
        return $this->view('staff/submissions/show', [
            'title' => $place['name'],
            'place' => $place,
            'documents' => PlaceDocument::forPlace($id),
            'missing' => PlaceDocument::missingFor($id),
            'checks' => PlaceCheck::forPlace($id),
            'photos' => PlaceImage::forPlace($id),
            'menus' => PlaceMenu::forPlace($id),
            'menuItemCount' => count(MenuItem::forPlace($id)),
            'role' => Auth::role(),
        ], 200, 'layouts/admin');
    }

    public function addNote(Request $request): Response
    {
        $place = $this->find($request);
        $this->authorize('add_check_note');
        $note = trim((string) $request->input('note', ''));
        if (mb_strlen($note) < 3 || mb_strlen($note) > 1000) {
            return $this->back($place, 'error', 'Write a note of 3 to 1000 characters.');
        }
        PlaceCheck::create(['place_id' => (int) $place['id'], 'user_id' => (int) Auth::id(), 'note' => $note]);
        return $this->back($place, 'success', 'Note added.');
    }

    public function approve(Request $request): Response
    {
        $place = $this->find($request);
        $this->authorize('decide_submission');
        if ($place['status'] !== 'pending') {
            return $this->back($place, 'error', 'Only a shop waiting for approval can be approved.');
        }
        if ($missing = PlaceDocument::missingFor((int) $place['id'])) {
            return $this->back($place, 'error', 'Cannot approve: missing ' . implode(' and ', array_map(fn($k) => strtolower(PlaceDocument::KINDS[$k]), $missing)) . '.');
        }
        $now = date('Y-m-d H:i:s');
        Place::updateById((int) $place['id'], ['status' => 'published', 'review_note' => null, 'reviewed_at' => $now,
            'reviewed_by' => (int) Auth::id(), 'approved_at' => $now]);
        $owner = User::find((int) $place['owner_id']);
        if ($owner && $owner['role'] === 'member') {
            User::setRole((int) $owner['id'], 'owner'); // first approved shop makes the account an owner
        }
        return $this->back($place, 'success', 'Approved. "' . $place['name'] . '" is now published.');
    }

    public function reject(Request $request): Response
    {
        $place = $this->find($request);
        $this->authorize('decide_submission');
        if ($place['status'] !== 'pending') {
            return $this->back($place, 'error', 'Only a shop waiting for approval can be rejected.');
        }
        return $this->decide($place, $request, 'rejected', 'Rejected. The owner sees your reason and can fix and resubmit.');
    }

    public function suspend(Request $request): Response
    {
        $place = $this->find($request);
        $this->authorize('suspend_place');
        if (!in_array($place['status'], ['published', 'hidden'], true)) {
            return $this->back($place, 'error', 'Only an approved shop can be suspended.');
        }
        return $this->decide($place, $request, 'suspended', 'Suspended. The shop is off the site and locked until it is reinstated.');
    }

    public function reinstate(Request $request): Response
    {
        $place = $this->find($request);
        $this->authorize('suspend_place');
        if ($place['status'] !== 'suspended') {
            return $this->back($place, 'error', 'This shop is not suspended.');
        }
        return $this->decide($place, $request, 'published', 'Reinstated. The shop is published again.');
    }

    /** Status change that needs a written reason (kept on the listing and shown to the owner). */
    private function decide(array $place, Request $request, string $status, string $message): Response
    {
        $reason = trim((string) $request->input('reason', ''));
        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 500) {
            return $this->back($place, 'error', 'Write a reason of 10 to 500 characters.');
        }
        Place::updateById((int) $place['id'], ['status' => $status, 'review_note' => $reason,
            'reviewed_at' => date('Y-m-d H:i:s'), 'reviewed_by' => (int) Auth::id()]);
        PlaceCheck::create(['place_id' => (int) $place['id'], 'user_id' => (int) Auth::id(),
            'note' => ucfirst(Place::STATUSES[$status] ?? $status) . ': ' . $reason]);
        return $this->back($place, 'success', $message);
    }

    private function find(Request $request): array
    {
        $place = Place::findForStaff((int) $request->param('id'));
        if (!$place || $place['status'] === 'draft') {
            abort(404, 'That shop does not exist or has not been submitted.'); // drafts stay private to the owner
        }
        return $place;
    }

    private function authorize(string $ability): void
    {
        if (!Roles::can(Auth::role(), $ability)) {
            abort(403, 'Your staff role (' . Roles::label(Auth::role()) . ') cannot do this.');
        }
    }

    private function back(array $place, string $type, string $message): Response
    {
        Session::flash($type, $message);
        return $this->redirect('/admin/shops/' . $place['id']);
    }
}
