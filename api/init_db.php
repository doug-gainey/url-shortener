<?php
require __DIR__ . '/bootstrap.php';

Link::ensureSchema();
Analytics::ensureSchema();

echo "SQLite database initialized at: {$config['db']['dsn']}\n";
