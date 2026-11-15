<?php
/**
 * Front controller: every request to LocalSpot enters here.
 *
 * 1. bootstrap.php loads SpotCore, .env and config
 * 2. Request::capture() wraps the HTTP request
 * 3. App::handle() runs the Router, middleware and controller
 * 4. Response::send() outputs status, headers and HTML
 */

use SpotCore\App;
use SpotCore\Config;
use SpotCore\Request;
use SpotCore\Router;
use SpotCore\Session;

require __DIR__ . '/../bootstrap.php';

$request = Request::capture((string) Config::get('app.base_path', ''));
Session::start($request->isSecure());

$router = new Router();
require BASE_PATH . '/routes/web.php';

$app = new App($router);
$app->setMiddlewareAliases(Config::get('middleware.aliases', []));
$app->setGlobalMiddleware(Config::get('middleware.global', []));

$app->handle($request)->send();
