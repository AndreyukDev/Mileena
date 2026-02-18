<?php

declare(strict_types=1);

namespace Mileena\Web;

use Mileena\Config;

class Debugger
{
    public function __construct(private readonly Config $config) {}

    public function dump(mixed $var, ?bool $force = false, ?bool $stop = false): void
    {
        if ($this->config->get('app.debug') || $force) {
            echo "<pre style='background:#222; color:#0f0; padding:10px;'>";
            print_r($var);
            echo "</pre>";
        }

        if ($stop) {
            die;
        }
    }
}
