<?php
class ConfigController
{
    public static function handle(string $method, array $segments): void
    {
        if ($method !== 'GET' || ($segments[0] ?? '') !== 'config') {
            self::respond(404, ['error' => 'Endpoint not found']);
            return;
        }

        self::respond(200, ['data' => ['base_url' => $GLOBALS['config']['app']['base_url']]]);
    }

    private static function respond(int $status, ?array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
