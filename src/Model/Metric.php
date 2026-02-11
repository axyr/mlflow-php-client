<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Contract\SerializableModelInterface;
use MLflow\Util\ValidationHelper;

/**
 * Represents an MLflow metric (immutable)
 */
readonly class Metric implements SerializableModelInterface
{
    public function __construct(
        public string $key,
        public float $value,
        public int $timestamp,
        public int $step = 0,
    ) {
    }

    /**
     * @param array{key: string, value: float|int|string, timestamp: int|string, step?: int|string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: ValidationHelper::requireString($data, 'key'),
            value: ValidationHelper::requireFloat($data, 'value'),
            timestamp: ValidationHelper::requireInt($data, 'timestamp'),
            step: ValidationHelper::optionalInt($data, 'step') ?? 0,
        );
    }

    /**
     * Create a metric with the current timestamp
     *
     * @example
     * ```php
     * $metric = Metric::now('accuracy', 0.95, step: 1);
     * ```
     */
    public static function now(string $key, float $value, int $step = 0): self
    {
        return new self(
            key: $key,
            value: $value,
            timestamp: (int) (microtime(true) * 1000),
            step: $step,
        );
    }

    /**
     * Create a metric with a specific timestamp
     *
     * @param string $key Metric key
     * @param float $value Metric value
     * @param int $timestamp Timestamp in milliseconds
     * @param int $step Training step
     *
     * @example
     * ```php
     * $metric = Metric::atTimestamp('loss', 0.05, 1638360000000, step: 10);
     * ```
     */
    public static function atTimestamp(string $key, float $value, int $timestamp, int $step = 0): self
    {
        return new self(
            key: $key,
            value: $value,
            timestamp: $timestamp,
            step: $step,
        );
    }

    /**
     * Create a metric from seconds-based timestamp
     *
     * @param string $key Metric key
     * @param float $value Metric value
     * @param int $timestampSeconds Timestamp in seconds (Unix timestamp)
     * @param int $step Training step
     *
     * @example
     * ```php
     * $metric = Metric::atTime('accuracy', 0.95, time(), step: 1);
     * ```
     */
    public static function atTime(string $key, float $value, int $timestampSeconds, int $step = 0): self
    {
        return new self(
            key: $key,
            value: $value,
            timestamp: $timestampSeconds * 1000,
            step: $step,
        );
    }

    /**
     * @return array{key: string, value: float, timestamp: int, step: int}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'timestamp' => $this->timestamp,
            'step' => $this->step,
        ];
    }

    /**
     * @return array{key: string, value: float, timestamp: int, step: int}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function getDateTime(): \DateTimeImmutable
    {
        $format = 'U.u';
        $timestamp = sprintf('%.3f', $this->timestamp / 1000);
        return \DateTimeImmutable::createFromFormat($format, $timestamp) ?: new \DateTimeImmutable();
    }

    public function isNewerThan(self $other): bool
    {
        return $this->timestamp > $other->timestamp;
    }

    public function isLaterStepThan(self $other): bool
    {
        return $this->step > $other->step;
    }
}
