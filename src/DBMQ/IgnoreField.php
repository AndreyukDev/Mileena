<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS  | Attribute::TARGET_PROPERTY)]
class IgnoreField {}
