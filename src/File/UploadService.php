<?php

declare(strict_types=1);

namespace Mileena\File;

class UploadService
{
    private string $fileClass;
    private string $uploadDir;
    private array $allowedMimeTypes;
    private int $maxFileSize;

    public function __construct(string $fileClass = UploadFile::class)
    {
        if (!is_subclass_of($fileClass, UploadFile::class)) {
            throw new \RuntimeException("Upload error: unsupported {$fileClass} class");
        }

        $this->fileClass = $fileClass;

        $this->uploadDir = rtrim($fileClass::getBaseDir(), '/') . '/';
        $this->allowedMimeTypes = $fileClass::getAllowedMimeTypes();
        $this->maxFileSize = $fileClass::getMaxFileSize();
    }

    /**
     * Upload single file from $_FILES entry.
     */
    public function upload(array $file): ?UploadFile
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException("Upload error: {$file['error']}");
        }

        // Validate mime
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!empty($this->allowedMimeTypes) && !in_array($mime, $this->allowedMimeTypes, true)) {
            throw new \RuntimeException("File type not allowed: {$mime}");
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new \RuntimeException("File too large: {$file['size']} bytes");
        }

        $targetDir = $this->uploadDir;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileObject = new $this->fileClass($file['name'], '', $file['size'], $mime);
        $newName = $fileObject->getFileName();
        $targetPath = $targetDir . $newName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new \RuntimeException("Failed to move uploaded file");
        }

        return new $this->fileClass($file['name'], $newName, $file['size'], $mime);
    }

    /**
     * Upload from $_FILES: supports both single file and array of files
     *
     * @param string $paramName name of field in $_FILES
     * @return UploadFile[]
     */
    public function uploadFromRequest(string $paramName): array
    {
        if (!isset($_FILES[$paramName])) {
            return [];
        }

        $input = $_FILES[$paramName];
        $files = [];

        // Определяем, передан ли массив файлов или один файл
        if (is_array($input['tmp_name'])) {
            // Массив файлов
            foreach ($input['tmp_name'] as $i => $tmpName) {
                $files[] = [
                    'name' => $input['name'][$i],
                    'tmp_name' => $tmpName,
                    'size' => $input['size'][$i],
                    'error' => $input['error'][$i],
                    'type' => $input['type'][$i],
                ];
            }
        } else {
            $files[] = $input;
        }

        $attachments = [];

        foreach ($files as $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                $attachments[] = $this->upload($file);
            }
        }

        return $attachments;
    }

    public function delete(string|UploadFile $file): bool
    {
        $path = $file instanceof UploadFile ? $file->getPath() : $file;

        if (is_file($path)) {
            return unlink($path);
        }

        return false;
    }
}
