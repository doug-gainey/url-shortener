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

// Record analytics
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
$referrer = $_SERVER['HTTP_REFERER'] ?? null;
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$ipHash = hash('sha256', $clientIp . 'analytics_salt'); // TODO: Use a proper salt in production
Analytics::recordClick($code, $userAgent, $referrer, $ipHash);

$cacheKey = 'url:' . $code;
$originalUrl = RedisService::get($cacheKey);
if (!$originalUrl) {
    RedisService::set($cacheKey, $config['redis']['ttl'], $link['original_url']);
    $originalUrl = $link['original_url'];
}

header('Location: ' . $originalUrl, true, 302);
