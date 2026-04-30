<?php
return [
    'db' => [
        'dsn' => 'sqlite:' . __DIR__ . '/../../data/database.sqlite',
        'user' => null,
        'pass' => null,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
    'redis' => [
        'host' => getenv('REDIS_HOST') ?: 'redis',
        'port' => getenv('REDIS_PORT') ?: 6379,
        'ttl' => 86400,
    ],
    'app' => [
        'base_url' => getenv('APP_BASE_URL') ?: 'http://localhost:8000',
    ],
];
