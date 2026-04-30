<?php
$config = require __DIR__ . '/config/config.php';

require_once __DIR__ . '/models/Link.php';
require_once __DIR__ . '/services/ShortCodeGenerator.php';
require_once __DIR__ . '/services/RedisService.php';
require_once __DIR__ . '/controllers/LinkController.php';
require_once __DIR__ . '/controllers/ConfigController.php';

Link::ensureSchema();
