<?php
namespace App\Controllers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Place;
use App\Models\PlaceDocument;
use App\Models\PlaceImage;
use App\Models\PlaceMenu;
use App\Models\Review;
use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Database;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;

/**
 * Shop owners manage their OWN listings: create, edit, submit for approval,
 * hide/show and delete. Every action loads the place with owner_id = the
 * signed-in user, so another owner's shop gives 404. Staff accounts cannot own
 * shops (they approve them). Routes add the 'member' and 'csrf' middleware.
 */
final class OwnerShopController extends Controller
{
    private const FIELDS = ['name', 'category_id', 'description', 'address', 'city', 'budget_min', 'budget_max', 'price_unit',
        'budget_is_estimate', 'budget_note', 'setting', 'hours_note', 'payment_options', 'payment_note', 'source_url'];

    public const LABELS = [
        'category_id' => 'Category', 'budget_min' => 'Lowest price', 'budget_max' => 'Highest price',
        'price_unit' => 'Price unit', 'hours_note' => 'Opening hours', 'budget_is_estimate' => 'Estimate',
        'budget_note' => 'Budget note', 'payment_options' => 'Payment options', 'payment_note' => 'Payment note',
        'source_url' => 'Website or Facebook page',
    ];

    /** Statuses in which the owner may edit details. Suspended shops are locked until staff reinstate them. */
    private const EDITABLE = ['draft', 'pending', 'published', 'rejected', 'hidden'];

    private function rules(): array
    {
        return [
            'name'        => 'required|max:150',
            'category_id' => 'required|integer|exists:categories,id',
            'description' => 'required|min:10|max:5000',
            'address'     => 'required|max:255',
            'city'        => 'required|max:100',
            'budget_min'  => 'nullable|required_with:budget_max|numeric|min:0|max:100000',
            'budget_max'  => 'nullable|required_with:budget_min|numeric|min:0|max:100000|gte:budget_min',
            'price_unit'  => 'nullable|required_with:budget_min|in:' . implode(',', array_keys(Place::PRICE_UNITS)),
            'budget_is_estimate' => 'nullable|in:1',
            'budget_note' => 'nullable|max:255',
            'setting'     => 'nullable|in:' . implode(',', array_keys(Place::SETTINGS)),
            'hours_note'  => 'nullable|max:255',
            'payment_options' => 'nullable|max:255',
            'payment_note' => 'nullable|max:255',
            'source_url'  => 'nullable|url|max:255',
        ];
    }

    public function index(Request $request): Response
    {
        $this->denyStaff();
        return $this->view('my/shops/index', ['title' => 'My shops', 'places' => Place::forOwner((int) Auth::id())]);
    }

    public function create(Request $request): Response
    {
        $this->denyStaff();
        return $this->view('my/shops/form', ['title' => 'List your shop', 'place' => null, 'categories' => Category::options()]);
    }

    public function store(Request $request): Response
    {
        $this->denyStaff();
        $data = $request->only(self::FIELDS);
        if ($fail = $this->validate($data, $this->rules(), '/my/shops/create', self::LABELS)) {
            return $fail;
        }
        $me = (int) Auth::id();
        $id = Place::create($this->clean($data) + ['owner_id' => $me, 'created_by' => $me, 'status' => 'draft']);
        Session::flash('success', 'Saved "' . $data['name'] . '" as a draft. Add your business permit and a photo of the shop, then submit it for approval.');
        return $this->redirect('/my/shops/' . $id . '/edit');
    }

    public function edit(Request $request): Response
    {
        $place = $this->own($request);
        $id = (int) $place['id'];
        return $this->view('my/shops/form', [
            'title' => 'Manage ' . $place['name'],
            'place' => $place,
            'categories' => Category::options(),
            'documents' => PlaceDocument::forPlace($id),
            'missing' => PlaceDocument::missingFor($id),
            'photos' => PlaceImage::forPlace($id),
            'menus' => PlaceMenu::forPlace($id),
            'menuItems' => MenuItem::forPlace($id),
        ]);
    }

    public function update(Request $request): Response
    {
        $place = $this->own($request);
        $this->assertEditable($place);
        $data = $request->only(self::FIELDS);
        if ($fail = $this->validate($data, $this->rules(), '/my/shops/' . $place['id'] . '/edit', self::LABELS)) {
            return $fail;
        }
        Place::updateById((int) $place['id'], $this->clean($data));
        Session::flash('success', $place['status'] === 'published' ? 'Saved. Your changes are live.' : 'Saved.');
        return $this->redirect('/my/shops/' . $place['id'] . '/edit');
    }

