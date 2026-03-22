<?php

declare(strict_types=1);

namespace Mileena\Helper;

class Uuid
{
    /**
     * Generate UUID version 4 (RFC 4122)
     */
    public static function v4(): string
    {
        $randomizer = new \Random\Randomizer();
        $bytes = $randomizer->getBytes(16);

        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        $hex = bin2hex($bytes);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
    }

    public static function isValid(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid,
        );
    }
}
