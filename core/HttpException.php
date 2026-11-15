<?php
namespace SpotCore;

/** Throw anywhere (e.g. abort(404)) and the kernel turns it into an error page. */
final class HttpException extends \RuntimeException
{
    private int $status;
    private array $headers;

    public function __construct(int $status, string $message = '', array $headers = [])
    {
        parent::__construct($message, $status);
        $this->status = $status;
        $this->headers = $headers;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function headers(): array
    {
        return $this->headers;
    }
}
