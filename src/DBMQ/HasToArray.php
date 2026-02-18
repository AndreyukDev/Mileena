<?php

declare(strict_types=1);

namespace Mileena\DBMQ;

trait HasToArray
{
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(?array $data): ?self
    {
        $reflection = new \ReflectionClass(static::class);
        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new static();
        }

        $params = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType()?->getName();
            $val = $data[$name] ?? null;

            // Авто-каст для DateTime
            if ($type === \DateTime::class && is_string($val) && !empty($val)) {
                $params[$name] = new \DateTime($val);
            } // Авто-каст для массивов (JSON из базы)
            elseif ($type === 'array' && is_string($val)) {
                $params[$name] = json_decode($val, true) ?: [];
            } // Типизация для чисел
            elseif ($type === 'int' && $val !== null) {
                $params[$name] = (int) $val;
            } else {
                $params[$name] = $val;
            }
        }

        return new static(...$params);
    }
}
