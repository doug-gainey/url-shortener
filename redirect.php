<?php
require __DIR__ . '/api/bootstrap.php';
require __DIR__ . '/api/error_page.php';

$code = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
if ($code === '') {
    outputErrorPage('Short code required.', 'Please provide a valid short code in the URL.', 400);
    exit;
}

$link = Link::findByCode($code);
if (!$link) {
    outputErrorPage('Link not found.', 'The requested short link does not exist or may have been removed.', 404);
    exit;
}

if (isset($link['is_active']) && $link['is_active'] == 0) {
    outputErrorPage('Link deactivated.', 'This link has been deactivated by the owner.', 410);
    exit;
}

if ($link['expires_at'] !== null && strtotime($link['expires_at']) < time()) {
    outputErrorPage('Link expired.', 'This link has expired and is no longer available.', 410);
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
