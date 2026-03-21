<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

trait HasToArray
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    private static function snakeToCamel(string $snake): string
    {
        return lcfirst(str_replace('_', '', ucwords($snake, '_')));
    }

    public static function fromArray(?array $data): ?self
    {
        if ($data === null) {
            return null;
        }

        $camelData = [];

        foreach ($data as $key => $value) {
            $camelKey = self::snakeToCamel($key);

            if (isset($camelData[$camelKey])) {
                //                error_log("Conflict: '{$key}' and previous key both map to '{$camelKey}'");
            }

            $camelData[$camelKey] = $value;
        }

        $reflection = new \ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new static();
        }

        $params = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType()?->getName();
            $val = $camelData[$name] ?? null;

            if ($type === \DateTime::class && is_string($val) && !empty($val)) {
                $params[$name] = new \DateTime($val);
            } // Авто-каст для массивов (JSON из базы)
            elseif ($type === 'array') {
                if (is_string($val) && !empty($val)) {
                    $params[$name] = json_decode($val, true) ?: [];
                } elseif ($val === null) {
                    $params[$name] = [];  // 👈 null → пустой массив
                } else {
                    $params[$name] = (array) $val;
                }
            } elseif ($type === 'int' && $val !== null) {
                $params[$name] = (int) $val;
            } elseif ($type === 'float' && $val !== null) {
                $params[$name] = (float) $val;
            } elseif ($type === 'double' && $val !== null) {
                $params[$name] = (float) $val;
            } elseif ($type === 'string' && $val !== null) {
                $params[$name] = (string) $val;
            } elseif ($type === 'bool' && $val !== null) {
                $params[$name] = (bool) $val;
            } else {
                $params[$name] = $val;
            }
        }

        return new static(...$params);
    }
}
