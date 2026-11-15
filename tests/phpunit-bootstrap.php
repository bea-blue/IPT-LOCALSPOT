<?php
/**
 * PHPUnit bootstrap. The test classes extend SpotCore\Testing\TestCase; when PHPUnit
 * runs them, this file supplies a version of that class built on PHPUnit's TestCase,
 * so the SAME tests run under both runners. (The app itself never needs Composer.)
 */
namespace SpotCore\Testing;

require_once __DIR__ . '/../vendor/autoload.php';  // PHPUnit (dev dependency)

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /** Asserts that $fn throws $class (optionally with a message fragment) and returns it. */
    protected function assertThrows(string $class, callable $fn, string $messagePart = ''): \Throwable
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->assertInstanceOf($class, $e);
            if ($messagePart !== '') {
                $this->assertStringContainsString($messagePart, $e->getMessage());
            }
            return $e;
        }
        $this->fail("Expected {$class} to be thrown");
    }
}

require_once __DIR__ . '/setup.php';
