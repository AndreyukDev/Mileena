<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

use vendor\andreyukdev\mileena\src\DBMQ\IgnoreField;

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

    /** @var array<string, bool> */
    #[IgnoreField]
    private array $dirty = [];

    #[IgnoreField]
    private bool $dirtyEnabled = false;

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Update specific fields (marks them as dirty)
     */
    public function update(array $changes): self
    {
        $this->enableDirtyTracking();

        foreach ($changes as $field => $value) {
            if (!property_exists($this, $field)) {
                throw new \InvalidArgumentException("Property {$field} does not exist");
            }

            if ($this->$field !== $value) {
                $this->dirty[$field] = true;
                $this->$field = $value;
            }
        }

        return $this;
    }

    public function getDirtyFields(): array
    {
        if (empty($this->dirty)) {
            return [];
        }

        $allData = $this->toArray();
        $reflection = new \ReflectionClass($this);
        $result = [];

        foreach ($this->dirty as $field => $bool) {
            $prop = $reflection->getProperty($field);

            if (!empty($prop->getAttributes(IgnoreField::class))) {
                continue;
            }

            if (array_key_exists($field, $allData)) {
                $result[$field] = $allData[$field];
            }
        }

        return $result;
    }

    public function enableDirtyTracking(): void
    {
        $this->dirtyEnabled = true;
    }

    /**
     * Disable dirty tracking for this object
     */
    public function disableDirtyTracking(): self
    {
        $this->dirtyEnabled = false;
        $this->dirty = [];

        return $this;
    }

    public function isDirty(): bool
    {
        return $this->dirtyEnabled;
    }
}
