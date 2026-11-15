<?php
use SpotCore\Env;

return [
    'endpoint'      => 'https://api.open-meteo.com/v1/forecast',
    'latitude'      => (float) Env::get('WEATHER_LATITUDE', '15.1450'),
    'longitude'     => (float) Env::get('WEATHER_LONGITUDE', '120.5887'),
    'label'         => Env::get('WEATHER_AREA_LABEL', 'Angeles City area weather'),
    'timezone'      => 'Asia/Manila',
    'fresh_seconds' => 15 * 60,        // reuse a successful response for 15 minutes
    'stale_seconds' => 2 * 60 * 60,    // show an older copy (labeled stale) for up to 2 hours
    'cooldown_seconds'      => 2 * 60, // after a failure, wait before calling again
    'rate_limit_cooldown'   => 15 * 60,// after HTTP 429, wait longer (never retry immediately)
    'connect_timeout'       => 3,
    'timeout'               => 5,
    'ca_bundle'     => Env::get('CURL_CA_BUNDLE', ''),
    'cache_dir'     => dirname(__DIR__) . '/storage/cache',
];
