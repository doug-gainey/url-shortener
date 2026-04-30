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
}
