<?php
require __DIR__ . '/api/bootstrap.php';

$code = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($code === '') {
    http_response_code(404);
    echo 'Short code required.';
    exit;
}

$link = Link::findByCode($code);
if (!$link) {
    http_response_code(404);
    echo 'Link not found.';
    exit;
}

if (isset($link['is_active']) && $link['is_active'] == 0) {
    http_response_code(410);
    echo 'Link deactivated.';
    exit;
}

if ($link['expires_at'] !== null && strtotime($link['expires_at']) < time()) {
    http_response_code(410);
    echo 'Link expired.';
    exit;
}

Link::incrementClicks($code);

$cacheKey = 'url:' . $code;
$originalUrl = RedisService::get($cacheKey);
if (!$originalUrl) {
    RedisService::set($cacheKey, $config['redis']['ttl'], $link['original_url']);
    $originalUrl = $link['original_url'];
}

header('Location: ' . $originalUrl, true, 302);
