<?php
namespace App\Services;

use SpotCore\Config;
use SpotCore\Http\CurlHttpClient;
use SpotCore\Http\HttpClient;
use SpotCore\Logger;

/**
 * Current "Angeles City area" weather from the Open-Meteo Forecast API.
 *
 *   GET https://api.open-meteo.com/v1/forecast
 *       ?latitude=..&longitude=..&current=temperature_2m,relative_humidity_2m,weather_code,is_day
 *       &timezone=Asia/Manila
 *
 * - No API key is needed for Open-Meteo's free, non-commercial API.
 * - One shared city response is cached for 15 minutes (one call serves every page).
 * - If the API fails, a cached copy up to 2 hours old is shown with a "stale" label;
 *   otherwise the page says weather is unavailable. Weather is NEVER invented.
 * - After a failure we wait (cooldown) before calling again; after HTTP 429 we wait longer.
 */
final class WeatherService
{
    private array $config;
    private HttpClient $http;
    /** @var callable(): int */
    private $clock;

    public function __construct(?array $config = null, ?HttpClient $http = null, ?callable $clock = null)
    {
        $this->config = $config ?? Config::get('weather', []);
        $this->http = $http ?? new CurlHttpClient(
            (int) ($this->config['connect_timeout'] ?? 3),
            (int) ($this->config['timeout'] ?? 5),
            ($this->config['ca_bundle'] ?? '') ?: null,
        );
        $this->clock = $clock ?? 'time';
    }

    /**
     * @return array{state: string, data: ?array, fetched_at: ?int, label: string, message: ?string}
     *   state = fresh | stale | unavailable
     */
    public function current(): array
    {
        $now = ($this->clock)();
        $cached = $this->readJson('weather.json');

        // 1. Fresh cache: no API call at all.
        if ($cached && $now - $cached['fetched_at'] < $this->config['fresh_seconds']) {
            return $this->result('fresh', $cached);
        }

        // 2. Recently failed? Don't hammer the API during an outage or after a 429.
        $cooldown = $this->readJson('weather_cooldown.json');
        if ($cooldown && $now < $cooldown['until']) {
            return $this->fallback($cached, $now);
        }

        // 3. Ask Open-Meteo.
        $data = $this->fetch($now);
        if ($data !== null) {
            $entry = ['fetched_at' => $now, 'data' => $data];
            $this->writeJson('weather.json', $entry);
            @unlink($this->path('weather_cooldown.json'));
            return $this->result('fresh', $entry);
        }

        return $this->fallback($cached, $now);
    }

    public function url(): string
    {
        return $this->config['endpoint'] . '?' . http_build_query([
            'latitude'  => $this->config['latitude'],
            'longitude' => $this->config['longitude'],
            'current'   => 'temperature_2m,relative_humidity_2m,weather_code,is_day',
            'timezone'  => $this->config['timezone'] ?? 'Asia/Manila',
        ]);
    }

    /** Calls the API and validates the response. Returns null on ANY problem. */
    private function fetch(int $now): ?array
    {
        $response = $this->http->get($this->url());
        $status = $response['status'];

        if ($status !== 200) {
            $reason = $status === 0 ? 'network error/timeout: ' . ($response['error'] ?? 'unknown') : "HTTP {$status}";
            $wait = $status === 429 ? $this->config['rate_limit_cooldown'] : $this->config['cooldown_seconds'];
            Logger::warning("Open-Meteo request failed ({$reason}); next try in {$wait}s");
            $this->writeJson('weather_cooldown.json', ['until' => $now + $wait, 'reason' => $reason]);
            return null;
        }

        $data = self::parse($response['body']);
        if ($data === null) {
            Logger::warning('Open-Meteo returned JSON without the expected fields');
            $this->writeJson('weather_cooldown.json', ['until' => $now + $this->config['cooldown_seconds'], 'reason' => 'invalid JSON']);
        }
        return $data;
    }

