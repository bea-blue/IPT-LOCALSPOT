<?php
namespace App\Controllers;

use App\Models\Category;
use App\Models\Place;
use App\Models\MenuItem;
use App\Models\PlaceImage;
use App\Models\PlaceMenu;
use App\Models\Review;
use App\Services\WeatherService;
use App\Support\Roles;
use SpotCore\Auth;
use App\Support\PlaceFilters;
use SpotCore\Controller;
use SpotCore\Request;
use SpotCore\Response;

/** Public pages: explore (list + filters) and place detail. */
final class PlaceController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = Category::options();
        $filters = PlaceFilters::fromQuery($request->queryAll(), array_map(fn($c) => (int) $c['id'], $categories));

        $places = Place::searchPublished($filters->toArray());
        $covers = PlaceImage::coversFor(array_column($places, 'id'));
        $ratings = Review::statsFor(array_column($places, 'id'));
        $withMenus = PlaceMenu::placesWithMenus(array_column($places, 'id'));
        foreach ($places as &$p) {
            $p['cover'] = $covers[(int) $p['id']] ?? null;
            $p['rating'] = $ratings[(int) $p['id']] ?? null;
            $p['has_menu'] = isset($withMenus[(int) $p['id']]);
        }
        unset($p);

        return $this->view('places/index', [
            'title' => 'Explore',
            'places' => $places,
            'categories' => $categories,
            'tiles' => Category::withPublishedCounts(),
            'filters' => $filters,
        ]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $place = Place::findPublished($id);
        $preview = false;
        if (!$place) {
            // The owner (and staff, once it is submitted) may preview an unpublished listing.
            $any = Place::findWithCategory($id);
            $canPreview = $any && (
                ((int) $any['owner_id'] === (int) Auth::id() && Auth::id() !== null)
                || (Roles::can(Auth::role(), 'view_submissions') && $any['status'] !== 'draft')
            );
            if (!$canPreview) {
                // Drafts and missing IDs look exactly the same to the public.
                abort(404, 'That place does not exist or is not published.');
            }
            $place = $any;
            $preview = true;
        }

        return $this->view('places/show', [
            'title' => $place['name'],
            'place' => $place,
            'photos' => PlaceImage::forPlace((int) $place['id']),
            'reviews' => Review::forPlace((int) $place['id'], Auth::id()),
            'preview' => $preview,
            'menus' => PlaceMenu::forPlace((int) $place['id']),
            'menuItems' => MenuItem::forPlace((int) $place['id']),
            'rating' => Review::statsFor([(int) $place['id']])[(int) $place['id']] ?? null,
            'weather' => (new WeatherService())->current(),
        ]);
    }
}
