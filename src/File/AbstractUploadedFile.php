<?php

declare(strict_types=1);

namespace Mileena\File;

abstract class AbstractUploadedFile implements UploadFile, \JsonSerializable
{
    public function __construct(
        protected string $name,
        protected string $relativePath,
        protected int $size,
        protected string $mimeType,
    ) {}

    abstract public static function getBaseDir(): string;
    abstract public static function getBaseUrl(): string;
    abstract public static function getAllowedMimeTypes(): array;
    abstract public static function getMaxFileSize(): int;

    public function getName(): string
    {
        return $this->name;
    }
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }
    public function getPath(): string
    {
        return static::getBaseDir() . $this->relativePath;
    }
    public function getUrl(): string
    {
        return static::getBaseUrl() . $this->relativePath;
    }
    public function getSize(): int
    {
        return $this->size;
    }
    public function getMimeType(): string
    {
        return $this->mimeType;
    }
    public function getExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getFileName(): string
    {
        return uniqid('', true) . '.' . $this->getExtension();
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    public static function fromArray(array $data): self
    {
        return new static(
            name: $data['name'] ?? '',
            relativePath: $data['path'] ?? '',
            size: (int) ($data['size'] ?? 0),
            mimeType: $data['mime'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'path' => $this->relativePath,
            'size' => $this->size,
            'mime' => $this->mimeType,
            'url' => $this->getUrl(),
        ];
    }
}
