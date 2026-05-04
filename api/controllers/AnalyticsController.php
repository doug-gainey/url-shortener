<?php
class AnalyticsController
{
    public static function handle(string $method, array $segments): void
    {
        // segments[0] = 'links'
        // segments[1] = code
        // segments[2] = 'analytics'
        // segments[3] = optional action (summary, etc)

        if ($method !== 'GET') {
            self::respond(405, ['error' => 'Method not allowed']);
            return;
        }

        $code = $segments[1] ?? null;
        $action = $segments[3] ?? null;

        if (!$code) {
            self::respond(400, ['error' => 'Short code required']);
            return;
        }

        // Verify link exists
        $link = Link::findByCode($code);
        if (!$link) {
            self::respond(404, ['error' => 'Link not found']);
            return;
        }

        if ($action === 'summary') {
            self::summary($code);
        } else {
            self::list($code);
        }
    }

    private static function list(string $code): void
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int) $_GET['offset'] : 0;

        // Validate limits
        if ($limit < 1 || $limit > 500) {
            $limit = 50;
        }
        if ($offset < 0) {
            $offset = 0;
        }

        $analytics = Analytics::getAnalytics($code, $limit, $offset);
        $total = Analytics::getAnalyticsCount($code);

        self::respond(200, [
            'data' => $analytics,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'total' => $total,
                'has_more' => ($offset + $limit) < $total,
            ],
        ]);
    }

    private static function summary(string $code): void
    {
        $summary = Analytics::getSummary($code);

        self::respond(200, ['data' => $summary]);
    }

    private static function respond(int $statusCode, array $data): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
