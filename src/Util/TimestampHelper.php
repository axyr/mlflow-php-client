<?php

declare(strict_types=1);

namespace MLflow\Util;

/**
 * Timestamp conversion utilities
 *
 * MLflow uses:
 * - Nanoseconds for span timestamps
 * - Milliseconds for trace timestamps
 */
class TimestampHelper
{
    /**
     * Get current timestamp in nanoseconds (for spans)
     */
    public static function nowNs(): int
    {
        return (int) (microtime(true) * 1_000_000_000);
    }

    /**
     * Get current timestamp in milliseconds (for traces)
     */
    public static function nowMs(): int
    {
        return (int) (microtime(true) * 1_000);
    }

    /**
     * Convert milliseconds to nanoseconds
     */
    public static function msToNs(int $ms): int
    {
        return $ms * 1_000_000;
    }

    /**
     * Convert nanoseconds to milliseconds
     */
    public static function nsToMs(int $ns): int
    {
        return (int) ($ns / 1_000_000);
    }

    /**
     * Convert nanoseconds to DateTime
     */
    public static function nsToDateTime(int $ns): \DateTimeImmutable
    {
        $seconds = $ns / 1_000_000_000;
        return \DateTimeImmutable::createFromFormat('U.u', sprintf('%.9f', $seconds)) ?: new \DateTimeImmutable();
    }

    /**
     * Convert milliseconds to DateTime
     */
    public static function msToDateTime(int $ms): \DateTimeImmutable
    {
        $seconds = $ms / 1_000;
        return \DateTimeImmutable::createFromFormat('U.u', sprintf('%.3f', $seconds)) ?: new \DateTimeImmutable();
    }

    /**
     * Convert DateTime to nanoseconds
     */
    public static function dateTimeToNs(\DateTimeInterface $dateTime): int
    {
        return (int) ($dateTime->getTimestamp() * 1_000_000_000);
    }

    /**
     * Convert DateTime to milliseconds
     */
    public static function dateTimeToMs(\DateTimeInterface $dateTime): int
    {
        return (int) ($dateTime->getTimestamp() * 1_000);
    }
}