    /** Pure function (unit-tested): JSON body -> validated weather data, or null. */
    public static function parse(string $body): ?array
    {
        $json = json_decode($body, true);
        if (!is_array($json) || !isset($json['current']) || !is_array($json['current'])) {
            return null;
        }
        $c = $json['current'];
        $u = is_array($json['current_units'] ?? null) ? $json['current_units'] : [];

        // weather_code 0 = clear sky, so check with is_numeric/isset, never with empty().
        if (!isset($c['time'], $c['temperature_2m'], $c['relative_humidity_2m'], $c['weather_code'])
            || !is_numeric($c['temperature_2m']) || !is_numeric($c['relative_humidity_2m']) || !is_numeric($c['weather_code'])) {
            return null;
        }

        $code = (int) $c['weather_code'];
        return [
            'time'             => (string) $c['time'],                 // provider's data time (local, Asia/Manila)
            'temperature'      => (float) $c['temperature_2m'],
            'temperature_unit' => (string) ($u['temperature_2m'] ?? '°C'),
            'humidity'         => (float) $c['relative_humidity_2m'],
            'humidity_unit'    => (string) ($u['relative_humidity_2m'] ?? '%'),
            'code'             => $code,
            'condition'        => self::describe($code),
            'is_day'           => isset($c['is_day']) ? (int) $c['is_day'] === 1 : null,
        ];
    }

    /** WMO weather interpretation codes, as listed in the Open-Meteo documentation. */
    public static function describe(int $code): string
    {
        $map = [
            0 => 'Clear sky', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
            45 => 'Fog', 48 => 'Depositing rime fog',
            51 => 'Light drizzle', 53 => 'Moderate drizzle', 55 => 'Dense drizzle',
            56 => 'Light freezing drizzle', 57 => 'Dense freezing drizzle',
            61 => 'Slight rain', 63 => 'Moderate rain', 65 => 'Heavy rain',
            66 => 'Light freezing rain', 67 => 'Heavy freezing rain',
            71 => 'Slight snowfall', 73 => 'Moderate snowfall', 75 => 'Heavy snowfall', 77 => 'Snow grains',
            80 => 'Slight rain showers', 81 => 'Moderate rain showers', 82 => 'Violent rain showers',
            85 => 'Slight snow showers', 86 => 'Heavy snow showers',
            95 => 'Thunderstorm', 96 => 'Thunderstorm with slight hail', 99 => 'Thunderstorm with heavy hail',
        ];
        return $map[$code] ?? "Unknown condition (code {$code})";
    }

    private function fallback(?array $cached, int $now): array
    {
        if ($cached && $now - $cached['fetched_at'] < $this->config['stale_seconds']) {
            return $this->result('stale', $cached);
        }
        return [
            'state' => 'unavailable', 'data' => null, 'fetched_at' => null,
            'label' => $this->config['label'], 'message' => 'Weather information is temporarily unavailable.',
        ];
    }

    private function result(string $state, array $entry): array
    {
        return ['state' => $state, 'data' => $entry['data'], 'fetched_at' => $entry['fetched_at'], 'label' => $this->config['label'], 'message' => null];
    }

    // ---------------------------------------------------------- cache files (outside public/)

    private function path(string $file): string
    {
        return rtrim($this->config['cache_dir'], '/\\') . DIRECTORY_SEPARATOR . $file;
    }

    private function readJson(string $file): ?array
    {
        $path = $this->path($file);
        if (!is_file($path)) {
            return null;
        }
        $data = json_decode((string) @file_get_contents($path), true);
        return is_array($data) ? $data : null;
    }

    /** Atomic write: write a temp file, then rename it over the old one. */
    private function writeJson(string $file, array $data): void
    {
        $dir = rtrim($this->config['cache_dir'], '/\\');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return;
        }
        $tmp = $this->path($file . '.' . bin2hex(random_bytes(4)) . '.tmp');
        if (@file_put_contents($tmp, json_encode($data), LOCK_EX) !== false) {
            if (!@rename($tmp, $this->path($file))) {
                @unlink($tmp);
            }
        }
    }
}
