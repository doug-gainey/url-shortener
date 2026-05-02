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
        $clientId = RateLimiter::getClientIdentifier();
        if (RateLimiter::isRateLimited($clientId)) {
            Logger::warning('Rate limit exceeded', ['client_id' => $clientId]);
            self::respond(429, ['error' => 'Rate limit exceeded. Please try again later.']);
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $originalUrl = trim($body['original_url'] ?? '');
        $customAlias = trim($body['custom_alias'] ?? '');
        $expiresAt = trim($body['expires_at'] ?? '');

        if ($originalUrl === '') {
            Logger::warning('Missing original_url in create request', ['client_id' => $clientId]);
            self::respond(422, ['error' => 'original_url is required']);
            return;
        }

        if (!UrlValidator::validateUrl($originalUrl)) {
            Logger::warning('Invalid original_url in create request', ['client_id' => $clientId, 'url' => $originalUrl]);
            self::respond(422, ['error' => 'original_url must be a valid http or https URL']);
            return;
        }

        $normalizedUrl = UrlValidator::normalizeUrl($originalUrl);

        if ($customAlias !== '' && !UrlValidator::validateCustomAlias($customAlias)) {
            Logger::warning('Invalid custom_alias in create request', ['client_id' => $clientId, 'alias' => $customAlias]);
            self::respond(422, ['error' => 'custom_alias must be 4-64 alphanumeric characters, underscores or hyphens, and not a reserved code']);
            return;
        }

        $code = $customAlias !== '' ? $customAlias : ShortCodeGenerator::uniqueCode(6);
        if (Link::existsCode($code)) {
            Logger::warning('Duplicate short code in create request', ['client_id' => $clientId, 'code' => $code]);
            self::respond(409, ['error' => 'short code already exists']);
            return;
        }

        $link = Link::create([
            'short_code' => $code,
            'original_url' => $normalizedUrl,
            'custom_alias' => $customAlias ?: null,
            'expires_at' => $expiresAt ?: null,
        ]);

        Logger::info('Link created successfully', ['client_id' => $clientId, 'code' => $code, 'url' => $normalizedUrl]);
        self::respond(201, ['data' => $link]);
    }

    private static function update(string $code): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        $updateData = [];

        if (array_key_exists('original_url', $body)) {
            $originalUrl = trim($body['original_url']);
            if ($originalUrl === '' || !UrlValidator::validateUrl($originalUrl)) {
                self::respond(422, ['error' => 'original_url must be a valid http or https URL']);
                return;
            }
            $updateData['original_url'] = UrlValidator::normalizeUrl($originalUrl);
        }

        if (array_key_exists('custom_alias', $body)) {
            $customAlias = trim($body['custom_alias'] ?? '');
            if ($customAlias !== '' && !UrlValidator::validateCustomAlias($customAlias)) {
                self::respond(422, ['error' => 'custom_alias must be 4-64 alphanumeric characters, underscores or hyphens, and not a reserved code']);
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

        if (array_key_exists('is_active', $body)) {
            $updateData['is_active'] = filter_var($body['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($updateData['is_active'] === null) {
                self::respond(422, ['error' => 'is_active must be a boolean']);
                return;
            }
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

        $stats = [
            'short_code' => $link['short_code'],
            'original_url' => $link['original_url'],
            'custom_alias' => $link['custom_alias'],
            'clicks' => $link['clicks'],
            'last_clicked_at' => $link['last_clicked_at'],
            'is_active' => $link['is_active'],
            'created_at' => $link['created_at'],
            'expires_at' => $link['expires_at'],
        ];

        self::respond(200, ['data' => $stats]);
    }

    private static function delete(string $code): void
    {
        $permanent = isset($_GET['permanent']) && filter_var($_GET['permanent'], FILTER_VALIDATE_BOOLEAN);

        if ($permanent) {
            if (!Link::permanentlyDeleteByCode($code)) {
                self::respond(422, ['error' => 'Link must be deactivated before permanent deletion']);
                return;
            }
            RedisService::delete('url:' . $code);
            Logger::info('Link permanently deleted', ['code' => $code]);
        } else {
            if (!Link::deleteByCode($code)) {
                self::respond(404, ['error' => 'Link not found']);
                return;
            }
            RedisService::delete('url:' . $code);
            Logger::info('Link deactivated', ['code' => $code]);
        }

        self::respond(204, null);
    }

    private static function respond(int $status, ?array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