    /** draft or rejected -> pending, only with a business permit and a shop photo on file. */
    public function submit(Request $request): Response
    {
        $place = $this->own($request);
        if (!in_array($place['status'], ['draft', 'rejected'], true)) {
            Session::flash('error', 'This shop is already ' . strtolower(Place::STATUSES[$place['status']] ?? $place['status']) . '.');
            return $this->redirect('/my/shops/' . $place['id'] . '/edit');
        }
        $missing = PlaceDocument::missingFor((int) $place['id']);
        if ($missing) {
            Session::flash('error', 'Upload these first: ' . implode(' and ', array_map(fn($k) => strtolower(PlaceDocument::KINDS[$k]), $missing)) . '.');
            return $this->redirect('/my/shops/' . $place['id'] . '/edit#documents');
        }
        Place::updateById((int) $place['id'], ['status' => 'pending', 'submitted_at' => date('Y-m-d H:i:s')]);
        Session::flash('success', 'Submitted. LocalSpot staff will check your documents and approve or reject the listing.');
        return $this->redirect('/my/shops');
    }

    /** published <-> hidden. Hiding needs no approval; showing again is allowed because it was already approved. */
    public function visibility(Request $request): Response
    {
        $place = $this->own($request);
        $next = ['published' => 'hidden', 'hidden' => 'published'][$place['status']] ?? null;
        if ($next === null) {
            abort(403, 'Only an approved shop can be hidden or shown.');
        }
        Place::updateById((int) $place['id'], ['status' => $next]);
        Session::flash('success', $next === 'hidden' ? 'Your shop is hidden from visitors.' : 'Your shop is visible again.');
        return $this->redirect('/my/shops');
    }

    public function destroy(Request $request): Response
    {
        $place = $this->own($request);
        $id = (int) $place['id'];
        $files = [];
        foreach (PlaceDocument::forPlace($id) as $d) {
            $files[] = PlaceDocument::path($d);
        }
        foreach (array_merge(PlaceImage::forPlace($id), PlaceMenu::forPlace($id)) as $m) {
            if (!empty($m['path']) && str_starts_with($m['path'], 'uploads/')) {
                $files[] = BASE_PATH . '/public/assets/' . $m['path'];
            }
        }
        Database::transaction(function () use ($id): void {
            PlaceImage::deleteForPlace($id);
            Review::deleteForPlace($id);
            PlaceMenu::deleteForPlace($id);
            MenuItem::deleteForPlace($id);
            Database::query('DELETE FROM place_documents WHERE place_id = :id', ['id' => $id]);
            Database::query('DELETE FROM place_checks WHERE place_id = :id', ['id' => $id]);
            Place::deleteById($id);
        });
        foreach ($files as $f) {
            if (is_file($f)) {
                @unlink($f);
            }
        }
        Session::flash('success', 'Deleted "' . $place['name'] . '".');
        return $this->redirect('/my/shops');
    }

    /** The signed-in owner's place, or 404 (another owner's shop looks exactly like a missing one). */
    public static function ownedPlace(int $id): array
    {
        $place = Place::findOwned($id, (int) Auth::id());
        if (!$place) {
            abort(404, 'That shop does not exist or is not yours.');
        }
        return $place;
    }

    public static function assertEditable(array $place): void
    {
        if (!in_array($place['status'], self::EDITABLE, true)) {
            abort(403, 'This shop is suspended by LocalSpot staff, so it cannot be changed. Reason: ' . ($place['review_note'] ?? 'not given'));
        }
    }

    private function own(Request $request): array
    {
        return self::ownedPlace((int) $request->param('id'));
    }

    private function denyStaff(): void
    {
        if (Roles::isStaff(Auth::role())) {
            abort(403, 'Staff accounts approve shops, so they cannot list one.');
        }
    }

    private function clean(array $data): array
    {
        $orNull = fn(?string $v) => ($v === null || trim($v) === '') ? null : trim($v);
        $money = fn(?string $v) => ($v === null || trim($v) === '') ? null : round((float) $v, 2); // unknown stays NULL, never 0
        return [
            'name'        => trim($data['name']),
            'category_id' => (int) $data['category_id'],
            'description' => trim($data['description']),
            'address'     => trim($data['address']),
            'city'        => trim($data['city']),
            'budget_min'  => $money($data['budget_min']),
            'budget_max'  => $money($data['budget_max']),
            'price_unit'  => $orNull($data['price_unit']),
            'budget_is_estimate' => $data['budget_is_estimate'] === '1' ? 1 : 0,
            'budget_note' => $orNull($data['budget_note']),
            'setting'     => $orNull($data['setting']),
            'hours_note'  => $orNull($data['hours_note']),
            'payment_options' => $orNull($data['payment_options']),
            'payment_note' => $orNull($data['payment_note']),
            'source_url'  => $orNull($data['source_url']),
            'checked_on'  => date('Y-m-d'), // "last updated by the owner"
        ];
    }
}
