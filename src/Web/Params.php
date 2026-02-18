<?php

declare(strict_types=1);

namespace Mileena\Web;

/**
 * Provides a structured and type-safe way to access input parameters from sources like $_GET, $_POST, or a custom array.
 * By default, it uses $_REQUEST as the data source.
 *
 * This class simplifies data validation and retrieval by providing methods
 * to get values cast to a specific type (int, string, float, array)
 * and to check for their existence.
 */
class Params
{
    private static $params;

    /**
     * The source array containing the input parameters.
     * @var array<string, mixed>
     */
    private array $source;

    /**
     * Initializes the Params object.
     *
     * @param array<string, mixed>|null $source The data source to use. If null, $_REQUEST is used by default.
     */
    public function __construct(?array $source = null)
    {
        $this->source = $source ?? $_REQUEST;
    }

    /**
     * Overwrites the current data source with a new one.
     *
     * @param array<string, mixed> $source The new data source.
     */
    public function setSource(array $source): void
    {
        $this->source = $source;
    }

    /**
     * Returns the current data source array.
     *
     * @return array<string, mixed>
     */
    public function getSource(): array
    {
        return $this->source;
    }

    /**
     * Sets a value for a parameter in both the local source and the global $_REQUEST array.
     *
     * @param string $param The parameter name (key).
     * @param mixed $value The value to set.
     */
    public function set(string $param, mixed $value): void
    {
        $_REQUEST[$param] = $value;
        $this->source[$param] = $value;
    }

    /**
     * Retrieves a parameter as an integer or null.
     *
     * @param string $name The primary key of the parameter.
     * @param string|int|null $second The secondary key for nested arrays.
     * @return int|null Returns the integer value, or null if the parameter is not set or empty.
     */
    public function getInt(string $name, int|string|null $second = null): ?int
    {
        $val = ($second !== null)
            ? ($this->source[$name][$second] ?? '')
            : ($this->source[$name] ?? '');

        return ($val === '' || $val === null) ? null : (int) $val;
    }

    /**
     * Retrieves a parameter as an integer, with a default of 0.
     *
     * @param string $name The primary key of the parameter.
     * @param string|int|null $second The secondary key for nested arrays.
     * @return int Returns the integer value, or 0 if the parameter is not set or empty.
     */
    public function getIntval(string $name, int|string|null $second = null): int
    {
        return $this->getInt($name, $second) ?? 0;
    }

    /**
     * Retrieves a parameter as a string or null.
     *
     * @param string $name The primary key of the parameter.
     * @param string|int|null $second The secondary key for nested arrays.
     * @param string|int|null $third The tertiary key for deeply nested arrays.
     * @return string|null Returns the string value, or null if the parameter is not set or empty.
     */
    public function getString(string $name, int|string|null $second = null, int|string|null $third = null): ?string
    {
        $val = $this->fetch($name, $second, $third);

        return ($val === '' || $val === null) ? null : (string) $val;
    }

    /**
     * Retrieves a parameter as a string, with a default of an empty string.
     *
     * @param string $name The primary key of the parameter.
     * @return string Returns the string value, or an empty string if not set.
     */
    public function getStrval(string $name): string
    {
        return (string) ($this->source[$name] ?? '');
    }

    /**
     * Cleans the phone number and formats it as 7XXXXXXXXXX.
     * - Removes all non-digit characters.
     * - Converts leading 8 to 7 for RU numbers.
     * - Returns a string of digits without the plus sign.
     *      *
     * @param string $name
     * @return string
     */
    public function getPhone(string $name): string
    {
        $clean = preg_replace('/[^0-9]/', '', $this->getStrval($name));

        if (strlen($clean) === 11 && strpos($clean, '8') === 0) {
            $clean[0] = '7';
        }

        return $clean;
    }

    /**
     * Retrieves a parameter as a float, with a default of 0.0.
     * It correctly handles comma-separated decimal values.
     *
     * @param string $name The primary key of the parameter.
     * @return float Returns the float value, or 0.0 if not set.
     */
    public function getDouble(string $name): float
    {
        $val = $this->source[$name] ?? '0';

        return (float) str_replace(",", '.', (string) $val);
    }

    /**
     * Retrieves a parameter as a float or null.
     * It correctly handles comma-separated decimal values.
     *
     * @param string $name The primary key of the parameter.
     * @return float|null Returns the float value, or null if not set or empty.
     */
    public function getFloat(string $name): ?float
    {
        if (!isset($this->source[$name]) || $this->source[$name] === '') {
            return null;
        }
        $val = $this->source[$name];

        return (float) str_replace(",", '.', (string) $val);
    }

    /**
     * Checks if a parameter exists and is not empty.
     *
     * @param string $name The primary key of the parameter.
     * @param string|int|null $second The secondary key for nested arrays.
     * @param string|int|null $third The tertiary key for deeply nested arrays.
     * @return bool True if the parameter exists and is not an empty value (0, '', false, null, []).
     */
    public function has(string $name, int|string|null $second = null, int|string|null $third = null): bool
    {
        return !empty($this->fetch($name, $second, $third));
    }

    /**
     * Checks if a parameter key exists in the source array, regardless of its value.
     *
     * @param string $name The primary key of the parameter.
     * @param string|int|null $second The secondary key for nested arrays.
     * @param string|int|null $third The tertiary key for deeply nested arrays.
     * @return bool True if the key exists.
     */
    public function is(string $name, int|string|null $second = null, int|string|null $third = null): bool
    {
        if ($third !== null) {
            return isset($this->source[$name][$second][$third]);
        }

        if ($second !== null) {
            return isset($this->source[$name][$second]);
        }

        return isset($this->source[$name]);
    }

    /**
     * Checks if a form parameter matches the given value.
     * * Returns false if:
     * - The comparison value ($v) is empty or null.
     * - The form parameter does not exist or is empty.
     * * Returns true only if the parameter value strictly matches $v.
     *
     * @param string $name The name of the input field.
     * @param mixed  $v    The value to compare against.
     * @return bool
     */
    public function eq(string $name, mixed $v): bool
    {
        if ($v === '' || $v === null) {
            return false;
        }

        if (!isset($this->source[$name]) || $this->source[$name] === '') {
            return false;
        }

        return $this->source[$name] == $v;
    }

    public function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest');
    }

    /**
     * Retrieves a parameter as an array.
     *
     * @param string $name The primary key of the parameter.
     * @param bool $toArray If true, a non-array value will be wrapped in an array.
     * @return array<mixed> Returns the array value. If the parameter is not an array,
     * returns an empty array unless $toArray is true.
     */
    public function getList(string $name, bool $toArray = false): array
    {
        $val = $this->source[$name] ?? [];

        if ($toArray && !is_array($val)) {
            return [$val];
        }

        return is_array($val) ? $val : [];
    }

    public static function getParams(): Params
    {
        if (!self::$params) {
            self::$params = new self();
        }

        return self::$params;
    }

    /**
     * Helper method to fetch a value from a potentially nested array.
     *
     * @param string $name The primary key.
     * @param string|int|null $second The secondary key.
     * @param string|int|null $third The tertiary key.
     * @return mixed The found value or null if not set.
     */
    private function fetch(string $name, int|string|null $second = null, int|string|null $third = null): mixed
    {
        if ($third !== null) {
            return $this->source[$name][$second][$third] ?? null;
        }

        if ($second !== null) {
            return $this->source[$name][$second] ?? null;
        }

        return $this->source[$name] ?? null;
    }
}
