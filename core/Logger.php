<?php
namespace SpotCore;

/** Appends lines to storage/logs/app-YYYY-MM-DD.log */
final class Logger
{
    private static string $path = '';

    public static function setPath(string $path): void { self::$path = rtrim($path, '/'); }

    public static function info(string $message, array $context = []): void { self::write('INFO', $message, $context); }
    public static function warning(string $message, array $context = []): void { self::write('WARNING', $message, $context); }
    public static function error(string $message, array $context = []): void { self::write('ERROR', $message, $context); }

    private static function write(string $level, string $message, array $context): void
    {
        if (self::$path === '') {
            return;
        }
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context ? json_encode($context, JSON_UNESCAPED_SLASHES) : '',
        );
        @file_put_contents(self::$path . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }
}
