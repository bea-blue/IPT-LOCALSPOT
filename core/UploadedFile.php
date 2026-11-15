<?php
namespace SpotCore;

/**
 * One file from a multipart form ($_FILES entry). The real type is detected from
 * the file's first bytes (magic numbers), never from the browser-supplied name or
 * MIME type, so "permit.php.jpg" or a renamed script is rejected.
 */
final class UploadedFile
{
    /** Detected type => file extension used when storing. */
    public const TYPES = ['jpeg' => 'jpg', 'png' => 'png', 'webp' => 'webp', 'pdf' => 'pdf'];

    public function __construct(
        private string $tmpPath,
        private string $clientName,
        private int $size,
        private int $error,
        private bool $fromHttpUpload = true
    ) {
    }

    /** @return self[] all files posted under one field name (single or name[] multiple). */
    public static function fromFilesArray(array $entry): array
    {
        if (!isset($entry['tmp_name'])) {
            return [];
        }
        if (!is_array($entry['tmp_name'])) {
            return [new self((string) $entry['tmp_name'], (string) ($entry['name'] ?? ''), (int) ($entry['size'] ?? 0), (int) ($entry['error'] ?? UPLOAD_ERR_NO_FILE))];
        }
        $files = [];
        foreach ($entry['tmp_name'] as $i => $tmp) {
            if (is_array($tmp)) {
                continue; // deeper nesting is never used by LocalSpot forms
            }
            $files[] = new self((string) $tmp, (string) ($entry['name'][$i] ?? ''), (int) ($entry['size'][$i] ?? 0), (int) ($entry['error'][$i] ?? UPLOAD_ERR_NO_FILE));
        }
        return $files;
    }

    public function wasSent(): bool { return $this->error !== UPLOAD_ERR_NO_FILE; }
    public function size(): int { return $this->size; }
    public function clientName(): string { return mb_substr(basename(str_replace('\\', '/', $this->clientName)), 0, 150); }

    /** Detects jpeg | png | webp | pdf from the first bytes, or null for anything else. */
    public static function sniff(string $head): ?string
    {
        if (strncmp($head, "\xFF\xD8\xFF", 3) === 0) {
            return 'jpeg';
        }
        if (strncmp($head, "\x89PNG\r\n\x1A\n", 8) === 0) {
            return 'png';
        }
        if (strncmp($head, 'RIFF', 4) === 0 && substr($head, 8, 4) === 'WEBP') {
            return 'webp';
        }
        if (strncmp($head, '%PDF-', 5) === 0) {
            return 'pdf';
        }
        return null;
    }

    /**
     * Checks the upload and returns [type, null] or [null, error message].
     * @param string[] $allowed subset of jpeg|png|webp|pdf
     */
    public function check(array $allowed, int $maxBytes): array
    {
        if ($this->error === UPLOAD_ERR_INI_SIZE || $this->error === UPLOAD_ERR_FORM_SIZE) {
            return [null, 'is larger than ' . self::mb($maxBytes) . '.'];
        }
        if ($this->error !== UPLOAD_ERR_OK) {
            return [null, 'could not be uploaded. Please try again.'];
        }
        if ($this->fromHttpUpload && !is_uploaded_file($this->tmpPath)) {
            return [null, 'could not be uploaded. Please try again.'];
        }
        if ($this->size <= 0 || $this->size > $maxBytes) {
            return [null, $this->size <= 0 ? 'is empty.' : 'is larger than ' . self::mb($maxBytes) . '.'];
        }
        $type = self::sniff((string) file_get_contents($this->tmpPath, false, null, 0, 16));
        if ($type === null || !in_array($type, $allowed, true)) {
            return [null, 'must be ' . self::describe($allowed) . '.'];
        }
        if ($type !== 'pdf' && @getimagesize($this->tmpPath) === false) {
            return [null, 'is not a readable image.'];
        }
        return [$type, null];
    }

    /** Moves the file into $dir under a random name with the extension of its detected type. */
    public function store(string $dir, string $type): string
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Upload folder could not be created.');
        }
        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$type];
        $target = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
        $ok = $this->fromHttpUpload ? move_uploaded_file($this->tmpPath, $target) : rename($this->tmpPath, $target);
        if (!$ok) {
            throw new \RuntimeException('Upload could not be saved.');
        }
        return $name;
    }

    private static function mb(int $bytes): string { return round($bytes / 1048576, 1) . ' MB'; }

    private static function describe(array $allowed): string
    {
        $names = array_map(fn($t) => strtoupper($t === 'jpeg' ? 'jpg' : $t), $allowed);
        return count($names) > 1 ? implode(', ', array_slice($names, 0, -1)) . ' or ' . end($names) : $names[0];
    }
}
