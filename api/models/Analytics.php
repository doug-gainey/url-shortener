<?php
class Analytics
{
    public static function db(): PDO
    {
        global $config;
        static $pdo;
        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $pdo = new PDO($config['db']['dsn'], $config['db']['user'], $config['db']['pass'], $config['db']['options']);
        return $pdo;
    }

    public static function ensureSchema(): void
    {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS analytics (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    short_code VARCHAR(16) NOT NULL,
    user_agent TEXT,
    referrer TEXT,
    ip_hash VARCHAR(64),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (short_code) REFERENCES links(short_code)
);

CREATE INDEX IF NOT EXISTS idx_short_code ON analytics(short_code);
CREATE INDEX IF NOT EXISTS idx_created_at ON analytics(created_at);
CREATE INDEX IF NOT EXISTS idx_ip_hash ON analytics(ip_hash);
SQL;
        self::db()->exec($sql);
    }

    public static function recordClick(string $code, ?string $userAgent = null, ?string $referrer = null, ?string $ipHash = null): bool
    {
        $stmt = self::db()->prepare('INSERT INTO analytics (short_code, user_agent, referrer, ip_hash) VALUES (:short_code, :user_agent, :referrer, :ip_hash)');
        return $stmt->execute([
            ':short_code' => $code,
            ':user_agent' => $userAgent ?? null,
            ':referrer' => $referrer ?? null,
            ':ip_hash' => $ipHash ?? null,
        ]);
    }

    public static function getAnalytics(string $code, int $limit = 100, int $offset = 0): array
    {
        $stmt = self::db()->prepare('SELECT * FROM analytics WHERE short_code = :short_code ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue(':short_code', $code);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getAnalyticsCount(string $code): int
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) as total FROM analytics WHERE short_code = :short_code');
        $stmt->execute([':short_code' => $code]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int) $result['total'];
    }

    public static function getSummary(string $code): array
    {
        $stmt = self::db()->prepare('SELECT COUNT(*) as total_clicks, COUNT(DISTINCT ip_hash) as unique_ips, COUNT(DISTINCT user_agent) as unique_agents FROM analytics WHERE short_code = :short_code');
        $stmt->execute([':short_code' => $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function deleteByCode(string $code): bool
    {
        $stmt = self::db()->prepare('DELETE FROM analytics WHERE short_code = :short_code');
        return $stmt->execute([':short_code' => $code]);
    }
}
