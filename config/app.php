<?php

declare(strict_types=1);

return [
    'hello' => $_ENV['MILEENA_HELLO'] ?? 'Hello by default config',
    'env'       => $_ENV['APP_ENV'] ?? 'prod',
    'debug'     => (bool) ($_ENV['APP_DEBUG'] ?? false),
    'timezone'  => 'UTC',
    'charset'   => 'UTF-8',
    'default_controller' => 'default',
    'default_controller_method' => 'index',
    'default_login_page' => '/default/login',
    'controller_namespace' => 'Mileena\\Public\\Controller\\',
    'controllers' => [
        'default' => 'Default',
    ],
];
