<?php
/**
 * SpotCore autoloader (PSR-4 style, written by hand — Composer is not needed).
 *   SpotCore\Router                 -> core/Router.php
 *   App\Controllers\PlaceController -> app/Controllers/PlaceController.php
 */
spl_autoload_register(function (string $class): void {
    $root = dirname(__DIR__);
    $namespaces = ['SpotCore\\' => $root . '/core/', 'App\\' => $root . '/app/'];
    foreach ($namespaces as $prefix => $dir) {
        if (strncmp($class, $prefix, strlen($prefix)) === 0) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});

require __DIR__ . '/helpers.php';
