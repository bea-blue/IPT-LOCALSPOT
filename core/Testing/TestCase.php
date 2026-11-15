<?php
namespace SpotCore\Testing;

/**
 * SpotCore's own minimal unit-test framework (no Composer/PHPUnit needed).
 * Test classes extend TestCase; every public method starting with "test" runs.
 * Run all tests with:  php tests/run.php
 */
abstract class TestCase
{
    protected function setUp(): void {}

    protected function assertTrue(bool $condition, string $message = 'Expected true'): void
    {
        if (!$condition) {
            throw new AssertionFailed($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Expected false'): void
    {
        $this->assertTrue(!$condition, $message);
    }

    protected function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailed(($message ? $message . ': ' : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ""): void
    {
        $this->assertTrue(str_contains($haystack, $needle), "Expected to find '{$needle}'");
    }

    /** Asserts that $fn throws $class (optionally with a message fragment). */
    protected function assertThrows(string $class, callable $fn, string $messagePart = ''): \Throwable
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            $this->assertTrue($e instanceof $class, 'Expected ' . $class . ', got ' . get_class($e));
            if ($messagePart !== '') {
                $this->assertStringContainsString($messagePart, $e->getMessage());
            }
            return $e;
        }
        throw new AssertionFailed("Expected {$class} to be thrown");
    }

    /** @return array{passed:int, failed:int, lines:string[]} */
    public function run(): array
    {
        $result = ['passed' => 0, 'failed' => 0, 'lines' => []];
        foreach (get_class_methods($this) as $method) {
            if (!str_starts_with($method, 'test')) {
                continue;
            }
            try {
                $this->setUp();
                $this->$method();
                $result['passed']++;
                $result['lines'][] = "  PASS  " . static::class . "::{$method}";
            } catch (\Throwable $e) {
                $result['failed']++;
                $result['lines'][] = "  FAIL  " . static::class . "::{$method} - " . $e->getMessage();
            }
        }
        return $result;
    }
}
