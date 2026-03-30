<?php

declare(strict_types=1);

namespace Mileena\Helper;

final class PhoneHelper
{
    private const int MAX_LENGTH = 15;

    /**
     * Clean phone number to E.164 format.
     *
     * Examples:
     * - "8 (999) 123-45-67" → "+79991234567"
     * - "9991234567" → "+79991234567"
     * - "+7 999 123-45-67" → "+79991234567"
     * - "+34 123 456 789" → "+34123456789"
     * - "+1 (212) 555-1234" → "+12125551234"
     * - "123" → null (invalid)
     * - "+796143549349349343200" → null (too long)
     *
     * @param string|null $phone Raw phone number
     * @return string|null Cleaned phone number in E.164 format, or null if invalid
     */
    public static function clean(?string $phone = null): ?string
    {
        if ($phone === null) {
            return null;
        }

        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        if (empty($cleaned)) {
            return null;
        }

        if (preg_match('/^\+\d+$/', $cleaned)) {
            // E.164: + + maximum 15 digits
            if (strlen($cleaned) > self::MAX_LENGTH + 1) {
                return null;
            }

            return $cleaned;
        }

        $digits = preg_replace('/[^0-9]/', '', $cleaned);

        if (empty($digits)) {
            return null;
        }

        if (strlen($digits) > self::MAX_LENGTH) {
            return null;
        }

        // Russian number: 8 or 7 + 10 digits
        if (preg_match('/^[78](\d{10})$/', $digits, $matches)) {
            return '+7' . $matches[1];
        }

        // 10 digits → add +7
        if (preg_match('/^(\d{10})$/', $digits, $matches)) {
            return '+7' . $matches[1];
        }

        // as is
        return '+' . $digits;
    }

    /**
     * Validate phone number in E.164 format.
     *
     * @param string $phone Phone number
     * @return bool True if valid
     */
    public static function isValid(string $phone): bool
    {
        return (bool) preg_match('/^\+\d{1,3}\d{6,14}$/', $phone);
    }

    /**
     * Get phone number without '+' prefix (raw format).
     *
     * @param string|null $phone Raw phone number
     * @return string|null Phone without '+', or null if invalid
     */
    public static function toRaw(?string $phone = null): ?string
    {
        if ($phone === null) {
            return null;
        }

        $cleaned = self::clean($phone);

        if ($cleaned === null) {
            return null;
        }

        return ltrim($cleaned, '+');
    }

    /**
     * Get country code from E.164 phone number.
     *
     * @param string $phone Phone number in E.164 format
     * @return string|null Country code (e.g., "7", "34", "1"), or null if invalid
     */
    public static function getCountryCode(string $phone): ?string
    {
        $cleaned = self::clean($phone);

        if ($cleaned === null) {
            return null;
        }

        if (preg_match('/^\+(\\d{1,3})/', $cleaned, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
