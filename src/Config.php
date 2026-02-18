<?php

declare(strict_types=1);

namespace Mileena;

use Dotenv\Dotenv;

class Config
{
    private array $settings = [];

    private string $projectRoot;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = rtrim($projectRoot, '/\\');

        // 1. Сначала грузим .env приложения (если он есть)
        if (file_exists($projectRoot . '/.env')) {
            Dotenv::createImmutable($projectRoot)->safeLoad();
        }

        // 2. Слой 1: Дефолты фреймворка (Mileena)
        // Предполагаем структуру vendor/mileena/core/config/
        $mileenaConfigPath = dirname(__DIR__) . '/config';
        $this->loadFromDir($mileenaConfigPath);

        // 3. Слой 2: Конфиги приложения (App)
        // Они перекрывают дефолты Mileena
        $appConfigPath  = $projectRoot . '/config';

        if (is_dir($appConfigPath)) {
            $this->loadFromDir($appConfigPath);

            // 4. Слой 3: Конфиги окружения (Prod/Test)
            // Если в .env указано APP_ENV=test, догружаем из /app/config/test/
            $env = $_ENV['APP_ENV'] ?? 'dev';
            $envPath = $appConfigPath . '/' . $env;

            if (is_dir($envPath)) {
                $this->loadFromDir($envPath);
            }
        }
    }

    private function loadFromDir(string $path): void
    {
        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $newSettings = require $file;

            // Рекурсивно сливаем массивы, чтобы можно было
            // переопределить только один ключ в большом конфиге
            if (is_array($newSettings)) {
                $this->settings[$key] = array_replace_recursive(
                    $this->settings[$key] ?? [],
                    $newSettings,
                );
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $value = $this->settings;

        //        print_r($value);
        foreach ($parts as $part) {
            if (!is_array($value) || !isset($value[$part])) {
                $envKey = strtoupper(str_replace('.', '_', $key));

                return $_ENV[$envKey] ?? getenv($envKey) ?: $default;
            }
            $value = $value[$part];
        }

        return $value;
    }

    public function getDBCredential(): array
    {
        return [
            $this->get('db.host'),
            $this->get('db.user'),
            $this->get('db.pass'),
            $this->get('db.name'),
            $this->get('db.port'),
        ];
    }

    public function getProjectDir(): string
    {
        return $this->projectRoot;
    }
}
