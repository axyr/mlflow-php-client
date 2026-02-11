<?php

declare(strict_types=1);

namespace MLflow\Model;

use MLflow\Util\ValidationHelper;

/**
 * Represents an MLflow metric (immutable)
 */
readonly class Metric implements \JsonSerializable
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
        return \DateTimeImmutable::createFromFormat('U.u', sprintf('%.3f', $this->timestamp / 1000))
            ?: new \DateTimeImmutable();
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