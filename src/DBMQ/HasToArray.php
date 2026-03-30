<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

trait HasToArray
{
    public function toArray(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);

        foreach ($reflection->getProperties() as $prop) {
            $name = $prop->getName();

            // skip #[IgnoreField]
            if (!empty($prop->getAttributes(IgnoreField::class))) {
                continue;
            }

            $data[$name] = $prop->getValue($this);
        }

        return $this->convertValues($data);
    }

    private function convertValues(array $data): array
    {
        foreach ($data as $k => $v) {
            if ($v instanceof \DateTimeInterface) {
                $data[$k] = $v->format('Y-m-d H:i:s');
            } elseif (is_bool($v)) {
                $data[$k] = (int) $v;
            } elseif (is_array($v)) {
                $data[$k] = json_encode($v);
            } elseif (is_object($v) && method_exists($v, 'toArray')) {
                $data[$k] = $v->toArray();
            }
        }

        return $data;
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
                // error_log("Conflict: '{$key}' and previous key both map to '{$camelKey}'");
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
            } elseif ($type === 'array') {
                if (is_string($val) && !empty($val)) {
                    $params[$name] = json_decode($val, true) ?: [];
                } elseif ($val === null) {
                    $params[$name] = [];
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

        $object = new static(...$params);

        if (isset($data['id'])) {
            $reflection = new \ReflectionClass(static::class);
            $idProperty = $reflection->getProperty('id');
            $type = $idProperty->getType();

            $value = $data['id'];

            if ($type && $type->getName() === 'int') {
                $value = (int) $value;
            } elseif ($type && $type->getName() === 'string') {
                $value = (string) $value;
            }
            $idProperty->setValue($object, $value);
        }

        return $object;
    }
}
