<?php
namespace App\Controllers;

use App\Models\Place;
use App\Models\PlaceDocument;
use App\Support\Roles;
use SpotCore\Auth;
use SpotCore\Controller;
use SpotCore\Request;
use SpotCore\Response;

/**
 * Streams a private evidence file. Allowed: staff with 'view_documents', or the
 * owner of the shop. Files live outside public/, so this is the only way to them.
 */
final class DocumentController extends Controller
{
    private const MIME = ['jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'pdf' => 'application/pdf'];

    public function show(Request $request): Response
    {
        $doc = PlaceDocument::find((int) $request->param('id'));
        if (!$doc) {
            abort(404, 'That document does not exist.');
        }
        $place = Place::find((int) $doc['place_id']);
        $isOwner = $place && (int) $place['owner_id'] === (int) Auth::id();
        if (!$isOwner && !Roles::can(Auth::role(), 'view_documents')) {
            abort(403, 'Only LocalSpot staff and the shop owner can open this document.');
        }
        $file = PlaceDocument::path($doc);
        if (!is_file($file) || !isset(self::MIME[$doc['file_type']])) {
            abort(404, 'The file is missing.');
        }
        $download = preg_replace('/[^A-Za-z0-9._ -]/', '_', $doc['original_name']) ?: 'document';
        return (new Response((string) file_get_contents($file), 200, ['Content-Type' => self::MIME[$doc['file_type']]]))
            ->withHeader('Content-Disposition', 'inline; filename="' . $download . '"')
            ->withHeader('Cache-Control', 'private, no-store');
    }
}
