<?php

use App\Services\WeatherService;
use SpotCore\Http\HttpClient;
use SpotCore\Testing\TestCase;

/** Scripted fake: returns prepared responses and counts calls. NEVER used by the real app. */
final class FakeHttpClient implements HttpClient
{
    public int $calls = 0;
    public array $responses;
    public function __construct(array $responses) { $this->responses = $responses; }
    public function get(string $url, array $headers = []): array
    {
        $this->calls++;
        return array_shift($this->responses) ?? ['status' => 0, 'body' => '', 'error' => 'no more fake responses'];
    }
}

final class WeatherServiceTest extends TestCase
{
    private string $dir;
    private int $now;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/localspot-weather-test-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->now = 1_800_000_000;
    }

    private function service(FakeHttpClient $http): WeatherService
    {
        $config = [
            'endpoint' => 'https://api.open-meteo.com/v1/forecast', 'latitude' => 15.145, 'longitude' => 120.5887,
            'label' => 'Angeles City area weather', 'timezone' => 'Asia/Manila',
            'fresh_seconds' => 900, 'stale_seconds' => 7200, 'cooldown_seconds' => 120, 'rate_limit_cooldown' => 900,
            'cache_dir' => $this->dir,
        ];
        return new WeatherService($config, $http, fn() => $this->now);
    }

    private static function ok(int $code = 0): array
    {
        return ['status' => 200, 'error' => null, 'body' => json_encode([
            'current_units' => ['temperature_2m' => '°C', 'relative_humidity_2m' => '%'],
            'current' => ['time' => '2026-11-15T09:00', 'interval' => 900, 'temperature_2m' => 29.4, 'relative_humidity_2m' => 74, 'weather_code' => $code, 'is_day' => 1],
        ])];
    }

    public function testBuildsTheDocumentedQuery(): void
    {
        $url = $this->service(new FakeHttpClient([]))->url();
        $this->assertStringContainsString('latitude=15.145', $url);
        $this->assertStringContainsString('current=temperature_2m%2Crelative_humidity_2m%2Cweather_code%2Cis_day', $url);
        $this->assertStringContainsString('timezone=Asia%2FManila', $url);
    }

    public function testCodeZeroIsClearSkyNotMissing(): void
    {
        $w = $this->service(new FakeHttpClient([self::ok(0)]))->current();
        $this->assertSame('fresh', $w['state']);
        $this->assertSame('Clear sky', $w['data']['condition']);
        $this->assertSame('°C', $w['data']['temperature_unit']);
    }

    public function testCacheAvoidsSecondCallWithin15Minutes(): void
    {
        $http = new FakeHttpClient([self::ok(3), self::ok(61)]);
        $s = $this->service($http);
        $s->current();
        $this->now += 600; // 10 minutes later
        $w = $s->current();
        $this->assertSame(1, $http->calls);
        $this->assertSame('Overcast', $w['data']['condition']);
    }

    public function testTimeoutShowsStaleCacheThenCooldownBlocksRetry(): void
    {
        $http = new FakeHttpClient([self::ok(1), ['status' => 0, 'body' => '', 'error' => 'Operation timed out']]);
        $s = $this->service($http);
        $s->current();
        $this->now += 1800; // 30 min: cache is no longer fresh
        $w = $s->current();
        $this->assertSame('stale', $w['state']);
        $this->now += 30;   // still inside the 2-minute cooldown
        $s->current();
        $this->assertSame(2, $http->calls);
    }

    public function testRateLimitIsNotRetriedImmediately(): void
    {
        $http = new FakeHttpClient([['status' => 429, 'body' => '{"error":true}', 'error' => null], self::ok()]);
        $s = $this->service($http);
        $this->assertSame('unavailable', $s->current()['state']);
        $this->now += 300; // 5 minutes: still inside the 15-minute 429 cooldown
        $this->assertSame('unavailable', $s->current()['state']);
        $this->assertSame(1, $http->calls);
    }

    public function testMalformedJsonWithoutCacheIsUnavailable(): void
    {
        $w = $this->service(new FakeHttpClient([['status' => 200, 'body' => '{"current":{"time":"x"}}', 'error' => null]]))->current();
        $this->assertSame('unavailable', $w['state']);
        $this->assertSame('Weather information is temporarily unavailable.', $w['message']);
    }

    public function testStaleCacheExpiresAfterTwoHours(): void
    {
        $http = new FakeHttpClient([self::ok(), ['status' => 503, 'body' => '', 'error' => null]]);
        $s = $this->service($http);
        $s->current();
        $this->now += 3 * 3600;
        $this->assertSame('unavailable', $s->current()['state']);
    }

    public function testUnknownCodeFallback(): void
    {
        $this->assertSame('Unknown condition (code 42)', WeatherService::describe(42));
    }
}
