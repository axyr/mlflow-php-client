<?php

declare(strict_types=1);

namespace MLflow\Util;

use MLflow\Exception\ValidationException;

/**
 * Utility class for validating API response data
 */
final class ResponseValidator
{
    /**
     * Require a field exists in response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @return mixed The field value
     * @throws ValidationException If field is missing
     */
    public static function requireField(array $response, string $field): mixed
    {
        if (!array_key_exists($field, $response)) {
            throw new ValidationException(
                "Expected field '{$field}' missing in API response"
            );
        }

        return $response[$field];
    }

    /**
     * Require an integer field from response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @return int The validated integer
     * @throws ValidationException If field is missing or not an integer
     */
    public static function requireInt(array $response, string $field): int
    {
        $value = self::requireField($response, $field);

        if (!is_int($value)) {
            throw new ValidationException(
                "Field '{$field}' must be an integer, got " . get_debug_type($value)
            );
        }

        return $value;
    }

    /**
     * Require a string field from response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @return string The validated string
     * @throws ValidationException If field is missing or not a string
     */
    public static function requireString(array $response, string $field): string
    {
        $value = self::requireField($response, $field);

        if (!is_string($value)) {
            throw new ValidationException(
                "Field '{$field}' must be a string, got " . get_debug_type($value)
            );
        }

        return $value;
    }

    /**
     * Require an array field from response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @return array<mixed> The validated array
     * @throws ValidationException If field is missing or not an array
     */
    public static function requireArray(array $response, string $field): array
    {
        $value = self::requireField($response, $field);

        if (!is_array($value)) {
            throw new ValidationException(
                "Field '{$field}' must be an array, got " . get_debug_type($value)
            );
        }

        return $value;
    }

    /**
     * Require a boolean field from response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @return bool The validated boolean
     * @throws ValidationException If field is missing or not a boolean
     */
    public static function requireBool(array $response, string $field): bool
    {
        $value = self::requireField($response, $field);

        if (!is_bool($value)) {
            throw new ValidationException(
                "Field '{$field}' must be a boolean, got " . get_debug_type($value)
            );
        }

        return $value;
    }

    /**
     * Get optional field from response with default
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @param mixed $default Default value if not present
     * @return mixed The field value or default
     */
    public static function optionalField(array $response, string $field, mixed $default = null): mixed
    {
        return $response[$field] ?? $default;
    }

    /**
     * Get optional integer from response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @param int|null $default Default value
     * @return int|null The integer value or default
     */
    public static function optionalInt(array $response, string $field, ?int $default = null): ?int
    {
        if (!isset($response[$field])) {
            return $default;
        }

        return is_int($response[$field]) ? $response[$field] : $default;
    }

    /**
     * Get optional string from response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @param string|null $default Default value
     * @return string|null The string value or default
     */
    public static function optionalString(array $response, string $field, ?string $default = null): ?string
    {
        if (!isset($response[$field])) {
            return $default;
        }

        return is_string($response[$field]) ? $response[$field] : $default;
    }

    /**
     * Get optional array from response
     *
     * @param array<string, mixed> $response Response data
     * @param string $field Field name
     * @param array<mixed>|null $default Default value
     * @return array<mixed>|null The array value or default
     */
    public static function optionalArray(array $response, string $field, ?array $default = null): ?array
    {
        if (!isset($response[$field])) {
            return $default;
        }

        return is_array($response[$field]) ? $response[$field] : $default;
    }

    private function __construct()
    {
        // Utility class - prevent instantiation
    }
}
