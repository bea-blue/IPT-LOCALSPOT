<?php
use SpotCore\Env;

return [
    'name'      => Env::get('APP_NAME', 'LocalSpot'),
    'tagline'   => 'Find your next local spot.',
    'env'       => Env::get('APP_ENV', 'local'),
    'debug'     => Env::get('APP_DEBUG', false),
    'timezone'  => Env::get('APP_TIMEZONE', 'Asia/Manila'),
    'base_path' => rtrim((string) Env::get('APP_BASE_PATH', ''), '/'),
];
