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

            // skip unInitialized and readonly properties
            if ($prop->isReadOnly() && !$prop->isInitialized($this)) {
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
            } elseif ($v instanceof \UnitEnum) {
                // support enum (backed и pure)
                $data[$k] = $v instanceof \BackedEnum ? $v->value : $v->name;
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
            $type = $param->getType();
            $val = $camelData[$name] ?? null;

            $typeName = null;
            $isEnum = false;
            $enumType = null;

            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                $isEnum = enum_exists($typeName);

                if ($isEnum) {
                    $enumType = $typeName;
                }
            }

            if ($isEnum && $val !== null) {
                $enumClass = $enumType;
                $params[$name] = $enumClass::tryFrom($val);

                if ($params[$name] === null) {
                    throw new \InvalidArgumentException(
                        sprintf('Invalid value "%s" for enum %s::%s', $val, $enumClass, $name),
                    );
                }
            } elseif ($typeName === \DateTime::class && is_string($val) && !empty($val)) {
                $params[$name] = new \DateTime($val);
            } elseif ($typeName === \DateTimeImmutable::class && is_string($val) && !empty($val)) {
                $params[$name] = new \DateTimeImmutable($val);
            } elseif ($typeName === 'array') {
                if (is_string($val) && !empty($val)) {
                    $params[$name] = json_decode($val, true) ?: [];
                } elseif ($val === null) {
                    $params[$name] = [];
                } else {
                    $params[$name] = (array) $val;
                }
            } elseif ($typeName === 'int' && $val !== null) {
                $params[$name] = (int) $val;
            } elseif ($typeName === 'float' && $val !== null) {
                $params[$name] = (float) $val;
            } elseif ($typeName === 'string' && $val !== null) {
                $params[$name] = (string) $val;
            } elseif ($typeName === 'bool' && $val !== null) {
                $params[$name] = (bool) $val;
            } else {
                $params[$name] = $val;
            }
        }

        $object = new static(...$params);

        if (isset($data['id'])) {
            $idProperty = $reflection->getProperty('id');
            $idType = $idProperty->getType();
            $value = $data['id'];

            if ($idType instanceof \ReflectionNamedType && $idType->getName() === 'int') {
                $value = (int) $value;
            } elseif ($idType instanceof \ReflectionNamedType && $idType->getName() === 'string') {
                $value = (string) $value;
            }
            $idProperty->setValue($object, $value);
        }

        return $object;
    }
}
