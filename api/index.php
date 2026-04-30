<?php
require __DIR__ . '/bootstrap.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$script = dirname($_SERVER['SCRIPT_NAME']);
$prefix = rtrim($script, '/');
$path = '/' . ltrim(substr($uri, strlen($prefix)), '/');
$segments = array_values(array_filter(explode('/', $path)));

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (($segments[0] ?? '') === 'config') {
    ConfigController::handle($_SERVER['REQUEST_METHOD'], $segments);
    return;
}

LinkController::handle($_SERVER['REQUEST_METHOD'], $segments);
