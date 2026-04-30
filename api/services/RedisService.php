<?php
class RedisService
{
    private static ?Redis $client = null;

    public static function client(): Redis
    {
        global $config;

        if (self::$client instanceof Redis) {
            return self::$client;
        }

        if (!extension_loaded('redis')) {
            throw new RuntimeException('The phpredis extension is required for Redis support.');
        }

        $client = new Redis();
        $client->connect($config['redis']['host'], $config['redis']['port']);
        self::$client = $client;
        return self::$client;
    }

    public static function get(string $key): ?string
    {
        try {
            return self::client()->get($key) ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public static function delete(string $key): ?string
    {
        try {
            return self::client()->del($key) ?: null;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function set(string $key, int $ttl, string $value): bool
    {
        try {
            return self::client()->set($key, $value, ['ex' => $ttl]);
        } catch (Throwable $e) {
            return false;
        }
    }
}
