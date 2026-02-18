<?php

declare(strict_types=1);

spl_autoload_register(function ($class): void {
    $checkNamespace = explode("\\", $class);

    if (array_shift($checkNamespace) == 'Mileena') {
        $filename = __DIR__ . DIRECTORY_SEPARATOR . join(DIRECTORY_SEPARATOR, $checkNamespace) . '.php';

        if (is_readable($filename)) {
            require $filename;
        }
    }
});
