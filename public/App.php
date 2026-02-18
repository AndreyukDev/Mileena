<?php

declare(strict_types=1);

namespace Mileena\Public;

use Mileena\Web\WebApp;

class App
{
    public static function cfg(string $key, mixed $default = null): mixed
    {
        return WebApp::getInstance()->config->get($key, $default);
    }

    public static function d(mixed $var, ?bool $stop = false): void
    {
        WebApp::getInstance()->debugger->dump($var, \Mileena\Web\Auth::canDebug(), $stop);
    }
}
