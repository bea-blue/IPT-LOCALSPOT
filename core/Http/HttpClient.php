<?php
namespace SpotCore\Http;

/**
 * Small HTTP client contract. Services depend on this interface, so tests can
 * pass a fake client and never touch the network.
 */
interface HttpClient
{
    /**
     * @return array{status: int, body: string, error: ?string}
     *         status 0 means the request never got an HTTP answer (timeout, DNS, TLS...).
     */
    public function get(string $url, array $headers = []): array;
}
