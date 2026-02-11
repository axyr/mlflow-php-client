<?php

declare(strict_types=1);

namespace MLflow\Util;

use MLflow\Exception\ValidationException;

/**
 * Utility class for validating data in model constructors and fromArray() methods
 */
final class ValidationHelper
{
    /**
     * Require a string field from data array
     *
     * @param array<string, mixed> $data        Data array
     * @param string               $key         Primary key to check
     * @param string|null          $fallbackKey Optional fallback key
     *
     * @return string The validated string value
     *
     * @throws ValidationException If field is missing or not a string
     */
    public static function requireString(array $data, string $key, ?string $fallbackKey = null): string
    {
        $value = $data[$key] ?? ($fallbackKey ? $data[$fallbackKey] ?? null : null);

        if (! is_string($value) || $value === '') {
            if ($fallbackKey) {
                $message = "Required field missing or empty: '{$key}' or '{$fallbackKey}'";
            } else {
                $message = "Required field missing or empty: '{$key}'";
            }

            throw new ValidationException($message);
        }

        return $value;
    }

    /**
     * Get optional string field from data array
     *
     * @param array<string, mixed> $data    Data array
     * @param string               $key     Key to check
     * @param string|null          $default Default value if not present
     *
     * @return string|null The string value or default
     */
    public static function optionalString(array $data, string $key, ?string $default = null): ?string
    {
        if (! isset($data[$key])) {
            return $default;
        }

        return is_string($data[$key]) ? $data[$key] : $default;
    }

    /**
     * Require an integer field from data array
     *
     * @param array<string, mixed> $data Data array
     * @param string               $key  Key to check
     *
     * @return int The validated integer value
     *
     * @throws ValidationException If field is missing or not an integer
     */
    public static function requireInt(array $data, string $key): int
    {
        if (! isset($data[$key])) {
            throw new ValidationException("Required field missing: '{$key}'");
        }

        if (! is_int($data[$key])) {
            throw new ValidationException("Field '{$key}' must be an integer, got " . get_debug_type($data[$key]));
        }

        return $data[$key];
    }

    /**
     * Get optional integer field from data array
     *
     * @param array<string, mixed> $data    Data array
     * @param string               $key     Key to check
     * @param int|null             $default Default value if not present
     *
     * @return int|null The integer value or default
     */
    public static function optionalInt(array $data, string $key, ?int $default = null): ?int
    {
        if (! isset($data[$key])) {
            return $default;
        }

        if (is_int($data[$key])) {
            return $data[$key];
        }

        // Allow numeric strings to be converted
        if (is_numeric($data[$key])) {
            return (int) $data[$key];
        }

        return $default;
    }

    /**
     * Require a float field from data array
     *
     * @param array<string, mixed> $data Data array
     * @param string               $key  Key to check
     *
     * @return float The validated float value
     *
     * @throws ValidationException If field is missing or not numeric
     */
    public static function requireFloat(array $data, string $key): float
    {
        if (! isset($data[$key])) {
            throw new ValidationException("Required field missing: '{$key}'");
        }

        if (! is_numeric($data[$key])) {
            throw new ValidationException("Field '{$key}' must be numeric, got " . get_debug_type($data[$key]));
        }

        return (float) $data[$key];
    }

    /**
     * Get optional float field from data array
     *
     * @param array<string, mixed> $data    Data array
     * @param string               $key     Key to check
     * @param float|null           $default Default value if not present
     *
     * @return float|null The float value or default
     */
    public static function optionalFloat(array $data, string $key, ?float $default = null): ?float
    {
        if (! isset($data[$key])) {
            return $default;
        }

        if (is_numeric($data[$key])) {
            return (float) $data[$key];
        }

        return $default;
    }

    /**
     * Require an array field from data array
     *
     * @param array<string, mixed> $data Data array
     * @param string               $key  Key to check
     *
     * @return array<mixed> The validated array value
     *
     * @throws ValidationException If field is missing or not an array
     */
    public static function requireArray(array $data, string $key): array
    {
        if (! isset($data[$key])) {
            throw new ValidationException("Required field missing: '{$key}'");
        }

        if (! is_array($data[$key])) {
            throw new ValidationException("Field '{$key}' must be an array, got " . get_debug_type($data[$key]));
        }

        return $data[$key];
    }

    /**
     * Get optional array field from data array
     *
     * @param array<string, mixed> $data    Data array
     * @param string               $key     Key to check
     * @param array<mixed>|null    $default Default value if not present
     *
     * @return array<mixed>|null The array value or default
     */
    public static function optionalArray(array $data, string $key, ?array $default = null): ?array
    {
        if (! isset($data[$key])) {
            return $default;
        }

        return is_array($data[$key]) ? $data[$key] : $default;
    }

    /**
     * Require a boolean field from data array
     *
     * @param array<string, mixed> $data Data array
     * @param string               $key  Key to check
     *
     * @return bool The validated boolean value
     *
     * @throws ValidationException If field is missing or not a boolean
     */
    public static function requireBool(array $data, string $key): bool
    {
        if (! isset($data[$key])) {
            throw new ValidationException("Required field missing: '{$key}'");
        }

        if (! is_bool($data[$key])) {
            throw new ValidationException("Field '{$key}' must be a boolean, got " . get_debug_type($data[$key]));
        }

        return $data[$key];
    }

    /**
     * Get optional boolean field from data array
     *
     * @param array<string, mixed> $data    Data array
     * @param string               $key     Key to check
     * @param bool|null            $default Default value if not present
     *
     * @return bool|null The boolean value or default
     */
    public static function optionalBool(array $data, string $key, ?bool $default = null): ?bool
    {
        if (! isset($data[$key])) {
            return $default;
        }

        return is_bool($data[$key]) ? $data[$key] : $default;
    }

    private function __construct()
    {
        // Utility class - prevent instantiation
    }
}
