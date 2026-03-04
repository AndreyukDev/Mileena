<?php

declare(strict_types=1);

namespace Mileena\Web;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class AllowPublicAccess {}
