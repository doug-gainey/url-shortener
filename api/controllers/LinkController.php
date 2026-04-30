<?php
class LinkController
{
    public static function handle(string $method, array $segments): void
    {
        if (empty($segments) || $segments[0] !== 'links') {
            self::respond(404, ['error' => 'Endpoint not found']);
            return;
        }

        $code = $segments[1] ?? null;
        $action = $segments[2] ?? null;

        switch ($method) {
            case 'GET':
                if ($code === null) {
                    self::list();
                    return;
                }

                if ($action === 'stats') {
                    self::stats($code);
                    return;
                }

                self::retrieve($code);
                return;
            case 'POST':
                self::create();
                return;
            case 'PUT':
                if ($code !== null) {
                    self::update($code);
                    return;
                }
                break;
            case 'DELETE':
                if ($code !== null) {
                    self::delete($code);
                    return;
                }
                break;
            default:
                self::respond(405, ['error' => 'Method not allowed']);
                return;
        }

        self::respond(404, ['error' => 'Endpoint not found']);
    }

    private static function list(): void
    {
        self::respond(200, ['data' => Link::findAll()]);
    }

    private static function retrieve(string $code): void
    {
        $link = Link::findByCode($code);
        if (!$link) {
            self::respond(404, ['error' => 'Link not found']);
            return;
        }

        self::respond(200, ['data' => $link]);
    }

    private static function create(): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $originalUrl = trim($body['original_url'] ?? '');
        $customAlias = trim($body['custom_alias'] ?? '');
        $expiresAt = trim($body['expires_at'] ?? '');

        if ($originalUrl === '') {
            self::respond(422, ['error' => 'original_url is required']);
            return;
        }

        if (!filter_var($originalUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $originalUrl)) {
            self::respond(422, ['error' => 'original_url must be a valid http or https URL']);
            return;
        }

        if ($customAlias !== '' && !preg_match('/^[a-zA-Z0-9_-]{4,64}$/', $customAlias)) {
            self::respond(422, ['error' => 'custom_alias must be 4-64 alphanumeric characters, underscores or hyphens']);
            return;
        }

        $code = $customAlias !== '' ? $customAlias : ShortCodeGenerator::uniqueCode(6);
        if (Link::existsCode($code)) {
            self::respond(409, ['error' => 'short code already exists']);
            return;
        }

        $link = Link::create([
            'short_code' => $code,
            'original_url' => $originalUrl,
            'custom_alias' => $customAlias ?: null,
            'expires_at' => $expiresAt ?: null,
        ]);

        self::respond(201, ['data' => $link]);
    }

    private static function update(string $code): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $updateData = [];

        if (array_key_exists('original_url', $body)) {
            $originalUrl = trim($body['original_url']);
            if ($originalUrl === '' || !filter_var($originalUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $originalUrl)) {
                self::respond(422, ['error' => 'original_url must be a valid http or https URL']);
                return;
            }
            $updateData['original_url'] = $originalUrl;
        }

        if (array_key_exists('custom_alias', $body)) {
            $customAlias = trim($body['custom_alias'] ?? '');
            if ($customAlias !== '' && !preg_match('/^[a-zA-Z0-9_-]{4,64}$/', $customAlias)) {
                self::respond(422, ['error' => 'custom_alias must be 4-64 alphanumeric characters, underscores or hyphens']);
                return;
            }
            if ($customAlias !== '' && $customAlias !== $code && Link::existsCode($customAlias)) {
                self::respond(409, ['error' => 'short code already exists']);
                return;
            }
            $updateData['custom_alias'] = $customAlias ?: null;
        }

        if (array_key_exists('expires_at', $body)) {
            $updateData['expires_at'] = trim($body['expires_at']) ?: null;
        }

        $link = Link::updateByCode($code, $updateData);
        if (!$link) {
            self::respond(404, ['error' => 'Link not found']);
            return;
        }

        self::respond(200, ['data' => $link]);
    }

    private static function stats(string $code): void
    {
        $link = Link::findByCode($code);
        if (!$link) {
            self::respond(404, ['error' => 'Link not found']);
            return;
        }

        self::respond(200, ['data' => ['clicks' => $link['clicks'], 'short_code' => $code]]);
    }

    private static function delete(string $code): void
    {
        if (!Link::deleteByCode($code)) {
            self::respond(404, ['error' => 'Link not found']);
            return;
        }

        // Remove cached URL
        RedisService::delete('url:' . $code);

        self::respond(204, null);
    }

    private static function respond(int $status, ?array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
