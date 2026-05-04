<?php
// tests/Bootstrap.php

// Mock config for tests - use in-memory SQLite
$GLOBALS['config'] = [
    'db' => [
        'dsn' => 'sqlite::memory:',
        'user' => null,
        'pass' => null,
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ],
    ],
    'redis' => [
        'host' => 'localhost',
        'port' => 6379,
        'ttl' => 86400,
    ],
    'app' => [
        'base_url' => 'http://localhost:8000',
    ],
];

// Load models and services directly (no bootstrap)
require_once __DIR__ . '/../api/models/Link.php';
require_once __DIR__ . '/../api/models/Analytics.php';
require_once __DIR__ . '/../api/services/ShortCodeGenerator.php';
require_once __DIR__ . '/../api/services/UrlValidator.php';
require_once __DIR__ . '/../api/services/RateLimiter.php';
require_once __DIR__ . '/../api/services/Logger.php';
require_once __DIR__ . '/../api/controllers/LinkController.php';
require_once __DIR__ . '/../api/controllers/AnalyticsController.php';

// Initialize in-memory database for tests
Link::ensureSchema();
Analytics::ensureSchema();