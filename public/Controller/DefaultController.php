<?php

declare(strict_types=1);

namespace Mileena\Public\Controller;

use Mileena\Public\App;
use Mileena\Web\AllowPublicAccess;

class DefaultController implements AllowPublicAccess
{
    public function index(): void
    {
        echo App::cfg('app.hello');
    }
}
