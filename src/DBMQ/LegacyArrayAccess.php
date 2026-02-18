<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

trait LegacyArrayAccess
{
    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        // Позволяет обращаться как $this->{'active'}
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (property_exists($this, $offset)) {
            $this->$offset = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if (property_exists($this, $offset)) {
            // Для мутабельных DTO часто лучше занулять, чем ассеттить
            $this->$offset = null;
        }
    }
}
