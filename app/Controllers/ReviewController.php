<?php
namespace App\Controllers;

use App\Models\Place;
use App\Models\Review;
use App\Models\ReviewReport;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;

/**
 * Signed-in users post one review per place. Reviews appear immediately.
 * Only the author can edit or delete their own review (checked on the server).
 * Anyone signed in can report someone else's review as spam, abusive or
 * off-topic; staff then hide it or dismiss the report. Owners cannot review
 * their own shop. Routes add the 'member' and 'csrf' middleware.
 */
final class ReviewController extends Controller
{
    private const RULES = [
        'rating'  => 'required|integer|in:1,2,3,4,5',
        'comment' => 'required|min:10|max:1000',
    ];
    private const LABELS = ['rating' => 'Rating', 'comment' => 'Review'];

    public function store(Request $request): Response
    {
        $place = Place::findPublished((int) $request->param('id'));
        if (!$place) {
            abort(404, 'That place does not exist or is not published.');
        }
        $back = '/places/' . $place['id'] . '#reviews';

        // Honeypot: real visitors never see or fill this field.
        if ($request->input('website', '') !== '') {
            return $this->redirect($back);
        }
        if ((int) $place['owner_id'] === (int) Auth::id()) {
            Session::flash('error', 'You cannot review your own shop.');
            return $this->redirect($back);
        }
        if (Review::findByUserAndPlace((int) Auth::id(), (int) $place['id'])) {
            Session::flash('error', 'You already reviewed this place. You can edit your review instead.');
            return $this->redirect($back);
        }

        $data = $request->only(['rating', 'comment']);
        if ($fail = $this->validate($data, self::RULES, $back, self::LABELS)) {
            return $fail;
        }

        Review::create([
            'place_id'    => (int) $place['id'],
            'user_id'     => (int) Auth::id(),
            'author_name' => Auth::user()['name'],
            'rating'      => (int) $data['rating'],
            'comment'     => trim($data['comment']),
        ]);
        Session::flash('success', 'Thanks! Your review was posted.');
        return $this->redirect($back);
    }

    public function edit(Request $request): Response
    {
        $review = $this->ownReview($request);
        $place = Place::find((int) $review['place_id']);
        return $this->view('reviews/edit', ['title' => 'Edit your review', 'review' => $review, 'place' => $place]);
    }

    public function update(Request $request): Response
    {
        $review = $this->ownReview($request);
        $data = $request->only(['rating', 'comment']);
        if ($fail = $this->validate($data, self::RULES, '/reviews/' . $review['id'] . '/edit', self::LABELS)) {
            return $fail;
        }
        Review::updateById((int) $review['id'], ['rating' => (int) $data['rating'], 'comment' => trim($data['comment'])]);
        Session::flash('success', 'Your review was updated.');
        return $this->redirect('/places/' . $review['place_id'] . '#reviews');
    }

    public function destroy(Request $request): Response
    {
        $review = $this->ownReview($request);
        Review::deleteById((int) $review['id']);
        Session::flash('success', 'Your review was deleted.');
        return $this->redirect('/places/' . $review['place_id'] . '#reviews');
    }

    public function report(Request $request): Response
    {
        $review = Review::find((int) $request->param('id'));
        $place = $review ? Place::findPublished((int) $review['place_id']) : null;
        if (!$review || !$place || (int) $review['is_hidden'] === 1) {
            abort(404, 'That review does not exist.');
        }
        $back = '/places/' . $place['id'] . '#review-' . $review['id'];
        if ((int) ($review['user_id'] ?? 0) === (int) Auth::id()) {
            Session::flash('error', 'You cannot report your own review. Edit or delete it instead.');
            return $this->redirect($back);
        }
        if (ReviewReport::findByReporter((int) $review['id'], (int) Auth::id())) {
            Session::flash('info', 'You already reported this review. Staff will look at it.');
            return $this->redirect($back);
        }
        $reason = (string) $request->input('reason', '');
        $note = trim((string) $request->input('note', ''));
        if (!isset(ReviewReport::REASONS[$reason]) || mb_strlen($note) > 500) {
            Session::flash('error', 'Choose a reason: spam, abusive or off-topic (and keep the note under 500 characters).');
            return $this->redirect($back);
        }
        ReviewReport::create(['review_id' => (int) $review['id'], 'reporter_id' => (int) Auth::id(), 'reason' => $reason,
            'note' => $note === '' ? null : $note, 'status' => 'open']);
        Session::flash('success', 'Thanks. LocalSpot staff will check the review.');
        return $this->redirect($back);
    }

    /** The review must exist and belong to the signed-in user, otherwise 404 / 403. */
    private function ownReview(Request $request): array
    {
        $review = Review::find((int) $request->param('id'));
        if (!$review) {
            abort(404, 'That review does not exist.');
        }
        if ((int) ($review['user_id'] ?? 0) !== (int) Auth::id()) {
            abort(403, 'You can only change your own reviews.');
        }
        return $review;
    }
}
