<?php
namespace App\Controllers;

use App\Models\Review;
use App\Models\ReviewReport;
use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;

/**
 * Reported reviews. Staff can only act on a review someone reported: hide it
 * (it disappears from the public page) or dismiss the report. Nobody on staff
 * can edit or delete a review.
 */
final class StaffReportController extends Controller
{
    public function index(Request $request): Response
    {
        $status = (string) $request->query('status', 'open');
        $status = isset(ReviewReport::STATUSES[$status]) ? $status : 'open';
        return $this->view('staff/reports/index', [
            'title' => 'Review reports', 'reports' => ReviewReport::forStaff($status), 'status' => $status,
            'canResolve' => Roles::can(Auth::role(), 'resolve_reports'),
        ], 200, 'layouts/admin');
    }

    public function hide(Request $request): Response
    {
        $report = $this->openReport($request);
        Review::updateById((int) $report['review_id'], ['is_hidden' => 1]);
        ReviewReport::resolveForReview((int) $report['review_id'], 'hidden', (int) Auth::id());
        Session::flash('success', 'The review is hidden from the public page. Its author can still see it.');
        return $this->redirect('/admin/reports');
    }

    public function dismiss(Request $request): Response
    {
        $report = $this->openReport($request);
        ReviewReport::resolveForReview((int) $report['review_id'], 'dismissed', (int) Auth::id());
        Session::flash('success', 'Report dismissed. The review stays visible.');
        return $this->redirect('/admin/reports');
    }

    private function openReport(Request $request): array
    {
        if (!Roles::can(Auth::role(), 'resolve_reports')) {
            abort(403, 'Your staff role (' . Roles::label(Auth::role()) . ') cannot resolve reports.');
        }
        $report = ReviewReport::find((int) $request->param('id'));
        if (!$report || $report['status'] !== 'open') {
            abort(404, 'That report does not exist or was already handled.');
        }
        return $report;
    }
}
