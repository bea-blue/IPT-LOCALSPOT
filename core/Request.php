<?php
namespace SpotCore;

/**
 * Wraps the incoming HTTP request ($_SERVER, $_GET, $_POST, $_FILES) in one object
 * so controllers never touch superglobals directly.
 */
final class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $body;
    private array $server;
    private array $params = [];
    private array $attributes = [];
    private array $files = [];

    public function __construct(string $method, string $path, array $query = [], array $body = [], array $server = [], array $files = [])
    {
        $this->files = $files;
        $this->method = strtoupper($method);
        $this->path = self::normalizePath($path);
        $this->query = $query;
        $this->body = $body;
        $this->server = $server;
    }

    /** Builds a Request from PHP's superglobals, removing the configured base path. */
    public static function capture(string $basePath = ''): self
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim($basePath, '/');
        if ($base !== '' && strncmp($uri, $base, strlen($base)) === 0) {
            $uri = substr($uri, strlen($base));
        }

        return new self($_SERVER['REQUEST_METHOD'] ?? 'GET', $uri, $_GET, $_POST, $_SERVER, $_FILES);
    }

    public static function normalizePath(string $path): string
    {
        $path = '/' . trim(rawurldecode($path), '/');
        return preg_replace('#/+#', '/', $path);
    }

    public function method(): string { return $this->method; }
    public function path(): string { return $this->path; }

    public function query(string $key, mixed $default = null): mixed
    {
        $value = $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    /** All query-string values (used by filter parsers). */
    public function queryAll(): array
    {
        return $this->query;
    }

    /** Form (POST) field, trimmed. Arrays are rejected to keep inputs simple strings. */
    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $default;
        if (is_array($value)) {
            return $default;
        }
        return is_string($value) ? trim($value) : $value;
    }

    public function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = $this->input($key, '');
        }
        return $out;
    }

    /** @return UploadedFile[] files sent under a form field (empty when none). */
    public function files(string $key): array
    {
        $files = UploadedFile::fromFilesArray($this->files[$key] ?? []);
        return array_values(array_filter($files, fn(UploadedFile $f) => $f->wasSent()));
    }

    public function file(string $key): ?UploadedFile
    {
        return $this->files($key)[0] ?? null;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function setParams(array $params): void { $this->params = $params; }

    public function setAttribute(string $key, mixed $value): void { $this->attributes[$key] = $value; }
    public function attribute(string $key, mixed $default = null): mixed { return $this->attributes[$key] ?? $default; }

    public function isSecure(): bool
    {
        return (!empty($this->server['HTTPS']) && $this->server['HTTPS'] !== 'off')
            || (($this->server['SERVER_PORT'] ?? null) == 443);
    }
}
