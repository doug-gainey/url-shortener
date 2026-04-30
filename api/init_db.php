<?php
require __DIR__ . '/bootstrap.php';

Link::ensureSchema();

echo "SQLite database initialized at: {$config['db']['dsn']}\n";
