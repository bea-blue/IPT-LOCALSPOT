<?php
namespace SpotCore\Http;

/**
 * Real HTTP client using PHP's cURL extension.
 * TLS certificate checks stay ON. If Windows reports "unable to get local issuer
 * certificate", set curl.cainfo in php.ini (or CURL_CA_BUNDLE in .env); never
 * disable verification.
 */
final class CurlHttpClient implements HttpClient
{
    private int $connectTimeout;
    private int $timeout;
    private ?string $caBundle;

    public function __construct(int $connectTimeout = 3, int $timeout = 5, ?string $caBundle = null)
    {
        $this->connectTimeout = $connectTimeout;
        $this->timeout = $timeout;
        $this->caBundle = $caBundle;
    }

    public function get(string $url, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            return ['status' => 0, 'body' => '', 'error' => 'The PHP cURL extension is not enabled.'];
        }

        $ch = curl_init($url);
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => array_merge(['Accept: application/json'], $headers),
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'LocalSpot/1.0 (student project)',
        ];
        if ($this->caBundle && is_file($this->caBundle)) {
            $options[CURLOPT_CAINFO] = $this->caBundle;
        }
        curl_setopt_array($ch, $options);

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = null;
        if ($body === false) {
            $error = curl_error($ch);
            $body = '';
            $status = 0;
        }
        curl_close($ch);

        return ['status' => $status, 'body' => (string) $body, 'error' => $error];
    }
}
