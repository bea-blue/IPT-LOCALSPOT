<?php
/**
 * Boots SpotCore for both web requests and CLI scripts:
 * autoloader, .env, config, timezone, logs, views and the database.
 */

use SpotCore\Config;
use SpotCore\Database;
use SpotCore\Env;
use SpotCore\Logger;
use SpotCore\View;

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

require BASE_PATH . '/core/autoload.php';

Env::load(BASE_PATH . '/.env');
Config::load(BASE_PATH . '/config');

date_default_timezone_set(Config::get('app.timezone', 'Asia/Manila'));
ini_set('display_errors', Config::get('app.debug') ? '1' : '0');
error_reporting(E_ALL);

Logger::setPath(BASE_PATH . '/storage/logs');
View::setPath(BASE_PATH . '/app/Views');
Database::configure(Config::get('database'));
