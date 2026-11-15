<?php
namespace App\Controllers;

use App\Models\MenuItem;
use App\Models\Place;
use App\Models\PlaceDocument;
use App\Models\PlaceImage;
use App\Models\PlaceMenu;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Database;
use SpotCore\Request;
use SpotCore\Response;
use SpotCore\Session;
use SpotCore\UploadedFile;
use SpotCore\Validator;

/**
 * Owner uploads for their OWN shop: private evidence (permit, shop photo, other),
 * public listing photos, menu photos, menu links and typed menu items.
 * - File type is detected from the file's bytes; size limit 5 MB; random file names.
 * - Evidence goes to storage/uploads/documents (outside public/); only staff and
 *   the owner can open it through DocumentController.
 * - Changing the business permit of an approved shop sends it back for approval.
 */
final class OwnerMediaController extends Controller
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    private const MAX_PHOTOS = 6;

    public function storeDocument(Request $request): Response
    {
        $place = $this->editablePlace($request);
        $back = '/my/shops/' . $place['id'] . '/edit#documents';
        $kind = (string) $request->input('kind', '');
        if (!isset(PlaceDocument::KINDS[$kind])) {
            return $this->fail($back, 'Choose what kind of document this is.');
        }
        $file = $request->file('document');
        if (!$file) {
            return $this->fail($back, 'Choose a file to upload.');
        }
        [$type, $error] = $file->check(['jpeg', 'png', 'webp', 'pdf'], self::MAX_BYTES);
        if ($error) {
            return $this->fail($back, 'The file ' . $error);
        }
        $stored = $file->store(BASE_PATH . '/storage/uploads/documents', $type);
        PlaceDocument::create([
            'place_id' => (int) $place['id'], 'kind' => $kind, 'stored_name' => $stored,
            'original_name' => $file->clientName() ?: 'document', 'file_type' => $type,
            'size' => $file->size(), 'uploaded_by' => (int) Auth::id(),
        ]);
        $msg = 'Uploaded ' . strtolower(PlaceDocument::KINDS[$kind]) . '.';
        if ($kind === 'permit' && $this->sendBackForApproval($place)) {
            $msg .= ' Because the business permit changed, your shop is hidden until staff approve it again.';
        }
        Session::flash('success', $msg);
        return $this->redirect($back);
    }

    public function destroyDocument(Request $request): Response
    {
        $doc = PlaceDocument::find((int) $request->param('id'));
        if (!$doc) {
            abort(404, 'That document does not exist.');
        }
        $place = OwnerShopController::ownedPlace((int) $doc['place_id']);
        OwnerShopController::assertEditable($place);
        PlaceDocument::deleteById((int) $doc['id']);
        @unlink(PlaceDocument::path($doc));
        $msg = 'Removed the document.';
        if ($doc['kind'] === 'permit' && $this->sendBackForApproval($place)) {
            $msg .= ' Because the business permit changed, your shop is hidden until staff approve it again.';
        }
        Session::flash('success', $msg);
        return $this->redirect('/my/shops/' . $place['id'] . '/edit#documents');
    }

    public function storePhoto(Request $request): Response
    {
        $place = $this->editablePlace($request);
        $back = '/my/shops/' . $place['id'] . '/edit#photos';
        if (count(PlaceImage::forPlace((int) $place['id'])) >= self::MAX_PHOTOS) {
            return $this->fail($back, 'You can add up to ' . self::MAX_PHOTOS . ' photos. Remove one first.');
        }
        $alt = trim((string) $request->input('alt', ''));
        if (mb_strlen($alt) < 5 || mb_strlen($alt) > 200) {
            return $this->fail($back, 'Describe the photo in 5 to 200 characters (for visitors who use screen readers).');
        }
        [$path, $w, $h, $error] = $this->storePublicImage($request->file('photo'), 'photos');
        if ($error) {
            return $this->fail($back, $error);
        }
        $next = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM place_images WHERE place_id = :id', ['id' => $place['id']]);
        PlaceImage::create([
            'place_id' => (int) $place['id'], 'path' => $path, 'sort_order' => $next, 'alt' => $alt,
            'credit' => 'Photo from the shop owner', 'source_url' => null, 'width' => $w, 'height' => $h,
        ]);
        Session::flash('success', 'Photo added.');
        return $this->redirect($back);
    }

    public function destroyPhoto(Request $request): Response
    {
        $photo = PlaceImage::find((int) $request->param('id'));
        if (!$photo) {
            abort(404, 'That photo does not exist.');
        }
        $place = OwnerShopController::ownedPlace((int) $photo['place_id']);
        OwnerShopController::assertEditable($place);
        PlaceImage::deleteById((int) $photo['id']);
        $this->unlinkUpload($photo['path']);
        Session::flash('success', 'Photo removed.');
        return $this->redirect('/my/shops/' . $place['id'] . '/edit#photos');
    }

    public function storeMenuPhoto(Request $request): Response
    {
        $place = $this->editablePlace($request);
        $back = '/my/shops/' . $place['id'] . '/edit#menu';
        $label = trim((string) $request->input('label', ''));
        if ($label === '' || mb_strlen($label) > 150) {
            return $this->fail($back, 'Give the menu photo a short label, e.g. "Drinks menu".');
        }
        [$path, $w, $h, $error] = $this->storePublicImage($request->file('menu_photo'), 'menus');
        if ($error) {
            return $this->fail($back, $error);
        }
        PlaceMenu::create([
            'place_id' => (int) $place['id'], 'kind' => 'image', 'path' => $path, 'label' => $label,
            'credit' => 'Menu photo from the shop owner', 'width' => $w, 'height' => $h, 'sort_order' => 1,
        ]);
        Session::flash('success', 'Menu photo added.');
        return $this->redirect($back);
    }

    public function storeMenuLink(Request $request): Response
    {
        $place = $this->editablePlace($request);
        $back = '/my/shops/' . $place['id'] . '/edit#menu';
        $data = ['label' => trim((string) $request->input('label', '')), 'url' => trim((string) $request->input('url', ''))];
        $v = new Validator($data, ['label' => 'required|max:150', 'url' => 'required|url|max:255'], ['label' => 'Link label', 'url' => 'Menu link']);
        if ($v->fails()) {
            return $this->fail($back, implode(' ', array_values($v->errors())));
        }
        PlaceMenu::create(['place_id' => (int) $place['id'], 'kind' => 'link', 'url' => $data['url'], 'label' => $data['label'],
            'credit' => 'Link from the shop owner', 'sort_order' => 2]);
        Session::flash('success', 'Menu link added.');
        return $this->redirect($back);
    }

    public function destroyMenu(Request $request): Response
    {
        $menu = PlaceMenu::find((int) $request->param('id'));
        if (!$menu) {
            abort(404, 'That menu entry does not exist.');
        }
        $place = OwnerShopController::ownedPlace((int) $menu['place_id']);
        OwnerShopController::assertEditable($place);
        PlaceMenu::deleteById((int) $menu['id']);
        if ($menu['kind'] === 'image') {
            $this->unlinkUpload((string) $menu['path']);
        }
        Session::flash('success', 'Removed from the menu.');
        return $this->redirect('/my/shops/' . $place['id'] . '/edit#menu');
    }

    public function storeMenuItem(Request $request): Response
    {
        $place = $this->editablePlace($request);
        $back = '/my/shops/' . $place['id'] . '/edit#menu';
        $data = $request->only(['section', 'name', 'price']);
        $v = new Validator($data, ['section' => 'required|max:80', 'name' => 'required|max:150', 'price' => 'nullable|numeric|min:0|max:100000'],
            ['section' => 'Menu section', 'name' => 'Item name', 'price' => 'Price']);
        if ($v->fails()) {
            return $this->fail($back, implode(' ', array_values($v->errors())));
        }
        $next = (int) Database::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM menu_items WHERE place_id = :id', ['id' => $place['id']]);
        MenuItem::create([
            'place_id' => (int) $place['id'], 'section' => trim($data['section']), 'name' => trim($data['name']),
            'price' => trim((string) $data['price']) === '' ? null : round((float) $data['price'], 2),
            'source' => 'Provided by the shop owner, ' . date('M Y'), 'sort_order' => $next,
        ]);
        Session::flash('success', 'Menu item added.');
        return $this->redirect($back);
    }

    public function destroyMenuItem(Request $request): Response
    {
        $item = MenuItem::find((int) $request->param('id'));
        if (!$item) {
            abort(404, 'That menu item does not exist.');
        }
        $place = OwnerShopController::ownedPlace((int) $item['place_id']);
        OwnerShopController::assertEditable($place);
        MenuItem::deleteById((int) $item['id']);
        Session::flash('success', 'Menu item removed.');
        return $this->redirect('/my/shops/' . $place['id'] . '/edit#menu');
    }

    // ---------------------------------------------------------------- helpers

    private function editablePlace(Request $request): array
    {
        $place = OwnerShopController::ownedPlace((int) $request->param('id'));
        OwnerShopController::assertEditable($place);
        return $place;
    }

    /** An approved shop whose permit changes goes back to "pending" (hidden until approved again). */
    private function sendBackForApproval(array $place): bool
    {
        if (!in_array($place['status'], ['published', 'hidden'], true)) {
            return false;
        }
        Place::updateById((int) $place['id'], ['status' => 'pending', 'submitted_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    /** @return array{0:?string,1:int,2:int,3:?string} [path under public/assets, width, height, error] */
    private function storePublicImage(?UploadedFile $file, string $folder): array
    {
        if (!$file) {
            return [null, 0, 0, 'Choose an image to upload.'];
        }
        [$type, $error] = $file->check(['jpeg', 'png', 'webp'], self::MAX_BYTES);
        if ($error) {
            return [null, 0, 0, 'The image ' . $error];
        }
        $dir = BASE_PATH . '/public/assets/uploads/' . $folder;
        $name = $file->store($dir, $type);
        $size = @getimagesize($dir . '/' . $name) ?: [0, 0];
        return ['uploads/' . $folder . '/' . $name, min(65535, (int) $size[0]), min(65535, (int) $size[1]), null];
    }

    /** Deletes a file only if it is an owner upload (imported photos are never touched). */
    private function unlinkUpload(string $path): void
    {
        if (preg_match('#^uploads/(photos|menus)/[a-f0-9]{32}\.(jpg|png|webp)$#', $path)) {
            @unlink(BASE_PATH . '/public/assets/' . $path);
        }
    }

    /** Errors go back to the top of the page (no #anchor), where the message is shown. */
    private function fail(string $back, string $message): Response
    {
        Session::flash('error', $message);
        return $this->redirect(explode('#', $back)[0]);
    }
}
