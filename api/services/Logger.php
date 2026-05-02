<?php
class Logger
{
    private const LOG_FILE = __DIR__ . '/../../logs/app.log';

    public static function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = empty($context) ? '' : ' ' . json_encode($context);

        $logEntry = sprintf("[%s] %s: %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        // Ensure log directory exists
        $logDir = dirname(self::LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents(self::LOG_FILE, $logEntry, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function hashIp(string $ip): string
    {
        return hash('sha256', $ip . 'salt'); // Use a proper salt in production
    }
}
