<?php

declare(strict_types=1);

return [
    'driver'   => 'mysqli',
    'host'     => $_ENV['DB_HOST'] ?? 'localhost',
    'user'     => $_ENV['DB_USER'] ?? 'root',
    'pass'     => $_ENV['DB_PASS'] ?? '',
    'name'     => $_ENV['DB_NAME'] ?? 'mileena_db',
    'port'     => (int) ($_ENV['DB_PORT'] ?? 3306),
    'charset'  => 'utf8mb4',
    'ignore_duplicates' => filter_var($_ENV['DB_IGNORE_DUPLICATES'] ?? false, FILTER_VALIDATE_BOOLEAN),
];
