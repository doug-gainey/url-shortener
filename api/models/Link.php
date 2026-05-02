<?php
class Link
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
CREATE TABLE IF NOT EXISTS links (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    short_code VARCHAR(16) NOT NULL UNIQUE,
    original_url TEXT NOT NULL,
    custom_alias VARCHAR(64),
    expires_at DATETIME NULL,
    clicks INTEGER NOT NULL DEFAULT 0,
    last_clicked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_short_code ON links(short_code);
CREATE INDEX IF NOT EXISTS idx_expires_at ON links(expires_at);
CREATE INDEX IF NOT EXISTS idx_created_at ON links(created_at);
CREATE INDEX IF NOT EXISTS idx_last_clicked_at ON links(last_clicked_at);
SQL;
        self::db()->exec($sql);
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = self::db()->prepare('SELECT * FROM links WHERE short_code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);
        $link = $stmt->fetch();
        return $link ?: null;
    }

    public static function findAll(): array
    {
        $stmt = self::db()->query('SELECT * FROM links ORDER BY created_at DESC');
        return $stmt->fetchAll();
    }

    public static function create(array $data): array
    {
        $stmt = self::db()->prepare('INSERT INTO links (short_code, original_url, custom_alias, expires_at) VALUES (:short_code, :original_url, :custom_alias, :expires_at)');
        $stmt->execute([
            ':short_code' => $data['short_code'],
            ':original_url' => $data['original_url'],
            ':custom_alias' => $data['custom_alias'] ?? null,
            ':expires_at' => $data['expires_at'] ?? null,
        ]);

        return self::findByCode($data['short_code']);
    }

    public static function deleteByCode(string $code): bool
    {
        $stmt = self::db()->prepare('DELETE FROM links WHERE short_code = :code');
        return $stmt->execute([':code' => $code]);
    }

    public static function updateByCode(string $code, array $data): ?array
    {
        $fields = [];
        $params = [':code' => $code];

        if (isset($data['original_url'])) {
            $fields[] = 'original_url = :original_url';
            $params[':original_url'] = $data['original_url'];
        }

        if (array_key_exists('custom_alias', $data)) {
            $fields[] = 'short_code = :short_code';
            $fields[] = 'custom_alias = :custom_alias';
            $params[':short_code'] = $data['custom_alias'] ?: $code;
            $params[':custom_alias'] = $data['custom_alias'] ?: null;
        }

        if (array_key_exists('expires_at', $data)) {
            $fields[] = 'expires_at = :expires_at';
            $params[':expires_at'] = $data['expires_at'] ?: null;
        }

        if (empty($fields)) {
            return self::findByCode($code);
        }

        $sql = 'UPDATE links SET ' . implode(', ', $fields) . ' WHERE short_code = :code';
        $stmt = self::db()->prepare($sql);
        $stmt->execute($params);

        $newCode = $params[':short_code'] ?? $code;
        return self::findByCode($newCode);
    }

    public static function incrementClicks(string $code): void
    {
        $stmt = self::db()->prepare('UPDATE links SET clicks = clicks + 1, last_clicked_at = CURRENT_TIMESTAMP WHERE short_code = :code');
        $stmt->execute([':code' => $code]);
    }

    public static function existsCode(string $code): bool
    {
        $stmt = self::db()->prepare('SELECT 1 FROM links WHERE short_code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);
        return (bool) $stmt->fetchColumn();
    }
}
