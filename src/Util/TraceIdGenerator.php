<?php

declare(strict_types=1);

namespace MLflow\Util;

/**
 * Generate OpenTelemetry-compatible trace and span IDs
 */
class TraceIdGenerator
{
    /**
     * Generate a trace ID compatible with OpenTelemetry
     * Returns a 32-character hexadecimal string (128-bit)
     */
    public static function generateTraceId(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Generate a span ID compatible with OpenTelemetry
     * Returns a 16-character hexadecimal string (64-bit)
     */
    public static function generateSpanId(): string
    {
        return bin2hex(random_bytes(8));
    }

    /**
     * Decode a hex ID string to integer
     * For IDs > PHP_INT_MAX, returns as hex string
     */
    public static function decodeId(string $hexId): int|string
    {
        // For IDs > PHP_INT_MAX, return as string
        if (strlen($hexId) > 14) {
            return $hexId; // Keep as hex string
        }
        return hexdec($hexId);
    }

    /**
     * Validate trace ID format (32 hex characters)
     */
    public static function isValidTraceId(string $traceId): bool
    {
        return preg_match('/^[0-9a-f]{32}$/i', $traceId) === 1;
    }

    /**
     * Validate span ID format (16 hex characters)
     */
    public static function isValidSpanId(string $spanId): bool
    {
        return preg_match('/^[0-9a-f]{16}$/i', $spanId) === 1;
    }
}
