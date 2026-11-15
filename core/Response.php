<?php
namespace SpotCore;

/**
 * Everything a controller returns becomes a Response: status, headers and body.
 * The kernel calls send() once at the very end.
 */
final class Response
{
    private string $body;
    private int $status;
    private array $headers;

    public function __construct(string $body = '', int $status = 200, array $headers = ['Content-Type' => 'text/html; charset=UTF-8'])
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        return new self(
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $status,
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }

    /**
     * Redirects only to paths inside this app. Anything that looks like another
     * site ("https://evil.test", "//evil.test") falls back to the home page.
     */
    public static function redirect(string $path, int $status = 303): self
    {
        if (!self::isInternalPath($path)) {
            $path = '/';
        }
        return new self('', $status, ['Location' => url($path)]);
    }

    public static function isInternalPath(string $path): bool
    {
        return $path !== ''
            && $path[0] === '/'
            && !str_starts_with($path, '//')
            && !str_contains($path, '\\')
            && !preg_match('#[\r\n]#', $path);
    }

    public function status(): int { return $this->status; }
    public function body(): string { return $this->body; }
    public function header(string $name): ?string { return $this->headers[$name] ?? null; }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;
        return $clone;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        echo $this->body;
    }
}
