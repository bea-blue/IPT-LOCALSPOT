<?php
/**
 * Runs every tests/*Test.php with SpotCore's built-in test framework.
 *   C:\xampp\php\php.exe tests\run.php
 * The same test files also run under PHPUnit 9.6:  vendor\bin\phpunit
 * No database or internet needed: tests use in-memory SQLite and a fake HTTP client.
 */
if (PHP_SAPI !== 'cli') {
    exit;
}

require __DIR__ . '/setup.php';

if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDOUT, "Note: pdo_sqlite is not enabled; database-backed tests will fail.\n");
}

$passed = $failed = 0;
foreach (glob(__DIR__ . '/*Test.php') as $file) {
    require_once $file;
    $class = basename($file, '.php');
    $result = (new $class())->run();
    $passed += $result['passed'];
    $failed += $result['failed'];
    echo implode(PHP_EOL, $result['lines']), PHP_EOL;
}

echo PHP_EOL, "{$passed} passed, {$failed} failed", PHP_EOL;
exit($failed > 0 ? 1 : 0);
