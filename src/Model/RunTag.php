<?php

declare(strict_types=1);

namespace MLflow\Model;

/**
 * Represents an MLflow run tag (immutable key-value pair)
 */
readonly class RunTag implements \JsonSerializable, \Stringable
{
    public function __construct(
        public string $key,
        public string $value,
    ) {
    }

    /**
     * Create RunTag from an array
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
     * Check equality with another RunTag
     */
    public function equals(self $other): bool
    {
        return $this->key === $other->key && $this->value === $other->value;
    }

    /**
     * Check if this is a system tag (starts with mlflow.)
     */
    public function isSystemTag(): bool
    {
        return str_starts_with($this->key, 'mlflow.');
    }
}