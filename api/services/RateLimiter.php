<?php
class RateLimiter
{
    private const MAX_REQUESTS_PER_MINUTE = 10;
    private const WINDOW_SECONDS = 60;

    public static function isRateLimited(string $identifier): bool
    {
        $key = 'ratelimit:' . $identifier;
        $current = RedisService::get($key);

        if ($current === null) {
            // First request, set counter to 1
            RedisService::set($key, self::WINDOW_SECONDS, '1');
            return false;
        }

        $count = (int) $current;
        if ($count >= self::MAX_REQUESTS_PER_MINUTE) {
            return true;
        }

        // Increment counter
        RedisService::set($key, self::WINDOW_SECONDS, (string) ($count + 1));
        return false;
    }

    public static function getClientIdentifier(): string
    {
        // Use hashed IP for privacy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return hash('sha256', $ip . 'salt'); // Use a proper salt in production
    }
}
