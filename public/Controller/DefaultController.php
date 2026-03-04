<?php

declare(strict_types=1);

namespace Mileena\Public\Controller;

use Mileena\Public\App;
use Mileena\Web\AllowPublicAccess;

#[AllowPublicAccess]
class DefaultController
{
    public function index(): void
    {
        echo App::cfg('app.hello');
    }
}
