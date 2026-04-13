<?php

declare(strict_types=1);

namespace Mileena\File;

interface UploadFile
{
    public function getName(): string;
    public function getRelativePath(): string;
    public function getPath(): string;
    public function getUrl(): string;
    public function getSize(): int;
    public function getMimeType(): string;
    public function getExtension(): string;
    public function toArray(): array;

    public static function fromArray(array $data): self;
    public static function getBaseDir(): string;
    public static function getBaseUrl(): string;
    public static function getAllowedMimeTypes(): array;
    public static function getMaxFileSize(): int;

    /**
     * Метод для генерации имени файла.
     * Можно переопределить в наследниках.
     */
    public function getFileName(): string;
}
