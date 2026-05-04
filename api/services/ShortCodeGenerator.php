<?php
class ShortCodeGenerator
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public static function generate(int $length = 6): string
    {
        $pool = self::ALPHABET;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return $result;
    }

    public static function uniqueCode(int $length = 6): string
    {
        $tries = 0;
        do {
            $code = self::generate($length);
            $tries++;
            if ($tries > 10) {
                $length++;
            }
        } while (Link::existsCode($code));

        return $code;
    }

    public static function isValidCustom(string $code): bool
    {
        // Must be 3-64 characters
        if (strlen($code) < 3 || strlen($code) > 64) {
            return false;
        }

        // Must contain only alphanumeric characters and hyphens
        if (!preg_match('/^[a-zA-Z0-9-]+$/', $code)) {
            return false;
        }

        // Must not be in reserved codes list
        $reserved = ['api', 'admin', 'static', 'login', 'dashboard', 'health', 'favicon.ico'];
        if (in_array(strtolower($code), $reserved)) {
            return false;
        }

        return true;
    }
}
