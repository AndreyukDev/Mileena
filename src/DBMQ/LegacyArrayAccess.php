<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

trait LegacyArrayAccess
{
    public function offsetExists(mixed $offset): bool
    {
        $offset = self::snakeToCamelStr($offset);

        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $offset = self::snakeToCamelStr($offset);

        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $offset = self::snakeToCamelStr($offset);

        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        $offset = self::snakeToCamelStr($offset);

        if (property_exists($this, $offset)) {
            $this->$offset = null;
        }
    }

    private static function snakeToCamelStr(mixed $snake): string
    {
        if (is_string($snake)) {
            return lcfirst(str_replace('_', '', ucwords($snake, '_')));
        }

        return $snake;
    }
}
