<?php
class UrlValidator
{
    private const RESERVED_CODES = [
        'api', 'admin', 'static', 'login', 'dashboard', 'health', 'favicon.ico'
    ];

    public static function validate(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        if (!preg_match('#^https?://#i', $url)) {
            return false;
        }

        // Optional: Reject known phishing/malware domains
        // You could add a DNS check here or maintain a blacklist

        return true;
    }

    public static function validateCustomAlias(string $alias): bool
    {
        if (strlen($alias) < 4 || strlen($alias) > 64) {
            return false;
        }

        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $alias)) {
            return false;
        }

        if (in_array(strtolower($alias), self::RESERVED_CODES, true)) {
            return false;
        }

        return true;
    }

    public static function normalize(string $url): string
    {
        $parsed = parse_url($url);

        if (!$parsed) {
            return $url;
        }

        // Lowercase scheme and host
        $scheme = strtolower($parsed['scheme'] ?? 'http');
        $host = strtolower($parsed['host'] ?? '');

        // Strip default ports
        $port = $parsed['port'] ?? null;
        if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
            $port = null;
        }

        // Rebuild URL
        $normalized = $scheme . '://' . $host;
        if ($port) {
            $normalized .= ':' . $port;
        }
        if (isset($parsed['path'])) {
            $normalized .= $parsed['path'];
        }
        if (isset($parsed['query'])) {
            $normalized .= '?' . $parsed['query'];
        }
        if (isset($parsed['fragment'])) {
            $normalized .= '#' . $parsed['fragment'];
        }

        return $normalized;
    }
}
