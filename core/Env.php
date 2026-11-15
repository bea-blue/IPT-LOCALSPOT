<?php
namespace SpotCore;

/**
 * Reads KEY=value pairs from a .env file so secrets (DB password,
 * API keys) never live inside the source code.
 */
final class Env
{
    private static array $vars = [];

    public static function load(string $path): void
    {
        if (!is_file($path)) {
            throw new \RuntimeException(".env file not found at {$path}. Copy .env.example to .env first.");
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            self::$vars[$key] = trim($value, "\"'");
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::$vars[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return match (strtolower((string) $value)) {
            'true'  => true,
            'false' => false,
            'null'  => null,
            default => $value,
        };
    }

    public static function set(string $key, string $value): void
    {
        self::$vars[$key] = $value;
    }
}
