<?php

declare(strict_types=1);

return [
    'isSMTP'   => true,
    'host'     => $_ENV['SMTP_HOST'] ?? 'localhost',
    'user'     => $_ENV['SMTP_USER'] ?? 'root',
    'pass'     => $_ENV['SMTP_PASS'] ?? '',
    'encryption'     => $_ENV['SMTP_ENC'] ?? '',
    'port'     => (int) ($_ENV['SMTP_PORT'] ?? 587),
    'charset'  => 'UTF-8',
    'formEmail' => 'no-reply@mileena.crm',
    'formName' => 'Mileena',
];
