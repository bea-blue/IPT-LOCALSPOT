<?php
/**
 * Every URL of LocalSpot. {id} only matches positive integers.
 *
 * Roles: member (signs up) -> owner (first shop approved). Staff: developer,
 * manager, employee (given by a developer). Who may do which staff action is
 * checked in the controllers with App\Support\Roles::can().
 *
 * @var SpotCore\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\DocumentController;
use App\Controllers\OwnerMediaController;
use App\Controllers\OwnerShopController;
use App\Controllers\PlaceController;
use App\Controllers\ReviewController;
use App\Controllers\StaffController;
use App\Controllers\StaffMemberController;
use App\Controllers\StaffReportController;
use SpotCore\Router;

// ---- Public ----
$router->get('/', [PlaceController::class, 'index']);
$router->get('/places/{id}', [PlaceController::class, 'show']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'], ['csrf']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register'], ['csrf']);
$router->post('/logout', [AuthController::class, 'logout'], ['member', 'csrf']);

// ---- Signed-in users: their OWN reviews, and reporting other reviews ----
$router->post('/places/{id}/reviews', [ReviewController::class, 'store'], ['member', 'csrf']);
$router->get('/reviews/{id}/edit', [ReviewController::class, 'edit'], ['member']);
$router->post('/reviews/{id}/update', [ReviewController::class, 'update'], ['member', 'csrf']);
$router->post('/reviews/{id}/delete', [ReviewController::class, 'destroy'], ['member', 'csrf']);
$router->post('/reviews/{id}/report', [ReviewController::class, 'report'], ['member', 'csrf']);

// ---- Shop owners: their OWN shops only (ownership checked in the controllers) ----
$router->group(['member'], function (Router $r) {
    $r->get('', [OwnerShopController::class, 'index']);
    $r->get('/create', [OwnerShopController::class, 'create']);
    $r->post('', [OwnerShopController::class, 'store'], ['csrf']);
    $r->get('/{id}/edit', [OwnerShopController::class, 'edit']);
    $r->post('/{id}/update', [OwnerShopController::class, 'update'], ['csrf']);
    $r->post('/{id}/submit', [OwnerShopController::class, 'submit'], ['csrf']);
    $r->post('/{id}/visibility', [OwnerShopController::class, 'visibility'], ['csrf']);
    $r->post('/{id}/delete', [OwnerShopController::class, 'destroy'], ['csrf']);
    $r->post('/{id}/documents', [OwnerMediaController::class, 'storeDocument'], ['csrf']);
    $r->post('/{id}/photos', [OwnerMediaController::class, 'storePhoto'], ['csrf']);
    $r->post('/{id}/menu-photos', [OwnerMediaController::class, 'storeMenuPhoto'], ['csrf']);
    $r->post('/{id}/menu-links', [OwnerMediaController::class, 'storeMenuLink'], ['csrf']);
    $r->post('/{id}/menu-items', [OwnerMediaController::class, 'storeMenuItem'], ['csrf']);
}, '/my/shops');
$router->group(['member', 'csrf'], function (Router $r) {
    $r->post('/documents/{id}/delete', [OwnerMediaController::class, 'destroyDocument']);
    $r->post('/photos/{id}/delete', [OwnerMediaController::class, 'destroyPhoto']);
    $r->post('/menus/{id}/delete', [OwnerMediaController::class, 'destroyMenu']);
    $r->post('/menu-items/{id}/delete', [OwnerMediaController::class, 'destroyMenuItem']);
}, '/my');
// Private evidence files: staff or the shop's owner (checked in the controller).
$router->get('/documents/{id}', [DocumentController::class, 'show'], ['member']);

// ---- Staff panel (developer, manager, employee). Staff never edit shops or reviews. ----
$router->group(['staff'], function (Router $r) {
    $r->get('', [StaffController::class, 'dashboard']);
    $r->get('/shops', [StaffController::class, 'index']);
    $r->get('/shops/{id}', [StaffController::class, 'show']);
    $r->post('/shops/{id}/notes', [StaffController::class, 'addNote'], ['csrf']);
    $r->post('/shops/{id}/approve', [StaffController::class, 'approve'], ['csrf']);
    $r->post('/shops/{id}/reject', [StaffController::class, 'reject'], ['csrf']);
    $r->post('/shops/{id}/suspend', [StaffController::class, 'suspend'], ['csrf']);
    $r->post('/shops/{id}/reinstate', [StaffController::class, 'reinstate'], ['csrf']);

    $r->get('/reports', [StaffReportController::class, 'index']);
    $r->post('/reports/{id}/hide', [StaffReportController::class, 'hide'], ['csrf']);
    $r->post('/reports/{id}/dismiss', [StaffReportController::class, 'dismiss'], ['csrf']);

    $r->get('/staff', [StaffMemberController::class, 'index']);
    $r->post('/staff', [StaffMemberController::class, 'save'], ['csrf']);
    $r->post('/staff/{id}/remove', [StaffMemberController::class, 'remove'], ['csrf']);
}, '/admin');
