<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';

use Mileena\Config;
use Mileena\Public\App;

session_start();

$app = new Mileena\Web\WebApp(new Config(__DIR__));
date_default_timezone_set(App::cfg('app.timezone'));

$app->webRoute();
