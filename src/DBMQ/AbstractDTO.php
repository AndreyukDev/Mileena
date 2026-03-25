<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

/**
 * Abstract base class for Data Transfer Objects.
 *
 * Provides a default implementation of the DTO interface,
 * including array conversion via the HasToArray trait.
 *
 * Extend this class to create type-safe DTOs with automatic
 * serialization and deserialization capabilities.
 *
 * @see DTO
 * @see HasToArray
 */
abstract class AbstractDTO implements DTO, \JsonSerializable
{
    use HasToArray;

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
