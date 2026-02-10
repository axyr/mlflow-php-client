<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents a tag on an experiment (immutable key-value pair)
 */
readonly class ExperimentTag implements \JsonSerializable, \Stringable
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
    }

    /**
     * Create ExperimentTag from an array
     *
     * @param array{key: string, value: string} $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            value: $data['value'],
        );
    }

    /**
     * Convert to array
     *
     * @return array{key: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
        ];
    }

    /**
     * JSON serialization
     *
     * @return array{key: string, value: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return "{$this->key}:{$this->value}";
    }

    /**
     * Check equality with another ExperimentTag
     */
    public function equals(self $other): bool
    {
        return $this->key === $other->key && $this->value === $other->value;
    }
}