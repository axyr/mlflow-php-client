<?php

declare(strict_types=1);

namespace MLflow\Util;

use MLflow\Exception\InvalidArgumentException;

/**
 * Security utilities for input validation and sanitization
 *
 * Provides methods to prevent common security issues:
 * - Path traversal attacks
 * - Injection attacks
 * - Invalid input
 */
final class SecurityHelper
{
    /**
     * Validate and sanitize file path to prevent traversal attacks
     *
     * @throws InvalidArgumentException if path traversal is detected
     */
    public static function validatePath(string $path, string $baseDir): string
    {
        // Normalize path separators
        $path = str_replace(['\\', '//'], '/', $path);
        $baseDir = str_replace(['\\', '//'], '/', $baseDir);

        $realPath = realpath($baseDir . '/' . $path);
        $realBase = realpath($baseDir);

        // Ensure path is within base directory
        if ($realPath === false || $realBase === false || ! str_starts_with($realPath, $realBase)) {
            throw new InvalidArgumentException(
                'Invalid path: path traversal detected or path does not exist'
            );
        }

        return $realPath;
    }

    /**
     * Sanitize experiment/run name
     *
     * @throws InvalidArgumentException if name is invalid
     */
    public static function sanitizeName(string $name): string
    {
        // Remove control characters
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        if (strlen($name) > 255) {
            throw new InvalidArgumentException(
                'Name too long: maximum 255 characters, got ' . strlen($name)
            );
        }

        if ($name === '') {
            throw new InvalidArgumentException('Name cannot be empty');
        }

        return $name;
    }

    /**
     * Validate tag key
     *
     * MLflow tag keys must contain only alphanumeric characters,
     * underscores, hyphens, periods, or forward slashes.
     *
     * @throws InvalidArgumentException if tag key is invalid
     */
    public static function validateTagKey(string $key): string
    {
        if (! preg_match('/^[a-zA-Z0-9_\-\.\/]+$/', $key)) {
            throw new InvalidArgumentException(
                "Invalid tag key '{$key}': must contain only alphanumeric, underscore, hyphen, period, or forward slash"
            );
        }

        if (strlen($key) > 250) {
            throw new InvalidArgumentException(
                'Tag key too long: maximum 250 characters, got ' . strlen($key)
            );
        }

        if ($key === '') {
            throw new InvalidArgumentException('Tag key cannot be empty');
        }

        return $key;
    }

    /**
     * Validate tag value
     *
     * @throws InvalidArgumentException if tag value is invalid
     */
    public static function validateTagValue(string $value): string
    {
        if (strlen($value) > 5000) {
            throw new InvalidArgumentException(
                'Tag value too long: maximum 5000 characters, got ' . strlen($value)
            );
        }

        return $value;
    }

    /**
     * Validate metric/parameter key
     *
     * @throws InvalidArgumentException if key is invalid
     */
    public static function validateMetricKey(string $key): string
    {
        if (! preg_match('/^[a-zA-Z0-9_\-\.\/\s]+$/', $key)) {
            throw new InvalidArgumentException(
                "Invalid metric key '{$key}': must contain only alphanumeric, underscore, " .
                'hyphen, period, forward slash, or space'
            );
        }

        if (strlen($key) > 250) {
            throw new InvalidArgumentException(
                'Metric key too long: maximum 250 characters, got ' . strlen($key)
            );
        }

        if ($key === '') {
            throw new InvalidArgumentException('Metric key cannot be empty');
        }

        return $key;
    }

    /**
     * Mask sensitive data in headers for logging
     *
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     */
    public static function maskSensitiveHeaders(array $headers): array
    {
        $sensitiveKeys = ['authorization', 'api-key', 'token', 'x-api-key', 'api_key', 'bearer'];
        $masked = $headers;

        foreach ($masked as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys, true)) {
                $masked[$key] = '***REDACTED***';
            }
        }

        return $masked;
    }

    /**
     * Validate experiment ID format
     *
     * @throws InvalidArgumentException if ID is invalid
     */
    public static function validateExperimentId(string $experimentId): string
    {
        if (! preg_match('/^[0-9]+$/', $experimentId)) {
            throw new InvalidArgumentException(
                "Invalid experiment ID '{$experimentId}': must be numeric"
            );
        }

        return $experimentId;
    }

    /**
     * Validate run ID format (UUID)
     *
     * @throws InvalidArgumentException if ID is invalid
     */
    public static function validateRunId(string $runId): string
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $runId)) {
            throw new InvalidArgumentException(
                "Invalid run ID '{$runId}': must be a 32-character hex string"
            );
        }

        return $runId;
    }
}
