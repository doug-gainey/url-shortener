<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requested = __DIR__ . $uri;

if ($uri !== '/' && file_exists($requested) && !is_dir($requested)) {
    return false;
}

if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/api/index.php';
    return;
}

require __DIR__ . '/redirect.php';
